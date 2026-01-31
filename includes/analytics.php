<?php
if (!defined('ABSPATH')) exit;

// Simple in-option analytics store with capped events
const WCWP_ANALYTICS_MAX_EVENTS = 200;

add_action('init', 'wcwp_handle_tracking_request');
add_action('wp_ajax_wcwp_track_event', 'wcwp_track_event_ajax');
add_action('wp_ajax_nopriv_wcwp_track_event', 'wcwp_track_event_ajax');

function wcwp_analytics_log_event($type, $data = []) {
    $events = get_option('wcwp_analytics_events', []);
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

    array_unshift($events, $event);
    if (count($events) > WCWP_ANALYTICS_MAX_EVENTS) {
        $events = array_slice($events, 0, WCWP_ANALYTICS_MAX_EVENTS);
    }

    update_option('wcwp_analytics_events', $events, false);
    return $id;
}

function wcwp_analytics_update_event($event_id, $fields = []) {
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
    $defaults = [ 'sent' => 0, 'delivered' => 0, 'clicked' => 0 ];
    $totals = get_option('wcwp_analytics_totals', $defaults);
    return wp_parse_args($totals, $defaults);
}

function wcwp_analytics_get_events($limit = 50) {
    $events = get_option('wcwp_analytics_events', []);
    return array_slice($events, 0, absint($limit));
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
