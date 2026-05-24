<?php
/**
 * Abstract base for Zignites Chat WhatsApp providers.
 *
 * Each concrete provider speaks one external API (Twilio, Meta Cloud,
 * 360dialog, etc.) and is responsible for: validating its own
 * credentials, formatting the destination number, and translating the
 * upstream response into a uniform { ok, message_id, error } shape that
 * the dispatcher in includes/messaging.php knows how to log + surface
 * through analytics.
 *
 * Adding a new provider is two steps:
 *   1. Subclass ZIGNITES_CHAT_Provider and implement the abstract methods.
 *   2. add_filter( 'zignites_chat_providers', fn( $p ) => $p + [ 'foo' => MyClass::class ] );
 */

if ( ! defined( 'ABSPATH' ) ) exit;

abstract class ZIGNITES_CHAT_Provider {

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

    /**
     * Cheaply verify that the credentials are accepted by the upstream API
     * without sending a real message.
     *
     * Implementations should hit a low-cost read endpoint (account fetch,
     * phone-info lookup) and translate the upstream response into the
     * uniform { ok, label?, error? } shape. The `label` field is a short
     * human-readable identifier returned by the API (account friendly name,
     * display phone number) so the UI can confirm the right account was
     * reached.
     *
     * Accepting a `$config` override lets the Test Connection button
     * validate credentials the admin has just pasted into the form, before
     * those values are persisted via options.php.
     *
     * @param array $config Optional overrides. Concrete providers document
     *                      their accepted keys.
     * @return array{ok:bool, label?:string, error?:string}
     */
    abstract public function verify_credentials( array $config = array() );
}
