// Zignites Chat – Premium Admin UI

// Chatbot Customizer Live Preview

document.addEventListener('DOMContentLoaded', function () {
    const bgColor = document.getElementById('zignites-chat-chatbot-bg');
    const textColor = document.getElementById('zignites-chat-chatbot-color');
    const iconColor = document.getElementById('zignites-chat-chatbot-icon');
    const iconOptions = document.querySelectorAll('.zignites-chat-icon-option');
    const previewBubble = document.querySelector('.zignites-chat-chatbot-preview-bubble');
    const previewIcon = document.querySelector('.zignites-chat-chatbot-preview-icon');
    const welcomeInput = document.getElementById('zignites-chat-chatbot-welcome');
    const previewWelcome = document.getElementById('zignites-chat-chatbot-preview-welcome');
    const iconHidden = document.getElementById('zignites-chat-chatbot-icon-value');

    function updatePreview() {
        if (previewBubble && bgColor && textColor) {
            previewBubble.style.setProperty('--zignites-chat-chatbot-bg', bgColor.value);
            previewBubble.style.setProperty('--zignites-chat-chatbot-color', textColor.value);
        }
        if (previewIcon && iconColor) {
            previewIcon.style.setProperty('--zignites-chat-chatbot-icon', iconColor.value);
        }
        if (welcomeInput && previewWelcome) {
            previewWelcome.textContent = welcomeInput.value;
        }
    }
    if (bgColor) bgColor.addEventListener('input', updatePreview);
    if (textColor) textColor.addEventListener('input', updatePreview);
    if (iconColor) iconColor.addEventListener('input', updatePreview);
    if (welcomeInput) welcomeInput.addEventListener('input', updatePreview);
    iconOptions.forEach(opt => {
        opt.addEventListener('click', function () {
            iconOptions.forEach(o => o.classList.remove('selected'));
            opt.classList.add('selected');
            if (previewIcon) previewIcon.innerHTML = opt.innerHTML;
            if (iconHidden) iconHidden.value = opt.textContent;
        });
    });

    // Initialize preview with saved values
    updatePreview();
});

// Multi-agent routing: agents table add/remove + serialize-on-change

document.addEventListener('DOMContentLoaded', function () {
    const table = document.getElementById('zignites-chat-agents-table');
    const hidden = document.getElementById('zignites_chat_agents_input');
    const addBtn = document.getElementById('zignites-chat-agent-add');
    if (!table || !hidden) return;

    const tbody = table.querySelector('tbody');

    function serialize() {
        const rows = tbody.querySelectorAll('.zignites-chat-agent-row');
        const list = [];
        rows.forEach(function (row) {
            const name  = (row.querySelector('.zignites-chat-agent-name')  || {}).value || '';
            const phone = (row.querySelector('.zignites-chat-agent-phone') || {}).value || '';
            if (!name.trim() || !phone.trim()) return;
            list.push({ name: name.trim(), phone: phone.trim() });
        });
        hidden.value = JSON.stringify(list);
    }

    function newRow() {
        const tr = document.createElement('tr');
        tr.className = 'zignites-chat-agent-row';
        tr.innerHTML =
            '<td><input type="text" class="zignites-chat-agent-name regular-text" /></td>' +
            '<td><input type="text" class="zignites-chat-agent-phone regular-text" /></td>' +
            '<td><button type="button" class="button-link zignites-chat-agent-remove" aria-label="Remove agent">&times;</button></td>';
        tbody.appendChild(tr);
    }

    if (addBtn) {
        addBtn.addEventListener('click', function () {
            newRow();
        });
    }

    // Event delegation: input change anywhere in the table re-serializes;
    // remove-button clicks delete the row then re-serialize.
    table.addEventListener('input', function (e) {
        if (e.target.classList.contains('zignites-chat-agent-name') || e.target.classList.contains('zignites-chat-agent-phone')) {
            serialize();
        }
    });
    table.addEventListener('click', function (e) {
        if (e.target.classList.contains('zignites-chat-agent-remove')) {
            const row = e.target.closest('.zignites-chat-agent-row');
            if (row) row.parentNode.removeChild(row);
            // Always keep at least one empty row visible so the admin can re-add.
            if (!tbody.querySelector('.zignites-chat-agent-row')) newRow();
            serialize();
        }
    });

    // Capture-phase form submit listener so the hidden input is current
    // even if the admin clicks Save without first blurring an input.
    const form = hidden.closest('form');
    if (form) form.addEventListener('submit', serialize, true);
});

// Upgrade Modal Logic

document.addEventListener('DOMContentLoaded', function () {
    const upgradeModal = document.getElementById('zignites-chat-upgrade-modal');
    const openUpgradeBtns = document.querySelectorAll('.zignites-chat-open-upgrade-modal');
    const closeUpgradeBtn = document.querySelector('.zignites-chat-upgrade-modal-close');
    openUpgradeBtns.forEach(btn => {
        btn.addEventListener('click', function () {
            if (upgradeModal) upgradeModal.style.display = 'flex';
        });
    });
    if (closeUpgradeBtn) {
        closeUpgradeBtn.addEventListener('click', function () {
            if (upgradeModal) upgradeModal.style.display = 'none';
        });
    }
    // Close modal on background click
    if (upgradeModal) {
        upgradeModal.addEventListener('click', function (e) {
            if (e.target === upgradeModal) upgradeModal.style.display = 'none';
        });
    }
});

// Resend cart recovery attempts from admin table
document.addEventListener('DOMContentLoaded', function () {
    const resendButtons = document.querySelectorAll('.zignites-chat-resend-cart');
    if (!resendButtons.length) return;

    resendButtons.forEach(btn => {
        btn.addEventListener('click', function () {
            const attempt = btn.getAttribute('data-attempt');
            if (!attempt) return;
            btn.disabled = true;
            btn.textContent = 'Resending...';

            const formData = new FormData();
            formData.append('action', 'zignites_chat_resend_cart_recovery');
            formData.append('attempt_id', attempt);
            formData.append('nonce', (window.zignitesChatAdminData && zignitesChatAdminData.resendNonce) ? zignitesChatAdminData.resendNonce : '');

            fetch((window.zignitesChatAdminData && zignitesChatAdminData.ajaxUrl) ? zignitesChatAdminData.ajaxUrl : '', {
                method: 'POST',
                credentials: 'same-origin',
                body: formData
            }).then(res => res.json())
              .then(data => {
                btn.textContent = data && data.success ? 'Resent' : 'Failed';
                if (!data || !data.success) {
                    setTimeout(() => { btn.textContent = 'Resend'; btn.disabled = false; }, 1200);
                }
              })
              .catch(() => {
                btn.textContent = 'Failed';
                setTimeout(() => { btn.textContent = 'Resend'; btn.disabled = false; }, 1200);
              });
        });
    });
}); 

// License activation/deactivation
document.addEventListener('DOMContentLoaded', function () {
    const activateBtn = document.getElementById('zignites-chat-activate-license');
    const deactivateBtn = document.getElementById('zignites-chat-deactivate-license');
    const statusBadge = document.getElementById('zignites-chat-license-status');
    const keyField = document.getElementById('zignites_chat_license_key');

    function setStatus(text, success) {
        if (!statusBadge) return;
        statusBadge.textContent = text;
        statusBadge.classList.remove('zignites-chat-badge-success', 'zignites-chat-badge-muted');
        statusBadge.classList.add(success ? 'zignites-chat-badge-success' : 'zignites-chat-badge-muted');
    }

    function postLicense(action, key) {
        const formData = new FormData();
        formData.append('action', action);
        formData.append('nonce', (window.zignitesChatAdminData && zignitesChatAdminData.licenseNonce) ? zignitesChatAdminData.licenseNonce : '');
        if (key) formData.append('license_key', key);

        return fetch((window.zignitesChatAdminData && zignitesChatAdminData.ajaxUrl) ? zignitesChatAdminData.ajaxUrl : '', {
            method: 'POST',
            credentials: 'same-origin',
            body: formData
        }).then(res => res.json());
    }

    // English fallbacks keep the UI working if the localization payload is
    // ever missing — degrades to English instead of "undefined".
    const labels = (window.zignitesChatAdminData && zignitesChatAdminData.licenseLabels) || {};
    const t = (key, fallback) => labels[key] || fallback;

    if (activateBtn) {
        activateBtn.addEventListener('click', function () {
            if (!keyField || !keyField.value) {
                setStatus(t('keyRequired', 'Key required'), false);
                return;
            }
            setStatus(t('activating', 'Activating…'), false);
            activateBtn.disabled = true;
            postLicense('zignites_chat_activate_license', keyField.value).then(data => {
                if (data && data.success) {
                    setStatus(t('active', 'Active'), true);
                } else {
                    setStatus((data && data.data && data.data.message) ? data.data.message : t('activationFailed', 'Activation failed'), false);
                }
            }).catch(() => setStatus(t('activationFailed', 'Activation failed'), false)).finally(() => {
                activateBtn.disabled = false;
            });
        });
    }

    if (deactivateBtn) {
        deactivateBtn.addEventListener('click', function () {
            setStatus(t('deactivating', 'Deactivating…'), false);
            deactivateBtn.disabled = true;
            postLicense('zignites_chat_deactivate_license').then(data => {
                if (data && data.success) {
                    setStatus(t('inactive', 'Inactive'), false);
                } else {
                    setStatus(t('deactivationFailed', 'Deactivation failed'), false);
                }
            }).catch(() => setStatus(t('deactivationFailed', 'Deactivation failed'), false)).finally(() => {
                deactivateBtn.disabled = false;
            });
        });
    }
});

// Dark Mode Toggle

document.addEventListener('DOMContentLoaded', function () {
    const wrapper = document.querySelector('.zignites-chat-admin-premium-wrap');
    let darkMode = localStorage.getItem('zignites-chat-dark-mode') === 'true';
    function applyDarkMode(on) {
        if (on) {
            document.body.classList.add('zignites-chat-dark-mode');
            if (wrapper) wrapper.classList.add('zignites-chat-dark-mode');
        } else {
            document.body.classList.remove('zignites-chat-dark-mode');
            if (wrapper) wrapper.classList.remove('zignites-chat-dark-mode');
        }
    }
    // Add toggle button
    let toggle = document.createElement('button');
    toggle.className = 'zignites-chat-dark-toggle';
    toggle.setAttribute('type', 'button');
    toggle.setAttribute('aria-label', 'Toggle dark mode');
    toggle.innerHTML = '<span class="zignites-chat-dark-icon">' + (darkMode ? '🌙' : '☀️') + '</span>';
    if (wrapper) wrapper.prepend(toggle);
    applyDarkMode(darkMode);
    toggle.addEventListener('click', function () {
        darkMode = !darkMode;
        localStorage.setItem('zignites-chat-dark-mode', darkMode);
        applyDarkMode(darkMode);
        // Animate icon and swap sun/moon
        const icon = toggle.querySelector('.zignites-chat-dark-icon');
        if (icon) icon.textContent = darkMode ? '🌙' : '☀️';
    });
});

document.addEventListener('DOMContentLoaded', function () {
    const apiProvider = document.getElementById('zignites_chat_api_provider');
    const cloudFields = document.querySelectorAll('.zignites-chat-cloud-fields');
    function toggleCloudFields() {
        if (!apiProvider) return;
        const show = apiProvider.value === 'cloud';
        cloudFields.forEach(f => f.style.display = show ? '' : 'none');
    }
    if (apiProvider) {
        apiProvider.addEventListener('change', toggleCloudFields);
        toggleCloudFields();
    }
}); 

document.addEventListener('DOMContentLoaded', function () {
    const testMode = document.getElementById('zignites_chat_test_mode_enabled');
    const hint = document.getElementById('zignites-chat-test-log-hint');
    const badge = document.getElementById('zignites-chat-test-mode-badge');
    if (!testMode || !hint) return;

    function toggleHint() {
        hint.style.display = testMode.checked ? '' : 'none';
        if (badge) badge.style.display = testMode.checked ? 'inline-block' : 'none';
    }

    testMode.addEventListener('change', toggleHint);
    toggleHint();
});

document.addEventListener('DOMContentLoaded', function () {
    const filterBtn = document.getElementById('zignites-chat-analytics-filter-button');
    if (!filterBtn) return;

    filterBtn.addEventListener('click', function () {
        const params = new URLSearchParams(window.location.search);
        params.set('page', 'zignites-chat-analytics');
        params.delete('tab');

        const fields = [
            { id: 'zignites_chat_type', key: 'zignites_chat_type' },
            { id: 'zignites_chat_status', key: 'zignites_chat_status' },
            { id: 'zignites_chat_phone', key: 'zignites_chat_phone' },
            { id: 'zignites_chat_date_from', key: 'zignites_chat_date_from' },
            { id: 'zignites_chat_date_to', key: 'zignites_chat_date_to' }
        ];

        fields.forEach(field => {
            const el = document.getElementById(field.id);
            const value = el ? el.value.trim() : '';
            if (value) {
                params.set(field.key, value);
            } else {
                params.delete(field.key);
            }
        });

        window.location.search = params.toString();
    });
});

// Webhooks tab — confirm-on-delete + AJAX test-fire button.
document.addEventListener('DOMContentLoaded', function () {
    const data = window.zignitesChatAdminData || {};

    document.querySelectorAll('.zignites-chat-webhook-delete').forEach(function (a) {
        a.addEventListener('click', function (e) {
            const msg = data.webhookDeleteConfirm || 'Delete this webhook?';
            if (!window.confirm(msg)) e.preventDefault();
        });
    });

    document.querySelectorAll('.zignites-chat-webhook-test').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const row = btn.closest('tr');
            const id = btn.getAttribute('data-webhook-id') || '';
            const result = row ? row.querySelector('.zignites-chat-webhook-test-result') : null;
            if (!id || !data.ajaxUrl || !data.webhookTestNonce) return;

            const originalLabel = btn.textContent;
            btn.disabled = true;
            btn.textContent = data.webhookTesting || 'Testing…';
            if (result) { result.textContent = ''; result.style.color = ''; }

            const form = new FormData();
            form.append('action', 'zignites_chat_webhook_test');
            form.append('nonce', data.webhookTestNonce);
            form.append('webhook_id', id);

            fetch(data.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: form })
                .then(function (r) { return r.json().catch(function () { return { success: false, data: { message: 'invalid response' } }; }); })
                .then(function (body) {
                    btn.disabled = false;
                    btn.textContent = data.webhookTestLabel || originalLabel;
                    if (!result) return;
                    if (body && body.success) {
                        result.textContent = (body.data && body.data.message) ? body.data.message : 'OK';
                        result.style.color = '#1c7c54';
                    } else {
                        result.textContent = (body && body.data && body.data.message) ? body.data.message : 'Test failed';
                        result.style.color = '#b32d2e';
                    }
                })
                .catch(function () {
                    btn.disabled = false;
                    btn.textContent = data.webhookTestLabel || originalLabel;
                    if (result) {
                        result.textContent = 'Network error';
                        result.style.color = '#b32d2e';
                    }
                });
        });
    });
});

// Logs tab — Apply button rebuilds the URL with current filter values,
// Clear button asks for explicit confirmation before destructive action.
document.addEventListener('DOMContentLoaded', function () {
    const applyBtn = document.getElementById('zignites-chat-log-filter-button');
    if (applyBtn) {
        applyBtn.addEventListener('click', function () {
            const params = new URLSearchParams(window.location.search);
            params.set('page', 'zignites-chat-logs');
            params.delete('tab');
            const fields = [
                { id: 'zignites_chat_log_q',     key: 'zignites_chat_log_q' },
                { id: 'zignites_chat_log_tag',   key: 'zignites_chat_log_tag' },
                { id: 'zignites_chat_log_lines', key: 'zignites_chat_log_lines' }
            ];
            fields.forEach(function (field) {
                const el = document.getElementById(field.id);
                const value = el ? String(el.value).trim() : '';
                if (value) {
                    params.set(field.key, value);
                } else {
                    params.delete(field.key);
                }
            });
            // The previous run may have left a status message in the URL —
            // strip it so the redirect view doesn't show a stale notice.
            params.delete('zignites_chat_log_msg');
            window.location.search = params.toString();
        });
    }

    const clearBtn = document.getElementById('zignites-chat-log-clear-button');
    if (clearBtn) {
        clearBtn.addEventListener('click', function (e) {
            const confirmMsg = (window.zignitesChatAdminData && zignitesChatAdminData.logClearConfirm)
                ? zignitesChatAdminData.logClearConfirm
                : 'Clear the log file? This cannot be undone.';
            if (!window.confirm(confirmMsg)) {
                e.preventDefault();
            }
        });
    }
});

// A/B test toggles — show/hide the Variant B textarea row when the
// admin flips the per-kind toggle. Persisting still requires WP's
// Save Changes; this is just live UI state.
document.addEventListener('DOMContentLoaded', function () {
    const toggles = document.querySelectorAll('.zignites-chat-ab-toggle');
    if (!toggles.length) return;
    toggles.forEach(function (sel) {
        const targetId = sel.getAttribute('data-ab-target');
        if (!targetId) return;
        const target = document.getElementById(targetId);
        if (!target) return;
        function sync() {
            target.style.display = sel.value === 'yes' ? '' : 'none';
        }
        sel.addEventListener('change', sync);
    });
});

// Date-range preset buttons (Today / Last 7 / Last 30 / This month / All time)
document.addEventListener('DOMContentLoaded', function () {
    const presets = document.querySelectorAll('.zignites-chat-analytics-preset');
    if (!presets.length) return;

    function fmt(d) {
        // Format Date as YYYY-MM-DD in local time so it matches what the
        // admin sees on screen (the date inputs are timezone-naive).
        const y = d.getFullYear();
        const m = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return `${y}-${m}-${day}`;
    }

    function setRange(range) {
        const fromInput = document.getElementById('zignites_chat_date_from');
        const toInput   = document.getElementById('zignites_chat_date_to');
        if (!fromInput || !toInput) return;

        const today = new Date();
        let from = '';
        let to   = '';

        if (range === 'today') {
            from = to = fmt(today);
        } else if (range === '7d') {
            const start = new Date(today);
            start.setDate(start.getDate() - 6);
            from = fmt(start);
            to   = fmt(today);
        } else if (range === '30d') {
            const start = new Date(today);
            start.setDate(start.getDate() - 29);
            from = fmt(start);
            to   = fmt(today);
        } else if (range === 'month') {
            const start = new Date(today.getFullYear(), today.getMonth(), 1);
            from = fmt(start);
            to   = fmt(today);
        } else if (range === 'all') {
            from = '';
            to   = '';
        }

        fromInput.value = from;
        toInput.value   = to;

        const filterBtn = document.getElementById('zignites-chat-analytics-filter-button');
        if (filterBtn) filterBtn.click();
    }

    presets.forEach(btn => {
        btn.addEventListener('click', function () {
            setRange(btn.getAttribute('data-range'));
        });
    });
});

// Export CSV honors the date inputs at click time so the user doesn't
// have to re-apply the filter just to export. The href was rendered with
// whatever filters were already in the URL; this rebuild syncs it with
// the live form values + presets.
document.addEventListener('DOMContentLoaded', function () {
    const exportLink = document.getElementById('zignites-chat-analytics-export-csv');
    if (!exportLink) return;

    exportLink.addEventListener('click', function (e) {
        const href = exportLink.getAttribute('href');
        if (!href) return;

        const url = new URL(href, window.location.origin);
        const fields = [
            { id: 'zignites_chat_type', key: 'zignites_chat_type' },
            { id: 'zignites_chat_status', key: 'zignites_chat_status' },
            { id: 'zignites_chat_phone', key: 'zignites_chat_phone' },
            { id: 'zignites_chat_date_from', key: 'zignites_chat_date_from' },
            { id: 'zignites_chat_date_to', key: 'zignites_chat_date_to' }
        ];
        fields.forEach(field => {
            const el = document.getElementById(field.id);
            const value = el ? el.value.trim() : '';
            if (value) {
                url.searchParams.set(field.key, value);
            } else {
                url.searchParams.delete(field.key);
            }
        });
        exportLink.setAttribute('href', url.pathname + url.search);
    });
});

// Send test WhatsApp message
document.addEventListener('DOMContentLoaded', function () {
    const sendBtn = document.getElementById('zignites-chat-send-test-message');
    const phoneField = document.getElementById('zignites_chat_test_phone');
    const messageField = document.getElementById('zignites_chat_test_message');
    const statusEl = document.getElementById('zignites-chat-test-status');
    if (!sendBtn || !phoneField || !messageField) return;

    function setStatus(text, ok) {
        if (!statusEl) return;
        statusEl.textContent = text;
        statusEl.style.color = ok ? '#1c7c54' : '#b32d2e';
    }

    sendBtn.addEventListener('click', function () {
        const phone = phoneField.value.trim();
        const message = messageField.value.trim();
        if (!phone || !message) {
            setStatus('Phone and message required.', false);
            return;
        }

        setStatus('Sending...', true);
        sendBtn.disabled = true;

        const formData = new FormData();
        formData.append('action', 'zignites_chat_send_test_whatsapp');
        formData.append('nonce', (window.zignitesChatAdminData && zignitesChatAdminData.testNonce) ? zignitesChatAdminData.testNonce : '');
        formData.append('phone', phone);
        formData.append('message', message);

        fetch((window.zignitesChatAdminData && zignitesChatAdminData.ajaxUrl) ? zignitesChatAdminData.ajaxUrl : '', {
            method: 'POST',
            credentials: 'same-origin',
            body: formData
        }).then(res => res.json())
          .then(data => {
            if (data && data.success) {
                setStatus('Test message sent.', true);
            } else {
                setStatus((data && data.data && data.data.message) ? data.data.message : 'Send failed.', false);
            }
          })
          .catch(() => setStatus('Send failed.', false))
          .finally(() => { sendBtn.disabled = false; });
    });
});