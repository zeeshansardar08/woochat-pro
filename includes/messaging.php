<?php
/**
 * WhatsApp message dispatcher.
 *
 * Single entry point for everything in the plugin that wants to send a
 * WhatsApp message: order confirmations, manual sends, admin test
 * messages, cart-recovery reminders, post-order follow-ups. Handles the
 * cross-cutting concerns once (opt-out, analytics, log file, test mode)
 * and delegates the actual HTTP call to a ZIGNITES_CHAT_Provider instance.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Map of provider id => class name (or pre-built instance, or factory).
 *
 * Filterable via 'zignites_chat_providers' so a third-party plugin can register
 * a new transport without touching core. Example:
 *
 *   add_filter( 'zignites_chat_providers', function ( $p ) {
 *       $p['kaleyra'] = My_Kaleyra_Provider::class;
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
 * @param string $id One of the keys returned by zignites_chat_get_provider_map().
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
function zignites_chat_send_whatsapp_message( $to, $message, $manual = false, $context = [] ) {
    $test_mode   = get_option( 'zignites_chat_test_mode_enabled', 'no' );
    $provider_id = get_option( 'zignites_chat_api_provider', 'twilio' );
    $log_file    = zignites_chat_get_log_file();
    $log_prefix  = $manual ? '[Zignites Chat - MANUAL]' : '[Zignites Chat]';
    $log_failed  = false;
    $safe_to     = zignites_chat_mask_phone( $to );
    $safe_msg    = zignites_chat_redact_message( $message );

    // Opt-out: blocked numbers never reach a provider.
    if ( zignites_chat_is_opted_out( $to ) ) {
        @error_log( "$log_prefix Opt-out: Message blocked for $safe_to\n", 3, $log_file ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
        return false;
    }

    // Analytics: create a row up-front so we can flip its status as we go.
    $event_id   = $context['event_id'] ?? null;
    $event_type = isset( $context['type'] ) && $context['type'] ? $context['type'] : 'order';

    if ( ! $event_id ) {
        $meta = [
            'source' => $context['type'] ?? 'order',
            'manual' => $manual ? 'yes' : 'no',
        ];
        if ( isset( $context['ab_variant'] ) && in_array( $context['ab_variant'], [ 'a', 'b' ], true ) ) {
            $meta['ab_variant'] = $context['ab_variant'];
        }
        $event_id = zignites_chat_analytics_log_event( $event_type, [
            'status'          => 'pending',
            'phone'           => $to,
            'order_id'        => isset( $context['order_id'] ) ? intval( $context['order_id'] ) : 0,
            'message_preview' => $safe_msg,
            'provider'        => $provider_id,
            'meta'            => $meta,
        ] );
    } else {
        zignites_chat_analytics_update_event( $event_id, [
            'status'          => 'pending',
            'provider'        => $provider_id,
            'message_preview' => $safe_msg,
        ] );
    }

    $log_write = function ( $msg ) use ( $log_file, &$log_failed ) {
        if ( @error_log( $msg, 3, $log_file ) === false ) { // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            $log_failed = true;
        }
    };

    // Test mode short-circuits before any HTTP call.
    if ( $test_mode === 'yes' ) {
        $log_write( "$log_prefix TEST MODE: Order message to $safe_to: $safe_msg\n" );
        zignites_chat_maybe_log_notice( $log_failed );
        if ( $event_id ) {
            zignites_chat_analytics_update_event( $event_id, [ 'status' => 'test' ] );
        }
        return true;
    }

    $provider = zignites_chat_get_provider( $provider_id );
    if ( ! $provider ) {
        $log_write( "$log_prefix Unknown provider: $provider_id\n" );
        zignites_chat_maybe_log_notice( $log_failed );
        if ( $event_id ) {
            zignites_chat_analytics_update_event( $event_id, [ 'status' => 'failed' ] );
        }
        return false;
    }

    if ( ! $provider->is_configured() ) {
        $log_write( "$log_prefix " . $provider->missing_credentials_message() . "\n" );
        zignites_chat_maybe_log_notice( $log_failed );
        // Note: original code did NOT update event status here for the
        // "missing credentials" path on the Cloud branch; original DID
        // for Twilio. Unifying to "failed" — strictly more informative
        // and matches what the analytics row should reflect.
        if ( $event_id ) {
            zignites_chat_analytics_update_event( $event_id, [ 'status' => 'failed' ] );
        }
        return false;
    }

    $result = $provider->send( $to, $message );

    if ( empty( $result['ok'] ) ) {
        $log_write( "$log_prefix " . ( $result['error'] ?? 'Unknown error' ) . "\n" );
        zignites_chat_maybe_log_notice( $log_failed );
        if ( $event_id ) {
            zignites_chat_analytics_update_event( $event_id, [ 'status' => 'failed' ] );
        }
        if ( function_exists( 'zignites_chat_dispatch_webhook' ) ) {
            zignites_chat_dispatch_webhook( 'message.failed', [
                'event_id' => $event_id,
                'type'     => $event_type,
                'phone'    => $to,
                'order_id' => isset( $context['order_id'] ) ? (int) $context['order_id'] : 0,
                'provider' => $provider_id,
                'error'    => (string) ( $result['error'] ?? 'unknown' ),
            ] );
        }
        return false;
    }

    // Success.
    $success_label = ( $provider->name() === 'cloud' ) ? 'WhatsApp Cloud message' : 'WhatsApp message';
    $log_write( "$log_prefix $success_label sent to $safe_to\n" );
    zignites_chat_maybe_log_notice( $log_failed );

    if ( $event_id ) {
        zignites_chat_analytics_update_event( $event_id, [
            'status'     => 'sent',
            'message_id' => $result['message_id'] ?? '',
        ] );
    }

    if ( function_exists( 'zignites_chat_dispatch_webhook' ) ) {
        zignites_chat_dispatch_webhook( 'message.sent', [
            'event_id'   => $event_id,
            'type'       => $event_type,
            'phone'      => $to,
            'order_id'   => isset( $context['order_id'] ) ? (int) $context['order_id'] : 0,
            'provider'   => $provider_id,
            'message_id' => (string) ( $result['message_id'] ?? '' ),
            'ab_variant' => isset( $context['ab_variant'] ) ? (string) $context['ab_variant'] : '',
        ] );
    }

    return true;
}

/**
 * Surface a single admin notice if the log file could not be written
 * during this request. Hooked once per request — multiple failed writes
 * collapse to one notice.
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
