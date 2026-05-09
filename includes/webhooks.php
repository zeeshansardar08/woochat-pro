<?php
/**
 * Outbound webhooks.
 *
 * Lets stores subscribe an external URL (Zapier / Make / n8n / their
 * own backend) to plugin lifecycle events. Each registered webhook
 * receives a signed JSON POST whenever one of its subscribed events
 * fires:
 *
 *   - message.sent          — wcwp_send_whatsapp_message succeeded.
 *   - message.delivered     — provider webhook flipped status to
 *                             delivered (currently surfaced via the
 *                             wcwp_track_event AJAX endpoint).
 *   - message.clicked       — recipient clicked the tracked link.
 *   - message.failed        — provider returned an error or send was
 *                             rejected before reaching the provider.
 *   - customer.opted_out    — a phone number was added to the
 *                             suppression list.
 *
 * Storage: option `wcwp_webhooks` (autoload off — contains secrets).
 * Each entry is `{id, url, secret, events[], active, created_at}`.
 *
 * Signing: each request carries `X-WCWP-Event` plus
 * `X-WCWP-Signature: sha256=<hmac>` where the HMAC is over the raw
 * request body using the per-webhook secret. Receivers verify by
 * recomputing — same scheme as GitHub / Stripe.
 *
 * Delivery model: first attempt is synchronous (so admins see immediate
 * feedback when sending a test event). Failures (non-2xx or transport
 * error) reschedule a retry via `wp_schedule_single_event` — same
 * primitive used for follow-up retries (PR #36) and order-confirmation
 * retries (PR #37). Backoff [5min, 15min], cap at 3 attempts.
 *
 * Logging: a 100-entry FIFO under `wcwp_webhook_log` (autoload off)
 * captures recent dispatches with timestamp / webhook_id / event /
 * status / HTTP code / attempt — surfaced on the Webhooks tab so admins
 * can see what fired without standing up an external listener.
 */

if (!defined('ABSPATH')) exit;

add_action('wcwp_webhook_retry', 'wcwp_webhook_retry_handler', 10, 4);
add_action('admin_post_wcwp_webhook_add', 'wcwp_webhook_add_handler');
add_action('admin_post_wcwp_webhook_delete', 'wcwp_webhook_delete_handler');
add_action('wp_ajax_wcwp_webhook_test', 'wcwp_webhook_test_ajax');

/**
 * Registry of supported event keys → human labels.
 *
 * Filterable so a third party can register additional events alongside
 * the dispatch points it has wired up.
 *
 * @return array<string, string>
 */
function wcwp_webhook_event_keys() {
    $keys = [
        'message.sent'        => __('Message sent', 'woochat-pro'),
        'message.delivered'   => __('Message delivered', 'woochat-pro'),
        'message.clicked'     => __('Tracked link clicked', 'woochat-pro'),
        'message.failed'      => __('Message send failed', 'woochat-pro'),
        'customer.opted_out'  => __('Customer opted out', 'woochat-pro'),
    ];
    return (array) apply_filters('wcwp_webhook_event_keys', $keys);
}

/**
 * Compute a `sha256=<hex>` signature for a webhook payload.
 *
 * Pure helper — separated so the test suite can pin the wire format
 * (matching GitHub/Stripe's `sha256=hex` convention) without rebuilding
 * the dispatcher.
 *
 * @param string $body   The raw JSON body that will be POSTed.
 * @param string $secret The per-webhook secret.
 * @return string `sha256=<hexdigest>`, or '' if either input is empty.
 */
function wcwp_webhook_signature($body, $secret) {
    $body   = (string) $body;
    $secret = (string) $secret;
    if ($body === '' || $secret === '') return '';
    return 'sha256=' . hash_hmac('sha256', $body, $secret);
}

/**
 * Sanitise + normalise a webhook configuration before save.
 *
 * Drops invalid URLs, dedupes/filters events to the supported set,
 * generates an id + secret if missing. Returns null when the URL is
 * unusable.
 *
 * Pure-ish: uses esc_url_raw / wp_generate_password / current_time but
 * has no DB or option access. Tests set $GLOBALS['wcwp_test_*'] to
 * make those deterministic where needed.
 *
 * @param array $args Caller-supplied values (`url`, `events`, etc.).
 * @return array|null Sanitised webhook, or null when the URL is invalid.
 */
function wcwp_sanitize_webhook($args) {
    if (!is_array($args)) return null;

    $url = isset($args['url']) ? esc_url_raw(trim((string) $args['url'])) : '';
    if ($url === '') return null;
    $parts = wp_parse_url($url);
    if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) return null;
    if (!in_array(strtolower($parts['scheme']), ['http', 'https'], true)) return null;

    $valid_events = array_keys(wcwp_webhook_event_keys());
    $requested = isset($args['events']) && is_array($args['events']) ? $args['events'] : [];
    $events = [];
    foreach ($requested as $e) {
        $e = is_string($e) ? trim($e) : '';
        if ($e !== '' && in_array($e, $valid_events, true)) $events[$e] = true;
    }
    $events = array_keys($events);
    if (empty($events)) return null;

    $id     = isset($args['id']) && $args['id'] !== '' ? (string) $args['id'] : 'wh_' . wp_generate_password(12, false, false);
    $secret = isset($args['secret']) && $args['secret'] !== '' ? (string) $args['secret'] : wp_generate_password(40, false, false);
    $active = isset($args['active']) ? (bool) $args['active'] : true;
    $created_at = isset($args['created_at']) && $args['created_at'] !== '' ? (string) $args['created_at'] : current_time('mysql');

    return [
        'id'         => $id,
        'url'        => $url,
        'secret'     => $secret,
        'events'     => $events,
        'active'     => $active,
        'created_at' => $created_at,
    ];
}

function wcwp_get_webhooks() {
    $list = get_option('wcwp_webhooks', []);
    if (!is_array($list)) return [];
    return array_values($list);
}

function wcwp_save_webhooks($list) {
    if (!is_array($list)) $list = [];
    update_option('wcwp_webhooks', array_values($list), false);
}

function wcwp_get_webhook($id) {
    $id = (string) $id;
    if ($id === '') return null;
    foreach (wcwp_get_webhooks() as $wh) {
        if (isset($wh['id']) && $wh['id'] === $id) return $wh;
    }
    return null;
}

function wcwp_add_webhook($args) {
    $sanitized = wcwp_sanitize_webhook($args);
    if (!$sanitized) return null;
    $list = wcwp_get_webhooks();
    $list[] = $sanitized;
    wcwp_save_webhooks($list);
    return $sanitized;
}

function wcwp_delete_webhook($id) {
    $id = (string) $id;
    if ($id === '') return false;
    $list = wcwp_get_webhooks();
    $kept = [];
    $found = false;
    foreach ($list as $wh) {
        if (isset($wh['id']) && $wh['id'] === $id) {
            $found = true;
            continue;
        }
        $kept[] = $wh;
    }
    if ($found) wcwp_save_webhooks($kept);
    return $found;
}

/**
 * Filter a webhook list down to the entries subscribed to one event.
 *
 * Pure helper, so the dispatcher's "who do I send to?" rule is
 * unit-testable without DB.
 *
 * @param array  $webhooks   Sanitised webhook entries.
 * @param string $event_name Event key.
 * @return array<int, array> Active webhooks subscribed to $event_name.
 */
function wcwp_filter_webhooks_for_event($webhooks, $event_name) {
    if (!is_array($webhooks) || $event_name === '') return [];
    $out = [];
    foreach ($webhooks as $wh) {
        if (empty($wh['active'])) continue;
        $events = isset($wh['events']) && is_array($wh['events']) ? $wh['events'] : [];
        if (in_array($event_name, $events, true)) $out[] = $wh;
    }
    return $out;
}

/**
 * Public dispatch entry point.
 *
 * Called from messaging.php / analytics.php / helpers.php at the
 * lifecycle touch points. No-op when no webhooks are subscribed —
 * cheap to call from hot paths.
 *
 * @param string $event_name One of wcwp_webhook_event_keys() keys.
 * @param array  $data       Event-specific payload.
 */
function wcwp_dispatch_webhook($event_name, $data = []) {
    $event_name = (string) $event_name;
    if ($event_name === '') return;
    $matches = wcwp_filter_webhooks_for_event(wcwp_get_webhooks(), $event_name);
    if (empty($matches)) return;
    foreach ($matches as $wh) {
        wcwp_send_webhook($wh, $event_name, $data, 1);
    }
}

/**
 * Build the JSON envelope sent for one event.
 *
 * Pure helper — separated so the wire shape is unit-testable and
 * stays stable across releases (receivers depend on it).
 *
 * @param string $event_name
 * @param array  $data
 * @return array{event:string, fired_at:string, data:array}
 */
function wcwp_webhook_payload($event_name, $data) {
    return [
        'event'    => (string) $event_name,
        'fired_at' => gmdate('Y-m-d\TH:i:s\Z'),
        'data'     => is_array($data) ? $data : [],
    ];
}

/**
 * POST one event to one webhook, then either log success or schedule
 * a retry. Synchronous on the first attempt so admins see immediate
 * feedback from the Test button; retries land via cron.
 *
 * @param array  $webhook
 * @param string $event_name
 * @param array  $data
 * @param int    $attempt 1-indexed.
 * @return array{ok:bool, code:int, error:string}
 */
function wcwp_send_webhook($webhook, $event_name, $data, $attempt = 1) {
    $url    = isset($webhook['url']) ? (string) $webhook['url'] : '';
    $secret = isset($webhook['secret']) ? (string) $webhook['secret'] : '';
    $id     = isset($webhook['id']) ? (string) $webhook['id'] : '';
    if ($url === '') return ['ok' => false, 'code' => 0, 'error' => 'missing url'];

    $payload = wcwp_webhook_payload($event_name, $data);
    $body    = wp_json_encode($payload);
    if ($body === false) $body = '{}';

    $signature = wcwp_webhook_signature($body, $secret);

    $response = wp_remote_post($url, [
        'timeout' => (int) apply_filters('wcwp_webhook_timeout', 8),
        'headers' => [
            'Content-Type'    => 'application/json',
            'User-Agent'      => 'WooChatPro/' . (defined('WCWP_VERSION') ? WCWP_VERSION : 'dev'),
            'X-WCWP-Event'    => $event_name,
            'X-WCWP-Signature' => $signature,
        ],
        'body'    => $body,
    ]);

    if (is_wp_error($response)) {
        $error = $response->get_error_message();
        wcwp_webhook_log_dispatch($id, $event_name, 'failed', 0, $attempt, $error);
        wcwp_maybe_schedule_webhook_retry($id, $event_name, $data, $attempt);
        return ['ok' => false, 'code' => 0, 'error' => $error];
    }

    $code = (int) wp_remote_retrieve_response_code($response);
    if ($code >= 200 && $code < 300) {
        wcwp_webhook_log_dispatch($id, $event_name, 'sent', $code, $attempt, '');
        return ['ok' => true, 'code' => $code, 'error' => ''];
    }

    wcwp_webhook_log_dispatch($id, $event_name, 'failed', $code, $attempt, '');
    wcwp_maybe_schedule_webhook_retry($id, $event_name, $data, $attempt);
    return ['ok' => false, 'code' => $code, 'error' => 'http ' . $code];
}

function wcwp_maybe_schedule_webhook_retry($webhook_id, $event_name, $data, $attempt) {
    $max_attempts = (int) apply_filters('wcwp_webhook_max_attempts', 3);
    if ($attempt >= $max_attempts) return;

    $backoffs = (array) apply_filters('wcwp_webhook_backoff_minutes', [5, 15]);
    $idx = max(0, $attempt - 1);
    $delay = isset($backoffs[$idx]) ? (int) $backoffs[$idx] : (int) end($backoffs);
    if ($delay < 1) $delay = 5;

    wp_schedule_single_event(
        time() + ($delay * MINUTE_IN_SECONDS),
        'wcwp_webhook_retry',
        [(string) $webhook_id, (string) $event_name, $data, $attempt + 1]
    );
}

function wcwp_webhook_retry_handler($webhook_id, $event_name, $data, $attempt) {
    $webhook = wcwp_get_webhook($webhook_id);
    if (!$webhook || empty($webhook['active'])) return;
    wcwp_send_webhook($webhook, $event_name, $data, (int) $attempt);
}

/**
 * Append one dispatch row to the FIFO log option.
 *
 * @return void
 */
function wcwp_webhook_log_dispatch($webhook_id, $event_name, $status, $code, $attempt, $error = '') {
    $cap = (int) apply_filters('wcwp_webhook_log_cap', 100);
    if ($cap < 1) $cap = 100;
    $log = get_option('wcwp_webhook_log', []);
    if (!is_array($log)) $log = [];
    $log[] = [
        'ts'         => current_time('mysql'),
        'webhook_id' => (string) $webhook_id,
        'event'      => (string) $event_name,
        'status'     => (string) $status,
        'code'       => (int) $code,
        'attempt'    => (int) $attempt,
        'error'      => (string) $error,
    ];
    if (count($log) > $cap) {
        $log = array_slice($log, -$cap);
    }
    update_option('wcwp_webhook_log', $log, false);
}

function wcwp_webhook_log_recent($webhook_id = '', $limit = 20) {
    $log = get_option('wcwp_webhook_log', []);
    if (!is_array($log)) return [];
    if ($webhook_id !== '') {
        $log = array_values(array_filter($log, static function ($row) use ($webhook_id) {
            return isset($row['webhook_id']) && $row['webhook_id'] === $webhook_id;
        }));
    }
    $log = array_reverse($log);
    if ($limit > 0 && count($log) > $limit) {
        $log = array_slice($log, 0, $limit);
    }
    return $log;
}

/* -------------------------------------------------------------------------
 * Admin handlers
 * ----------------------------------------------------------------------- */

function wcwp_webhook_add_handler() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Unauthorized', 'woochat-pro'), '', ['response' => 403]);
    }
    check_admin_referer('wcwp_webhook_add', 'wcwp_webhook_add_nonce');

    $url    = isset($_POST['wcwp_webhook_url']) ? sanitize_text_field(wp_unslash($_POST['wcwp_webhook_url'])) : '';
    $events = isset($_POST['wcwp_webhook_events']) && is_array($_POST['wcwp_webhook_events'])
        ? array_map('sanitize_text_field', wp_unslash($_POST['wcwp_webhook_events']))
        : [];

    $created = wcwp_add_webhook(['url' => $url, 'events' => $events]);

    $msg = $created ? 'added' : 'invalid';
    wp_safe_redirect(add_query_arg(['page' => 'wcwp-settings', 'tab' => 'webhooks', 'wcwp_webhook_msg' => $msg], admin_url('admin.php')));
    exit;
}

function wcwp_webhook_delete_handler() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Unauthorized', 'woochat-pro'), '', ['response' => 403]);
    }
    check_admin_referer('wcwp_webhook_delete', 'wcwp_webhook_delete_nonce');

    $id = isset($_REQUEST['webhook_id']) ? sanitize_text_field(wp_unslash($_REQUEST['webhook_id'])) : '';
    $deleted = wcwp_delete_webhook($id);

    wp_safe_redirect(add_query_arg([
        'page'              => 'wcwp-settings',
        'tab'               => 'webhooks',
        'wcwp_webhook_msg'  => $deleted ? 'deleted' : 'invalid',
    ], admin_url('admin.php')));
    exit;
}

function wcwp_webhook_test_ajax() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Unauthorized', 'woochat-pro')], 403);
    }
    if (!check_ajax_referer('wcwp_webhook_test', 'nonce', false)) {
        wp_send_json_error(['message' => __('Bad nonce', 'woochat-pro')], 400);
    }

    $id = isset($_POST['webhook_id']) ? sanitize_text_field(wp_unslash($_POST['webhook_id'])) : '';
    $webhook = wcwp_get_webhook($id);
    if (!$webhook) {
        wp_send_json_error(['message' => __('Webhook not found.', 'woochat-pro')], 404);
    }

    $result = wcwp_send_webhook($webhook, 'webhook.test', [
        'note' => 'This is a test event triggered from the WooChat Pro admin.',
    ], 1);

    if (!empty($result['ok'])) {
        wp_send_json_success([
            'code'    => $result['code'],
            'message' => sprintf(
                /* translators: %d is the HTTP response code */
                __('Test fired — receiver responded %d.', 'woochat-pro'),
                (int) $result['code']
            ),
        ]);
    }

    wp_send_json_error([
        'code'    => $result['code'],
        'message' => sprintf(
            /* translators: %s is the failure reason (HTTP code or transport error) */
            __('Test failed: %s', 'woochat-pro'),
            $result['error'] !== '' ? $result['error'] : 'unknown'
        ),
    ], 502);
}
