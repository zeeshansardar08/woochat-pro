<?php
/**
 * Inbound delivery / read receipts from the messaging providers.
 *
 * Providers report what happened to a message *after* we hand it off —
 * delivered, read, or failed at the handset. This module ingests those
 * callbacks and advances the matching analytics event so the Analytics
 * dashboard's delivered / read / failed funnel reflects reality instead of
 * only "sent".
 *
 *   - Twilio: a per-message StatusCallback URL (added to the send in the
 *     Twilio provider). Twilio POSTs MessageSid + MessageStatus here and
 *     signs the request with X-Twilio-Signature.
 *   - Meta Cloud: status objects arrive on the same webhook Meta uses for
 *     inbound messages — handled in optout.php, which calls
 *     zignites_chat_ingest_meta_statuses() below.
 *
 * Matching is by provider message id (Twilio SID / Meta wamid), which the
 * dispatcher already stores on the analytics row at send time. Updates are
 * monotonic (see zignites_chat_analytics_resolve_status_transition()), so
 * out-of-order or duplicate callbacks are idempotent.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'rest_api_init', function () {
	register_rest_route( 'zignites-chat/v1', '/status/twilio', array(
		'methods'             => 'POST',
		'callback'            => 'zignites_chat_twilio_status_callback',
		'permission_callback' => '__return_true',
	) );
} );

/**
 * Public URL a provider should POST status callbacks to.
 *
 * @param string $provider Provider id (e.g. 'twilio').
 * @return string REST URL, or '' if the provider has no callback route.
 */
function zignites_chat_delivery_callback_url( $provider ) {
	$provider = sanitize_key( $provider );
	$url      = ( $provider === 'twilio' ) ? rest_url( 'zignites-chat/v1/status/twilio' ) : '';

	/**
	 * Filter the delivery-callback URL handed to a provider. Useful behind a
	 * reverse proxy / custom REST base.
	 *
	 * @param string $url      Default REST URL.
	 * @param string $provider Provider id.
	 */
	return (string) apply_filters( 'zignites_chat_delivery_callback_url', $url, $provider );
}

/**
 * Map a raw Twilio MessageStatus to our normalized funnel status.
 *
 * @param string $status Twilio status (queued, sent, delivered, read, …).
 * @return string One of sent|delivered|read|failed, or '' to ignore.
 */
function zignites_chat_map_twilio_status( $status ) {
	switch ( strtolower( (string) $status ) ) {
		case 'queued':
		case 'sending':
		case 'sent':
		case 'accepted':
			return 'sent';
		case 'delivered':
			return 'delivered';
		case 'read':
			return 'read';
		case 'failed':
		case 'undelivered':
			return 'failed';
		default:
			return '';
	}
}

/**
 * Map a raw Meta Cloud status to our normalized funnel status.
 *
 * @param string $status Meta status (sent, delivered, read, failed).
 * @return string One of sent|delivered|read|failed, or '' to ignore.
 */
function zignites_chat_map_meta_status( $status ) {
	switch ( strtolower( (string) $status ) ) {
		case 'sent':
			return 'sent';
		case 'delivered':
			return 'delivered';
		case 'read':
			return 'read';
		case 'failed':
			return 'failed';
		default:
			return '';
	}
}

/**
 * Fire the outbound webhook that corresponds to a freshly-applied receipt
 * status, if the webhooks module is loaded.
 *
 * @param string $applied  Normalized status that was written.
 * @param string $event_id Analytics event id.
 * @param string $provider Provider id for the payload.
 */
function zignites_chat_receipt_dispatch_webhook( $applied, $event_id, $provider ) {
	if ( ! function_exists( 'zignites_chat_dispatch_webhook' ) ) {
		return;
	}
	$map = array(
		'delivered' => 'message.delivered',
		'read'      => 'message.read',
		'failed'    => 'message.failed',
	);
	if ( isset( $map[ $applied ] ) ) {
		zignites_chat_dispatch_webhook( $map[ $applied ], array(
			'event_id' => $event_id,
			'provider' => $provider,
		) );
	}
}

/**
 * REST handler for Twilio StatusCallback POSTs.
 *
 * Requires a valid X-Twilio-Signature — an unsigned or mis-signed request is
 * rejected so the public endpoint can't be used to forge delivery states.
 *
 * @param WP_REST_Request $request
 * @return WP_REST_Response
 */
function zignites_chat_twilio_status_callback( WP_REST_Request $request ) {
	// Reuse the signature verifier from the opt-out module. Returns:
	//   null  → no signature header or no auth token configured
	//   false → signature present but invalid
	//   true  → verified
	$verified = function_exists( 'zignites_chat_verify_twilio_signature' )
		? zignites_chat_verify_twilio_signature( $request )
		: null;

	if ( $verified !== true ) {
		return new WP_REST_Response( array( 'message' => __( 'Invalid signature', 'zignites-chat' ) ), 401 );
	}

	$sid    = (string) $request->get_param( 'MessageSid' );
	$status = (string) $request->get_param( 'MessageStatus' );
	if ( $sid === '' || $status === '' ) {
		return new WP_REST_Response( array( 'message' => __( 'Missing data', 'zignites-chat' ) ), 400 );
	}

	$mapped = zignites_chat_map_twilio_status( $status );
	if ( $mapped === '' ) {
		// A status we don't track (e.g. 'queued' duplicates) — acknowledge.
		return new WP_REST_Response( array( 'message' => __( 'Ignored', 'zignites-chat' ) ), 200 );
	}

	$result = zignites_chat_analytics_apply_receipt_by_message_id( $sid, $mapped );
	if ( is_array( $result ) ) {
		zignites_chat_receipt_dispatch_webhook( $result['applied'], $result['event_id'], 'twilio' );
	}

	return new WP_REST_Response( array( 'message' => __( 'OK', 'zignites-chat' ) ), 200 );
}

/**
 * Ingest the `statuses` array from a Meta Cloud webhook payload.
 *
 * Called by the Meta webhook handler in optout.php after it has verified the
 * X-Hub-Signature-256, since Meta delivers message statuses and inbound
 * messages to the same callback URL.
 *
 * @param array $statuses The decoded `entry[].changes[].value.statuses` list.
 * @return int Number of receipts that advanced an event.
 */
function zignites_chat_ingest_meta_statuses( $statuses ) {
	if ( ! is_array( $statuses ) ) {
		return 0;
	}

	$applied_count = 0;
	foreach ( $statuses as $status_obj ) {
		if ( ! is_array( $status_obj ) ) {
			continue;
		}
		$wamid  = isset( $status_obj['id'] ) ? (string) $status_obj['id'] : '';
		$mapped = isset( $status_obj['status'] ) ? zignites_chat_map_meta_status( $status_obj['status'] ) : '';
		if ( $wamid === '' || $mapped === '' ) {
			continue;
		}

		$result = zignites_chat_analytics_apply_receipt_by_message_id( $wamid, $mapped );
		if ( is_array( $result ) ) {
			zignites_chat_receipt_dispatch_webhook( $result['applied'], $result['event_id'], 'cloud' );
			$applied_count++;
		}
	}

	return $applied_count;
}
