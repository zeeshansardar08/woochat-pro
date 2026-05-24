<?php
/**
 * Twilio WhatsApp transport.
 *
 * Posts to https://api.twilio.com/2010-04-01/Accounts/{SID}/Messages.json
 * with HTTP Basic auth (SID:auth_token). The "From" / "To" fields are
 * prefixed with "whatsapp:+" per Twilio's WhatsApp channel spec.
 *
 * @see https://www.twilio.com/docs/whatsapp/api
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class ZIGNITES_CHAT_Provider_Twilio extends ZIGNITES_CHAT_Provider {

    public function name() {
        return 'twilio';
    }

    public function is_configured() {
        return get_option( 'zignites_chat_twilio_sid' )
            && get_option( 'zignites_chat_twilio_auth_token' )
            && get_option( 'zignites_chat_twilio_from' );
    }

    public function missing_credentials_message() {
        return 'Twilio Error: Missing credentials';
    }

    public function send( $to, $message ) {
        $sid       = (string) get_option( 'zignites_chat_twilio_sid' );
        $token     = (string) get_option( 'zignites_chat_twilio_auth_token' );
        $from      = (string) get_option( 'zignites_chat_twilio_from' );
        $to_number = 'whatsapp:+' . preg_replace( '/[^0-9]/', '', (string) $to );

        $response = wp_remote_post(
            'https://api.twilio.com/2010-04-01/Accounts/' . rawurlencode( $sid ) . '/Messages.json',
            [
                'method'  => 'POST',
                'timeout' => 30,
                'headers' => [
                    'Authorization' => 'Basic ' . base64_encode( $sid . ':' . $token ),
                ],
                'body' => [
                    'From' => $from,
                    'To'   => $to_number,
                    'Body' => $message,
                ],
            ]
        );

        if ( is_wp_error( $response ) ) {
            return [
                'ok'    => false,
                'error' => 'Twilio Error: ' . $response->get_error_message(),
            ];
        }

        $data = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( isset( $data['code'], $data['message'] ) ) {
            return [
                'ok'    => false,
                'error' => 'Twilio API Error: [' . $data['code'] . '] ' . $data['message'],
            ];
        }

        return [
            'ok'         => true,
            'message_id' => isset( $data['sid'] ) ? sanitize_text_field( $data['sid'] ) : '',
        ];
    }

    /**
     * Verify a Twilio Account SID + Auth Token by fetching the account
     * resource. A 401 from Twilio means the SID/token pair doesn't
     * authenticate; a 404 means the SID format is wrong.
     *
     * Accepted overrides:
     *   sid   — Twilio Account SID (defaults to saved option).
     *   token — Twilio Auth Token (defaults to saved option).
     *
     * The "From" number is not checked here because Twilio doesn't
     * expose a per-number authorisation lookup; an invalid From number
     * will be surfaced the first time we actually send a message.
     */
    public function verify_credentials( array $config = array() ) {
        $sid   = isset( $config['sid'] )
            ? (string) $config['sid']
            : (string) get_option( 'zignites_chat_twilio_sid' );
        $token = isset( $config['token'] )
            ? (string) $config['token']
            : (string) get_option( 'zignites_chat_twilio_auth_token' );

        if ( '' === $sid || '' === $token ) {
            return array(
                'ok'    => false,
                'error' => __( 'Twilio SID and Auth Token are required.', 'zignites-chat' ),
            );
        }

        $response = wp_remote_get(
            'https://api.twilio.com/2010-04-01/Accounts/' . rawurlencode( $sid ) . '.json',
            array(
                'timeout' => 15,
                'headers' => array(
                    'Authorization' => 'Basic ' . base64_encode( $sid . ':' . $token ),
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
            $message = is_array( $body ) && isset( $body['message'] )
                ? (string) $body['message']
                : sprintf(
                    /* translators: %d: HTTP status code returned by Twilio */
                    __( 'Twilio returned HTTP %d. Double-check the SID and Auth Token.', 'zignites-chat' ),
                    $code
                );
            return array(
                'ok'    => false,
                'error' => $message,
            );
        }

        $label = '';
        if ( is_array( $body ) ) {
            if ( isset( $body['friendly_name'] ) ) {
                $label = (string) $body['friendly_name'];
            } elseif ( isset( $body['sid'] ) ) {
                $label = (string) $body['sid'];
            }
        }

        return array(
            'ok'    => true,
            'label' => sanitize_text_field( $label ),
        );
    }
}
