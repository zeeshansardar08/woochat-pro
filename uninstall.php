<?php
if (!defined('WP_UNINSTALL_PLUGIN')) exit;

if (!function_exists('wcwp_is_woocommerce_active')) {
    $helpers = WP_PLUGIN_DIR . '/woochat-pro/includes/helpers.php';
    if (file_exists($helpers)) {
        include_once $helpers;
    }
}

if (function_exists('wcwp_is_woocommerce_active') && !wcwp_is_woocommerce_active()) {
    return;
}

$delete = get_option('wcwp_delete_data_on_uninstall', 'no');
if ($delete !== 'yes') {
    return;
}

$option_keys = [
    'wcwp_twilio_sid',
    'wcwp_twilio_auth_token',
    'wcwp_twilio_from',
    'wcwp_order_message_template',
    'wcwp_cart_recovery_enabled',
    'wcwp_chatbot_enabled',
    'wcwp_faq_pairs',
    'wcwp_license_key',
    'wcwp_license_status',
    'wcwp_license_expires',
    'wcwp_license_message',
    'wcwp_license_last_check',
    'wcwp_test_mode_enabled',
    'wcwp_api_provider',
    'wcwp_cloud_token',
    'wcwp_cloud_phone_id',
    'wcwp_cloud_from',
    'wcwp_cart_recovery_delay',
    'wcwp_cart_recovery_message',
    'wcwp_cart_recovery_require_consent',
    'wcwp_followup_enabled',
    'wcwp_followup_delay_minutes',
    'wcwp_followup_template',
    'wcwp_followup_use_gpt',
    'wcwp_gpt_api_endpoint',
    'wcwp_gpt_api_key',
    'wcwp_gpt_model',
    'wcwp_chatbot_bg',
    'wcwp_chatbot_text',
    'wcwp_chatbot_icon_color',
    'wcwp_chatbot_icon',
    'wcwp_chatbot_welcome',
    'wcwp_analytics_events',
    'wcwp_analytics_totals',
    'wcwp_data_retention_days',
    'wcwp_delete_data_on_uninstall',
    'wcwp_optout_keywords',
    'wcwp_optout_list',
    'wcwp_optout_webhook_token',
];

foreach ($option_keys as $key) {
    delete_option($key);
}

delete_transient('wcwp_cart_recovery_attempts');

global $wpdb;
$table = $wpdb->prefix . 'wcwp_abandoned_carts';
$wpdb->query("DROP TABLE IF EXISTS {$table}");

$analytics_table = $wpdb->prefix . 'wcwp_analytics_events';
$wpdb->query("DROP TABLE IF EXISTS {$analytics_table}");

$upload_dir = wp_upload_dir();
$plugin_log = $upload_dir['basedir'] . '/woochat-pro/woochat-pro.log';
$plugin_log_dir = $upload_dir['basedir'] . '/woochat-pro';
if (file_exists($plugin_log)) @unlink($plugin_log);
// Clean up auxiliary files created by the log helper.
$htaccess = $plugin_log_dir . '/.htaccess';
$index    = $plugin_log_dir . '/index.php';
if (file_exists($htaccess)) @unlink($htaccess);
if (file_exists($index))    @unlink($index);
if (is_dir($plugin_log_dir)) @rmdir($plugin_log_dir);
