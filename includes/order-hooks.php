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

// Refactor error handling in wcwp_send_whatsapp_message
function wcwp_send_whatsapp_message($to, $message, $manual = false, $context = []) {
    $test_mode = get_option('wcwp_test_mode_enabled', 'no');
    $provider = get_option('wcwp_api_provider', 'twilio');
    $log_file = function_exists('wcwp_get_log_file') ? wcwp_get_log_file() : WP_CONTENT_DIR . '/woochat-pro.log';
    $log_prefix = $manual ? '[WooChat Pro - MANUAL]' : '[WooChat Pro]';
    $log_failed = false;
    $safe_to = function_exists('wcwp_mask_phone') ? wcwp_mask_phone($to) : $to;
    $safe_msg = function_exists('wcwp_redact_message') ? wcwp_redact_message($message) : $message;

    if (function_exists('wcwp_is_opted_out') && wcwp_is_opted_out($to)) {
        $log_msg = "$log_prefix Opt-out: Message blocked for $safe_to\n";
        @error_log($log_msg, 3, $log_file);
        return false;
    }

    $event_id = $context['event_id'] ?? null;
    $preview = function_exists('wcwp_redact_message') ? wcwp_redact_message($message) : $message;
    $event_type = isset($context['type']) && $context['type'] ? $context['type'] : 'order';

    if (function_exists('wcwp_analytics_log_event')) {
        if (!$event_id) {
            $event_id = wcwp_analytics_log_event($event_type, [
                'status' => 'pending',
                'phone' => $to,
                'order_id' => isset($context['order_id']) ? intval($context['order_id']) : 0,
                'message_preview' => $preview,
                'provider' => $provider,
                'meta' => ['source' => $context['type'] ?? 'order', 'manual' => $manual ? 'yes' : 'no'],
            ]);
        } else {
            wcwp_analytics_update_event($event_id, [
                'status' => 'pending',
                'provider' => $provider,
                'message_preview' => $preview,
            ]);
        }
    }

    $log_write = function($msg) use ($log_file, &$log_failed) {
        if (@error_log($msg, 3, $log_file) === false) {
            $log_failed = true;
        }
    };

    if ($test_mode === 'yes') {
        $log_msg = "$log_prefix TEST MODE: Order message to $safe_to: $safe_msg\n";
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
            $log_msg = "$log_prefix WhatsApp Cloud message sent to $safe_to\n";
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
        $log_msg = "$log_prefix WhatsApp message sent to $safe_to\n";
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
            echo '<div class="notice notice-error is-dismissible"><p><b>' . esc_html__('WooChat Pro:', 'woochat-pro') . '</b> ' . esc_html__('Unable to write to log file. Please check file permissions for wp-content/uploads/woochat-pro/.', 'woochat-pro') . '</p></div>';
        });
    }
}

