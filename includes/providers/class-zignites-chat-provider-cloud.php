<?php
/**
 * Meta WhatsApp Cloud API transport.
 *
 * Posts to https://graph.facebook.com/v19.0/{phone_id}/messages with a
 * Bearer token. Body is a JSON envelope with messaging_product=whatsapp.
 *
 * @see https://developers.facebook.com/docs/whatsapp/cloud-api/reference/messages
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class ZIGNITES_CHAT_Provider_Cloud extends ZIGNITES_CHAT_Provider {

    public function name() {
        return 'cloud';
    }

    public function is_configured() {
        return get_option( 'zignites_chat_cloud_token' )
            && get_option( 'zignites_chat_cloud_phone_id' )
            && get_option( 'zignites_chat_cloud_from' );
    }

    public function missing_credentials_message() {
        return 'WhatsApp Cloud API Error: Missing credentials';
    }

    public function send( $to, $message ) {
        $token     = (string) get_option( 'zignites_chat_cloud_token' );
        $phone_id  = (string) get_option( 'zignites_chat_cloud_phone_id' );
        $to_number = preg_replace( '/[^0-9]/', '', (string) $to );

        $response = wp_remote_post(
            'https://graph.facebook.com/v19.0/' . rawurlencode( $phone_id ) . '/messages',
            [
                'method'  => 'POST',
                'timeout' => 30,
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type'  => 'application/json',
                ],
                'body' => wp_json_encode( [
                    'messaging_product' => 'whatsapp',
                    'to'                => $to_number,
                    'type'              => 'text',
                    'text'              => [ 'body' => $message ],
                ] ),
            ]
        );

        if ( is_wp_error( $response ) ) {
            return [
                'ok'    => false,
                'error' => 'WhatsApp Cloud API Error: ' . $response->get_error_message(),
            ];
        }

        $data = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( isset( $data['error'] ) ) {
            return [
                'ok'    => false,
                'error' => 'WhatsApp Cloud API Error: ' . wp_json_encode( $data['error'] ),
            ];
        }

        return [
            'ok'         => true,
            'message_id' => isset( $data['messages'][0]['id'] )
                ? sanitize_text_field( $data['messages'][0]['id'] )
                : '',
        ];
    }

    /**
     * Verify a Meta Cloud API access token + phone-number ID pair by
     * fetching the phone resource's display_phone_number field. A 401
     * means the token can't see the phone; a 404 means the phone ID is
     * wrong; either way the API returns a structured error body.
     *
     * Accepted overrides:
     *   token    — Meta access token (defaults to saved option).
     *   phone_id — WhatsApp phone-number ID (defaults to saved option).
     */
    public function verify_credentials( array $config = array() ) {
        $token    = isset( $config['token'] )
            ? (string) $config['token']
            : (string) get_option( 'zignites_chat_cloud_token' );
        $phone_id = isset( $config['phone_id'] )
            ? (string) $config['phone_id']
            : (string) get_option( 'zignites_chat_cloud_phone_id' );

        if ( '' === $token || '' === $phone_id ) {
            return array(
                'ok'    => false,
                'error' => __( 'Cloud API token and Phone Number ID are required.', 'zignites-chat' ),
            );
        }

        $response = wp_remote_get(
            'https://graph.facebook.com/v19.0/' . rawurlencode( $phone_id ) . '?fields=display_phone_number,verified_name',
            array(
                'timeout' => 15,
                'headers' => array(
                    'Authorization' => 'Bearer ' . $token,
                ),
            )
        );

        if ( is_wp_error( $response ) ) {
            return array(
                'ok'    => false,
                'error' => $response->get_error_message(),
            );
        }

        $code = (int) wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( 200 !== $code ) {
            $message = is_array( $body ) && isset( $body['error']['message'] )
                ? (string) $body['error']['message']
                : sprintf(
                    /* translators: %d: HTTP status code returned by the Cloud API */
                    __( 'Meta Cloud API returned HTTP %d. Double-check the access token and Phone Number ID.', 'zignites-chat' ),
                    $code
                );
            return array(
                'ok'    => false,
                'error' => $message,
            );
        }

        $label = '';
        if ( is_array( $body ) ) {
            $parts = array();
            if ( ! empty( $body['verified_name'] ) ) {
                $parts[] = (string) $body['verified_name'];
            }
            if ( ! empty( $body['display_phone_number'] ) ) {
                $parts[] = (string) $body['display_phone_number'];
            }
            $label = implode( ' · ', $parts );
        }

        return array(
            'ok'    => true,
            'label' => sanitize_text_field( $label ),
        );
    }
}
