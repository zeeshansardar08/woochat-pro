<?php
if (!defined('ABSPATH')) exit;

// Hook into WooCommerce order complete
add_action('woocommerce_order_status_completed', 'wcwp_send_whatsapp_on_order_complete');
add_action('woocommerce_order_status_processing', 'wcwp_send_whatsapp_on_order_complete');

function wcwp_send_whatsapp_on_order_complete($order_id) {
    $order = wc_get_order($order_id);
    if (!$order) return;

    if ($order->get_meta('_wcwp_order_msg_sent')) {
        return;
    }

    $to = sanitize_text_field($order->get_billing_phone());
    $name = $order->get_billing_first_name();
    $total = $order->get_total();

    $template = get_option('wcwp_order_message_template', 'Hi {name}, thanks for your order #{order_id}! Total: {total} PKR.');
    $message = str_replace(
        ['{name}', '{order_id}', '{total}'],
        [$name, $order_id, $total],
        $template
    );

    $result = wcwp_send_whatsapp_message($to, $message, false, ['type' => 'order', 'order_id' => $order_id]);
    if ($result === true) {
        $order->update_meta_data('_wcwp_order_msg_sent', current_time('mysql'));
        $order->save();
    }
}

// Add manual WhatsApp message button to order admin screen
add_action('woocommerce_admin_order_actions_end', function($order) {
    $order_id = $order->get_id();
    $nonce    = wp_create_nonce('wcwp_send_manual_whatsapp_' . $order_id);
    printf(
        '<button type="button" class="button tips wcwp-send-whatsapp" data-order-id="%1$d" data-nonce="%2$s" data-tip="%3$s" aria-label="%3$s"><span class="dashicons dashicons-format-chat"></span></button>',
        (int) $order_id,
        esc_attr($nonce),
        esc_attr__('Send WhatsApp Message', 'woochat-pro')
    );
});

// Enqueue the small handler script only on Woo orders screens (HPOS + legacy).
add_action('admin_enqueue_scripts', function() {
    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if (!$screen) return;
    $allowed = ['woocommerce_page_wc-orders', 'edit-shop_order', 'shop_order'];
    if (!in_array($screen->id, $allowed, true)) return;
    wp_enqueue_script(
        'wcwp-admin-orders',
        WCWP_URL . 'assets/js/admin-orders.js',
        [],
        WCWP_VERSION,
        true
    );
    wp_localize_script('wcwp-admin-orders', 'wcwpManualSend', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
    ]);
});

add_action('wp_ajax_wcwp_send_manual_whatsapp', function() {
    if (!current_user_can('manage_woocommerce')) {
        wp_send_json_error(['message' => __('Unauthorized', 'woochat-pro')], 403);
    }

    $order_id = isset($_POST['order_id']) ? absint(wp_unslash($_POST['order_id'])) : 0;
    if (!$order_id) {
        wp_send_json_error(['message' => __('Invalid order', 'woochat-pro')], 400);
    }

    if (!check_ajax_referer('wcwp_send_manual_whatsapp_' . $order_id, 'nonce', false)) {
        wp_send_json_error(['message' => __('Invalid nonce', 'woochat-pro')], 400);
    }

    $order = wc_get_order($order_id);
    if (!$order) {
        wp_send_json_error(['message' => __('Order not found', 'woochat-pro')], 404);
    }

    $to       = sanitize_text_field($order->get_billing_phone());
    $name     = $order->get_billing_first_name();
    $total    = $order->get_total();
    $template = get_option('wcwp_order_message_template', 'Hi {name}, thanks for your order #{order_id}! Total: {total} PKR.');
    $message  = str_replace(['{name}', '{order_id}', '{total}'], [$name, $order_id, $total], $template);
    $result   = wcwp_send_whatsapp_message($to, $message, true, ['type' => 'order', 'order_id' => $order_id]);

    $redirect = add_query_arg(
        ['wcwp_msg' => $result === true ? 'success' : 'fail'],
        admin_url('post.php?post=' . $order_id . '&action=edit')
    );

    if ($result === true) {
        wp_send_json_success(['redirect' => $redirect]);
    }
    wp_send_json_error(['redirect' => $redirect, 'message' => __('Send failed', 'woochat-pro')]);
});

// Admin test message sender
add_action('wp_ajax_wcwp_send_test_whatsapp', function() {
    if (!current_user_can('manage_woocommerce')) {
        wp_send_json_error(['message' => __('Unauthorized', 'woochat-pro')], 403);
    }
    if (!check_ajax_referer('wcwp_test_message', 'nonce', false)) {
        wp_send_json_error(['message' => __('Bad nonce', 'woochat-pro')], 400);
    }

    $phone = isset($_POST['phone']) ? sanitize_text_field(wp_unslash($_POST['phone'])) : '';
    $message = isset($_POST['message']) ? sanitize_textarea_field(wp_unslash($_POST['message'])) : '';

    if (!$phone || !$message) {
        wp_send_json_error(['message' => __('Phone and message required', 'woochat-pro')], 400);
    }

    update_option('wcwp_test_phone', $phone, false);
    update_option('wcwp_test_message', $message, false);

    if (!function_exists('wcwp_send_whatsapp_message')) {
        wp_send_json_error(['message' => __('Messaging unavailable', 'woochat-pro')], 500);
    }

    $result = wcwp_send_whatsapp_message($phone, $message, true, ['type' => 'test']);
    if ($result === true) {
        wp_send_json_success(['message' => __('Sent', 'woochat-pro')]);
    }

    wp_send_json_error(['message' => __('Send failed', 'woochat-pro')], 500);
});

// Show admin notice for manual send result
add_action('admin_notices', function() {
    if (!isset($_GET['wcwp_msg'])) return;
    $msg = sanitize_text_field(wp_unslash($_GET['wcwp_msg']));
    if ($msg === 'success') {
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('WhatsApp message sent successfully.', 'woochat-pro') . '</p></div>';
    } elseif ($msg === 'fail') {
        echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__('Failed to send WhatsApp message. Check logs for details.', 'woochat-pro') . '</p></div>';
    }
});

// Note: wcwp_send_whatsapp_message() and wcwp_maybe_log_notice() now
// live in includes/messaging.php with the provider classes — see
// includes/providers/. The public function signature is unchanged so
// every caller in this file (and in cart-recovery.php / scheduler.php)
// keeps working.

