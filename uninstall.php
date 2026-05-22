<?php
if (!defined('WP_UNINSTALL_PLUGIN')) exit;

if (!function_exists('wcwp_is_woocommerce_active')) {
    $helpers = WP_PLUGIN_DIR . '/woochat/includes/helpers.php';
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
    'wcwp_chatbot_gpt_enabled',
    'wcwp_agents',
    'wcwp_agent_routing_mode',
    'wcwp_faq_pairs',
    'wcwp_license_key',
    'wcwp_license_status',
    'wcwp_license_expires',
    'wcwp_license_message',
    'wcwp_license_last_check',
    'wcwp_test_mode_enabled',
    'wcwp_test_phone',
    'wcwp_test_message',
    'wcwp_pro_notice_dismissed',
    'wcwp_api_provider',
    'wcwp_cloud_token',
    'wcwp_cloud_phone_id',
    'wcwp_cloud_from',
    'wcwp_cloud_app_secret',
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
    'wcwp_db_version',
    'wcwp_onboarding_completed',
    'wcwp_order_message_template_b',
    'wcwp_cart_recovery_message_b',
    'wcwp_followup_template_b',
    'wcwp_order_message_ab_enabled',
    'wcwp_cart_recovery_ab_enabled',
    'wcwp_followup_ab_enabled',
    'wcwp_webhooks',
    'wcwp_webhook_log',
];

foreach ($option_keys as $key) {
    delete_option($key);
}

global $wpdb;

// Drop the plugin's custom tables. A table name cannot be passed as a
// prepare() placeholder, and these names are built solely from $wpdb->prefix
// plus hard-coded suffixes, so there is no user input to bind.
$wcwp_tables = array(
	$wpdb->prefix . 'wcwp_abandoned_carts',
	$wpdb->prefix . 'wcwp_analytics_events',
	$wpdb->prefix . 'wcwp_campaign_recipients',
	$wpdb->prefix . 'wcwp_campaigns',
);
foreach ( $wcwp_tables as $wcwp_table ) {
	$wpdb->query( "DROP TABLE IF EXISTS `{$wcwp_table}`" ); // phpcs:ignore WordPress.DB.PreparedSQL
}

// Clear any scheduled cron events the plugin registered.
$wcwp_cron_hooks = array(
	'wcwp_cleanup_analytics',
	'wcwp_process_cart_recovery_queue',
	'wcwp_process_campaign',
	'wcwp_send_order_message',
	'wcwp_send_followup_message',
	'wcwp_webhook_retry',
);
foreach ($wcwp_cron_hooks as $wcwp_hook) {
	wp_clear_scheduled_hook($wcwp_hook);
}

$upload_dir = wp_upload_dir();
$plugin_log = $upload_dir['basedir'] . '/woochat/woochat.log';
$plugin_log_dir = $upload_dir['basedir'] . '/woochat';
if (file_exists($plugin_log)) @unlink($plugin_log);
// Clean up auxiliary files created by the log helper.
$htaccess = $plugin_log_dir . '/.htaccess';
$index    = $plugin_log_dir . '/index.php';
if (file_exists($htaccess)) @unlink($htaccess);
if (file_exists($index))    @unlink($index);
if (is_dir($plugin_log_dir)) @rmdir($plugin_log_dir);
