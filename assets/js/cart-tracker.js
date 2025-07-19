jQuery(document).ready(function ($) {
    const phoneInputSelector = 'input[name=billing_phone]'; // Woo default
    const delayMinutes = typeof wcwp_cart_recovery_delay !== 'undefined' ? parseInt(wcwp_cart_recovery_delay) : 20;

    function getCartData() {
        let items = [];
        $('.woocommerce-cart-form .cart_item').each(function () {
            let name = $(this).find('.product-name').text().trim();
            let price = parseFloat($(this).find('.product-price .amount').text().replace(/[^0-9.]/g, ''));
            let qty = parseInt($(this).find('.qty').val());

            if (name && qty > 0) {
                items.push({ name, price, qty });
            }
        });
        return items;
    }

    let timer;

    function startRecoveryTimer() {
        clearTimeout(timer);

        timer = setTimeout(function () {
            const cart = getCartData();
            const phone = $(phoneInputSelector).val();

            if (cart.length && phone) {
                $.post(wcwp_ajax_obj.ajax_url, {
                    action: 'wcwp_save_cart',
                    nonce: wcwp_ajax_obj.nonce,
                    phone: phone,
                    cart: JSON.stringify(cart)
                });
            }
        }, delayMinutes * 60 * 1000);
    }

    $(document).on('change', '.cart_item input.qty, input[name=billing_phone]', function () {
        startRecoveryTimer();
    });
});
