<?php
/**
 * Abstract base for WooChat Pro WhatsApp providers.
 *
 * Each concrete provider speaks one external API (Twilio, Meta Cloud,
 * 360dialog, etc.) and is responsible for: validating its own
 * credentials, formatting the destination number, and translating the
 * upstream response into a uniform { ok, message_id, error } shape that
 * the dispatcher in includes/messaging.php knows how to log + surface
 * through analytics.
 *
 * Adding a new provider is two steps:
 *   1. Subclass WCWP_Provider and implement the abstract methods.
 *   2. add_filter( 'wcwp_providers', fn( $p ) => $p + [ 'foo' => MyClass::class ] );
 */

if ( ! defined( 'ABSPATH' ) ) exit;

abstract class WCWP_Provider {

    /**
     * Send a WhatsApp message through this provider.
     *
     * Implementations MUST NOT throw; return an array describing the
     * outcome instead. The dispatcher routes everything else (logging,
     * analytics, opt-out, test mode).
     *
     * @param string $to      Raw phone number from billing data.
     * @param string $message Message body.
     * @return array{ok:bool, message_id?:string, error?:string}
     */
    abstract public function send( $to, $message );

    /**
     * Whether all credentials this provider needs are configured.
     */
    abstract public function is_configured();

    /**
     * Stable, lowercase identifier (also stored on analytics rows).
     */
    abstract public function name();

    /**
     * Pre-formatted "missing credentials" line for the log.
     */
    abstract public function missing_credentials_message();
}
