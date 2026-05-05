<?php
if (!defined('ABSPATH')) exit;

// Schedule follow-up WhatsApp messages after order events (Pro only)
add_action('woocommerce_order_status_completed', 'wcwp_maybe_schedule_followup');
add_action('woocommerce_order_status_processing', 'wcwp_maybe_schedule_followup');
add_action('wcwp_send_followup_message', 'wcwp_send_followup_message_handler');

function wcwp_maybe_schedule_followup($order_id) {
    if (!wcwp_is_pro_active()) return;

    $enabled = get_option('wcwp_followup_enabled', 'no');
    if ($enabled !== 'yes') return;

    $delay_minutes = absint(get_option('wcwp_followup_delay_minutes', 120));
    if ($delay_minutes < 1) $delay_minutes = 60;

    $order = wc_get_order( $order_id );
    if ( ! $order ) return;

    // Avoid duplicate scheduling
    if ($order->get_meta('_wcwp_followup_scheduled')) return;

    $timestamp = time() + ($delay_minutes * MINUTE_IN_SECONDS);
    wp_schedule_single_event($timestamp, 'wcwp_send_followup_message', [$order_id]);
    $order->update_meta_data('_wcwp_followup_scheduled', $timestamp);
    $order->save();
}

function wcwp_send_followup_message_handler($order_id) {
    if (!wcwp_is_pro_active()) return;

    $order = wc_get_order($order_id);
    if (!$order) return;

    // Final states — never retry once we're here.
    if ($order->get_meta('_wcwp_followup_sent')) return;
    if ($order->get_meta('_wcwp_followup_failed')) return;

    $to = sanitize_text_field($order->get_billing_phone());
    if (!$to) return;

    // Permanent skip: opt-outs never reach the provider, so retrying just
    // burns attempts on the same `false` return.
    if (wcwp_is_opted_out($to)) return;

    $message = wcwp_build_followup_message($order);

    // Optional GPT-generated content. Falls back to the templated $message
    // when the helper returns '' (missing creds, network error, non-200,
    // or empty response).
    if (get_option('wcwp_followup_use_gpt', 'no') === 'yes') {
        $maybe_ai = wcwp_generate_gpt_followup($order);
        if (!empty($maybe_ai)) {
            $message = $maybe_ai;
        }
    }

    $result = wcwp_send_whatsapp_message($to, $message, false, ['type' => 'followup', 'order_id' => $order_id]);
    if ($result === true) {
        $order->update_meta_data('_wcwp_followup_sent', current_time('mysql'));
        $order->save();
        return;
    }

    // 3-attempt cap mirrors the cart-recovery queue. Backoff steps are
    // shorter (5/15 vs the cart queue's 15min) because the value of a
    // followup decays fast — a 4h-old "thanks for your order" is awkward.
    $attempts     = intval($order->get_meta('_wcwp_followup_attempts')) + 1;
    $max_attempts = 3;
    $backoffs     = [5, 15];

    $order->update_meta_data('_wcwp_followup_attempts', $attempts);

    if ($attempts >= $max_attempts) {
        $order->update_meta_data('_wcwp_followup_failed', current_time('mysql'));
        $order->save();
        return;
    }

    $delay_minutes = isset($backoffs[$attempts - 1]) ? $backoffs[$attempts - 1] : 60;
    $next_at       = time() + ($delay_minutes * MINUTE_IN_SECONDS);

    $order->save();
    wp_schedule_single_event($next_at, 'wcwp_send_followup_message', [$order_id]);
}

function wcwp_build_followup_message($order) {
    $template = get_option('wcwp_followup_template', "Hi {name}, thanks again for your order #{order_id}! Reply if you have any questions.");
    return str_replace(
        ['{name}', '{order_id}', '{total}', '{status}', '{date}', '{currency_symbol}'],
        [
            $order->get_billing_first_name(),
            $order->get_id(),
            $order->get_total(),
            $order->get_status(),
            $order->get_date_created() ? $order->get_date_created()->date_i18n(get_option('date_format')) : '',
            wcwp_currency_symbol_text()
        ],
        $template
    );
}

function wcwp_generate_gpt_followup($order) {
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
