jQuery(document).ready(function ($) {
    const phoneInputSelector = 'input[name=billing_phone]'; // Woo default
    const debounceMs = 5000;

    // Fetch the current cart from the WooCommerce Store API. Returns a
    // Promise that resolves to an array of {name, price, qty} — the shape
    // the wcwp_save_cart AJAX endpoint expects. Theme-independent: works on
    // cart, checkout, custom pages, and Block-based templates alike.
    function fetchCartItems() {
        if (!wcwp_ajax_obj || !wcwp_ajax_obj.cart_url) {
            return Promise.resolve([]);
        }
        return fetch(wcwp_ajax_obj.cart_url, {
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json' }
        }).then(function (res) {
            if (!res.ok) throw new Error('Store API cart fetch failed: ' + res.status);
            return res.json();
        }).then(function (data) {
            if (!data || !Array.isArray(data.items)) return [];
            return data.items.map(function (item) {
                const qty = parseInt(item.quantity, 10) || 0;
                const prices = item.prices || {};
                const minor = (typeof prices.currency_minor_unit === 'number') ? prices.currency_minor_unit : 2;
                const minorPrice = parseInt(prices.price, 10);
                const price = isNaN(minorPrice) ? 0 : minorPrice / Math.pow(10, minor);
                return {
                    name: typeof item.name === 'string' ? item.name : '',
                    price: price,
                    qty: qty
                };
            }).filter(function (i) { return i.name && i.qty > 0; });
        }).catch(function () {
            return [];
        });
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

    function scheduleSave() {
        clearTimeout(timer);
        timer = setTimeout(function () {
            const phone = $(phoneInputSelector).val();
            if (!phone || !hasConsent()) return;

            fetchCartItems().then(function (cart) {
                if (!cart.length) return;
                $.post(wcwp_ajax_obj.ajax_url, {
                    action: 'wcwp_save_cart',
                    nonce: wcwp_ajax_obj.nonce,
                    phone: phone,
                    cart: JSON.stringify(cart),
                    consent: hasConsent() ? 'yes' : 'no'
                });
            });
        }, debounceMs);
    }

    $(document).on('change input', '.cart_item input.qty, input[name=billing_phone]', function () {
        scheduleSave();
    });

    $(document.body).on('updated_wc_div updated_checkout', function () {
        scheduleSave();
    });

    // Kick off timer on load if data already present
    scheduleSave();
});
