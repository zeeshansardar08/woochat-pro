<?php
if (!defined('ABSPATH')) exit;

// Simple in-option analytics store with capped events
const WCWP_ANALYTICS_MAX_EVENTS = 200;

add_action('init', 'wcwp_handle_tracking_request');
add_action('wp_ajax_wcwp_track_event', 'wcwp_track_event_ajax');
add_action('wp_ajax_nopriv_wcwp_track_event', 'wcwp_track_event_ajax');

add_action('wcwp_cleanup_analytics', 'wcwp_cleanup_analytics');

function wcwp_get_analytics_table_name() {
    global $wpdb;
    return $wpdb->prefix . 'wcwp_analytics_events';
}

function wcwp_analytics_table_exists() {
    global $wpdb;
    $table = wcwp_get_analytics_table_name();
    $exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table));
    return $exists === $table;
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
    if (!wcwp_analytics_table_exists()) return;

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

    if (wcwp_analytics_table_exists()) {
        wcwp_analytics_insert_event($event);
        return $id;
    }

    $events = get_option('wcwp_analytics_events', []);
    array_unshift($events, $event);
    $events = wcwp_analytics_apply_retention($events);
    if (count($events) > WCWP_ANALYTICS_MAX_EVENTS) {
        $events = array_slice($events, 0, WCWP_ANALYTICS_MAX_EVENTS);
    }

    update_option('wcwp_analytics_events', $events, false);
    return $id;
}

function wcwp_analytics_update_event($event_id, $fields = []) {
    if (wcwp_analytics_table_exists()) {
        wcwp_analytics_update_event_row($event_id, $fields);
        return;
    }

    $events = get_option('wcwp_analytics_events', []);
    $updated = false;
    foreach ($events as &$evt) {
        if (!isset($evt['id']) || $evt['id'] !== $event_id) continue;
        foreach ($fields as $key => $val) {
            if ($key === 'meta' && is_array($val)) {
                $evt['meta'] = array_merge(isset($evt['meta']) && is_array($evt['meta']) ? $evt['meta'] : [], $val);
            } else {
                $evt[$key] = $val;
            }
        }
        $updated = true;
        break;
    }
    if ($updated) {
        update_option('wcwp_analytics_events', $events, false);
    }
}

function wcwp_analytics_increment_total($bucket, $amount = 1) {
    $totals = get_option('wcwp_analytics_totals', [
        'sent' => 0,
        'delivered' => 0,
        'clicked' => 0,
    ]);
    if (!isset($totals[$bucket])) {
        $totals[$bucket] = 0;
    }
    $totals[$bucket] += $amount;
    update_option('wcwp_analytics_totals', $totals, false);
}

function wcwp_analytics_get_totals() {
    if (wcwp_analytics_table_exists()) {
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

    $defaults = [ 'sent' => 0, 'delivered' => 0, 'clicked' => 0 ];
    $totals = get_option('wcwp_analytics_totals', $defaults);
    return wp_parse_args($totals, $defaults);
}

function wcwp_analytics_get_events($limit = 50, $filters = []) {
    if (wcwp_analytics_table_exists()) {
        return wcwp_analytics_get_events_db($limit, $filters);
    }

    $events = get_option('wcwp_analytics_events', []);
    $events = wcwp_analytics_apply_retention($events);
    update_option('wcwp_analytics_events', $events, false);
    return array_slice($events, 0, absint($limit));
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

function wcwp_analytics_update_event_row($event_id, $fields = []) {
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

function wcwp_analytics_get_events_db($limit = 50, $filters = []) {
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

function wcwp_analytics_apply_retention($events) {
    $days = absint(get_option('wcwp_data_retention_days', 0));
    if ($days < 1) return $events;
    $cutoff = time() - ($days * DAY_IN_SECONDS);
    $filtered = [];
    foreach ($events as $evt) {
        $time_str = isset($evt['time']) ? $evt['time'] : '';
        $ts = $time_str ? strtotime($time_str) : 0;
        if ($ts && $ts >= $cutoff) {
            $filtered[] = $evt;
        }
    }
    return $filtered;
}

function wcwp_analytics_tracking_url($event_id, $redirect_url) {
    $redirect = $redirect_url ? esc_url_raw($redirect_url) : home_url('/');
    return add_query_arg([
        'wcwp_track' => 'click',
        'event_id' => $event_id,
        'redirect' => $redirect,
    ], home_url('/'));
}

function wcwp_handle_tracking_request() {
    if (!isset($_GET['wcwp_track'])) return;
    $type = sanitize_text_field($_GET['wcwp_track']);
    $event_id = sanitize_text_field($_GET['event_id'] ?? '');

    if ($type === 'click' && $event_id) {
        wcwp_analytics_increment_total('clicked');
        wcwp_analytics_update_event($event_id, ['status' => 'clicked']);
    }

    $redirect = isset($_GET['redirect']) ? esc_url_raw($_GET['redirect']) : home_url('/');
    if (!$redirect || strpos($redirect, 'http') !== 0) {
        $redirect = home_url('/');
    }
    wp_safe_redirect($redirect);
    exit;
}

function wcwp_track_event_ajax() {
    $type = sanitize_text_field($_REQUEST['type'] ?? '');
    $event_id = sanitize_text_field($_REQUEST['event_id'] ?? '');
    if (!$type || !$event_id) {
        wp_send_json_error(['message' => 'Missing data'], 400);
    }
    if ($type === 'delivered') {
        wcwp_analytics_increment_total('delivered');
        wcwp_analytics_update_event($event_id, ['status' => 'delivered']);
    }
    wp_send_json_success();
}
