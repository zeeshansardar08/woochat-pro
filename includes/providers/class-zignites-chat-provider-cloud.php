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
}
