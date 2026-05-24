<?php
if (!defined('ABSPATH')) exit;

// License Manager: activation, deactivation, periodic validation

add_action('admin_init', 'zignites_chat_check_license_status');
add_action('wp_ajax_zignites_chat_activate_license', 'zignites_chat_activate_license_ajax');
add_action('wp_ajax_zignites_chat_deactivate_license', 'zignites_chat_deactivate_license_ajax');

// Endpoint helper (filterable)
function zignites_chat_license_api_endpoint() {
    $default = '';
    return apply_filters('zignites_chat_license_api_endpoint', $default);
}

function zignites_chat_license_request($action, $license_key) {
    $endpoint = zignites_chat_license_api_endpoint();
    if (!$endpoint || !$license_key) {
        return new WP_Error('missing_data', __('Missing endpoint or license key', 'zignites-chat'));
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
        return new WP_Error('bad_response', __('Unexpected response from license server', 'zignites-chat'));
    }

    return $body;
}

function zignites_chat_update_license_state($data) {
    if (!is_array($data)) return;
    if (isset($data['status'])) {
        update_option('zignites_chat_license_status', sanitize_text_field($data['status']));
    }
    if (isset($data['expires'])) {
        update_option('zignites_chat_license_expires', sanitize_text_field($data['expires']));
    }
    if (isset($data['message'])) {
        update_option('zignites_chat_license_message', sanitize_text_field($data['message']));
    }
    update_option('zignites_chat_license_last_check', time());
}

function zignites_chat_check_license_status() {
    $last_check = get_option('zignites_chat_license_last_check', 0);
    if (time() - $last_check < DAY_IN_SECONDS) return;

    $license_key = get_option('zignites_chat_license_key');
    if (!$license_key) return;

    $result = zignites_chat_license_request('validate', $license_key);
    if (is_wp_error($result)) {
        update_option('zignites_chat_license_message', $result->get_error_message());
        return;
    }

    zignites_chat_update_license_state($result);
}

function zignites_chat_activate_license_ajax() {
    if (!current_user_can('manage_options')) wp_send_json_error(['message' => __('Unauthorized', 'zignites-chat')], 403);
    check_ajax_referer('zignites_chat_license_nonce', 'nonce');

    $license_key = isset($_POST['license_key']) ? sanitize_text_field(wp_unslash($_POST['license_key'])) : '';
    if (!$license_key) wp_send_json_error(['message' => __('License key required', 'zignites-chat')], 400);

    $result = zignites_chat_license_request('activate', $license_key);
    if (is_wp_error($result)) wp_send_json_error(['message' => $result->get_error_message()], 400);

    zignites_chat_update_license_state($result);
    update_option('zignites_chat_license_key', $license_key);
    wp_send_json_success(['status' => $result['status'] ?? 'unknown', 'message' => $result['message'] ?? __('Activated', 'zignites-chat')]);
}

function zignites_chat_deactivate_license_ajax() {
    if (!current_user_can('manage_options')) wp_send_json_error(['message' => __('Unauthorized', 'zignites-chat')], 403);
    check_ajax_referer('zignites_chat_license_nonce', 'nonce');

    $license_key = sanitize_text_field(get_option('zignites_chat_license_key', ''));
    if ($license_key) {
        $result = zignites_chat_license_request('deactivate', $license_key);
        if (is_wp_error($result)) wp_send_json_error(['message' => $result->get_error_message()], 400);
    }

    update_option('zignites_chat_license_status', 'inactive');
    update_option('zignites_chat_license_message', __('License deactivated', 'zignites-chat'));
    wp_send_json_success(['status' => 'inactive', 'message' => __('Deactivated', 'zignites-chat')]);
}

// Helper function to use in feature gates.
//
// On the Pro build the ZIGNITES_CHAT_IS_PRO constant is defined in the main
// plugin file; until Freemius is wired in we use that as the unlock. Once
// my_freemius() is available, this returns true only when
// can_use_premium_code__premium_only() reports a valid license.
function zignites_chat_is_pro_active() {
    if (function_exists('zignites_chat_pro_freemius')) {
        return zignites_chat_pro_freemius()->can_use_premium_code__premium_only();
    }
    if (defined('ZIGNITES_CHAT_IS_PRO') && ZIGNITES_CHAT_IS_PRO) {
        return true;
    }
    return get_option('zignites_chat_license_status') === 'valid';
}

function zignites_chat_license_status_label($status) {
    // Known statuses get translated labels. Unknown values fall back to
    // ucfirst() so a new server-side status string still renders something
    // human-readable (just untranslated) instead of breaking the UI.
    $labels = [
        'valid'    => __('Active', 'zignites-chat'),
        'inactive' => __('Inactive', 'zignites-chat'),
        'expired'  => __('Expired', 'zignites-chat'),
        'invalid'  => __('Invalid', 'zignites-chat'),
    ];
    if (isset($labels[$status])) {
        return $labels[$status];
    }
    return ucfirst((string) $status);
}
