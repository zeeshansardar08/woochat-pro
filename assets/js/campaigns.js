// WooChat Pro – Campaigns admin tab.

(function () {
    document.addEventListener('DOMContentLoaded', function () {
        var root = document.getElementById('wcwp-tab-content-campaigns');
        if (!root) return;
        var config = (typeof wcwpCampaigns !== 'undefined') ? wcwpCampaigns : {};
        var i18n = config.i18n || {};

        var segmentEl = document.getElementById('wcwp-campaign-segment');
        var daysWrap  = document.getElementById('wcwp-campaign-days-wrap');
        var nameEl    = document.getElementById('wcwp-campaign-name');
        var tmplEl    = document.getElementById('wcwp-campaign-template');
        var daysEl    = document.getElementById('wcwp-campaign-days');
        var submit    = document.getElementById('wcwp-campaign-submit');
        var feedback  = document.getElementById('wcwp-campaign-feedback');

        function updateDaysVisibility() {
            if (!segmentEl || !daysWrap) return;
            daysWrap.style.display = segmentEl.value === 'recent_orders' ? 'inline-block' : 'none';
        }

        if (segmentEl) {
            segmentEl.addEventListener('change', updateDaysVisibility);
            updateDaysVisibility();
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
            body.append('action', 'wcwp_create_campaign');
            body.append('nonce', config.nonce);
            body.append('name', name);
            body.append('template', template);
            body.append('segment_type', segmentEl.value);
            if (segmentEl.value === 'recent_orders') {
                body.append('segment_days', daysEl.value || '30');
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
        var rows = root.querySelectorAll('#wcwp-campaigns-list tbody tr');
        var active = [];
        rows.forEach(function (row) {
            var status = (row.querySelector('.wcwp-campaign-status') || {}).textContent || '';
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
                var url = config.ajaxUrl + '?action=wcwp_campaign_status&nonce=' + encodeURIComponent(config.nonce) + '&campaign_id=' + encodeURIComponent(id);
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
            var statusCell = row.querySelector('.wcwp-campaign-status');
            if (statusCell) {
                var label = data.status;
                if (label === 'completed' && i18n.completed) label = i18n.completed;
                else if (label === 'running' && i18n.running) label = i18n.running;
                else if (label === 'queued' && i18n.queued) label = i18n.queued;
                else label = label.charAt(0).toUpperCase() + label.slice(1);
                statusCell.textContent = label;
            }
            setCell(row, '.wcwp-campaign-sent',    data.sent_count);
            setCell(row, '.wcwp-campaign-failed',  data.failed_count);
            setCell(row, '.wcwp-campaign-skipped', data.skipped_count);
            setCell(row, '.wcwp-campaign-total',   data.total_count);
        }

        function setCell(row, sel, n) {
            var el = row.querySelector(sel);
            if (el) el.textContent = String(n);
        }
    });
})();
