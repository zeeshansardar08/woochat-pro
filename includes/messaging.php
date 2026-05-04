<?php
/**
 * WhatsApp message dispatcher.
 *
 * Single entry point for everything in the plugin that wants to send a
 * WhatsApp message: order confirmations, manual sends, admin test
 * messages, cart-recovery reminders, post-order follow-ups. Handles the
 * cross-cutting concerns once (opt-out, analytics, log file, test mode)
 * and delegates the actual HTTP call to a WCWP_Provider instance.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Map of provider id => class name (or pre-built instance, or factory).
 *
 * Filterable via 'wcwp_providers' so a third-party plugin can register
 * a new transport without touching core. Example:
 *
 *   add_filter( 'wcwp_providers', function ( $p ) {
 *       $p['kaleyra'] = My_Kaleyra_Provider::class;
 *       return $p;
 *   } );
 */
function wcwp_get_provider_map() {
    $map = [
        'twilio' => 'WCWP_Provider_Twilio',
        'cloud'  => 'WCWP_Provider_Cloud',
    ];
    return (array) apply_filters( 'wcwp_providers', $map );
}

/**
 * Resolve a provider id to an instance, or null if unknown.
 *
 * @param string $id One of the keys returned by wcwp_get_provider_map().
 * @return WCWP_Provider|null
 */
function wcwp_get_provider( $id ) {
    $map = wcwp_get_provider_map();
    if ( ! isset( $map[ $id ] ) ) {
        return null;
    }

    $entry = $map[ $id ];

    if ( $entry instanceof WCWP_Provider ) {
        return $entry;
    }
    if ( is_string( $entry ) && class_exists( $entry ) ) {
        $obj = new $entry();
        return $obj instanceof WCWP_Provider ? $obj : null;
    }
    if ( is_callable( $entry ) ) {
        $obj = call_user_func( $entry );
        return $obj instanceof WCWP_Provider ? $obj : null;
    }
    return null;
}

/**
 * Send a WhatsApp message.
 *
 * Public function with the same signature as previously lived in
 * order-hooks.php — every existing caller (order hook, manual button,
 * test-message admin AJAX, cart-recovery worker, follow-up scheduler)
 * keeps working unchanged.
 *
 * @param string $to       Raw phone number from billing data.
 * @param string $message  Body text.
 * @param bool   $manual   True when triggered by an admin button (changes log prefix).
 * @param array  $context  Optional metadata: type, order_id, event_id.
 * @return bool True on send (or test-mode log), false on any failure.
 */
function wcwp_send_whatsapp_message( $to, $message, $manual = false, $context = [] ) {
    $test_mode   = get_option( 'wcwp_test_mode_enabled', 'no' );
    $provider_id = get_option( 'wcwp_api_provider', 'twilio' );
    $log_file    = function_exists( 'wcwp_get_log_file' ) ? wcwp_get_log_file() : WP_CONTENT_DIR . '/woochat-pro.log';
    $log_prefix  = $manual ? '[WooChat Pro - MANUAL]' : '[WooChat Pro]';
    $log_failed  = false;
    $safe_to     = function_exists( 'wcwp_mask_phone' )    ? wcwp_mask_phone( $to )         : $to;
    $safe_msg    = function_exists( 'wcwp_redact_message' ) ? wcwp_redact_message( $message ) : $message;

    // Opt-out: blocked numbers never reach a provider.
    if ( function_exists( 'wcwp_is_opted_out' ) && wcwp_is_opted_out( $to ) ) {
        @error_log( "$log_prefix Opt-out: Message blocked for $safe_to\n", 3, $log_file ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
        return false;
    }

    // Analytics: create a row up-front so we can flip its status as we go.
    $event_id   = $context['event_id'] ?? null;
    $event_type = isset( $context['type'] ) && $context['type'] ? $context['type'] : 'order';

    if ( function_exists( 'wcwp_analytics_log_event' ) ) {
        if ( ! $event_id ) {
            $event_id = wcwp_analytics_log_event( $event_type, [
                'status'          => 'pending',
                'phone'           => $to,
                'order_id'        => isset( $context['order_id'] ) ? intval( $context['order_id'] ) : 0,
                'message_preview' => $safe_msg,
                'provider'        => $provider_id,
                'meta'            => [
                    'source' => $context['type'] ?? 'order',
                    'manual' => $manual ? 'yes' : 'no',
                ],
            ] );
        } else {
            wcwp_analytics_update_event( $event_id, [
                'status'          => 'pending',
                'provider'        => $provider_id,
                'message_preview' => $safe_msg,
            ] );
        }
    }

    $log_write = function ( $msg ) use ( $log_file, &$log_failed ) {
        if ( @error_log( $msg, 3, $log_file ) === false ) { // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            $log_failed = true;
        }
    };

    // Test mode short-circuits before any HTTP call.
    if ( $test_mode === 'yes' ) {
        $log_write( "$log_prefix TEST MODE: Order message to $safe_to: $safe_msg\n" );
        wcwp_maybe_log_notice( $log_failed );
        if ( function_exists( 'wcwp_analytics_update_event' ) && $event_id ) {
            wcwp_analytics_update_event( $event_id, [ 'status' => 'test' ] );
        }
        return true;
    }

    $provider = wcwp_get_provider( $provider_id );
    if ( ! $provider ) {
        $log_write( "$log_prefix Unknown provider: $provider_id\n" );
        wcwp_maybe_log_notice( $log_failed );
        if ( function_exists( 'wcwp_analytics_update_event' ) && $event_id ) {
            wcwp_analytics_update_event( $event_id, [ 'status' => 'failed' ] );
        }
        return false;
    }

    if ( ! $provider->is_configured() ) {
        $log_write( "$log_prefix " . $provider->missing_credentials_message() . "\n" );
        wcwp_maybe_log_notice( $log_failed );
        // Note: original code did NOT update event status here for the
        // "missing credentials" path on the Cloud branch; original DID
        // for Twilio. Unifying to "failed" — strictly more informative
        // and matches what the analytics row should reflect.
        if ( function_exists( 'wcwp_analytics_update_event' ) && $event_id ) {
            wcwp_analytics_update_event( $event_id, [ 'status' => 'failed' ] );
        }
        return false;
    }

    $result = $provider->send( $to, $message );

    if ( empty( $result['ok'] ) ) {
        $log_write( "$log_prefix " . ( $result['error'] ?? 'Unknown error' ) . "\n" );
        wcwp_maybe_log_notice( $log_failed );
        if ( function_exists( 'wcwp_analytics_update_event' ) && $event_id ) {
            wcwp_analytics_update_event( $event_id, [ 'status' => 'failed' ] );
        }
        return false;
    }

    // Success.
    $success_label = ( $provider->name() === 'cloud' ) ? 'WhatsApp Cloud message' : 'WhatsApp message';
    $log_write( "$log_prefix $success_label sent to $safe_to\n" );
    wcwp_maybe_log_notice( $log_failed );

    if ( function_exists( 'wcwp_analytics_update_event' ) && $event_id ) {
        wcwp_analytics_update_event( $event_id, [
            'status'     => 'sent',
            'message_id' => $result['message_id'] ?? '',
        ] );
    }
    return true;
}

/**
 * Surface a single admin notice if the log file could not be written
 * during this request. Hooked once per request — multiple failed writes
 * collapse to one notice.
 */
function wcwp_maybe_log_notice( $log_failed ) {
    if ( ! $log_failed ) return;
    add_action( 'admin_notices', function () {
        echo '<div class="notice notice-error is-dismissible"><p><b>'
            . esc_html__( 'WooChat Pro:', 'woochat-pro' )
            . '</b> '
            . esc_html__( 'Unable to write to log file. Please check file permissions for wp-content/uploads/woochat-pro/.', 'woochat-pro' )
            . '</p></div>';
    } );
}
