// Zignites Chat – Admin UI

// Agent table: serialize name+phone into hidden field on change/submit.
document.addEventListener('DOMContentLoaded', function () {
    const table  = document.getElementById('zignites-chat-agents-table');
    const hidden = document.getElementById('zignites_chat_agents_input');
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

    table.addEventListener('input', function (e) {
        if (e.target.classList.contains('zignites-chat-agent-name') || e.target.classList.contains('zignites-chat-agent-phone')) {
            serialize();
        }
    });

    // Capture-phase submit so the hidden field is current before WP reads it.
    const form = hidden.closest('form');
    if (form) form.addEventListener('submit', serialize, true);
});

// Dark mode toggle — persisted in localStorage.
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

    const toggle = document.createElement('button');
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
        const icon = toggle.querySelector('.zignites-chat-dark-icon');
        if (icon) icon.textContent = darkMode ? '🌙' : '☀️';
    });
});

// General Settings — show/hide Cloud API credential rows.
document.addEventListener('DOMContentLoaded', function () {
    const apiProvider  = document.getElementById('zignites_chat_api_provider');
    const cloudFields  = document.querySelectorAll('.zignites-chat-cloud-fields');

    function toggleCloudFields() {
        if (!apiProvider) return;
        const show = apiProvider.value === 'cloud';
        cloudFields.forEach(function (f) { f.style.display = show ? '' : 'none'; });
    }

    if (apiProvider) {
        apiProvider.addEventListener('change', toggleCloudFields);
        toggleCloudFields();
    }
});

// Messaging — show/hide test-mode hint badge alongside the Test Mode checkbox.
document.addEventListener('DOMContentLoaded', function () {
    const testMode = document.getElementById('zignites_chat_test_mode_enabled');
    const hint     = document.getElementById('zignites-chat-test-log-hint');
    const badge    = document.getElementById('zignites-chat-test-mode-badge');
    if (!testMode || !hint) return;

    function toggleHint() {
        hint.style.display  = testMode.checked ? '' : 'none';
        if (badge) badge.style.display = testMode.checked ? 'inline-block' : 'none';
    }

    testMode.addEventListener('change', toggleHint);
    toggleHint();
});

// Logs — apply filter + confirm before clear.
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
                const el    = document.getElementById(field.id);
                const value = el ? String(el.value).trim() : '';
                if (value) {
                    params.set(field.key, value);
                } else {
                    params.delete(field.key);
                }
            });
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

// Messaging — send test WhatsApp message via AJAX.
document.addEventListener('DOMContentLoaded', function () {
    const sendBtn      = document.getElementById('zignites-chat-send-test-message');
    const phoneField   = document.getElementById('zignites_chat_test_phone');
    const messageField = document.getElementById('zignites_chat_test_message');
    const statusEl     = document.getElementById('zignites-chat-test-status');
    if (!sendBtn || !phoneField || !messageField) return;

    function setStatus(text, ok) {
        if (!statusEl) return;
        statusEl.textContent = text;
        statusEl.style.color = ok ? '#1c7c54' : '#b32d2e';
    }

    sendBtn.addEventListener('click', function () {
        const phone   = phoneField.value.trim();
        const message = messageField.value.trim();
        if (!phone || !message) {
            setStatus('Phone and message required.', false);
            return;
        }

        setStatus('Sending...', true);
        sendBtn.disabled = true;

        const formData = new FormData();
        formData.append('action', 'zignites_chat_send_test_whatsapp');
        formData.append('nonce',   (window.zignitesChatAdminData && zignitesChatAdminData.testNonce) ? zignitesChatAdminData.testNonce : '');
        formData.append('phone',   phone);
        formData.append('message', message);

        fetch((window.zignitesChatAdminData && zignitesChatAdminData.ajaxUrl) ? zignitesChatAdminData.ajaxUrl : '', {
            method: 'POST',
            credentials: 'same-origin',
            body: formData
        })
        .then(function (res) { return res.json(); })
        .then(function (data) {
            if (data && data.success) {
                setStatus('Test message sent.', true);
            } else {
                setStatus((data && data.data && data.data.message) ? data.data.message : 'Send failed.', false);
            }
        })
        .catch(function () { setStatus('Send failed.', false); })
        .finally(function () { sendBtn.disabled = false; });
    });
});
