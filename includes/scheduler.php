<?php
if (!defined('ABSPATH')) exit;

// Schedule follow-up WhatsApp messages after order events (Pro only)
add_action('woocommerce_order_status_completed', 'zignites_chat_maybe_schedule_followup');
add_action('woocommerce_order_status_processing', 'zignites_chat_maybe_schedule_followup');
add_action('zignites_chat_send_followup_message', 'zignites_chat_send_followup_message_handler');

function zignites_chat_maybe_schedule_followup($order_id) {
    if (!zignites_chat_is_pro_active()) return;

    $enabled = get_option('zignites_chat_followup_enabled', 'no');
    if ($enabled !== 'yes') return;

    $delay_minutes = absint(get_option('zignites_chat_followup_delay_minutes', 120));
    if ($delay_minutes < 1) $delay_minutes = 60;

    $order = wc_get_order( $order_id );
    if ( ! $order ) return;

    // Avoid duplicate scheduling
    if ($order->get_meta('_zignites_chat_followup_scheduled')) return;

    $timestamp = time() + ($delay_minutes * MINUTE_IN_SECONDS);
    wp_schedule_single_event($timestamp, 'zignites_chat_send_followup_message', [$order_id]);
    $order->update_meta_data('_zignites_chat_followup_scheduled', $timestamp);
    $order->save();
}

function zignites_chat_send_followup_message_handler($order_id) {
    if (!zignites_chat_is_pro_active()) return;

    $order = wc_get_order($order_id);
    if (!$order) return;

    // Final states — never retry once we're here.
    if ($order->get_meta('_zignites_chat_followup_sent')) return;
    if ($order->get_meta('_zignites_chat_followup_failed')) return;

    $to = sanitize_text_field($order->get_billing_phone());
    if (!$to) return;

    // Permanent skip: opt-outs never reach the provider, so retrying just
    // burns attempts on the same `false` return.
    if (zignites_chat_is_opted_out($to)) return;

    // Central outbound budget: if the shared per-minute cap is hit, defer this
    // follow-up by re-scheduling shortly. This is a deferral, not a failure —
    // attempts are NOT incremented — so a saturated window can't burn the
    // 3-attempt cap. Done before the (possibly paid) GPT call below.
    if (function_exists('zignites_chat_outbound_rate_acquire') && !zignites_chat_outbound_rate_acquire()) {
        $defer = max(1, (int) apply_filters('zignites_chat_outbound_rate_defer_seconds', MINUTE_IN_SECONDS));
        wp_schedule_single_event(time() + $defer, 'zignites_chat_send_followup_message', [$order_id]);
        return;
    }

    $picked  = zignites_chat_ab_get_template('followup', $order_id);
    $message = zignites_chat_build_followup_message($order, $picked['template']);

    // Optional GPT-generated content. Falls back to the templated $message
    // when the helper returns '' (missing creds, network error, non-200,
    // or empty response). Note: GPT output is variant-agnostic — the
    // ab_variant tag still rides along on the event so you can compare A
    // vs B once you turn GPT off.
    if (get_option('zignites_chat_followup_use_gpt', 'no') === 'yes') {
        $maybe_ai = zignites_chat_generate_gpt_followup($order);
        if (!empty($maybe_ai)) {
            $message = $maybe_ai;
        }
    }

    $context = zignites_chat_maybe_apply_template('followup', [
        '{name}'            => $order->get_billing_first_name(),
        '{order_id}'        => $order->get_id(),
        '{total}'           => $order->get_total(),
        '{status}'          => $order->get_status(),
        '{date}'            => $order->get_date_created() ? $order->get_date_created()->date_i18n(get_option('date_format')) : '',
        '{currency_symbol}' => zignites_chat_currency_symbol_text(),
    ], [
        'type'       => 'followup',
        'order_id'   => $order_id,
        'ab_variant' => $picked['variant'],
    ]);

    $result = zignites_chat_send_whatsapp_message($to, $message, false, $context);
    if ($result === true) {
        $order->update_meta_data('_zignites_chat_followup_sent', current_time('mysql'));
        $order->save();
        return;
    }

    // 3-attempt cap mirrors the cart-recovery queue. Backoff steps are
    // shorter (5/15 vs the cart queue's 15min) because the value of a
    // followup decays fast — a 4h-old "thanks for your order" is awkward.
    $attempts     = intval($order->get_meta('_zignites_chat_followup_attempts')) + 1;
    $max_attempts = 3;
    $backoffs     = [5, 15];

    $order->update_meta_data('_zignites_chat_followup_attempts', $attempts);

    if ($attempts >= $max_attempts) {
        $order->update_meta_data('_zignites_chat_followup_failed', current_time('mysql'));
        $order->save();
        return;
    }

    $delay_minutes = isset($backoffs[$attempts - 1]) ? $backoffs[$attempts - 1] : 60;
    $next_at       = time() + ($delay_minutes * MINUTE_IN_SECONDS);

    $order->save();
    wp_schedule_single_event($next_at, 'zignites_chat_send_followup_message', [$order_id]);
}

function zignites_chat_build_followup_message($order, $template = null) {
    if ($template === null || $template === '') {
        $template = get_option('zignites_chat_followup_template', "Hi {name}, thanks again for your order #{order_id}! Reply if you have any questions.");
    }
    return str_replace(
        ['{name}', '{order_id}', '{total}', '{status}', '{date}', '{currency_symbol}'],
        [
            $order->get_billing_first_name(),
            $order->get_id(),
            $order->get_total(),
            $order->get_status(),
            $order->get_date_created() ? $order->get_date_created()->date_i18n(get_option('date_format')) : '',
            zignites_chat_currency_symbol_text()
        ],
        $template
    );
}

function zignites_chat_generate_gpt_followup($order) {
    $endpoint = trim(get_option('zignites_chat_gpt_api_endpoint', ''));
    $api_key = trim(get_option('zignites_chat_gpt_api_key', ''));
    $model = trim(get_option('zignites_chat_gpt_model', zignites_chat_default_gpt_model())) ?: zignites_chat_default_gpt_model();

    if (!$endpoint || !$api_key) {
        zignites_chat_record_gpt_error('followup', 'Missing endpoint or API key — GPT follow-up skipped, falling back to template.');
        return '';
    }

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

    if (is_wp_error($response)) {
        zignites_chat_record_gpt_error('followup', 'Network error: ' . $response->get_error_message());
        return '';
    }

    $code = wp_remote_retrieve_response_code($response);
    if ($code !== 200) {
        zignites_chat_record_gpt_error('followup', sprintf('GPT endpoint returned HTTP %d.', (int) $code));
        return '';
    }

    $data = json_decode(wp_remote_retrieve_body($response), true);
    if (!isset($data['choices'][0]['message']['content'])) {
        zignites_chat_record_gpt_error('followup', 'GPT response missing choices[0].message.content.');
        return '';
    }

    return trim($data['choices'][0]['message']['content']);
}
