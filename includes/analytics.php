<?php
if (!defined('ABSPATH')) exit;

// Click-tracking redirect handler — only attach when the parameter is
// actually present, and only on frontend requests. Tracking URLs are
// always built against home_url('/'), so template_redirect is the right
// hook (admin / AJAX / REST / cron paths never need this callback).
if (!empty($_GET['wcwp_track'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- presence-only switch; nonce-free public click tracker, validated inside the handler.
    add_action('template_redirect', 'wcwp_handle_tracking_request', 1);
}
add_action('wp_ajax_wcwp_track_event', 'wcwp_track_event_ajax');
add_action('wp_ajax_nopriv_wcwp_track_event', 'wcwp_track_event_ajax');

add_action('admin_post_wcwp_analytics_export_csv', 'wcwp_analytics_export_csv');

add_action('wcwp_cleanup_analytics', 'wcwp_cleanup_analytics');

function wcwp_get_analytics_table_name() {
    global $wpdb;
    return $wpdb->prefix . 'wcwp_analytics_events';
}

function wcwp_create_analytics_table() {
    global $wpdb;
    $table = wcwp_get_analytics_table_name();
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE {$table} (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        event_id VARCHAR(64) NOT NULL,
        type VARCHAR(32) NOT NULL,
        status VARCHAR(32) NOT NULL,
        phone VARCHAR(32) NULL,
        order_id BIGINT UNSIGNED NULL,
        message_preview TEXT NULL,
        provider VARCHAR(32) NULL,
        message_id VARCHAR(64) NULL,
        meta LONGTEXT NULL,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY event_id (event_id),
        KEY type_status (type, status),
        KEY created_at (created_at),
        KEY phone (phone)
    ) {$charset_collate};";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);

    if (!wp_next_scheduled('wcwp_cleanup_analytics')) {
        wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', 'wcwp_cleanup_analytics');
    }
}

function wcwp_cleanup_analytics() {
    $days = absint(get_option('wcwp_data_retention_days', 0));
    if ($days < 1) return;

    global $wpdb;
    $table = wcwp_get_analytics_table_name();
    $cutoff = date('Y-m-d H:i:s', time() - ($days * DAY_IN_SECONDS));
    $wpdb->query($wpdb->prepare("DELETE FROM {$table} WHERE created_at < %s", $cutoff));
}

function wcwp_analytics_log_event($type, $data = []) {
    $id = uniqid('wcwp_evt_', true);

    $event = [
        'id' => $id,
        'type' => sanitize_text_field($type),
        'time' => current_time('mysql'),
        'status' => sanitize_text_field($data['status'] ?? 'pending'),
        'phone' => sanitize_text_field($data['phone'] ?? ''),
        'order_id' => isset($data['order_id']) ? intval($data['order_id']) : 0,
        'message_preview' => isset($data['message_preview']) ? wp_trim_words(wp_kses_post($data['message_preview']), 40, '...') : '',
        'provider' => sanitize_text_field($data['provider'] ?? ''),
        'message_id' => sanitize_text_field($data['message_id'] ?? ''),
        'meta' => isset($data['meta']) && is_array($data['meta']) ? $data['meta'] : [],
    ];

    wcwp_analytics_insert_event($event);
    return $id;
}

function wcwp_analytics_update_event($event_id, $fields = []) {
    global $wpdb;
    $table = wcwp_get_analytics_table_name();

    $data = [];
    $format = [];
    foreach ($fields as $key => $val) {
        if ($key === 'meta' && is_array($val)) {
            $data['meta'] = wp_json_encode($val);
            $format[] = '%s';
            continue;
        }
        $data[$key] = $val;
        $format[] = '%s';
    }
    $data['updated_at'] = current_time('mysql');
    $format[] = '%s';

    if (!empty($data)) {
        $wpdb->update($table, $data, ['event_id' => $event_id], $format, ['%s']);
    }
}

/**
 * @deprecated 1.0.2 Totals are derived from the analytics events table; this
 * is now a no-op kept only for backward compatibility with any external
 * code that may still call it. See wcwp_analytics_get_totals().
 */
function wcwp_analytics_increment_total($bucket, $amount = 1) {
    // No-op.
}

function wcwp_analytics_get_totals() {
    global $wpdb;
    $table = wcwp_get_analytics_table_name();
    $rows = $wpdb->get_results("SELECT status, COUNT(*) AS total FROM {$table} GROUP BY status");
    $totals = [ 'sent' => 0, 'delivered' => 0, 'clicked' => 0 ];
    if ($rows) {
        foreach ($rows as $row) {
            if (isset($totals[$row->status])) {
                $totals[$row->status] = intval($row->total);
            }
        }
    }
    return $totals;
}

function wcwp_analytics_get_events($limit = 50, $filters = []) {
    global $wpdb;
    $table = wcwp_get_analytics_table_name();

    $where = [];
    $params = [];

    if (!empty($filters['type'])) {
        $where[] = 'type = %s';
        $params[] = $filters['type'];
    }
    if (!empty($filters['status'])) {
        $where[] = 'status = %s';
        $params[] = $filters['status'];
    }
    if (!empty($filters['phone'])) {
        $where[] = 'phone LIKE %s';
        $params[] = '%' . $wpdb->esc_like($filters['phone']) . '%';
    }
    if (!empty($filters['date_from'])) {
        $where[] = 'created_at >= %s';
        $params[] = $filters['date_from'] . ' 00:00:00';
    }
    if (!empty($filters['date_to'])) {
        $where[] = 'created_at <= %s';
        $params[] = $filters['date_to'] . ' 23:59:59';
    }

    $sql = "SELECT event_id as id, type, status, phone, order_id, message_preview, provider, message_id, meta, created_at as time FROM {$table}";
    if (!empty($where)) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $sql .= ' ORDER BY created_at DESC LIMIT %d';
    $params[] = absint($limit);

    $prepared = $wpdb->prepare($sql, $params);
    $rows = $wpdb->get_results($prepared, ARRAY_A);
    if (!$rows) return [];

    foreach ($rows as &$row) {
        if (!empty($row['meta'])) {
            $decoded = json_decode($row['meta'], true);
            $row['meta'] = is_array($decoded) ? $decoded : [];
        }
    }
    return $rows;
}

/**
 * Per-type (template) performance breakdown for the given filter window.
 *
 * Aggregates event rows grouped by the `type` column — which acts as a
 * coarse template/source dimension (order, cart_recovery, followup, bulk,
 * chatbot_gpt). Returned counts are raw per-status totals matching the
 * "latest state" semantics of `wcwp_analytics_get_totals()`; `total` sums
 * the four reportable terminal states (sent + delivered + clicked +
 * failed) and excludes operational states like pending / test / opted_out.
 *
 * @param array $filters Same shape as wcwp_analytics_get_events filters.
 * @return array<int, array{type:string,sent:int,delivered:int,clicked:int,failed:int,total:int}>
 */
function wcwp_analytics_get_per_type_breakdown($filters = []) {
    global $wpdb;
    $table = wcwp_get_analytics_table_name();

    $where = [];
    $params = [];

    if (!empty($filters['type'])) {
        $where[] = 'type = %s';
        $params[] = $filters['type'];
    }
    if (!empty($filters['status'])) {
        $where[] = 'status = %s';
        $params[] = $filters['status'];
    }
    if (!empty($filters['phone'])) {
        $where[] = 'phone LIKE %s';
        $params[] = '%' . $wpdb->esc_like($filters['phone']) . '%';
    }
    if (!empty($filters['date_from'])) {
        $where[] = 'created_at >= %s';
        $params[] = $filters['date_from'] . ' 00:00:00';
    }
    if (!empty($filters['date_to'])) {
        $where[] = 'created_at <= %s';
        $params[] = $filters['date_to'] . ' 23:59:59';
    }

    $sql = "SELECT type, status, COUNT(*) AS total FROM {$table}";
    if (!empty($where)) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $sql .= ' GROUP BY type, status';

    $rows = !empty($params)
        ? $wpdb->get_results($wpdb->prepare($sql, $params), ARRAY_A)
        : $wpdb->get_results($sql, ARRAY_A);

    if (!$rows) return [];

    $reportable = ['sent', 'delivered', 'clicked', 'failed'];
    $out = [];
    foreach ($rows as $row) {
        $type = (string) $row['type'];
        $status = (string) $row['status'];
        if (!isset($out[$type])) {
            $out[$type] = [
                'type' => $type,
                'sent' => 0,
                'delivered' => 0,
                'clicked' => 0,
                'failed' => 0,
                'total' => 0,
            ];
        }
        if (in_array($status, $reportable, true)) {
            $count = (int) $row['total'];
            $out[$type][$status] = $count;
            $out[$type]['total'] += $count;
        }
    }
    ksort($out);
    return array_values($out);
}

/**
 * Pure conversion-attribution matcher.
 *
 * Given a set of analytics events and a set of WooCommerce orders, return
 * how many events map to a subsequent order placed by the same normalized
 * phone number, within `$window_seconds` of the event firing.
 *
 * Rules:
 * - First-event-wins: events are processed in chronological order, each
 *   matched event consumes one order so a single order is never double-
 *   counted across two events to the same phone.
 * - The matched order must be created at or after the event time.
 * - Orders older than the event are ignored (no false positives from
 *   pre-existing customer history).
 *
 * Extracted from `wcwp_analytics_get_conversions()` so it can be unit-
 * tested without a database.
 *
 * @param array $events Each: ['event_id' => string, 'phone_norm' => string, 'time' => int unix].
 * @param array $orders Each: ['order_id' => int, 'phone_norm' => string, 'time' => int unix, 'total' => float].
 * @param int   $window_seconds Maximum elapsed time between event and order.
 * @return array{conversions:int,revenue:float,matched:array<int,string>}
 */
function wcwp_analytics_match_conversions($events, $orders, $window_seconds) {
    $empty = ['conversions' => 0, 'revenue' => 0.0, 'matched' => []];
    if (!is_array($events) || !is_array($orders) || (int) $window_seconds <= 0) {
        return $empty;
    }

    $orders_by_phone = [];
    foreach ($orders as $o) {
        if (empty($o['phone_norm'])) continue;
        $orders_by_phone[$o['phone_norm']][] = $o;
    }
    foreach ($orders_by_phone as &$list) {
        usort($list, static function ($a, $b) {
            return (int) $a['time'] <=> (int) $b['time'];
        });
    }
    unset($list);

    usort($events, static function ($a, $b) {
        return (int) $a['time'] <=> (int) $b['time'];
    });

    $consumed = [];
    $conversions = 0;
    $revenue = 0.0;
    $matched = [];

    foreach ($events as $e) {
        $phone = $e['phone_norm'] ?? '';
        if ($phone === '' || empty($orders_by_phone[$phone])) continue;
        foreach ($orders_by_phone[$phone] as $o) {
            if (!empty($consumed[$o['order_id']])) continue;
            if ((int) $o['time'] < (int) $e['time']) continue;
            if (((int) $o['time'] - (int) $e['time']) > (int) $window_seconds) break;
            $consumed[$o['order_id']] = true;
            $conversions++;
            $revenue += (float) $o['total'];
            $matched[(int) $o['order_id']] = (string) ($e['event_id'] ?? '');
            break;
        }
    }

    return [
        'conversions' => $conversions,
        'revenue' => $revenue,
        'matched' => $matched,
    ];
}

/**
 * Conversion attribution summary for the given filter window.
 *
 * Pulls eligible events (status sent/delivered/clicked, phone present)
 * within the filter window and joins them against WooCommerce orders by
 * normalized phone within an attribution window (default 7 days,
 * filterable). Best-effort — caps both event and order fetch at 5000.
 *
 * @param array $filters Same shape as wcwp_analytics_get_events filters.
 * @return array{conversions:int,revenue:float,eligible_events:int,window_days:int}
 */
function wcwp_analytics_get_conversions($filters = []) {
    $window_days = (int) apply_filters('wcwp_analytics_attribution_window_days', 7);
    if ($window_days < 1) $window_days = 7;
    $window_seconds = $window_days * DAY_IN_SECONDS;

    $base = ['conversions' => 0, 'revenue' => 0.0, 'eligible_events' => 0, 'window_days' => $window_days];
    if (!function_exists('wc_get_orders')) {
        return $base;
    }

    // Drop status filter — we always look at sent/delivered/clicked.
    $event_filters = $filters;
    unset($event_filters['status']);
    $events = wcwp_analytics_get_events(5000, $event_filters);

    $eligible = [];
    $earliest = PHP_INT_MAX;
    $latest = 0;
    foreach ($events as $e) {
        if (empty($e['phone'])) continue;
        if (!in_array($e['status'] ?? '', ['sent', 'delivered', 'clicked'], true)) continue;
        $time = isset($e['time']) ? strtotime((string) $e['time']) : 0;
        if (!$time) continue;
        $eligible[] = [
            'event_id' => (string) ($e['id'] ?? ''),
            'phone_norm' => wcwp_normalize_phone($e['phone']),
            'time' => $time,
        ];
        if ($time < $earliest) $earliest = $time;
        if ($time > $latest) $latest = $time;
    }

    if (empty($eligible)) {
        return $base;
    }

    $orders = wc_get_orders([
        'limit' => 5000,
        'date_created' => gmdate('Y-m-d\TH:i:s', $earliest) . '...' . gmdate('Y-m-d\TH:i:s', $latest + $window_seconds),
        'status' => ['wc-processing', 'wc-on-hold', 'wc-completed'],
        'orderby' => 'date',
        'order' => 'ASC',
    ]);

    $order_records = [];
    if (is_array($orders)) {
        foreach ($orders as $order) {
            if (!is_object($order) || !method_exists($order, 'get_billing_phone')) continue;
            $phone_norm = wcwp_normalize_phone($order->get_billing_phone());
            if ($phone_norm === '') continue;
            $created = $order->get_date_created();
            if (!$created) continue;
            $order_records[] = [
                'order_id' => (int) $order->get_id(),
                'phone_norm' => $phone_norm,
                'time' => (int) $created->getTimestamp(),
                'total' => (float) $order->get_total(),
            ];
        }
    }

    $result = wcwp_analytics_match_conversions($eligible, $order_records, $window_seconds);
    return [
        'conversions' => $result['conversions'],
        'revenue' => $result['revenue'],
        'eligible_events' => count($eligible),
        'window_days' => $window_days,
    ];
}

function wcwp_analytics_export_csv() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Unauthorized', 'woochat-pro'), '', ['response' => 403]);
    }
    check_admin_referer('wcwp_analytics_export', 'wcwp_analytics_export_nonce');

    $filters = [
        'type'      => isset($_GET['wcwp_type']) ? sanitize_text_field(wp_unslash($_GET['wcwp_type'])) : '',
        'status'    => isset($_GET['wcwp_status']) ? sanitize_text_field(wp_unslash($_GET['wcwp_status'])) : '',
        'phone'     => isset($_GET['wcwp_phone']) ? sanitize_text_field(wp_unslash($_GET['wcwp_phone'])) : '',
        'date_from' => isset($_GET['wcwp_date_from']) ? sanitize_text_field(wp_unslash($_GET['wcwp_date_from'])) : '',
        'date_to'   => isset($_GET['wcwp_date_to']) ? sanitize_text_field(wp_unslash($_GET['wcwp_date_to'])) : '',
    ];

    $limit = (int) apply_filters('wcwp_analytics_export_limit', 5000);
    if ($limit < 1) $limit = 5000;

    $events = wcwp_analytics_get_events($limit, $filters);

    $filename = 'woochat-analytics-' . gmdate('Ymd-His') . '.csv';
    nocache_headers();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $out = fopen('php://output', 'w');
    fputcsv($out, ['time', 'type', 'status', 'phone', 'order_id', 'provider', 'message_id', 'preview']);
    foreach ($events as $evt) {
        fputcsv($out, [
            $evt['time'] ?? '',
            $evt['type'] ?? '',
            $evt['status'] ?? '',
            $evt['phone'] ?? '',
            $evt['order_id'] ?? '',
            $evt['provider'] ?? '',
            $evt['message_id'] ?? '',
            $evt['message_preview'] ?? '',
        ]);
    }
    fclose($out);
    exit;
}

function wcwp_analytics_insert_event($event) {
    global $wpdb;
    $table = wcwp_get_analytics_table_name();
    $wpdb->insert(
        $table,
        [
            'event_id' => $event['id'],
            'type' => $event['type'],
            'status' => $event['status'],
            'phone' => $event['phone'],
            'order_id' => $event['order_id'],
            'message_preview' => $event['message_preview'],
            'provider' => $event['provider'],
            'message_id' => $event['message_id'],
            'meta' => wp_json_encode($event['meta']),
            'created_at' => $event['time'],
            'updated_at' => $event['time'],
        ],
        ['%s','%s','%s','%s','%d','%s','%s','%s','%s','%s','%s']
    );
}

function wcwp_analytics_tracking_url($event_id, $redirect_url) {
    $redirect = $redirect_url ? esc_url_raw($redirect_url) : home_url('/');
    return add_query_arg([
        'wcwp_track' => 'click',
        'event_id' => $event_id,
        'redirect' => $redirect,
    ], home_url('/'));
}

/**
 * Validate a click-tracking redirect target against this site's hosts.
 *
 * Tracking URLs are always generated against home_url('/') with the
 * destination as a query arg, so a legitimate redirect target must
 * resolve to the same host as home_url() / site_url(). Anything else is
 * either a misconfiguration or an open-redirect attempt.
 *
 * @param string $url Raw URL from the request.
 * @return string Safe URL — original if it passes, home_url('/') otherwise.
 */
function wcwp_validate_tracking_redirect($url) {
    $fallback = home_url('/');
    if (!is_string($url) || $url === '') {
        return $fallback;
    }

    $parts = wp_parse_url($url);
    if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
        return $fallback;
    }

    if (!in_array(strtolower($parts['scheme']), ['http', 'https'], true)) {
        return $fallback;
    }

    $allowed_hosts = array_filter([
        wp_parse_url(home_url(), PHP_URL_HOST),
        wp_parse_url(site_url(), PHP_URL_HOST),
    ]);

    /**
     * Filter the host allowlist for click-tracking redirects.
     *
     * Useful for multisite, CDN-fronted setups, or stores that legitimately
     * redirect to a sister domain. Hosts are compared case-insensitively.
     *
     * @param string[] $allowed_hosts Default: home_url and site_url hosts.
     */
    $allowed_hosts = (array) apply_filters('wcwp_tracking_allowed_hosts', $allowed_hosts);
    $allowed_hosts = array_map('strtolower', array_filter($allowed_hosts));

    if (!in_array(strtolower($parts['host']), $allowed_hosts, true)) {
        return $fallback;
    }

    return $url;
}

function wcwp_handle_tracking_request() {
    if (!isset($_GET['wcwp_track'])) return;
    $type = sanitize_text_field(wp_unslash($_GET['wcwp_track']));
    $event_id = isset($_GET['event_id']) ? sanitize_text_field(wp_unslash($_GET['event_id'])) : '';

    if ($type === 'click' && $event_id) {
        wcwp_analytics_update_event($event_id, ['status' => 'clicked']);
        if (function_exists('wcwp_dispatch_webhook')) {
            wcwp_dispatch_webhook('message.clicked', ['event_id' => $event_id]);
        }
    }

    $redirect_raw = isset($_GET['redirect']) ? esc_url_raw(wp_unslash($_GET['redirect'])) : '';
    $redirect = wcwp_validate_tracking_redirect($redirect_raw);
    wp_safe_redirect($redirect);
    exit;
}

function wcwp_track_event_ajax() {
    if ( ! check_ajax_referer( 'wcwp_track_event', 'nonce', false ) ) {
        wp_send_json_error( [ 'message' => __( 'Invalid nonce', 'woochat-pro' ) ], 403 );
    }
    $type = isset($_REQUEST['type']) ? sanitize_text_field(wp_unslash($_REQUEST['type'])) : '';
    $event_id = isset($_REQUEST['event_id']) ? sanitize_text_field(wp_unslash($_REQUEST['event_id'])) : '';
    if (!$type || !$event_id) {
        wp_send_json_error(['message' => __('Missing data', 'woochat-pro')], 400);
    }
    if ($type === 'delivered') {
        wcwp_analytics_update_event($event_id, ['status' => 'delivered']);
        if (function_exists('wcwp_dispatch_webhook')) {
            wcwp_dispatch_webhook('message.delivered', ['event_id' => $event_id]);
        }
    }
    wp_send_json_success();
}
