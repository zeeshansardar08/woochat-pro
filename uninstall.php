<?php
if (!defined('WP_UNINSTALL_PLUGIN')) exit;

if (!function_exists('zignites_chat_is_woocommerce_active')) {
    $zignites_chat_helpers = WP_PLUGIN_DIR . '/zignites-chat/includes/helpers.php';
    if (file_exists($zignites_chat_helpers)) {
        include_once $zignites_chat_helpers;
    }
}

if (function_exists('zignites_chat_is_woocommerce_active') && !zignites_chat_is_woocommerce_active()) {
    return;
}

$zignites_chat_delete = get_option('zignites_chat_delete_data_on_uninstall', 'no');
if ($zignites_chat_delete !== 'yes') {
    return;
}

$zignites_chat_option_keys = [
    'zignites_chat_twilio_sid',
    'zignites_chat_twilio_auth_token',
    'zignites_chat_twilio_from',
    'zignites_chat_order_message_template',
    'zignites_chat_cart_recovery_enabled',
    'zignites_chat_chatbot_enabled',
    'zignites_chat_chatbot_gpt_enabled',
    'zignites_chat_chatbot_catalog_context',
    'zignites_chat_agents',
    'zignites_chat_agent_routing_mode',
    'zignites_chat_faq_pairs',
    'zignites_chat_license_key',
    'zignites_chat_license_status',
    'zignites_chat_license_expires',
    'zignites_chat_license_message',
    'zignites_chat_license_last_check',
    'zignites_chat_test_mode_enabled',
    'zignites_chat_test_phone',
    'zignites_chat_test_message',
    'zignites_chat_pro_notice_dismissed',
    'zignites_chat_api_provider',
    'zignites_chat_cloud_token',
    'zignites_chat_cloud_phone_id',
    'zignites_chat_cloud_from',
    'zignites_chat_cloud_app_secret',
    'zignites_chat_meta_verify_token',
    'zignites_chat_cart_recovery_delay',
    'zignites_chat_cart_recovery_message',
    'zignites_chat_cart_recovery_require_consent',
    'zignites_chat_followup_enabled',
    'zignites_chat_followup_delay_minutes',
    'zignites_chat_followup_template',
    'zignites_chat_followup_use_gpt',
    'zignites_chat_gpt_api_endpoint',
    'zignites_chat_gpt_api_key',
    'zignites_chat_gpt_model',
    'zignites_chat_wa_templates',
    'zignites_chat_cloud_waba_id',
    'zignites_chat_wa_synced_templates',
    'zignites_chat_wa_synced_at',
    'zignites_chat_chatbot_bg',
    'zignites_chat_chatbot_text',
    'zignites_chat_chatbot_icon_color',
    'zignites_chat_chatbot_icon',
    'zignites_chat_chatbot_welcome',
    'zignites_chat_analytics_events',
    'zignites_chat_analytics_totals',
    'zignites_chat_data_retention_days',
    'zignites_chat_delete_data_on_uninstall',
    'zignites_chat_optout_keywords',
    'zignites_chat_optout_list',
    'zignites_chat_optout_webhook_token',
    'zignites_chat_db_version',
    'zignites_chat_onboarding_completed',
    'zignites_chat_order_message_template_b',
    'zignites_chat_cart_recovery_message_b',
    'zignites_chat_followup_template_b',
    'zignites_chat_order_message_ab_enabled',
    'zignites_chat_cart_recovery_ab_enabled',
    'zignites_chat_followup_ab_enabled',
    'zignites_chat_webhooks',
    'zignites_chat_webhook_log',
    'zignites_chat_outbound_rate_state',
    'zignites_chat_quiet_hours_enabled',
    'zignites_chat_quiet_start',
    'zignites_chat_quiet_end',
    'zignites_chat_stock_alerts_enabled',
    'zignites_chat_stock_alert_message',
    'zignites_chat_stock_form_heading',
    'zignites_chat_review_request_enabled',
    'zignites_chat_review_trigger_status',
    'zignites_chat_review_delay_days',
    'zignites_chat_review_url',
    'zignites_chat_review_message',
    'zignites_chat_cod_enabled',
    'zignites_chat_cod_gateways',
    'zignites_chat_cod_message_template',
    'zignites_chat_cod_confirm_keywords',
    'zignites_chat_cod_cancel_keywords',
    'zignites_chat_cod_on_confirm_status',
    'zignites_chat_cod_on_cancel_status',
    'zignites_chat_status_notify_enabled',
    'zignites_chat_status_notifications',
    'zignites_chat_optin_enabled',
    'zignites_chat_optin_label',
    'zignites_chat_optin_default_checked',
    'zignites_chat_optin_required',
    'zignites_chat_optin_log',
    'zignites_chat_inbox_canned_replies',
    'zignites_chat_inbox_notify_enabled',
    'zignites_chat_inbox_notify_email',
    'zignites_chat_sequences',
];

foreach ($zignites_chat_option_keys as $zignites_chat_key) {
    delete_option($zignites_chat_key);
}

global $wpdb;

// Drop the plugin's custom tables. A table name cannot be passed as a
// prepare() placeholder, and these names are built solely from $wpdb->prefix
// plus hard-coded suffixes, so there is no user input to bind.
$zignites_chat_tables = array(
	$wpdb->prefix . 'zignites_chat_abandoned_carts',
	$wpdb->prefix . 'zignites_chat_analytics_events',
	$wpdb->prefix . 'zignites_chat_campaign_recipients',
	$wpdb->prefix . 'zignites_chat_campaigns',
	$wpdb->prefix . 'zignites_chat_conversations',
	$wpdb->prefix . 'zignites_chat_messages',
	$wpdb->prefix . 'zignites_chat_stock_subs',
	$wpdb->prefix . 'zignites_chat_sequence_enrollments',
);
foreach ( $zignites_chat_tables as $zignites_chat_table ) {
	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- One-time uninstall cleanup; table name is built from $wpdb->prefix, no user input.
	$wpdb->query( "DROP TABLE IF EXISTS `{$zignites_chat_table}`" );
}

// Clear any scheduled cron events the plugin registered.
$zignites_chat_cron_hooks = array(
	'zignites_chat_cleanup_analytics',
	'zignites_chat_process_cart_recovery_queue',
	'zignites_chat_process_campaign',
	'zignites_chat_promote_scheduled_campaigns',
	'zignites_chat_send_order_message',
	'zignites_chat_send_followup_message',
	'zignites_chat_send_review_request',
	'zignites_chat_webhook_retry',
	'zignites_chat_process_stock_alerts',
	'zignites_chat_process_sequences',
);
foreach ($zignites_chat_cron_hooks as $zignites_chat_hook) {
	wp_clear_scheduled_hook($zignites_chat_hook);
}

// Remove the plugin's upload directory (log file plus the .htaccess /
// index.php siblings created by zignites_chat_get_log_file()).
$zignites_chat_upload_dir = wp_upload_dir();
$zignites_chat_plugin_log_dir = $zignites_chat_upload_dir['basedir'] . '/zignites-chat';

global $wp_filesystem;
if (empty($wp_filesystem)) {
	require_once ABSPATH . 'wp-admin/includes/file.php';
	WP_Filesystem();
}
if (!empty($wp_filesystem) && $wp_filesystem->is_dir($zignites_chat_plugin_log_dir)) {
	$wp_filesystem->delete($zignites_chat_plugin_log_dir, true);
}
