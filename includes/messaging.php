<?php
/**
 * WhatsApp message dispatcher.
 *
 * Single entry point for sending WhatsApp messages: order confirmations,
 * manual admin sends, and test messages. Handles opt-out suppression,
 * file logging, and test mode short-circuit, then delegates to a provider.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Map of provider id => class name.
 *
 * Filterable so third-party code can register a custom transport:
 *
 *   add_filter( 'zignites_chat_providers', function ( $p ) {
 *       $p['myapi'] = My_Provider::class;
 *       return $p;
 *   } );
 */
function zignites_chat_get_provider_map() {
	$map = [
		'twilio' => 'ZIGNITES_CHAT_Provider_Twilio',
		'cloud'  => 'ZIGNITES_CHAT_Provider_Cloud',
	];
	return (array) apply_filters( 'zignites_chat_providers', $map );
}

/**
 * Resolve a provider id to an instance, or null if unknown.
 *
 * @param string $id Provider key from zignites_chat_get_provider_map().
 * @return ZIGNITES_CHAT_Provider|null
 */
function zignites_chat_get_provider( $id ) {
	$map = zignites_chat_get_provider_map();
	if ( ! isset( $map[ $id ] ) ) {
		return null;
	}

	$entry = $map[ $id ];

	if ( $entry instanceof ZIGNITES_CHAT_Provider ) {
		return $entry;
	}
	if ( is_string( $entry ) && class_exists( $entry ) ) {
		$obj = new $entry();
		return $obj instanceof ZIGNITES_CHAT_Provider ? $obj : null;
	}
	if ( is_callable( $entry ) ) {
		$obj = call_user_func( $entry );
		return $obj instanceof ZIGNITES_CHAT_Provider ? $obj : null;
	}
	return null;
}

/**
 * Send a WhatsApp message.
 *
 * @param string $to      Recipient phone number (from billing data).
 * @param string $message Message body.
 * @param bool   $manual  True when triggered by an admin action.
 * @param array  $context Optional metadata (type, order_id) for log context.
 * @return bool True on success or test-mode log; false on any failure.
 */
function zignites_chat_send_whatsapp_message( $to, $message, $manual = false, $context = [] ) {
	$test_mode   = get_option( 'zignites_chat_test_mode_enabled', 'no' );
	$provider_id = get_option( 'zignites_chat_api_provider', 'twilio' );
	$log_file    = zignites_chat_get_log_file();
	$log_prefix  = $manual ? '[Zignites Chat - MANUAL]' : '[Zignites Chat]';
	$log_failed  = false;
	$safe_to     = zignites_chat_mask_phone( $to );
	$safe_msg    = zignites_chat_redact_message( $message );

	if ( zignites_chat_is_opted_out( $to ) ) {
		@error_log( "$log_prefix Opt-out: Message blocked for $safe_to\n", 3, $log_file ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		return false;
	}

	$log_write = function ( $msg ) use ( $log_file, &$log_failed ) {
		if ( @error_log( $msg, 3, $log_file ) === false ) { // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			$log_failed = true;
		}
	};

	if ( $test_mode === 'yes' ) {
		$log_write( "$log_prefix TEST MODE: Message to $safe_to: $safe_msg\n" );
		zignites_chat_maybe_log_notice( $log_failed );
		return true;
	}

	$provider = zignites_chat_get_provider( $provider_id );
	if ( ! $provider ) {
		$log_write( "$log_prefix Unknown provider: $provider_id\n" );
		zignites_chat_maybe_log_notice( $log_failed );
		return false;
	}

	if ( ! $provider->is_configured() ) {
		$log_write( "$log_prefix " . $provider->missing_credentials_message() . "\n" );
		zignites_chat_maybe_log_notice( $log_failed );
		return false;
	}

	$result = $provider->send( $to, $message );

	if ( empty( $result['ok'] ) ) {
		$log_write( "$log_prefix " . ( $result['error'] ?? 'Unknown error' ) . "\n" );
		zignites_chat_maybe_log_notice( $log_failed );
		return false;
	}

	$success_label = ( $provider->name() === 'cloud' ) ? 'WhatsApp Cloud message' : 'WhatsApp message';
	$log_write( "$log_prefix $success_label sent to $safe_to\n" );
	zignites_chat_maybe_log_notice( $log_failed );

	return true;
}

/**
 * Surface a single admin notice when the log file cannot be written.
 * Multiple failed writes in one request collapse to one notice.
 */
function zignites_chat_maybe_log_notice( $log_failed ) {
	if ( ! $log_failed ) return;
	add_action( 'admin_notices', function () {
		echo '<div class="notice notice-error is-dismissible"><p><b>'
			. esc_html__( 'Zignites Chat:', 'zignites-chat' )
			. '</b> '
			. esc_html__( 'Unable to write to log file. Please check file permissions for wp-content/uploads/zignites-chat/.', 'zignites-chat' )
			. '</p></div>';
	} );
}
