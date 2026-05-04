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

class WCWP_Provider_Twilio extends WCWP_Provider {

    public function name() {
        return 'twilio';
    }

    public function is_configured() {
        return get_option( 'wcwp_twilio_sid' )
            && get_option( 'wcwp_twilio_auth_token' )
            && get_option( 'wcwp_twilio_from' );
    }

    public function missing_credentials_message() {
        return 'Twilio Error: Missing credentials';
    }

    public function send( $to, $message ) {
        $sid       = (string) get_option( 'wcwp_twilio_sid' );
        $token     = (string) get_option( 'wcwp_twilio_auth_token' );
        $from      = (string) get_option( 'wcwp_twilio_from' );
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
}
