<?php
/**
 * COD order confirmation / verification (Pro) — increment C1.
 *
 * In cash-on-delivery markets a large share of orders are fake or abandoned,
 * driving return-to-origin (RTO) cost. This feature asks the customer to
 * confirm a new COD order over WhatsApp; their reply (a quick-reply button on
 * an approved template, or a keyword) then flips the WooCommerce order status
 * (C2). C1 covers the data model, settings, and the send-on-new-COD-order path.
 *
 * Because the confirmation is business-initiated (outside any 24h session
 * window), it must go out as an approved HSM template with quick-reply buttons
 * — configured under WhatsApp Templates as the `cod_confirmation` type. The
 * rendered text is still sent as the free-form fallback/preview.
 *
 * @package Zignites_Chat
 */

if (!defined('ABSPATH')) exit;

/* -------------------------------------------------------------------------
 * Template type — expose a `cod_confirmation` HSM mapping on the WA Templates
 * page so admins can point it at their approved buttoned template.
 * ----------------------------------------------------------------------- */

add_filter('zignites_chat_wa_template_types', 'zignites_chat_cod_register_template_type');
function zignites_chat_cod_register_template_type($types) {
    if (is_array($types) && !isset($types['cod_confirmation'])) {
        $types['cod_confirmation'] = [
            'label'        => __('COD order confirmation', 'zignites-chat'),
            'placeholders' => ['{name}', '{order_id}', '{total}', '{currency_symbol}'],
        ];
    }
    return $types;
}

/* -------------------------------------------------------------------------
 * Settings access
 * ----------------------------------------------------------------------- */

/**
 * Whether COD confirmation is enabled (Pro + toggle on).
 *
 * @return bool
 */
function zignites_chat_cod_is_enabled() {
    if (function_exists('zignites_chat_is_pro_active') && !zignites_chat_is_pro_active()) {
        return false;
    }
    return get_option('zignites_chat_cod_enabled', 'no') === 'yes';
}

/**
 * Configured list of payment-gateway ids that count as cash-on-delivery.
 *
 * @return array<int, string>
 */
function zignites_chat_cod_gateways() {
    $stored = get_option('zignites_chat_cod_gateways', ['cod']);
    if (!is_array($stored)) {
        $stored = ['cod'];
    }
    $clean = [];
    foreach ($stored as $id) {
        $id = sanitize_key((string) $id);
        if ($id !== '') {
            $clean[] = $id;
        }
    }
    return $clean ?: ['cod'];
}

/**
 * Sanitize the COD gateways option into a clean list of gateway keys.
 *
 * @param mixed $value Raw option value (array of gateway ids).
 * @return array<int, string>
 */
function zignites_chat_cod_sanitize_gateways($value) {
    if (!is_array($value)) {
        return [];
    }
    $clean = [];
    foreach ($value as $id) {
        $id = sanitize_key((string) $id);
        if ($id !== '' && !in_array($id, $clean, true)) {
            $clean[] = $id;
        }
    }
    return $clean;
}

/* -------------------------------------------------------------------------
 * Pure helpers (no WC, no DB) — unit-tested
 * ----------------------------------------------------------------------- */

/**
 * Whether an order's payment method counts as cash-on-delivery.
 *
 * @param string            $payment_method Order payment-method id.
 * @param array<int,string> $gateways       Configured COD gateway ids.
 * @return bool
 */
function zignites_chat_cod_is_cod_gateway($payment_method, $gateways) {
    $payment_method = sanitize_key((string) $payment_method);
    if ($payment_method === '' || !is_array($gateways)) {
        return false;
    }
    return in_array($payment_method, $gateways, true);
}

/**
 * Classify an inbound reply as a confirm / cancel decision (or neither).
 *
 * Matches the customer's reply text (a quick-reply button title or a typed
 * keyword) against the configured confirm/cancel keyword lists using
 * whole-word, case-insensitive matching. Cancel wins a tie so an ambiguous
 * "no, cancel" never silently confirms an order. Returns 'confirm', 'cancel',
 * or '' when nothing matches.
 *
 * @param string $text            Inbound reply text / button title.
 * @param string $confirm_keywords Comma-separated confirm keywords.
 * @param string $cancel_keywords  Comma-separated cancel keywords.
 * @return string 'confirm' | 'cancel' | ''
 */
function zignites_chat_cod_classify_reply($text, $confirm_keywords, $cancel_keywords) {
    $text = strtolower(trim((string) $text));
    if ($text === '') {
        return '';
    }

    $tokenize = static function ($csv) {
        $out = [];
        foreach (explode(',', strtolower((string) $csv)) as $kw) {
            $kw = trim($kw);
            if ($kw !== '') {
                $out[] = $kw;
            }
        }
        return $out;
    };

    $matches = static function ($needle, $haystack) {
        // Whole-word (or exact) match so "yes" doesn't fire on "yesterday".
        return (bool) preg_match('/(?:^|\b)' . preg_quote($needle, '/') . '(?:\b|$)/u', $haystack);
    };

    foreach ($tokenize($cancel_keywords) as $kw) {
        if ($matches($kw, $text)) {
            return 'cancel';
        }
    }
    foreach ($tokenize($confirm_keywords) as $kw) {
        if ($matches($kw, $text)) {
            return 'confirm';
        }
    }
    return '';
}

/**
 * Whether two phone numbers refer to the same person.
 *
 * Compares normalized (digits-only) numbers, with a suffix fallback so a
 * locally-stored billing number ("03001234567") still matches the full
 * international number WhatsApp delivers ("923001234567"). Pure.
 *
 * @param string $a          First phone.
 * @param string $b          Second phone.
 * @param int    $min_suffix Digits that must match on the suffix path. Default 9.
 * @return bool
 */
function zignites_chat_cod_phone_matches($a, $b, $min_suffix = 9) {
    $a = zignites_chat_normalize_phone($a);
    $b = zignites_chat_normalize_phone($b);
    if ($a === '' || $b === '') {
        return false;
    }
    if ($a === $b) {
        return true;
    }
    $min_suffix = max(1, (int) $min_suffix);
    if (strlen($a) < $min_suffix || strlen($b) < $min_suffix) {
        return false;
    }
    return substr($a, -$min_suffix) === substr($b, -$min_suffix);
}

/* -------------------------------------------------------------------------
 * Inbound reply → order-status transition (C2)
 * ----------------------------------------------------------------------- */

add_action('zignites_chat_inbound_message', 'zignites_chat_cod_handle_inbound_reply');

/**
 * Find the sender's most-recent order awaiting COD confirmation.
 *
 * Pulls recent pending-COD orders and matches by phone in PHP (tolerant of
 * formatting / country-code differences via zignites_chat_cod_phone_matches).
 *
 * @param string $phone Inbound sender phone.
 * @return \WC_Order|null
 */
function zignites_chat_cod_find_pending_order_by_phone($phone) {
    if (!function_exists('wc_get_orders') || zignites_chat_normalize_phone($phone) === '') {
        return null;
    }
    $orders = wc_get_orders([
        'limit'      => (int) apply_filters('zignites_chat_cod_pending_lookup_limit', 50),
        'orderby'    => 'date',
        'order'      => 'DESC',
        'meta_key'   => '_zignites_chat_cod_status', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
        'meta_value' => 'pending',                   // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
        'return'     => 'objects',
    ]);
    if (empty($orders) || !is_array($orders)) {
        return null;
    }
    foreach ($orders as $order) {
        if (is_object($order) && zignites_chat_cod_phone_matches($phone, $order->get_billing_phone())) {
            return $order;
        }
    }
    return null;
}

/**
 * Process a customer's inbound reply to a COD confirmation.
 *
 * Hooked to zignites_chat_inbound_message. Matches the sender to a pending COD
 * order, classifies the reply as confirm/cancel, transitions the order to the
 * configured status, records a note, and (optionally) acknowledges. A reply
 * that matches no keyword leaves the order pending.
 *
 * @param array $msg Normalized inbound message (phone, body, …).
 * @return void
 */
function zignites_chat_cod_handle_inbound_reply($msg) {
    if (!zignites_chat_cod_is_enabled() || !is_array($msg)) {
        return;
    }
    $phone = isset($msg['phone']) ? (string) $msg['phone'] : '';
    $body  = isset($msg['body']) ? (string) $msg['body'] : '';
    if ($phone === '' || $body === '') {
        return;
    }

    $decision = zignites_chat_cod_classify_reply(
        $body,
        get_option('zignites_chat_cod_confirm_keywords', 'confirm,yes,1'),
        get_option('zignites_chat_cod_cancel_keywords', 'cancel,no,2')
    );
    if ($decision === '') {
        return;
    }

    $order = zignites_chat_cod_find_pending_order_by_phone($phone);
    if (!$order) {
        return;
    }

    if ($decision === 'confirm') {
        $status   = sanitize_text_field(get_option('zignites_chat_cod_on_confirm_status', 'processing'));
        $note     = __('Customer confirmed the COD order via WhatsApp.', 'zignites-chat');
        $cod_meta = 'confirmed';
    } else {
        $status   = sanitize_text_field(get_option('zignites_chat_cod_on_cancel_status', 'cancelled'));
        $note     = __('Customer cancelled the COD order via WhatsApp.', 'zignites-chat');
        $cod_meta = 'cancelled';
    }

    $order->update_meta_data('_zignites_chat_cod_status', $cod_meta);
    $order->update_meta_data('_zignites_chat_cod_replied_at', current_time('mysql'));
    // update_status saves the order (including the meta set above).
    if ($status !== '') {
        $order->update_status($status, $note);
    } else {
        $order->add_order_note($note);
        $order->save();
    }

    // Acknowledge in the now-open 24h window (the customer just messaged us, so
    // a free-form reply is allowed). Filterable text; empty disables the ack.
    $ack_default = ($decision === 'confirm')
        ? __('Thanks! Your order is confirmed. 🎉', 'zignites-chat')
        : __('Your order has been cancelled. Let us know if that was a mistake.', 'zignites-chat');
    $ack = (string) apply_filters('zignites_chat_cod_ack_message', $ack_default, $decision, $order);
    if ($ack !== '') {
        zignites_chat_send_whatsapp_message($order->get_billing_phone(), $ack, false, [
            'type'     => 'cod',
            'order_id' => $order->get_id(),
        ]);
    }
}

/* -------------------------------------------------------------------------
 * Send on new COD order
 * ----------------------------------------------------------------------- */

add_action('woocommerce_checkout_order_processed', 'zignites_chat_cod_maybe_send', 20);
add_action('woocommerce_store_api_checkout_order_processed', 'zignites_chat_cod_maybe_send_from_order', 20);

/**
 * Blocks-checkout passes the order object; bridge to the id-based handler.
 *
 * @param \WC_Order $order
 */
function zignites_chat_cod_maybe_send_from_order($order) {
    if (is_object($order) && method_exists($order, 'get_id')) {
        zignites_chat_cod_maybe_send($order->get_id());
    }
}

/**
 * Send the COD confirmation for a freshly-placed COD order.
 *
 * Idempotent: once `_zignites_chat_cod_status` is set the order is never
 * re-messaged. The order is left in whatever status checkout produced; C2
 * transitions it on the customer's reply.
 *
 * @param int $order_id
 * @return void
 */
function zignites_chat_cod_maybe_send($order_id) {
    if (!zignites_chat_cod_is_enabled()) {
        return;
    }
    $order = function_exists('wc_get_order') ? wc_get_order($order_id) : null;
    if (!$order) {
        return;
    }

    // Send once.
    if ($order->get_meta('_zignites_chat_cod_status')) {
        return;
    }
    if (!zignites_chat_cod_is_cod_gateway($order->get_payment_method(), zignites_chat_cod_gateways())) {
        return;
    }

    $to = sanitize_text_field($order->get_billing_phone());
    if (!$to) {
        return;
    }
    if (zignites_chat_is_opted_out($to)) {
        return;
    }

    $name  = $order->get_billing_first_name();
    $total = $order->get_total();
    $values = [
        '{name}'            => $name,
        '{order_id}'        => $order->get_id(),
        '{total}'           => $total,
        '{currency_symbol}' => function_exists('zignites_chat_currency_symbol_text') ? zignites_chat_currency_symbol_text() : '',
    ];

    $template = get_option(
        'zignites_chat_cod_message_template',
        'Hi {name}, please confirm your cash-on-delivery order #{order_id} for {total} {currency_symbol}. Reply CONFIRM to proceed or CANCEL to cancel.'
    );
    $message = str_replace(array_keys($values), array_values($values), $template);

    // Attach the approved cod_confirmation template (with quick-reply buttons)
    // when configured; the rendered $message rides along as preview/fallback.
    $context = zignites_chat_maybe_apply_template('cod_confirmation', $values, [
        'type'     => 'cod',
        'order_id' => $order->get_id(),
    ]);

    $sent = zignites_chat_send_whatsapp_message($to, $message, false, $context);

    // Record pending state regardless of provider outcome so the admin column
    // (C3) can show "awaiting confirmation"; a failed send is visible in logs.
    $order->update_meta_data('_zignites_chat_cod_status', $sent === true ? 'pending' : 'send_failed');
    $order->update_meta_data('_zignites_chat_cod_sent_at', current_time('mysql'));
    $order->save();
}
