<?php
/**
 * GDPR / WP privacy-tools integration.
 *
 * Wires the plugin's PII-bearing data into WordPress' built-in
 * Tools → Export Personal Data and Erase Personal Data flows so EU
 * stores have a one-click answer to a Subject Access Request or
 * Right-to-Erasure request.
 *
 * Data we own and surface:
 *   - zignites_chat_optout_list option — suppression list of phones
 *                                        that opted out.
 *
 * Email → phone resolution: WP privacy keys everything by email, but
 * our option stores phone numbers. zignites_chat_privacy_phones_for_email()
 * pulls billing_phone from the matching WP user (if any) and from every
 * WC order under that email, normalises each via zignites_chat_normalize_phone(),
 * and dedupes — covers both registered customers and guest checkout.
 *
 * Erasure policy: The opt-out list is *retained* with a message — keeping
 * a suppression record is a legitimate-interest exception under Recital 47
 * GDPR; deleting it would re-enable communications, the opposite of what
 * the user originally requested.
 */

if (!defined('ABSPATH')) exit;

add_filter('wp_privacy_personal_data_exporters', 'zignites_chat_privacy_register_exporters', 20);
add_filter('wp_privacy_personal_data_erasers', 'zignites_chat_privacy_register_erasers', 20);
add_action('admin_init', 'zignites_chat_privacy_register_policy_content');

function zignites_chat_privacy_register_exporters($exporters) {
    $exporters['zignites-chat-optout'] = [
        'exporter_friendly_name' => __('Zignites Chat – Opt-out status', 'zignites-chat'),
        'callback'               => 'zignites_chat_privacy_export_optout',
    ];
    return $exporters;
}

function zignites_chat_privacy_register_erasers($erasers) {
    $erasers['zignites-chat-optout'] = [
        'eraser_friendly_name' => __('Zignites Chat – Opt-out status', 'zignites-chat'),
        'callback'             => 'zignites_chat_privacy_erase_optout',
    ];
    return $erasers;
}

function zignites_chat_privacy_register_policy_content() {
    if (!function_exists('wp_add_privacy_policy_content')) return;
    $content  = '<p>' . esc_html__('When Zignites Chat is active, it may send WhatsApp messages to customers about their orders.', 'zignites-chat') . '</p>';
    $content .= '<p><strong>' . esc_html__('What is stored:', 'zignites-chat') . '</strong></p>';
    $content .= '<ul>';
    $content .= '<li>' . esc_html__('Suppression list: phone numbers that opted out of further messages. Retained for compliance even after a personal-data erasure request, to keep the opt-out honoured.', 'zignites-chat') . '</li>';
    $content .= '</ul>';
    $content .= '<p>' . esc_html__('Customers can request export or erasure of this data via Tools → Export Personal Data and Tools → Erase Personal Data, keyed on the email address they used at checkout.', 'zignites-chat') . '</p>';
    wp_add_privacy_policy_content('Zignites Chat', $content);
}

/**
 * Resolve an email address to the set of normalised phone numbers we
 * might have stored for that customer.
 *
 * Looks up:
 *   1. The matching WP user's billing_phone meta (registered customers).
 *   2. billing_phone on every WC order placed under this email (guest
 *      checkouts + customers who used a different phone on a later order).
 *
 * Returns a numerically-indexed list of unique normalised phones — or
 * an empty list if nothing matched.
 *
 * @param string $email
 * @return string[]
 */
function zignites_chat_privacy_phones_for_email($email) {
    $email = is_string($email) ? sanitize_email($email) : '';
    if ($email === '') return [];

    $phones = [];

    $user = get_user_by('email', $email);
    if ($user) {
        $meta_phone = get_user_meta($user->ID, 'billing_phone', true);
        $norm = zignites_chat_normalize_phone($meta_phone);
        if ($norm !== '') $phones[$norm] = true;
    }

    if (function_exists('wc_get_orders')) {
        $orders = wc_get_orders([
            'limit'         => 500,
            'billing_email' => $email,
            'return'        => 'objects',
        ]);
        if (is_array($orders)) {
            foreach ($orders as $order) {
                if (!is_object($order) || !method_exists($order, 'get_billing_phone')) continue;
                $norm = zignites_chat_normalize_phone($order->get_billing_phone());
                if ($norm !== '') $phones[$norm] = true;
            }
        }
    }

    return array_keys($phones);
}

/**
 * Build the trailing-digits fragment for coarse phone matching.
 *
 * @param string $normalized_phone
 * @return string
 */
function zignites_chat_privacy_phone_match_suffix($normalized_phone) {
    $digits = preg_replace('/\D+/', '', (string) $normalized_phone);
    if ($digits === '') return '';
    return strlen($digits) > 8 ? substr($digits, -8) : $digits;
}

/**
 * Filter rows down to those whose `phone` field, once normalised, exactly
 * matches one of the target phones.
 *
 * @param array<int, array>   $rows
 * @param array<string, true> $phone_lookup Normalised phones indexed for O(1) check.
 * @return array<int, array>
 */
function zignites_chat_privacy_filter_rows_by_normalized_phone($rows, $phone_lookup) {
    if (!is_array($rows) || empty($rows) || empty($phone_lookup)) return [];
    $out = [];
    foreach ($rows as $row) {
        if (!isset($row['phone'])) continue;
        $norm = zignites_chat_normalize_phone($row['phone']);
        if ($norm === '') continue;
        if (isset($phone_lookup[$norm])) $out[] = $row;
    }
    return $out;
}

/* -------------------------------------------------------------------------
 * Exporters
 * ----------------------------------------------------------------------- */

function zignites_chat_privacy_export_optout($email_address, $page = 1) {
    $phones = zignites_chat_privacy_phones_for_email($email_address);
    $items = [];
    if (!empty($phones)) {
        $optout = zignites_chat_get_optout_list();
        foreach ($phones as $phone) {
            if (!in_array($phone, $optout, true)) continue;
            $items[] = [
                'group_id'    => 'zignites-chat-optout',
                'group_label' => __('Zignites Chat – Opt-out status', 'zignites-chat'),
                'item_id'     => 'zignites-chat-optout-' . md5($phone),
                'data'        => [
                    ['name' => __('Phone',  'zignites-chat'), 'value' => $phone],
                    ['name' => __('Status', 'zignites-chat'), 'value' => __('Opted out of further messages', 'zignites-chat')],
                ],
            ];
        }
    }
    return ['data' => $items, 'done' => true];
}

/* -------------------------------------------------------------------------
 * Erasers
 * ----------------------------------------------------------------------- */

function zignites_chat_privacy_erase_optout($email_address, $page = 1) {
    $phones = zignites_chat_privacy_phones_for_email($email_address);
    $retained = 0;
    $messages = [];
    if (!empty($phones)) {
        $optout = zignites_chat_get_optout_list();
        foreach ($phones as $phone) {
            if (in_array($phone, $optout, true)) {
                $retained++;
            }
        }
        if ($retained > 0) {
            $messages[] = __('Suppression list entries are retained to keep your prior opt-out request honoured. They will not be used to contact you.', 'zignites-chat');
        }
    }
    return [
        'items_removed'  => 0,
        'items_retained' => $retained,
        'messages'       => $messages,
        'done'           => true,
    ];
}
