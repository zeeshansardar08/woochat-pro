<?php
if (!defined('ABSPATH')) exit;

// Inject JS into footer to track cart activity
add_action('wp_footer', 'wcwp_cart_recovery_script');

function wcwp_cart_recovery_script() {
    if (is_admin()) return;

    if (!function_exists('wcwp_is_pro_active') || !wcwp_is_pro_active()) return;

    $enabled = get_option('wcwp_cart_recovery_enabled', 'yes');
    if ($enabled !== 'yes') return;

    wp_enqueue_script('wcwp-cart-tracker', plugin_dir_url(__FILE__) . '../assets/js/cart-tracker.js', ['jquery'], null, true);

    wp_localize_script('wcwp-cart-tracker', 'wcwp_ajax_obj', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('wcwp_cart_nonce'),
    ]);
    wp_localize_script('wcwp-cart-tracker', 'wcwp_cart_recovery_delay', get_option('wcwp_cart_recovery_delay', 20));
    wp_localize_script('wcwp-cart-tracker', 'wcwp_cart_consent_required', get_option('wcwp_cart_recovery_require_consent', 'no'));
}

// Optional consent checkbox on checkout
add_action('woocommerce_after_checkout_billing_form', 'wcwp_cart_recovery_consent_field');
function wcwp_cart_recovery_consent_field() {
    if (get_option('wcwp_cart_recovery_require_consent', 'no') !== 'yes') return;
    echo '<div class="wcwp-cart-consent" style="margin-top:12px;">';
    echo '<label style="display:flex;align-items:center;gap:8px;">';
    echo '<input type="checkbox" id="wcwp-cart-consent" name="wcwp-cart-consent" value="yes" checked />';
    echo '<span>Send me WhatsApp cart reminders</span>';
    echo '</label>';
    echo '</div>';
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
    $consent = sanitize_text_field($_POST['consent'] ?? 'no');

    if (get_option('wcwp_cart_recovery_require_consent', 'no') === 'yes' && $consent !== 'yes') {
        wp_send_json_error(['message' => 'Consent missing']);
        return;
    }

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
    $attempt_id = uniqid('wcwp_cart_', true);
    $event_id = null;

    foreach ($cart_items as $item) {
        $name  = sanitize_text_field($item['name']);
        $price = floatval($item['price']);
        $qty   = intval($item['qty']);
        $total += $price * $qty;
        $items[] = "- $name × $qty";
    }

    $body = implode("\n", $items);
    $cart_url = wc_get_cart_url();
    if (function_exists('wcwp_analytics_log_event')) {
        $event_id = wcwp_analytics_log_event('sent', [
            'status' => 'pending',
            'phone' => $phone,
            'message_preview' => '',
            'meta' => ['items' => $items, 'total' => $total, 'source' => 'cart_recovery'],
        ]);
    }
    $tracked_cart_url = ($event_id && function_exists('wcwp_analytics_tracking_url')) ? wcwp_analytics_tracking_url($event_id, $cart_url) : $cart_url;
    $template = get_option('wcwp_cart_recovery_message', "👋 Hey! You left items in your cart:\n\n{items}\n\nTotal: {total} PKR\nClick here to complete your order: {cart_url}");
    $message = str_replace(
        ['{items}', '{total}', '{cart_url}'],
        [$body, $total, $tracked_cart_url],
        $template
    );

    // Log all attempts
    $log_file = WCWP_PATH . 'woochat-pro.log';
    $log_msg = "[WooChat Pro - Cart Recovery] Attempt {$attempt_id} to $phone: $message\n";
    @error_log($log_msg, 3, $log_file);

    // Store attempt in transient for admin UI
    $attempts = get_transient('wcwp_cart_recovery_attempts') ?: [];
    $attempts[] = [
        'id' => $attempt_id,
        'time' => current_time('mysql'),
        'phone' => $phone,
        'message' => $message,
        'items' => $items,
        'total' => $total
    ];
    set_transient('wcwp_cart_recovery_attempts', $attempts, DAY_IN_SECONDS);

    // Check if test mode is enabled
    $test_mode = get_option('wcwp_test_mode_enabled', 'no');
    if ($test_mode === 'yes') {
        $log_msg = "[WooChat Pro - Cart Recovery TEST MODE] {$attempt_id} to $phone: $message\n";
        @error_log($log_msg, 3, $log_file);
        if ($event_id && function_exists('wcwp_analytics_update_event')) {
            wcwp_analytics_update_event($event_id, ['status' => 'test', 'message_preview' => $message]);
        }
        return;
    }

    if (function_exists('wcwp_send_whatsapp_message')) {
        $result = wcwp_send_whatsapp_message($phone, $message, false, ['type' => 'cart_recovery', 'event_id' => $event_id]);
        if ($event_id && function_exists('wcwp_analytics_update_event')) {
            wcwp_analytics_update_event($event_id, ['status' => $result === true ? 'sent' : 'failed', 'message_preview' => $message]);
        }
    }
}

// Helper to fetch attempts
function wcwp_get_cart_recovery_attempts() {
    $attempts = get_transient('wcwp_cart_recovery_attempts') ?: [];
    return array_slice(array_reverse($attempts), 0, 25);
}

// Admin resend handler
add_action('wp_ajax_wcwp_resend_cart_recovery', function() {
    if (!current_user_can('manage_woocommerce')) wp_send_json_error(['message' => 'Unauthorized'], 403);
    if (!check_ajax_referer('wcwp_resend_cart', 'nonce', false)) wp_send_json_error(['message' => 'Bad nonce'], 400);

    $attempt_id = sanitize_text_field($_POST['attempt_id'] ?? '');
    if (!$attempt_id) wp_send_json_error(['message' => 'Missing attempt id'], 400);

    $attempts = get_transient('wcwp_cart_recovery_attempts') ?: [];
    $found = null;
    foreach ($attempts as $a) {
        if (isset($a['id']) && $a['id'] === $attempt_id) {
            $found = $a;
            break;
        }
    }

    if (!$found) wp_send_json_error(['message' => 'Attempt not found'], 404);

    if (function_exists('wcwp_send_whatsapp_message')) {
        $result = wcwp_send_whatsapp_message($found['phone'], $found['message'], true);
        if ($result === true) {
            wp_send_json_success(['message' => 'Resent']);
        }
        wp_send_json_error(['message' => 'Send failed']);
    }

    wp_send_json_error(['message' => 'Messaging unavailable'], 500);
});
