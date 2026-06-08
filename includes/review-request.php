<?php
/**
 * Post-delivery review / NPS request (Pro) — roadmap Q2.
 *
 * When an order reaches the configured "delivered" status (default: completed)
 * this schedules a single WhatsApp message a few days later asking the customer
 * to leave a review or rate their experience. It reuses the follow-up
 * scheduler's plumbing: a one-off cron event per order, meta-based dedup, the
 * marketing consent gate, quiet-hours / rate-limiter deferral, and a bounded
 * retry with a sent/failed terminal flag.
 *
 * The message renderer, status normaliser and delay calculator are pure and
 * unit-tested; the rest is thin WooCommerce + cron glue.
 *
 * @package Zignites_Chat
 */

if (!defined('ABSPATH')) exit;

/* -------------------------------------------------------------------------
 * Pure helpers (no WC, no DB) — unit-tested
 * ----------------------------------------------------------------------- */

/**
 * Strip WooCommerce's `wc-` status prefix. Pure.
 *
 * @param string $status
 * @return string
 */
function zignites_chat_review_normalize_status($status) {
    $status = (string) $status;
    return strpos($status, 'wc-') === 0 ? substr($status, 3) : $status;
}

/**
 * Convert a whole-day delay into seconds. Pure.
 *
 * Negative values clamp to 0 (send on the next cron tick); the option
 * sanitizer already rejects non-numeric input.
 *
 * @param int $days
 * @return int
 */
function zignites_chat_review_delay_seconds($days) {
    $days = (int) $days;
    if ($days < 0) {
        $days = 0;
    }
    return $days * DAY_IN_SECONDS;
}

/**
 * Render the review-request template by substituting placeholders. Pure.
 *
 * @param string $template
 * @param array  $values Map of '{placeholder}' => replacement.
 * @return string
 */
function zignites_chat_review_render_message($template, $values) {
    if (!is_array($values)) {
        return (string) $template;
    }
    return str_replace(array_keys($values), array_values($values), (string) $template);
}

/* -------------------------------------------------------------------------
 * Settings access
 * ----------------------------------------------------------------------- */

/**
 * Master toggle (Pro + enabled).
 *
 * @return bool
 */
function zignites_chat_review_is_enabled() {
    if (function_exists('zignites_chat_is_pro_active') && !zignites_chat_is_pro_active()) {
        return false;
    }
    return get_option('zignites_chat_review_request_enabled', 'no') === 'yes';
}

/* -------------------------------------------------------------------------
 * Schedule on the delivered status
 * ----------------------------------------------------------------------- */

add_action('woocommerce_order_status_changed', 'zignites_chat_review_maybe_schedule', 20, 4);
add_action('zignites_chat_send_review_request', 'zignites_chat_review_send_handler');

/**
 * Schedule a review request when an order enters the configured trigger status.
 *
 * @param int            $order_id
 * @param string         $from
 * @param string         $to
 * @param \WC_Order|null $order
 * @return void
 */
function zignites_chat_review_maybe_schedule($order_id, $from, $to, $order = null) {
    if (!zignites_chat_review_is_enabled()) {
        return;
    }

    $trigger = zignites_chat_review_normalize_status(get_option('zignites_chat_review_trigger_status', 'completed'));
    if (zignites_chat_review_normalize_status($to) !== $trigger) {
        return;
    }

    if (!$order) {
        $order = function_exists('wc_get_order') ? wc_get_order($order_id) : null;
    }
    if (!$order) {
        return;
    }

    // One request per order — never re-arm on a later status bounce.
    if ($order->get_meta('_zignites_chat_review_scheduled')) {
        return;
    }

    $days      = (int) get_option('zignites_chat_review_delay_days', 3);
    $timestamp = time() + zignites_chat_review_delay_seconds($days);

    wp_schedule_single_event($timestamp, 'zignites_chat_send_review_request', [$order_id]);
    $order->update_meta_data('_zignites_chat_review_scheduled', $timestamp);
    $order->save();
}

/**
 * Send the review request for an order (cron handler).
 *
 * @param int $order_id
 * @return void
 */
function zignites_chat_review_send_handler($order_id) {
    if (!zignites_chat_review_is_enabled()) {
        return;
    }

    $order = function_exists('wc_get_order') ? wc_get_order($order_id) : null;
    if (!$order) {
        return;
    }

    // Terminal states — never retry once we're here.
    if ($order->get_meta('_zignites_chat_review_sent')) {
        return;
    }
    if ($order->get_meta('_zignites_chat_review_failed')) {
        return;
    }

    $to = sanitize_text_field($order->get_billing_phone());
    if (!$to) {
        return;
    }

    // Permanent skip: opt-outs (and, when consent is required, non-consented
    // numbers) never reach the provider. A review request is marketing, so the
    // consent gate applies — mirrors the follow-up scheduler.
    if (function_exists('zignites_chat_marketing_blocked')) {
        if (zignites_chat_marketing_blocked($to)) {
            return;
        }
    } elseif (zignites_chat_is_opted_out($to)) {
        return;
    }

    // Quiet hours: defer out of the nightly window. Re-scheduled to the
    // window's end; not a failure, so no attempt bump.
    if (function_exists('zignites_chat_quiet_hours_active') && zignites_chat_quiet_hours_active()) {
        $resume = function_exists('zignites_chat_quiet_hours_resume_seconds') ? zignites_chat_quiet_hours_resume_seconds() : 0;
        wp_schedule_single_event(time() + max(60, $resume), 'zignites_chat_send_review_request', [$order_id]);
        return;
    }

    // Central outbound budget: defer (not fail) when the shared per-minute cap
    // is hit so a saturated window can't burn the retry cap.
    if (function_exists('zignites_chat_outbound_rate_acquire') && !zignites_chat_outbound_rate_acquire()) {
        $defer = max(1, (int) apply_filters('zignites_chat_outbound_rate_defer_seconds', MINUTE_IN_SECONDS));
        wp_schedule_single_event(time() + $defer, 'zignites_chat_send_review_request', [$order_id]);
        return;
    }

    $message = zignites_chat_review_build_message($order);
    if (trim($message) === '') {
        return;
    }

    $result = zignites_chat_send_whatsapp_message($to, $message, false, [
        'type'     => 'review_request',
        'order_id' => $order->get_id(),
    ]);

    if ($result === true) {
        $order->update_meta_data('_zignites_chat_review_sent', current_time('mysql'));
        $order->save();
        return;
    }

    // Bounded retry. Backoff is generous (30/120 min) — a review ask is not
    // time-critical, so we'd rather space attempts out than hammer.
    $attempts     = intval($order->get_meta('_zignites_chat_review_attempts')) + 1;
    $max_attempts = 3;
    $backoffs     = [30, 120];

    $order->update_meta_data('_zignites_chat_review_attempts', $attempts);

    if ($attempts >= $max_attempts) {
        $order->update_meta_data('_zignites_chat_review_failed', current_time('mysql'));
        $order->save();
        return;
    }

    $delay_minutes = isset($backoffs[$attempts - 1]) ? $backoffs[$attempts - 1] : 120;
    $order->save();
    wp_schedule_single_event(time() + ($delay_minutes * MINUTE_IN_SECONDS), 'zignites_chat_send_review_request', [$order_id]);
}

/**
 * Build the rendered review-request message for an order.
 *
 * @param \WC_Order $order
 * @return string
 */
function zignites_chat_review_build_message($order) {
    $template = get_option(
        'zignites_chat_review_message',
        __('Hi {name}, thanks for your order #{order_id}! How did we do? We’d love a quick review: {review_url}', 'zignites-chat')
    );

    // First line item's product name, for stores that want a per-product ask.
    $product_name = '';
    if (method_exists($order, 'get_items')) {
        $items = $order->get_items();
        if (is_array($items) && !empty($items)) {
            $first = reset($items);
            if (is_object($first) && method_exists($first, 'get_name')) {
                $product_name = $first->get_name();
            }
        }
    }

    return zignites_chat_review_render_message($template, [
        '{name}'       => $order->get_billing_first_name(),
        '{order_id}'   => $order->get_id(),
        '{product}'    => $product_name,
        '{review_url}' => esc_url_raw(get_option('zignites_chat_review_url', '')),
        '{site}'       => function_exists('get_bloginfo') ? get_bloginfo('name') : '',
    ]);
}
