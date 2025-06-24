<?php
if (!defined('ABSPATH')) exit;

// Periodic license check (once per 24h)
add_action('admin_init', 'wcwp_check_license_status');

function wcwp_check_license_status() {
    $last_check = get_option('wcwp_license_last_check', 0);
    if (time() - $last_check < DAY_IN_SECONDS) return;

    $license_key = get_option('wcwp_license_key');
    if (!$license_key) return;

    // Replace this URL with your actual API endpoint
    $url = "https://yourdomain.com/license-api.php?key=" . urlencode($license_key);

    $response = wp_remote_get($url, ['timeout' => 10]);

    if (!is_wp_error($response)) {
        $data = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($data['status']) && $data['status'] === 'valid') {
            update_option('wcwp_license_status', 'valid');
        } else {
            update_option('wcwp_license_status', 'invalid');
        }

        update_option('wcwp_license_last_check', time());
    }
}

// Helper function to use in feature gates
function wcwp_is_pro_active() {
    return get_option('wcwp_license_status') === 'valid';
}
