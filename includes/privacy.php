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
 *   - {prefix}zignites_chat_analytics_events     — per-message log (phone,
 *                                          message_preview, meta).
 *   - {prefix}zignites_chat_abandoned_carts      — recovery queue (phone, cart
 *                                          contents JSON).
 *   - {prefix}zignites_chat_campaign_recipients  — bulk-campaign recipient list
 *                                          (phone, customer_name).
 *   - zignites_chat_optout_list option            — suppression list of phones
 *                                          that opted out.
 *
 * Email → phone resolution: WP privacy keys everything by email, but
 * our tables store phone numbers. zignites_chat_privacy_phones_for_email() pulls
 * billing_phone from the matching WP user (if any) and from every WC
 * order under that email, normalises each via zignites_chat_normalize_phone(),
 * and dedupes — covers both registered customers and guest checkout.
 *
 * Erasure policy choices:
 *   - Analytics events are *anonymised* (phone, preview, meta cleared)
 *     rather than deleted, so aggregate counts on the dashboard remain
 *     truthful.
 *   - Cart and campaign-recipient rows are deleted outright.
 *   - The opt-out list is *retained* with a message — keeping a
 *     suppression record is a legitimate-interest exception under
 *     Recital 47 GDPR; deleting it would re-enable communications, the
 *     opposite of what the user originally requested.
 */

if (!defined('ABSPATH')) exit;

/*
 * Direct SQL below runs against the plugin's own custom tables. Every
 * user-supplied value is bound through $wpdb->prepare(); the only values
 * interpolated into query strings are table names derived from
 * $wpdb->prefix. This transactional data is not object-cached.
 */
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, PluginCheck.Security.DirectDB.UnescapedDBParameter

add_filter('wp_privacy_personal_data_exporters', 'zignites_chat_privacy_register_exporters', 20);
add_filter('wp_privacy_personal_data_erasers', 'zignites_chat_privacy_register_erasers', 20);
add_action('admin_init', 'zignites_chat_privacy_register_policy_content');

function zignites_chat_privacy_register_exporters($exporters) {
    $exporters['zignites-chat-events'] = [
        'exporter_friendly_name' => __('Zignites Chat – WhatsApp messaging events', 'zignites-chat'),
        'callback'               => 'zignites_chat_privacy_export_events',
    ];
    $exporters['zignites-chat-carts'] = [
        'exporter_friendly_name' => __('Zignites Chat – Abandoned cart records', 'zignites-chat'),
        'callback'               => 'zignites_chat_privacy_export_carts',
    ];
    $exporters['zignites-chat-campaigns'] = [
        'exporter_friendly_name' => __('Zignites Chat – Bulk campaign recipients', 'zignites-chat'),
        'callback'               => 'zignites_chat_privacy_export_campaigns',
    ];
    $exporters['zignites-chat-optout'] = [
        'exporter_friendly_name' => __('Zignites Chat – Opt-out status', 'zignites-chat'),
        'callback'               => 'zignites_chat_privacy_export_optout',
    ];
    return $exporters;
}

function zignites_chat_privacy_register_erasers($erasers) {
    $erasers['zignites-chat-events'] = [
        'eraser_friendly_name' => __('Zignites Chat – WhatsApp messaging events', 'zignites-chat'),
        'callback'             => 'zignites_chat_privacy_erase_events',
    ];
    $erasers['zignites-chat-carts'] = [
        'eraser_friendly_name' => __('Zignites Chat – Abandoned cart records', 'zignites-chat'),
        'callback'             => 'zignites_chat_privacy_erase_carts',
    ];
    $erasers['zignites-chat-campaigns'] = [
        'eraser_friendly_name' => __('Zignites Chat – Bulk campaign recipients', 'zignites-chat'),
        'callback'             => 'zignites_chat_privacy_erase_campaigns',
    ];
    $erasers['zignites-chat-optout'] = [
        'eraser_friendly_name' => __('Zignites Chat – Opt-out status', 'zignites-chat'),
        'callback'             => 'zignites_chat_privacy_erase_optout',
    ];
    return $erasers;
}

function zignites_chat_privacy_register_policy_content() {
    if (!function_exists('wp_add_privacy_policy_content')) return;
    $content  = '<p>' . esc_html__('When Zignites Chat is active, WhatsApp messages sent on your behalf are recorded for analytics, retry, and compliance reasons.', 'zignites-chat') . '</p>';
    $content .= '<p><strong>' . esc_html__('What is stored:', 'zignites-chat') . '</strong></p>';
    $content .= '<ul>';
    $content .= '<li>' . esc_html__('Per-message events: timestamp, message type, status, phone number, order id (if any), provider id, message id, and a redacted preview of the message body.', 'zignites-chat') . '</li>';
    $content .= '<li>' . esc_html__('Abandoned cart records: phone number, cart contents, and timestamps used to schedule recovery messages.', 'zignites-chat') . '</li>';
    $content .= '<li>' . esc_html__('Bulk campaign recipients: phone number and first name, scoped to the campaign that targeted them.', 'zignites-chat') . '</li>';
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
 *      checkouts + customers who used a different phone on a later
 *      order).
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
 * Build the LIKE candidate fragment for matching a normalised phone
 * against a raw, possibly-formatted phone column.
 *
 * Stored phones may have spaces, dashes, parentheses, or country-code
 * prefix variations. We can't reliably exact-match, but the trailing
 * 8 digits of a number are usually unique enough to gate an indexed
 * scan; PHP-side, we then normalise both sides and confirm exact match
 * to filter false positives.
 *
 * @param string $normalized_phone
 * @return string Substring (no wildcards) — caller composes the LIKE.
 */
function zignites_chat_privacy_phone_match_suffix($normalized_phone) {
    $digits = preg_replace('/\D+/', '', (string) $normalized_phone);
    if ($digits === '') return '';
    return strlen($digits) > 8 ? substr($digits, -8) : $digits;
}

/**
 * Format one analytics-event row as a WP privacy export item.
 *
 * Pure: no DB, no globals. Extracted so the field-name labels and
 * masking choices are unit-testable.
 *
 * @param array $row Row from zignites_chat_analytics_events (associative).
 * @return array{group_id:string,group_label:string,item_id:string,data:array}
 */
function zignites_chat_privacy_format_event_row($row) {
    $event_id = isset($row['event_id']) ? (string) $row['event_id'] : (isset($row['id']) ? (string) $row['id'] : '');
    return [
        'group_id'    => 'zignites-chat-events',
        'group_label' => __('Zignites Chat – WhatsApp messaging events', 'zignites-chat'),
        'item_id'     => 'zignites-chat-event-' . $event_id,
        'data'        => [
            ['name' => __('Date',       'zignites-chat'), 'value' => $row['created_at'] ?? ($row['time'] ?? '')],
            ['name' => __('Type',       'zignites-chat'), 'value' => $row['type'] ?? ''],
            ['name' => __('Status',     'zignites-chat'), 'value' => $row['status'] ?? ''],
            ['name' => __('Phone',      'zignites-chat'), 'value' => $row['phone'] ?? ''],
            ['name' => __('Order ID',   'zignites-chat'), 'value' => isset($row['order_id']) ? (string) (int) $row['order_id'] : ''],
            ['name' => __('Provider',   'zignites-chat'), 'value' => $row['provider'] ?? ''],
            ['name' => __('Message ID', 'zignites-chat'), 'value' => $row['message_id'] ?? ''],
            ['name' => __('Preview',    'zignites-chat'), 'value' => $row['message_preview'] ?? ''],
        ],
    ];
}

function zignites_chat_privacy_format_cart_row($row) {
    return [
        'group_id'    => 'zignites-chat-carts',
        'group_label' => __('Zignites Chat – Abandoned cart records', 'zignites-chat'),
        'item_id'     => 'zignites-chat-cart-' . (isset($row['id']) ? (int) $row['id'] : 0),
        'data'        => [
            ['name' => __('Created',  'zignites-chat'), 'value' => $row['created_at'] ?? ''],
            ['name' => __('Phone',    'zignites-chat'), 'value' => $row['phone'] ?? ''],
            ['name' => __('Total',    'zignites-chat'), 'value' => isset($row['total']) ? (string) $row['total'] : ''],
            ['name' => __('Items',    'zignites-chat'), 'value' => $row['cart_json'] ?? ''],
            ['name' => __('Status',   'zignites-chat'), 'value' => $row['status'] ?? ''],
            ['name' => __('Consent',  'zignites-chat'), 'value' => $row['consent'] ?? ''],
            ['name' => __('Attempts', 'zignites-chat'), 'value' => isset($row['attempts']) ? (string) (int) $row['attempts'] : '0'],
        ],
    ];
}

function zignites_chat_privacy_format_campaign_recipient_row($row, $campaign_name = '') {
    return [
        'group_id'    => 'zignites-chat-campaigns',
        'group_label' => __('Zignites Chat – Bulk campaign recipients', 'zignites-chat'),
        'item_id'     => 'zignites-chat-campaign-recipient-' . (isset($row['id']) ? (int) $row['id'] : 0),
        'data'        => [
            ['name' => __('Campaign',      'zignites-chat'), 'value' => $campaign_name !== '' ? $campaign_name : (isset($row['campaign_id']) ? '#' . (int) $row['campaign_id'] : '')],
            ['name' => __('Phone',         'zignites-chat'), 'value' => $row['phone'] ?? ''],
            ['name' => __('Customer name', 'zignites-chat'), 'value' => $row['customer_name'] ?? ''],
            ['name' => __('Status',        'zignites-chat'), 'value' => $row['status'] ?? ''],
            ['name' => __('Sent at',       'zignites-chat'), 'value' => $row['sent_at'] ?? ''],
        ],
    ];
}

/**
 * Filter a candidate set of rows down to those whose `phone` field, once
 * normalised, exactly matches one of the target phones.
 *
 * Pure: takes raw associative rows and the precomputed normalised-phone
 * lookup, returns the matching rows. The caller is responsible for
 * picking up rows from the DB with a coarse LIKE filter — this helper
 * removes the false positives that LIKE produced.
 *
 * @param array<int, array> $rows
 * @param array<string, true> $phone_lookup Normalised phones indexed for O(1) check.
 * @return array<int, array> Rows in original order whose phone matches.
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

function zignites_chat_privacy_export_events($email_address, $page = 1) {
    $phones = zignites_chat_privacy_phones_for_email($email_address);
    $items = [];
    if (!empty($phones)) {
        global $wpdb;
        $table = zignites_chat_get_analytics_table_name();
        $lookup = array_flip($phones);
        $candidates = [];
        foreach ($phones as $phone) {
            $suffix = zignites_chat_privacy_phone_match_suffix($phone);
            if ($suffix === '') continue;
            $rows = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT event_id, type, status, phone, order_id, message_preview, provider, message_id, created_at FROM {$table} WHERE phone LIKE %s LIMIT 500",
                    '%' . $wpdb->esc_like($suffix) . '%'
                ),
                ARRAY_A
            );
            if ($rows) {
                foreach ($rows as $row) $candidates[$row['event_id']] = $row;
            }
        }
        $matched = zignites_chat_privacy_filter_rows_by_normalized_phone(array_values($candidates), $lookup);
        foreach ($matched as $row) {
            $items[] = zignites_chat_privacy_format_event_row($row);
        }
    }
    return ['data' => $items, 'done' => true];
}

function zignites_chat_privacy_export_carts($email_address, $page = 1) {
    $phones = zignites_chat_privacy_phones_for_email($email_address);
    $items = [];
    if (!empty($phones)) {
        global $wpdb;
        $table = zignites_chat_get_cart_table_name();
        $lookup = array_flip($phones);
        $candidates = [];
        foreach ($phones as $phone) {
            $suffix = zignites_chat_privacy_phone_match_suffix($phone);
            if ($suffix === '') continue;
            $rows = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT id, phone, total, cart_json, status, consent, attempts, created_at FROM {$table} WHERE phone LIKE %s LIMIT 500",
                    '%' . $wpdb->esc_like($suffix) . '%'
                ),
                ARRAY_A
            );
            if ($rows) {
                foreach ($rows as $row) $candidates[(int) $row['id']] = $row;
            }
        }
        $matched = zignites_chat_privacy_filter_rows_by_normalized_phone(array_values($candidates), $lookup);
        foreach ($matched as $row) {
            $items[] = zignites_chat_privacy_format_cart_row($row);
        }
    }
    return ['data' => $items, 'done' => true];
}

function zignites_chat_privacy_export_campaigns($email_address, $page = 1) {
    $phones = zignites_chat_privacy_phones_for_email($email_address);
    $items = [];
    if (!empty($phones)) {
        global $wpdb;
        $recipients_table = zignites_chat_campaign_recipients_table_name();
        $campaigns_table  = zignites_chat_campaigns_table_name();
        $lookup = array_flip($phones);
        $candidates = [];
        foreach ($phones as $phone) {
            $suffix = zignites_chat_privacy_phone_match_suffix($phone);
            if ($suffix === '') continue;
            $rows = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT r.id, r.campaign_id, r.phone, r.customer_name, r.status, r.sent_at, c.name AS campaign_name
                     FROM {$recipients_table} r
                     LEFT JOIN {$campaigns_table} c ON c.id = r.campaign_id
                     WHERE r.phone LIKE %s LIMIT 500",
                    '%' . $wpdb->esc_like($suffix) . '%'
                ),
                ARRAY_A
            );
            if ($rows) {
                foreach ($rows as $row) $candidates[(int) $row['id']] = $row;
            }
        }
        $matched = zignites_chat_privacy_filter_rows_by_normalized_phone(array_values($candidates), $lookup);
        foreach ($matched as $row) {
            $campaign_name = isset($row['campaign_name']) ? (string) $row['campaign_name'] : '';
            $items[] = zignites_chat_privacy_format_campaign_recipient_row($row, $campaign_name);
        }
    }
    return ['data' => $items, 'done' => true];
}

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

function zignites_chat_privacy_erase_events($email_address, $page = 1) {
    $phones = zignites_chat_privacy_phones_for_email($email_address);
    $removed = 0;
    $messages = [];
    if (!empty($phones)) {
        global $wpdb;
        $table = zignites_chat_get_analytics_table_name();
        $lookup = array_flip($phones);
        $candidates = [];
        foreach ($phones as $phone) {
            $suffix = zignites_chat_privacy_phone_match_suffix($phone);
            if ($suffix === '') continue;
            $rows = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT id, phone FROM {$table} WHERE phone LIKE %s LIMIT 500",
                    '%' . $wpdb->esc_like($suffix) . '%'
                ),
                ARRAY_A
            );
            if ($rows) {
                foreach ($rows as $row) $candidates[(int) $row['id']] = $row;
            }
        }
        $matched = zignites_chat_privacy_filter_rows_by_normalized_phone(array_values($candidates), $lookup);
        if (!empty($matched)) {
            $ids = array_map('intval', array_column($matched, 'id'));
            $placeholders = implode(',', array_fill(0, count($ids), '%d'));
            // Anonymise rather than delete — preserves aggregate counts on
            // the analytics dashboard (sent/delivered/clicked totals stay
            // truthful) while removing the personally-identifiable fields.
            $wpdb->query(
                $wpdb->prepare(
                    "UPDATE {$table} SET phone='', message_preview='', meta='[]', updated_at=%s WHERE id IN ({$placeholders})",
                    array_merge([current_time('mysql')], $ids)
                )
            );
            $removed = count($ids);
            $messages[] = __('WhatsApp messaging events were anonymised. Aggregate counts are preserved.', 'zignites-chat');
        }
    }
    return [
        'items_removed'  => $removed,
        'items_retained' => 0,
        'messages'       => $messages,
        'done'           => true,
    ];
}

function zignites_chat_privacy_erase_carts($email_address, $page = 1) {
    $phones = zignites_chat_privacy_phones_for_email($email_address);
    $removed = 0;
    if (!empty($phones)) {
        global $wpdb;
        $table = zignites_chat_get_cart_table_name();
        $lookup = array_flip($phones);
        $candidates = [];
        foreach ($phones as $phone) {
            $suffix = zignites_chat_privacy_phone_match_suffix($phone);
            if ($suffix === '') continue;
            $rows = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT id, phone FROM {$table} WHERE phone LIKE %s LIMIT 500",
                    '%' . $wpdb->esc_like($suffix) . '%'
                ),
                ARRAY_A
            );
            if ($rows) {
                foreach ($rows as $row) $candidates[(int) $row['id']] = $row;
            }
        }
        $matched = zignites_chat_privacy_filter_rows_by_normalized_phone(array_values($candidates), $lookup);
        if (!empty($matched)) {
            $ids = array_map('intval', array_column($matched, 'id'));
            $placeholders = implode(',', array_fill(0, count($ids), '%d'));
            $wpdb->query($wpdb->prepare("DELETE FROM {$table} WHERE id IN ({$placeholders})", $ids));
            $removed = count($ids);
        }
    }
    return [
        'items_removed'  => $removed,
        'items_retained' => 0,
        'messages'       => [],
        'done'           => true,
    ];
}

function zignites_chat_privacy_erase_campaigns($email_address, $page = 1) {
    $phones = zignites_chat_privacy_phones_for_email($email_address);
    $removed = 0;
    if (!empty($phones)) {
        global $wpdb;
        $table = zignites_chat_campaign_recipients_table_name();
        $lookup = array_flip($phones);
        $candidates = [];
        foreach ($phones as $phone) {
            $suffix = zignites_chat_privacy_phone_match_suffix($phone);
            if ($suffix === '') continue;
            $rows = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT id, phone FROM {$table} WHERE phone LIKE %s LIMIT 500",
                    '%' . $wpdb->esc_like($suffix) . '%'
                ),
                ARRAY_A
            );
            if ($rows) {
                foreach ($rows as $row) $candidates[(int) $row['id']] = $row;
            }
        }
        $matched = zignites_chat_privacy_filter_rows_by_normalized_phone(array_values($candidates), $lookup);
        if (!empty($matched)) {
            $ids = array_map('intval', array_column($matched, 'id'));
            $placeholders = implode(',', array_fill(0, count($ids), '%d'));
            $wpdb->query($wpdb->prepare("DELETE FROM {$table} WHERE id IN ({$placeholders})", $ids));
            $removed = count($ids);
        }
    }
    return [
        'items_removed'  => $removed,
        'items_retained' => 0,
        'messages'       => [],
        'done'           => true,
    ];
}

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
