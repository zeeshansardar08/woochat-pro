// Zignites Chat – Sync approved WhatsApp templates from the Meta Graph API.

(function () {
    document.addEventListener('DOMContentLoaded', function () {
        var btn = document.getElementById('zignites-chat-sync-templates');
        if (!btn) return;

        var cfg = (typeof zignitesChatTemplateSync !== 'undefined') ? zignitesChatTemplateSync : {};
        var i18n = cfg.i18n || {};
        var msg = document.getElementById('zignites-chat-sync-status');
        var wabaEl = document.getElementById('zignites_chat_cloud_waba_id');

        btn.addEventListener('click', function () {
            var wabaId = wabaEl ? wabaEl.value.trim() : '';
            btn.disabled = true;
            var original = btn.textContent;
            btn.textContent = i18n.syncing || 'Syncing…';
            if (msg) { msg.textContent = ''; msg.className = 'description'; }

            var body = 'action=zignites_chat_sync_templates'
                + '&nonce=' + encodeURIComponent(cfg.nonce || '')
                + '&waba_id=' + encodeURIComponent(wabaId);

            fetch(cfg.ajaxUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: body
            }).then(function (r) { return r.json(); }).then(function (res) {
                btn.disabled = false;
                btn.textContent = original;
                if (res && res.success) {
                    if (msg) { msg.textContent = (res.data && res.data.message) || ''; }
                    // Reload so the synced names + reference list re-render from PHP.
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
