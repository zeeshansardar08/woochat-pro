// Zignites Chat – Campaigns admin tab.

(function () {
    document.addEventListener('DOMContentLoaded', function () {
        var root = document.getElementById('zignites-chat-tab-content-campaigns');
        if (!root) return;
        var config = (typeof zignitesChatCampaigns !== 'undefined') ? zignitesChatCampaigns : {};
        var i18n = config.i18n || {};

        var segmentEl = document.getElementById('zignites-chat-campaign-segment');
        var metaBlocks = root.querySelectorAll('.zignites-chat-campaign-meta');
        var nameEl    = document.getElementById('zignites-chat-campaign-name');
        var tmplEl    = document.getElementById('zignites-chat-campaign-template');
        var daysEl    = document.getElementById('zignites-chat-campaign-days');
        var scheduleEl = document.getElementById('zignites-chat-campaign-schedule');
        var excludeEl  = document.getElementById('zignites-chat-campaign-exclude-days');
        var submit    = document.getElementById('zignites-chat-campaign-submit');
        var feedback  = document.getElementById('zignites-chat-campaign-feedback');

        function updateSegmentMeta() {
            if (!segmentEl) return;
            var selected = segmentEl.value;
            metaBlocks.forEach(function (block) {
                block.style.display = block.getAttribute('data-segment') === selected ? 'block' : 'none';
            });
        }

        if (segmentEl) {
            segmentEl.addEventListener('change', updateSegmentMeta);
            updateSegmentMeta();
        }

        // Comma-join the selected values of a multi-select, or read a text input.
        function selectedCsv(selectId, textFallbackId) {
            var sel = document.getElementById(selectId);
            if (sel && sel.options) {
                var vals = [];
                for (var i = 0; i < sel.options.length; i++) {
                    if (sel.options[i].selected) vals.push(sel.options[i].value);
                }
                return vals.join(',');
            }
            if (textFallbackId) {
                var t = document.getElementById(textFallbackId);
                return t ? t.value : '';
            }
            return '';
        }

        function appendSegmentMeta(body, segment) {
            if (segment === 'recent_orders') {
                body.append('segment_days', (daysEl && daysEl.value) || '30');
            } else if (segment === 'product_purchased') {
                var p = document.getElementById('zignites-chat-campaign-product-ids');
                body.append('product_ids', (p && p.value) || '');
            } else if (segment === 'category_purchased') {
                body.append('category_ids', selectedCsv('zignites-chat-campaign-category-ids'));
            } else if (segment === 'min_spend') {
                var m = document.getElementById('zignites-chat-campaign-min-spend');
                body.append('min_spend', (m && m.value) || '0');
            } else if (segment === 'location') {
                body.append('countries', selectedCsv('zignites-chat-campaign-countries', 'zignites-chat-campaign-countries-text'));
            } else if (segment === 'win_back') {
                var w = document.getElementById('zignites-chat-campaign-winback-days');
                body.append('winback_days', (w && w.value) || '60');
            }
        }

        if (submit) {
            submit.addEventListener('click', createCampaign);
        }

        function createCampaign() {
            if (!config.ajaxUrl || !config.nonce) return;
            if (submit.disabled) return;

            feedback.textContent = '';
            feedback.classList.remove('is-error');

            var name = (nameEl && nameEl.value || '').trim();
            var template = (tmplEl && tmplEl.value || '').trim();
            if (!name || !template) {
                feedback.textContent = i18n.genericError || 'Please fill in name and template.';
                feedback.classList.add('is-error');
                return;
            }

            submit.disabled = true;
            var originalLabel = submit.textContent;
            submit.textContent = i18n.submitting || 'Creating campaign…';

            var body = new URLSearchParams();
            body.append('action', 'zignites_chat_create_campaign');
            body.append('nonce', config.nonce);
            body.append('name', name);
            body.append('template', template);
            body.append('segment_type', segmentEl.value);
            appendSegmentMeta(body, segmentEl.value);
            if (scheduleEl && scheduleEl.value) {
                body.append('scheduled_at', scheduleEl.value);
            }
            if (excludeEl && excludeEl.value) {
                body.append('exclude_recent_days', excludeEl.value);
            }

            fetch(config.ajaxUrl, {
                method: 'POST',
                credentials: 'same-origin',
                body: body
            }).then(function (res) {
                return res.json().then(function (data) { return { ok: res.ok, data: data }; });
            }).then(function (result) {
                submit.disabled = false;
                submit.textContent = originalLabel;

                if (result.ok && result.data && result.data.success) {
                    // Reload to render the new row in the recent-campaigns table;
                    // simpler than client-side row insertion and matches the existing
                    // settings-page pattern (no SPA wiring elsewhere).
                    window.location.reload();
                    return;
                }
                var msg = (result.data && result.data.data && result.data.data.message) || i18n.genericError;
                feedback.textContent = msg || 'Could not create campaign.';
                feedback.classList.add('is-error');
            }).catch(function () {
                submit.disabled = false;
                submit.textContent = originalLabel;
                feedback.textContent = i18n.genericError || 'Network error.';
                feedback.classList.add('is-error');
            });
        }

        // Live-poll any rows that aren't yet completed so the admin sees
        // sent/failed counters tick up without reloading. Stops polling
        // once every visible row reaches a terminal state.
        var rows = root.querySelectorAll('#zignites-chat-campaigns-list tbody tr');
        var active = [];
        rows.forEach(function (row) {
            var status = (row.querySelector('.zignites-chat-campaign-status') || {}).textContent || '';
            if (/^(queued|running)$/i.test(status.trim())) {
                active.push(row);
            }
        });
        if (active.length && config.ajaxUrl && config.nonce) {
            pollActive();
        }

        function pollActive() {
            var stillActive = [];
            var pending = active.length;
            if (!pending) return;

            active.forEach(function (row) {
                var id = row.getAttribute('data-campaign-id');
                var url = config.ajaxUrl + '?action=zignites_chat_campaign_status&nonce=' + encodeURIComponent(config.nonce) + '&campaign_id=' + encodeURIComponent(id);
                fetch(url, { credentials: 'same-origin' })
                    .then(function (res) { return res.json(); })
                    .then(function (data) {
                        if (data && data.success && data.data) {
                            updateRow(row, data.data);
                            if (/^(queued|running)$/i.test(data.data.status)) {
                                stillActive.push(row);
                            }
                        }
                    })
                    .catch(function () { /* swallow; next tick may succeed */ })
                    .then(function () {
                        pending--;
                        if (pending === 0) {
                            active = stillActive;
                            if (active.length) setTimeout(pollActive, 5000);
                        }
                    });
            });
        }

        function updateRow(row, data) {
            var statusCell = row.querySelector('.zignites-chat-campaign-status');
            if (statusCell) {
                var label = data.status;
                if (label === 'completed' && i18n.completed) label = i18n.completed;
                else if (label === 'running' && i18n.running) label = i18n.running;
                else if (label === 'queued' && i18n.queued) label = i18n.queued;
                else label = label.charAt(0).toUpperCase() + label.slice(1);
                statusCell.textContent = label;
            }
            setCell(row, '.zignites-chat-campaign-sent',    data.sent_count);
            setCell(row, '.zignites-chat-campaign-failed',  data.failed_count);
            setCell(row, '.zignites-chat-campaign-skipped', data.skipped_count);
            setCell(row, '.zignites-chat-campaign-total',   data.total_count);
        }

        function setCell(row, sel, n) {
            var el = row.querySelector(sel);
            if (el) el.textContent = String(n);
        }
    });
})();
