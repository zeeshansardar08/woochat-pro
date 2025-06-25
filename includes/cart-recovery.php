<?php
if (!defined('ABSPATH')) exit;

// Inject JS into footer to track cart activity
add_action('wp_footer', 'wcwp_cart_recovery_script');

function wcwp_cart_recovery_script() {
    if (is_admin() || is_cart() || is_checkout()) return;

    if (!function_exists('wcwp_is_pro_active') || !wcwp_is_pro_active()) return;

    $enabled = get_option('wcwp_cart_recovery_enabled', 'yes');
    if ($enabled !== 'yes') return;

    wp_enqueue_script('wcwp-cart-tracker', plugin_dir_url(__FILE__) . '../assets/js/cart-tracker.js', ['jquery'], null, true);

    wp_localize_script('wcwp-cart-tracker', 'wcwp_ajax_obj', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('wcwp_cart_nonce'),
    ]);
}

// Handle AJAX for cart tracking
add_action('wp_ajax_nopriv_wcwp_save_cart', 'wcwp_save_cart_ajax');

function wcwp_save_cart_ajax() {
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wcwp_cart_nonce')) {
        wp_send_json_error(['message' => 'Unauthorized']);
        return;
    }

    $phone = sanitize_text_field($_POST['phone'] ?? '');
    $cart_items = json_decode(stripslashes($_POST['cart'] ?? ''), true);

    if (!$phone || empty($cart_items)) {
        wp_send_json_error(['message' => 'Missing data']);
        return;
    }

    wcwp_send_cart_recovery_whatsapp($phone, $cart_items);
    wp_send_json_success(['message' => 'Reminder sent']);
}

function wcwp_send_cart_recovery_whatsapp($phone, $cart_items) {
    $total = 0;
    $items = [];

    foreach ($cart_items as $item) {
        $name  = sanitize_text_field($item['name']);
        $price = floatval($item['price']);
        $qty   = intval($item['qty']);
        $total += $price * $qty;
        $items[] = "- $name × $qty";
    }

    $body = implode("\n", $items);
    $message = "👋 Hey! You left items in your cart:\n\n$body\n\nTotal: $total PKR\nClick here to complete your order: " . wc_get_cart_url();

    // Check if test mode is enabled
    $test_mode = get_option('wcwp_test_mode_enabled', 'no');
    if ($test_mode === 'yes') {
        // Log message to debug.log
        if (defined('WP_DEBUG') && WP_DEBUG === true) {
            error_log("[WooChat Pro - TEST MODE] Message to $phone: $message");
        }
        return; // Skip sending real message
    }

    if (function_exists('wcwp_send_whatsapp_message')) {
        wcwp_send_whatsapp_message($phone, $message);
    }
}
