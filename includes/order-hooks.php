<?php
if (!defined('ABSPATH')) exit;

// Hook into WooCommerce order complete
add_action('woocommerce_order_status_completed', 'wcwp_send_whatsapp_on_order_complete');
add_action('woocommerce_order_status_processing', 'wcwp_send_whatsapp_on_order_complete');

function wcwp_send_whatsapp_on_order_complete($order_id) {
    $order = wc_get_order($order_id);
    if (!$order) return;

    $to = sanitize_text_field($order->get_billing_phone());
    $name = $order->get_billing_first_name();
    $total = $order->get_total();

    $template = get_option('wcwp_order_message_template', 'Hi {name}, thanks for your order #{order_id}! Total: {total} PKR.');
    $message = str_replace(
        ['{name}', '{order_id}', '{total}'],
        [$name, $order_id, $total],
        $template
    );

    wcwp_send_whatsapp_message($to, $message);
}

function wcwp_send_whatsapp_message($to, $message) {
    $test_mode = get_option('wcwp_test_mode_enabled', 'no');

    if ($test_mode === 'yes') {
        if (defined('WP_DEBUG') && WP_DEBUG === true) {
            error_log("[WooChat Pro - TEST MODE] Order message to $to: $message");
        }
        return; // ✅ Skip real Twilio request
    }

    $sid = get_option('wcwp_twilio_sid');
    $token = get_option('wcwp_twilio_auth_token');
    $from = get_option('wcwp_twilio_from');
    $to_number = 'whatsapp:+' . preg_replace('/[^0-9]/', '', $to);

    if (!$sid || !$token || !$from) {
        error_log('WooChat Error: Missing Twilio credentials');
        return;
    }

    $url = "https://api.twilio.com/2010-04-01/Accounts/$sid/Messages.json";

    $body = [
        'From' => $from,
        'To' => $to_number,
        'Body' => $message
    ];

    $response = wp_remote_post($url, [
        'method'  => 'POST',
        'timeout' => 30,
        'headers' => [
            'Authorization' => 'Basic ' . base64_encode("$sid:$token"),
        ],
        'body' => $body,
    ]);

    if (is_wp_error($response)) {
        error_log('WhatsApp Error: ' . $response->get_error_message());
    } else {
        error_log("WhatsApp message sent to $to_number");
    }
}

