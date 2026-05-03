// WooChat Pro – Admin orders list: manual "Send WhatsApp" button.
// Submits via POST + nonce so the action is no longer a GET side-effect.

(function () {
    'use strict';

    function getAjaxUrl() {
        return (window.wcwpManualSend && window.wcwpManualSend.ajaxUrl)
            ? window.wcwpManualSend.ajaxUrl
            : '';
    }

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.wcwp-send-whatsapp');
        if (!btn) return;

        e.preventDefault();
        if (btn.disabled) return;

        var orderId = btn.getAttribute('data-order-id');
        var nonce = btn.getAttribute('data-nonce');
        var ajaxUrl = getAjaxUrl();
        if (!orderId || !nonce || !ajaxUrl) return;

        btn.disabled = true;

        var form = new FormData();
        form.append('action', 'wcwp_send_manual_whatsapp');
        form.append('order_id', orderId);
        form.append('nonce', nonce);

        fetch(ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            body: form
        }).then(function (res) {
            return res.json().catch(function () { return null; });
        }).then(function (data) {
            var redirect = data && data.data && data.data.redirect;
            if (redirect) {
                window.location.href = redirect;
                return;
            }
            btn.disabled = false;
        }).catch(function () {
            btn.disabled = false;
        });
    });
})();
