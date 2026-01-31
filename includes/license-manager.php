<?php
if (!defined('ABSPATH')) exit;

// License Manager: activation, deactivation, periodic validation

add_action('admin_init', 'wcwp_check_license_status');
add_action('wp_ajax_wcwp_activate_license', 'wcwp_activate_license_ajax');
add_action('wp_ajax_wcwp_deactivate_license', 'wcwp_deactivate_license_ajax');

// Endpoint helper (filterable)
function wcwp_license_api_endpoint() {
    $default = 'https://yourdomain.com/license-api.php';
    return apply_filters('wcwp_license_api_endpoint', $default);
}

function wcwp_license_request($action, $license_key) {
    $endpoint = wcwp_license_api_endpoint();
    if (!$endpoint || !$license_key) {
        return new WP_Error('missing_data', 'Missing endpoint or license key');
    }

    $payload = [
        'action' => $action,
        'license_key' => $license_key,
        'site_url' => home_url(),
    ];

    $response = wp_remote_post($endpoint, [
        'timeout' => 15,
        'headers' => ['Content-Type' => 'application/json'],
        'body' => wp_json_encode($payload),
    ]);

    if (is_wp_error($response)) {
        return $response;
    }

    $code = wp_remote_retrieve_response_code($response);
    $body = json_decode(wp_remote_retrieve_body($response), true);

    if ($code !== 200 || !is_array($body)) {
        return new WP_Error('bad_response', 'Unexpected response from license server');
    }

    return $body;
}

function wcwp_update_license_state($data) {
    if (!is_array($data)) return;
    if (isset($data['status'])) {
        update_option('wcwp_license_status', sanitize_text_field($data['status']));
    }
    if (isset($data['expires'])) {
        update_option('wcwp_license_expires', sanitize_text_field($data['expires']));
    }
    if (isset($data['message'])) {
        update_option('wcwp_license_message', sanitize_text_field($data['message']));
    }
    update_option('wcwp_license_last_check', time());
}

function wcwp_check_license_status() {
    $last_check = get_option('wcwp_license_last_check', 0);
    if (time() - $last_check < DAY_IN_SECONDS) return;

    $license_key = get_option('wcwp_license_key');
    if (!$license_key) return;

    $result = wcwp_license_request('validate', $license_key);
    if (is_wp_error($result)) {
        update_option('wcwp_license_message', $result->get_error_message());
        return;
    }

    wcwp_update_license_state($result);
}

function wcwp_activate_license_ajax() {
    if (!current_user_can('manage_options')) wp_send_json_error(['message' => 'Unauthorized'], 403);
    check_ajax_referer('wcwp_license_nonce', 'nonce');

    $license_key = sanitize_text_field($_POST['license_key'] ?? '');
    if (!$license_key) wp_send_json_error(['message' => 'License key required'], 400);

    $result = wcwp_license_request('activate', $license_key);
    if (is_wp_error($result)) wp_send_json_error(['message' => $result->get_error_message()], 400);

    wcwp_update_license_state($result);
    update_option('wcwp_license_key', $license_key);
    wp_send_json_success(['status' => $result['status'] ?? 'unknown', 'message' => $result['message'] ?? 'Activated']);
}

function wcwp_deactivate_license_ajax() {
    if (!current_user_can('manage_options')) wp_send_json_error(['message' => 'Unauthorized'], 403);
    check_ajax_referer('wcwp_license_nonce', 'nonce');

    $license_key = sanitize_text_field(get_option('wcwp_license_key', ''));
    if ($license_key) {
        $result = wcwp_license_request('deactivate', $license_key);
        if (is_wp_error($result)) wp_send_json_error(['message' => $result->get_error_message()], 400);
    }

    update_option('wcwp_license_status', 'inactive');
    update_option('wcwp_license_message', 'License deactivated');
    wp_send_json_success(['status' => 'inactive', 'message' => 'Deactivated']);
}

// Helper function to use in feature gates
function wcwp_is_pro_active() {
    return get_option('wcwp_license_status') === 'valid';
}
