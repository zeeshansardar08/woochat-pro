// Zignites Chat – Refresh WhatsApp sender health (quality rating + messaging tier).

(function () {
    document.addEventListener('DOMContentLoaded', function () {
        var btn = document.getElementById('zignites-chat-refresh-sender-health');
        if (!btn) return;

        var cfg = (typeof zignitesChatSenderHealth !== 'undefined') ? zignitesChatSenderHealth : {};
        var i18n = cfg.i18n || {};
        var msg = document.getElementById('zignites-chat-sender-health-status');

        btn.addEventListener('click', function () {
            btn.disabled = true;
            var original = btn.textContent;
            btn.textContent = i18n.checking || 'Checking…';
            if (msg) { msg.textContent = ''; msg.className = 'description'; }

            var body = 'action=zignites_chat_refresh_sender_health'
                + '&nonce=' + encodeURIComponent(cfg.nonce || '');

            fetch(cfg.ajaxUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: body
            }).then(function (r) { return r.json(); }).then(function (res) {
                btn.disabled = false;
                btn.textContent = original;
                if (res && res.success) {
                    // Reload so the panel re-renders the fresh snapshot from PHP.
                    window.location.reload();
                } else {
                    if (msg) {
                        msg.textContent = (res && res.data && res.data.message) || (i18n.error || '');
                        msg.className = 'description zignites-chat-sync-error';
                    }
                }
            }).catch(function () {
                btn.disabled = false;
                btn.textContent = original;
                if (msg) {
                    msg.textContent = i18n.error || '';
                    msg.className = 'description zignites-chat-sync-error';
                }
            });
        });
    });
})();
