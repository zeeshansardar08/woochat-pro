<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) exit;

if ( ! function_exists( 'zignites_chat_is_woocommerce_active' ) ) {
	$zignites_chat_helpers = WP_PLUGIN_DIR . '/zignites-chat/includes/helpers.php';
	if ( file_exists( $zignites_chat_helpers ) ) {
		include_once $zignites_chat_helpers;
	}
}

if ( function_exists( 'zignites_chat_is_woocommerce_active' ) && ! zignites_chat_is_woocommerce_active() ) {
	return;
}

$zignites_chat_delete = get_option( 'zignites_chat_delete_data_on_uninstall', 'no' );
if ( $zignites_chat_delete !== 'yes' ) {
	return;
}

$zignites_chat_option_keys = [
	'zignites_chat_twilio_sid',
	'zignites_chat_twilio_auth_token',
	'zignites_chat_twilio_from',
	'zignites_chat_api_provider',
	'zignites_chat_cloud_token',
	'zignites_chat_cloud_phone_id',
	'zignites_chat_cloud_from',
	'zignites_chat_cloud_app_secret',
	'zignites_chat_test_mode_enabled',
	'zignites_chat_test_phone',
	'zignites_chat_test_message',
	'zignites_chat_order_message_template',
	'zignites_chat_chatbot_enabled',
	'zignites_chat_chatbot_welcome',
	'zignites_chat_agents',
	'zignites_chat_faq_pairs',
	'zignites_chat_data_retention_days',
	'zignites_chat_delete_data_on_uninstall',
	'zignites_chat_optout_keywords',
	'zignites_chat_optout_list',
	'zignites_chat_optout_webhook_token',
	'zignites_chat_db_version',
	'zignites_chat_onboarding_completed',
];

foreach ( $zignites_chat_option_keys as $zignites_chat_key ) {
	delete_option( $zignites_chat_key );
}

global $wpdb;

// Drop the plugin's custom table. Table name is built from $wpdb->prefix only — no user input.
// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
$wpdb->query( "DROP TABLE IF EXISTS `{$wpdb->prefix}zignites_chat_analytics_events`" );

// Clear any scheduled cron events.
wp_clear_scheduled_hook( 'zignites_chat_send_order_message' );

// Remove the plugin's upload directory (log file + .htaccess + index.php).
$zignites_chat_upload_dir    = wp_upload_dir();
$zignites_chat_plugin_log_dir = $zignites_chat_upload_dir['basedir'] . '/zignites-chat';

global $wp_filesystem;
if ( empty( $wp_filesystem ) ) {
	require_once ABSPATH . 'wp-admin/includes/file.php';
	WP_Filesystem();
}
if ( ! empty( $wp_filesystem ) && $wp_filesystem->is_dir( $zignites_chat_plugin_log_dir ) ) {
	$wp_filesystem->delete( $zignites_chat_plugin_log_dir, true );
}
