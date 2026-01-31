<?php
if (!defined('ABSPATH')) exit;

// Schedule follow-up WhatsApp messages after order events (Pro only)
add_action('woocommerce_order_status_completed', 'wcwp_maybe_schedule_followup');
add_action('woocommerce_order_status_processing', 'wcwp_maybe_schedule_followup');
add_action('wcwp_send_followup_message', 'wcwp_send_followup_message_handler');

function wcwp_maybe_schedule_followup($order_id) {
    if (!function_exists('wcwp_is_pro_active') || !wcwp_is_pro_active()) return;

    $enabled = get_option('wcwp_followup_enabled', 'no');
    if ($enabled !== 'yes') return;

    $delay_minutes = absint(get_option('wcwp_followup_delay_minutes', 120));
    if ($delay_minutes < 1) $delay_minutes = 60;

    // Avoid duplicate scheduling
    if (get_post_meta($order_id, '_wcwp_followup_scheduled', true)) return;

    $timestamp = time() + ($delay_minutes * MINUTE_IN_SECONDS);
    wp_schedule_single_event($timestamp, 'wcwp_send_followup_message', [$order_id]);
    update_post_meta($order_id, '_wcwp_followup_scheduled', $timestamp);
}

function wcwp_send_followup_message_handler($order_id) {
    if (!function_exists('wcwp_is_pro_active') || !wcwp_is_pro_active()) return;

    $order = wc_get_order($order_id);
    if (!$order) return;

    // Prevent repeat sends
    if (get_post_meta($order_id, '_wcwp_followup_sent', true)) return;

    $to = sanitize_text_field($order->get_billing_phone());
    if (!$to) return;

    $message = wcwp_build_followup_message($order);

    // Optional GPT-generated content
    if (get_option('wcwp_followup_use_gpt', 'no') === 'yes') {
        $maybe_ai = wcwp_generate_gpt_followup($order, $message);
        if (!empty($maybe_ai)) {
            $message = $maybe_ai;
        }
    }

    if (function_exists('wcwp_send_whatsapp_message')) {
        $result = wcwp_send_whatsapp_message($to, $message);
        if ($result === true) {
            update_post_meta($order_id, '_wcwp_followup_sent', current_time('mysql'));
        }
    }
}

function wcwp_build_followup_message($order) {
    $template = get_option('wcwp_followup_template', "Hi {name}, thanks again for your order #{order_id}! Reply if you have any questions.");
    return str_replace(
        ['{name}', '{order_id}', '{total}', '{status}', '{date}'],
        [
            $order->get_billing_first_name(),
            $order->get_id(),
            $order->get_total(),
            $order->get_status(),
            $order->get_date_created() ? $order->get_date_created()->date_i18n(get_option('date_format')) : ''
        ],
        $template
    );
}

function wcwp_generate_gpt_followup($order, $fallback_message) {
    $endpoint = trim(get_option('wcwp_gpt_api_endpoint', ''));
    $api_key = trim(get_option('wcwp_gpt_api_key', ''));
    $model = trim(get_option('wcwp_gpt_model', 'gpt-3.5-turbo')) ?: 'gpt-3.5-turbo';

    if (!$endpoint || !$api_key) return '';

    $user_prompt = sprintf(
        'Create a short, friendly WhatsApp follow-up for %s (order #%d, total %s, status %s). Keep under 320 characters.',
        $order->get_billing_first_name(),
        $order->get_id(),
        $order->get_total(),
        $order->get_status()
    );

    $body = [
        'model' => $model,
        'messages' => [
            ['role' => 'system', 'content' => 'You craft concise WhatsApp follow-ups for customers. Keep it friendly, short, and actionable.'],
            ['role' => 'user', 'content' => $user_prompt],
            ['role' => 'user', 'content' => 'If unsure, reply exactly with: ' . $fallback_message]
        ],
        'max_tokens' => 120,
        'temperature' => 0.7,
    ];

    $response = wp_remote_post($endpoint, [
        'timeout' => 20,
        'headers' => [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $api_key,
        ],
        'body' => wp_json_encode($body),
    ]);

    if (is_wp_error($response)) return '';

    $code = wp_remote_retrieve_response_code($response);
    if ($code !== 200) return '';

    $data = json_decode(wp_remote_retrieve_body($response), true);
    if (!isset($data['choices'][0]['message']['content'])) return '';

    return trim($data['choices'][0]['message']['content']);
}
