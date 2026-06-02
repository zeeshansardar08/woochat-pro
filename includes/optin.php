<?php
/**
 * WhatsApp opt-in capture & consent log (Pro) — roadmap T1.3.
 *
 * Complements the opt-out suppression list with proactive, explicit consent:
 * a checkout checkbox records who agreed to receive WhatsApp messages, when,
 * and from where. An optional "require consent for marketing" mode then gates
 * the bulk channels (cart recovery, campaigns, follow-ups) to consented
 * numbers only — transactional sends (order/COD/status) are never gated.
 *
 * The log-mutation and gating decisions are pure and unit-tested; the rest is
 * thin option/WooCommerce glue.
 *
 * @package Zignites_Chat
 */

if (!defined('ABSPATH')) exit;

/* -------------------------------------------------------------------------
 * Pure helpers (no DB) — unit-tested
 * ----------------------------------------------------------------------- */

/**
 * Add/refresh a consent entry in the log. Pure.
 *
 * @param array  $log    Existing log keyed by normalized phone.
 * @param string $phone  Normalized phone (digits only).
 * @param string $time   MySQL datetime of consent.
 * @param string $source Where consent was captured (e.g. 'checkout').
 * @return array Updated log.
 */
function zignites_chat_optin_log_add($log, $phone, $time, $source) {
    if (!is_array($log)) {
        $log = [];
    }
    $phone = (string) $phone;
    if ($phone === '') {
        return $log;
    }
    $log[$phone] = [
        'time'   => (string) $time,
        'source' => (string) $source,
    ];
    return $log;
}

/**
 * Decide whether a marketing send to a phone is blocked. Pure.
 *
 * Blocked when the number opted out, or when consent is required and the
 * number has not opted in. Transactional callers do not use this.
 *
 * @param bool $opted_out   Number is on the suppression list.
 * @param bool $required    "Require consent for marketing" is on.
 * @param bool $has_consent Number is in the opt-in log.
 * @return bool
 */
function zignites_chat_optin_decide_blocked($opted_out, $required, $has_consent) {
    if ($opted_out) {
        return true;
    }
    return $required && !$has_consent;
}

/* -------------------------------------------------------------------------
 * Consent store
 * ----------------------------------------------------------------------- */

/**
 * Read the consent log: [ normalized_phone => ['time'=>…, 'source'=>…] ].
 *
 * @return array<string, array{time:string, source:string}>
 */
function zignites_chat_get_optin_log() {
    $log = get_option('zignites_chat_optin_log', []);
    return is_array($log) ? $log : [];
}

/**
 * Whether a phone has given WhatsApp consent.
 *
 * @param string $phone Phone in any format.
 * @return bool
 */
function zignites_chat_has_consent($phone) {
    $phone = zignites_chat_normalize_phone($phone);
    if ($phone === '') {
        return false;
    }
    $log = zignites_chat_get_optin_log();
    return isset($log[$phone]);
}

/**
 * Record explicit WhatsApp consent for a phone.
 *
 * Removes the number from the opt-out suppression list (an explicit opt-in
 * supersedes a prior opt-out; filterable) and fires the customer.opted_in
 * webhook.
 *
 * @param string $phone  Phone in any format.
 * @param string $source Capture source (e.g. 'checkout').
 * @return bool False when the phone is empty, true otherwise.
 */
function zignites_chat_record_optin($phone, $source = 'checkout') {
    $phone = zignites_chat_normalize_phone($phone);
    if ($phone === '') {
        return false;
    }

    $log = zignites_chat_optin_log_add(zignites_chat_get_optin_log(), $phone, current_time('mysql'), sanitize_text_field($source));
    update_option('zignites_chat_optin_log', $log, false);

    /** @var bool $clear Whether an explicit opt-in clears a prior opt-out. */
    if (apply_filters('zignites_chat_optin_clears_optout', true, $phone)) {
        $optout = zignites_chat_get_optout_list();
        $idx = array_search($phone, $optout, true);
        if ($idx !== false) {
            unset($optout[$idx]);
            update_option('zignites_chat_optout_list', array_values($optout), false);
        }
    }

    if (function_exists('zignites_chat_dispatch_webhook')) {
        zignites_chat_dispatch_webhook('customer.opted_in', ['phone' => $phone, 'source' => $source]);
    }
    return true;
}

/**
 * Whether a marketing send to a phone is blocked (opted out, or consent
 * required and missing). Transactional sends must not call this.
 *
 * @param string $phone Phone in any format.
 * @return bool
 */
function zignites_chat_marketing_blocked($phone) {
    return zignites_chat_optin_decide_blocked(
        zignites_chat_is_opted_out($phone),
        get_option('zignites_chat_optin_required', 'no') === 'yes',
        zignites_chat_has_consent($phone)
    );
}

/* -------------------------------------------------------------------------
 * Checkout capture (classic checkout)
 * ----------------------------------------------------------------------- */

add_action('woocommerce_review_order_before_submit', 'zignites_chat_optin_checkout_field');
add_action('woocommerce_checkout_order_processed', 'zignites_chat_optin_capture_checkout', 20, 1);

/**
 * Render the opt-in checkbox on the classic checkout.
 */
function zignites_chat_optin_checkout_field() {
    if (get_option('zignites_chat_optin_enabled', 'no') !== 'yes') {
        return;
    }
    if (function_exists('zignites_chat_is_pro_active') && !zignites_chat_is_pro_active()) {
        return;
    }
    $label   = get_option('zignites_chat_optin_label', __('Yes, send me order updates and offers on WhatsApp.', 'zignites-chat'));
    $checked = get_option('zignites_chat_optin_default_checked', 'no') === 'yes';
    echo '<p class="form-row zignites-chat-optin-row">';
    echo '<label style="display:flex; gap:8px; align-items:flex-start;">';
    echo '<input type="checkbox" name="zignites_chat_optin" value="yes" ' . checked($checked, true, false) . ' />';
    echo '<span>' . esc_html($label) . '</span>';
    echo '</label>';
    echo '</p>';
}

/**
 * Persist the opt-in choice when a classic-checkout order is placed.
 *
 * @param int $order_id
 * @return void
 */
function zignites_chat_optin_capture_checkout($order_id) {
    if (get_option('zignites_chat_optin_enabled', 'no') !== 'yes') {
        return;
    }
    // WooCommerce verifies the checkout nonce before this hook runs.
    // phpcs:ignore WordPress.Security.NonceVerification.Missing
    $opted_in = isset($_POST['zignites_chat_optin']) && sanitize_text_field(wp_unslash($_POST['zignites_chat_optin'])) === 'yes';
    if (!$opted_in) {
        return;
    }
    $order = function_exists('wc_get_order') ? wc_get_order($order_id) : null;
    if (!$order) {
        return;
    }
    $order->update_meta_data('_zignites_chat_optin', 'yes');
    $order->save();
    zignites_chat_record_optin($order->get_billing_phone(), 'checkout');
}
