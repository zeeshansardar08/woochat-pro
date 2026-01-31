jQuery(document).ready(function ($) {
    const phoneInputSelector = 'input[name=billing_phone]'; // Woo default
    const delayMinutes = typeof wcwp_cart_recovery_delay !== 'undefined' ? parseInt(wcwp_cart_recovery_delay, 10) : 20;

    function getCartData() {
        let items = [];
        const rows = $('.woocommerce-cart-form .cart_item, #order_review .cart_item');
        rows.each(function () {
            let name = $(this).find('.product-name').text().trim();
            let price = parseFloat($(this).find('.product-price .amount').text().replace(/[^0-9.]/g, ''));
            let qty = parseInt($(this).find('.qty').val(), 10);

            if (name && !isNaN(qty) && qty > 0) {
                items.push({ name, price: isNaN(price) ? 0 : price, qty });
            }
        });
        return items;
    }

    function hasConsent() {
        if (typeof wcwp_cart_consent_required !== 'undefined' && wcwp_cart_consent_required === 'yes') {
            const box = $('#wcwp-cart-consent');
            if (box.length) return box.is(':checked');
            return false;
        }
        return true;
    }

    let timer;

    function startRecoveryTimer() {
        clearTimeout(timer);

        timer = setTimeout(function () {
            const cart = getCartData();
            const phone = $(phoneInputSelector).val();

            if (cart.length && phone && hasConsent()) {
                $.post(wcwp_ajax_obj.ajax_url, {
                    action: 'wcwp_save_cart',
                    nonce: wcwp_ajax_obj.nonce,
                    phone: phone,
                    cart: JSON.stringify(cart),
                    consent: hasConsent() ? 'yes' : 'no'
                });
            }
        }, delayMinutes * 60 * 1000);
    }

    $(document).on('change input', '.cart_item input.qty, input[name=billing_phone]', function () {
        startRecoveryTimer();
    });

    $(document.body).on('updated_wc_div updated_checkout', function () {
        startRecoveryTimer();
    });

    // Kick off timer on load if data already present
    startRecoveryTimer();
});
