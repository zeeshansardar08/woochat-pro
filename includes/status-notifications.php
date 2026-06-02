<?php
/**
 * Order-status + shipping/tracking notifications (Pro) — roadmap T1.2.
 *
 * The classic order confirmation only fires on processing/completed. This
 * module lets the store send a WhatsApp message on *any* order status change
 * the admin opts into (shipped, out-for-delivery, on-hold, refunded,
 * cancelled, …), each with its own template, and injects tracking details
 * pulled from the common shipment-tracking plugins.
 *
 * Defaults are all-off so it never overlaps the classic confirmation until the
 * admin enables a status. The placeholder renderer and tracking extractor are
 * pure and unit-tested; the rest is thin WooCommerce glue.
 *
 * @package Zignites_Chat
 */

if (!defined('ABSPATH')) exit;

/* -------------------------------------------------------------------------
 * Settings access
 * ----------------------------------------------------------------------- */

/**
 * Master toggle (Pro + enabled).
 *
 * @return bool
 */
function zignites_chat_status_notify_is_enabled() {
    if (function_exists('zignites_chat_is_pro_active') && !zignites_chat_is_pro_active()) {
        return false;
    }
    return get_option('zignites_chat_status_notify_enabled', 'no') === 'yes';
}

/**
 * Per-status config: ['<slug>' => ['enabled' => 'yes'|'no', 'template' => string]].
 *
 * @return array<string, array{enabled:string, template:string}>
 */
function zignites_chat_status_get_config() {
    $stored = get_option('zignites_chat_status_notifications', []);
    return is_array($stored) ? $stored : [];
}

/**
 * Order statuses the UI offers / sends for — every registered WC status minus
 * internal ones that never warrant a customer message.
 *
 * @return array<string, string> slug => label
 */
function zignites_chat_status_eligible_statuses() {
    $statuses = function_exists('wc_get_order_statuses') ? wc_get_order_statuses() : [];
    $out = [];
    foreach ($statuses as $key => $label) {
        $slug = zignites_chat_status_normalize($key);
        if (in_array($slug, ['checkout-draft', 'trash', 'pending'], true)) {
            continue;
        }
        $out[$slug] = $label;
    }
    return $out;
}

/* -------------------------------------------------------------------------
 * Pure helpers (no WC, no DB) — unit-tested
 * ----------------------------------------------------------------------- */

/**
 * Strip WooCommerce's `wc-` status prefix. Pure.
 *
 * @param string $status
 * @return string
 */
function zignites_chat_status_normalize($status) {
    $status = (string) $status;
    return strpos($status, 'wc-') === 0 ? substr($status, 3) : $status;
}

/**
 * Whether a notification is enabled for a given status. Pure.
 *
 * @param string $status Normalized status slug.
 * @param array  $config Per-status config (see zignites_chat_status_get_config).
 * @return bool
 */
function zignites_chat_status_should_notify($status, $config) {
    $status = zignites_chat_status_normalize($status);
    if (!is_array($config) || !isset($config[$status]) || !is_array($config[$status])) {
        return false;
    }
    $entry = $config[$status];
    return (isset($entry['enabled']) && $entry['enabled'] === 'yes')
        && isset($entry['template']) && trim((string) $entry['template']) !== '';
}

/**
 * Extract tracking details from shipment-tracking order meta items. Pure.
 *
 * Reads the `_wc_shipment_tracking_items` shape used by WooCommerce Shipment
 * Tracking and Advanced Shipment Tracking. Returns the most-recent item as
 * ['number' => , 'url' => , 'carrier' => ] (empty strings when absent).
 *
 * @param mixed $items Decoded meta value (array of items) or anything.
 * @return array{number:string, url:string, carrier:string}
 */
function zignites_chat_extract_tracking($items) {
    $empty = ['number' => '', 'url' => '', 'carrier' => ''];
    if (!is_array($items) || empty($items)) {
        return $empty;
    }
    // Most-recent item is typically last; prefer the last array entry.
    $item = end($items);
    if (!is_array($item)) {
        return $empty;
    }
    $number  = isset($item['tracking_number']) ? (string) $item['tracking_number'] : '';
    $carrier = '';
    foreach (['formatted_tracking_provider', 'tracking_provider', 'custom_tracking_provider'] as $k) {
        if (!empty($item[$k])) {
            $carrier = (string) $item[$k];
            break;
        }
    }
    $url = '';
    foreach (['formatted_tracking_link', 'custom_tracking_link', 'tracking_link'] as $k) {
        if (!empty($item[$k])) {
            $url = (string) $item[$k];
            break;
        }
    }
    return [
        'number'  => trim($number),
        'url'     => trim($url),
        'carrier' => trim(wp_strip_all_tags($carrier)),
    ];
}

/**
 * Render a status-notification template by substituting placeholders. Pure.
 *
 * @param string $template
 * @param array  $values Map of '{placeholder}' => replacement.
 * @return string
 */
function zignites_chat_status_render($template, $values) {
    if (!is_array($values)) {
        return (string) $template;
    }
    return str_replace(array_keys($values), array_values($values), (string) $template);
}

/* -------------------------------------------------------------------------
 * Send on status change
 * ----------------------------------------------------------------------- */

add_action('woocommerce_order_status_changed', 'zignites_chat_status_maybe_notify', 20, 4);

/**
 * Read tracking details off an order via the shipment-tracking meta.
 *
 * @param \WC_Order $order
 * @return array{number:string, url:string, carrier:string}
 */
function zignites_chat_status_get_order_tracking($order) {
    $items = $order->get_meta('_wc_shipment_tracking_items');
    if (is_string($items) && $items !== '') {
        $decoded = json_decode($items, true);
        $items = is_array($decoded) ? $decoded : [];
    }
    $tracking = zignites_chat_extract_tracking($items);
    /**
     * Filter the tracking details for an order, for stores using a shipment
     * plugin that stores tracking elsewhere.
     */
    return apply_filters('zignites_chat_order_tracking', $tracking, $order);
}

/**
 * Send a WhatsApp notification when an order moves into an opted-in status.
 *
 * @param int       $order_id
 * @param string    $from
 * @param string    $to
 * @param \WC_Order $order
 * @return void
 */
function zignites_chat_status_maybe_notify($order_id, $from, $to, $order = null) {
    if (!zignites_chat_status_notify_is_enabled()) {
        return;
    }
    $config = zignites_chat_status_get_config();
    if (!zignites_chat_status_should_notify($to, $config)) {
        return;
    }
    if (!$order) {
        $order = function_exists('wc_get_order') ? wc_get_order($order_id) : null;
    }
    if (!$order) {
        return;
    }

    $phone = sanitize_text_field($order->get_billing_phone());
    if (!$phone || zignites_chat_is_opted_out($phone)) {
        return;
    }

    $to_slug  = zignites_chat_status_normalize($to);
    $template = (string) $config[$to_slug]['template'];
    $tracking = zignites_chat_status_get_order_tracking($order);

    $message = zignites_chat_status_render($template, [
        '{name}'            => $order->get_billing_first_name(),
        '{order_id}'        => $order->get_id(),
        '{total}'           => $order->get_total(),
        '{status}'          => wc_get_order_status_name($to_slug),
        '{currency_symbol}' => function_exists('zignites_chat_currency_symbol_text') ? zignites_chat_currency_symbol_text() : '',
        '{tracking_number}' => $tracking['number'],
        '{tracking_url}'    => $tracking['url'],
        '{carrier}'         => $tracking['carrier'],
    ]);
    if (trim($message) === '') {
        return;
    }

    zignites_chat_send_whatsapp_message($phone, $message, false, [
        'type'     => 'status_' . $to_slug,
        'order_id' => $order->get_id(),
    ]);
}

/* -------------------------------------------------------------------------
 * Settings sanitizer
 * ----------------------------------------------------------------------- */

/**
 * Sanitize the per-status notifications option.
 *
 * @param mixed $raw Submitted array keyed by status slug.
 * @return array<string, array{enabled:string, template:string}>
 */
function zignites_chat_status_sanitize_notifications($raw) {
    if (!is_array($raw)) {
        return [];
    }
    $clean = [];
    foreach ($raw as $slug => $entry) {
        $slug = sanitize_key((string) $slug);
        if ($slug === '' || !is_array($entry)) {
            continue;
        }
        $enabled  = (isset($entry['enabled']) && $entry['enabled'] === 'yes') ? 'yes' : 'no';
        $template = isset($entry['template']) ? zignites_chat_sanitize_textarea($entry['template']) : '';
        $clean[$slug] = ['enabled' => $enabled, 'template' => $template];
    }
    return $clean;
}
