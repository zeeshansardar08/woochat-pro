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

    wcwp_send_whatsapp_message($to, $message, false, ['type' => 'order', 'order_id' => $order_id]);
}

// Add manual WhatsApp message button to order admin screen
add_action('woocommerce_admin_order_actions_end', function($order) {
    $order_id = $order->get_id();
    echo '<a class="button tips wcwp-send-whatsapp" href="' . esc_url( wp_nonce_url( admin_url( 'admin-ajax.php?action=wcwp_send_manual_whatsapp&order_id=' . $order_id ), 'wcwp_send_manual_whatsapp_' . $order_id ) ) . '" data-tip="Send WhatsApp Message"><span class="dashicons dashicons-format-chat"></span></a>';
});

add_action('wp_ajax_wcwp_send_manual_whatsapp', function() {
    if (!current_user_can('manage_woocommerce')) wp_die('Unauthorized');
    $order_id = intval($_GET['order_id'] ?? 0);
    if (!$order_id || !wp_verify_nonce($_GET['_wpnonce'] ?? '', 'wcwp_send_manual_whatsapp_' . $order_id)) wp_die('Invalid nonce');
    $order = wc_get_order($order_id);
    if (!$order) wp_die('Order not found');
    $to = sanitize_text_field($order->get_billing_phone());
    $name = $order->get_billing_first_name();
    $total = $order->get_total();
    $template = get_option('wcwp_order_message_template', 'Hi {name}, thanks for your order #{order_id}! Total: {total} PKR.');
    $message = str_replace(['{name}', '{order_id}', '{total}'], [$name, $order_id, $total], $template);
    $result = wcwp_send_whatsapp_message($to, $message, true, ['type' => 'order', 'order_id' => $order_id]);
    if ($result === true) {
        wp_safe_redirect( admin_url('post.php?post=' . $order_id . '&action=edit&wcwp_msg=success') );
    } else {
        wp_safe_redirect( admin_url('post.php?post=' . $order_id . '&action=edit&wcwp_msg=fail') );
    }
    exit;
});

// Show admin notice for manual send result
add_action('admin_notices', function() {
    if (!isset($_GET['wcwp_msg'])) return;
    if ($_GET['wcwp_msg'] === 'success') {
        echo '<div class="notice notice-success is-dismissible"><p>WhatsApp message sent successfully.</p></div>';
    } elseif ($_GET['wcwp_msg'] === 'fail') {
        echo '<div class="notice notice-error is-dismissible"><p>Failed to send WhatsApp message. Check logs for details.</p></div>';
    }
});

// Refactor error handling in wcwp_send_whatsapp_message
function wcwp_send_whatsapp_message($to, $message, $manual = false, $context = []) {
    $test_mode = get_option('wcwp_test_mode_enabled', 'no');
    $provider = get_option('wcwp_api_provider', 'twilio');
    $plugin_log_file = WCWP_PATH . 'woochat-pro.log';
    $fallback_log_file = WP_CONTENT_DIR . '/woochat-pro.log';
    $log_file = is_writable(dirname($plugin_log_file)) ? $plugin_log_file : $fallback_log_file;
    $log_prefix = $manual ? '[WooChat Pro - MANUAL]' : '[WooChat Pro]';
    $log_failed = false;

    $event_id = $context['event_id'] ?? null;
    if (function_exists('wcwp_analytics_log_event')) {
        if (!$event_id) {
            $event_id = wcwp_analytics_log_event('sent', [
                'status' => 'pending',
                'phone' => $to,
                'order_id' => isset($context['order_id']) ? intval($context['order_id']) : 0,
                'message_preview' => $message,
                'provider' => $provider,
                'meta' => ['source' => $context['type'] ?? 'order', 'manual' => $manual ? 'yes' : 'no'],
            ]);
        } else {
            wcwp_analytics_update_event($event_id, [
                'status' => 'pending',
                'provider' => $provider,
                'message_preview' => $message,
            ]);
        }
    }

    $log_write = function($msg) use ($log_file, &$log_failed) {
        if (@error_log($msg, 3, $log_file) === false) {
            $log_failed = true;
        }
    };

    if ($test_mode === 'yes') {
        $log_msg = "$log_prefix TEST MODE: Order message to $to: $message\n";
        $log_write($log_msg);
        wcwp_maybe_log_notice($log_failed);
        if (function_exists('wcwp_analytics_update_event') && $event_id) {
            wcwp_analytics_update_event($event_id, ['status' => 'test']);
        }
        return true;
    }

    if ($provider === 'cloud') {
        $token = get_option('wcwp_cloud_token');
        $phone_id = get_option('wcwp_cloud_phone_id');
        $from = get_option('wcwp_cloud_from');
        $to_number = preg_replace('/[^0-9]/', '', $to);
        if (!$token || !$phone_id || !$from) {
            $log_msg = "$log_prefix WhatsApp Cloud API Error: Missing credentials\n";
            $log_write($log_msg);
            wcwp_maybe_log_notice($log_failed);
            return false;
        }
        $url = "https://graph.facebook.com/v19.0/$phone_id/messages";
        $body = [
            'messaging_product' => 'whatsapp',
            'to' => $to_number,
            'type' => 'text',
            'text' => [ 'body' => $message ]
        ];
        $response = wp_remote_post($url, [
            'method'  => 'POST',
            'timeout' => 30,
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type'  => 'application/json',
            ],
            'body' => json_encode($body),
        ]);
        if (is_wp_error($response)) {
            $log_msg = "$log_prefix WhatsApp Cloud API Error: " . $response->get_error_message() . "\n";
            $log_write($log_msg);
            wcwp_maybe_log_notice($log_failed);
            if (function_exists('wcwp_analytics_update_event') && $event_id) wcwp_analytics_update_event($event_id, ['status' => 'failed']);
            return false;
        } else {
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            if (isset($data['error'])) {
                $log_msg = "$log_prefix WhatsApp Cloud API Error: " . print_r($data['error'], true) . "\n";
                $log_write($log_msg);
                wcwp_maybe_log_notice($log_failed);
                if (function_exists('wcwp_analytics_update_event') && $event_id) wcwp_analytics_update_event($event_id, ['status' => 'failed']);
                return false;
            }
            $msg_id = isset($data['messages'][0]['id']) ? sanitize_text_field($data['messages'][0]['id']) : '';
            $log_msg = "$log_prefix WhatsApp Cloud message sent to $to_number\n";
            $log_write($log_msg);
            wcwp_maybe_log_notice($log_failed);
            if (function_exists('wcwp_analytics_update_event') && $event_id) {
                wcwp_analytics_update_event($event_id, ['status' => 'sent', 'message_id' => $msg_id]);
            }
            if (function_exists('wcwp_analytics_increment_total')) {
                wcwp_analytics_increment_total('sent');
            }
            return true;
        }
    }

    // Default: Twilio
    $sid = get_option('wcwp_twilio_sid');
    $token = get_option('wcwp_twilio_auth_token');
    $from = get_option('wcwp_twilio_from');
    $to_number = 'whatsapp:+' . preg_replace('/[^0-9]/', '', $to);
    if (!$sid || !$token || !$from) {
        $log_msg = "$log_prefix Twilio Error: Missing credentials\n";
        $log_write($log_msg);
        wcwp_maybe_log_notice($log_failed);
        if (function_exists('wcwp_analytics_update_event') && $event_id) wcwp_analytics_update_event($event_id, ['status' => 'failed']);
        return false;
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
        $log_msg = "$log_prefix Twilio Error: " . $response->get_error_message() . "\n";
        $log_write($log_msg);
        wcwp_maybe_log_notice($log_failed);
        if (function_exists('wcwp_analytics_update_event') && $event_id) wcwp_analytics_update_event($event_id, ['status' => 'failed']);
        return false;
    } else {
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        if (isset($data['code']) && isset($data['message'])) {
            $log_msg = "$log_prefix Twilio API Error: [{$data['code']}] {$data['message']}\n";
            $log_write($log_msg);
            wcwp_maybe_log_notice($log_failed);
            if (function_exists('wcwp_analytics_update_event') && $event_id) wcwp_analytics_update_event($event_id, ['status' => 'failed']);
            return false;
        }
        $msg_id = isset($data['sid']) ? sanitize_text_field($data['sid']) : '';
        $log_msg = "$log_prefix WhatsApp message sent to $to_number\n";
        $log_write($log_msg);
        wcwp_maybe_log_notice($log_failed);
        if (function_exists('wcwp_analytics_update_event') && $event_id) wcwp_analytics_update_event($event_id, ['status' => 'sent', 'message_id' => $msg_id]);
        if (function_exists('wcwp_analytics_increment_total')) {
            wcwp_analytics_increment_total('sent');
        }
        return true;
    }
}

// Show admin notice if logging fails
function wcwp_maybe_log_notice($log_failed) {
    if ($log_failed) {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error is-dismissible"><p><b>WooChat Pro:</b> Unable to write to plugin log file. Please check file permissions for wp-content/plugins/woochat-pro/woochat-pro.log or wp-content/woochat-pro.log.</p></div>';
        });
    }
}

