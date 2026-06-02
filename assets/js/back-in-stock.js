// Zignites Chat – Back-in-stock WhatsApp subscribe form.

(function () {
    document.addEventListener('DOMContentLoaded', function () {
        var root = document.querySelector('.zignites-chat-stock-form');
        if (!root) return;

        var cfg = (typeof zignitesChatStock !== 'undefined') ? zignitesChatStock : {};
        var i18n = cfg.i18n || {};
        var phoneEl = root.querySelector('.zignites-chat-stock-phone');
        var btn = root.querySelector('.zignites-chat-stock-submit');
        var msg = root.querySelector('.zignites-chat-stock-msg');
        var productId = root.getAttribute('data-product');

        function submit() {
            var phone = phoneEl ? phoneEl.value.trim() : '';
            if (!phone) {
                msg.textContent = i18n.empty || '';
                return;
            }
            btn.disabled = true;
            var original = btn.textContent;
            btn.textContent = i18n.sending || '';
            msg.textContent = '';

            var body = 'action=zignites_chat_stock_subscribe'
                + '&nonce=' + encodeURIComponent(cfg.nonce || '')
                + '&product_id=' + encodeURIComponent(productId)
                + '&phone=' + encodeURIComponent(phone);

            fetch(cfg.ajaxUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: body
            }).then(function (r) { return r.json(); }).then(function (res) {
                btn.disabled = false;
                btn.textContent = original;
                if (res && res.success) {
                    msg.textContent = res.data.message || '';
                    if (phoneEl) phoneEl.value = '';
                } else {
                    msg.textContent = (res && res.data && res.data.message) || (i18n.error || '');
                }
            }).catch(function () {
                btn.disabled = false;
                btn.textContent = original;
                msg.textContent = i18n.error || '';
            });
        }

        if (btn) btn.addEventListener('click', submit);
        if (phoneEl) {
            phoneEl.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') { e.preventDefault(); submit(); }
            });
        }
    });
})();
