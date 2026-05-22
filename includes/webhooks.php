<?php
/**
 * Outbound webhooks.
 *
 * Lets stores subscribe an external URL (Zapier / Make / n8n / their
 * own backend) to plugin lifecycle events. Each registered webhook
 * receives a signed JSON POST whenever one of its subscribed events
 * fires:
 *
 *   - message.sent          — zignites_chat_send_whatsapp_message succeeded.
 *   - message.delivered     — provider webhook flipped status to
 *                             delivered (currently surfaced via the
 *                             zignites_chat_track_event AJAX endpoint).
 *   - message.clicked       — recipient clicked the tracked link.
 *   - message.failed        — provider returned an error or send was
 *                             rejected before reaching the provider.
 *   - customer.opted_out    — a phone number was added to the
 *                             suppression list.
 *
 * Storage: option `zignites_chat_webhooks` (autoload off — contains secrets).
 * Each entry is `{id, url, secret, events[], active, created_at}`.
 *
 * Signing: each request carries `X-Zignites-Chat-Event` plus
 * `X-Zignites-Chat-Signature: sha256=<hmac>` where the HMAC is over the raw
 * request body using the per-webhook secret. Receivers verify by
 * recomputing — same scheme as GitHub / Stripe.
 *
 * Delivery model: first attempt is synchronous (so admins see immediate
 * feedback when sending a test event). Failures (non-2xx or transport
 * error) reschedule a retry via `wp_schedule_single_event` — same
 * primitive used for follow-up retries (PR #36) and order-confirmation
 * retries (PR #37). Backoff [5min, 15min], cap at 3 attempts.
 *
 * Logging: a 100-entry FIFO under `zignites_chat_webhook_log` (autoload off)
 * captures recent dispatches with timestamp / webhook_id / event /
 * status / HTTP code / attempt — surfaced on the Webhooks tab so admins
 * can see what fired without standing up an external listener.
 */

if (!defined('ABSPATH')) exit;

add_action('zignites_chat_webhook_retry', 'zignites_chat_webhook_retry_handler', 10, 4);
add_action('admin_post_zignites_chat_webhook_add', 'zignites_chat_webhook_add_handler');
add_action('admin_post_zignites_chat_webhook_delete', 'zignites_chat_webhook_delete_handler');
add_action('wp_ajax_zignites_chat_webhook_test', 'zignites_chat_webhook_test_ajax');

/**
 * Registry of supported event keys → human labels.
 *
 * Filterable so a third party can register additional events alongside
 * the dispatch points it has wired up.
 *
 * @return array<string, string>
 */
function zignites_chat_webhook_event_keys() {
    $keys = [
        'message.sent'        => __('Message sent', 'zignites-chat'),
        'message.delivered'   => __('Message delivered', 'zignites-chat'),
        'message.clicked'     => __('Tracked link clicked', 'zignites-chat'),
        'message.failed'      => __('Message send failed', 'zignites-chat'),
        'customer.opted_out'  => __('Customer opted out', 'zignites-chat'),
    ];
    return (array) apply_filters('zignites_chat_webhook_event_keys', $keys);
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
function zignites_chat_webhook_signature($body, $secret) {
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
 * has no DB or option access. Tests set $GLOBALS['zignites_chat_test_*'] to
 * make those deterministic where needed.
 *
 * @param array $args Caller-supplied values (`url`, `events`, etc.).
 * @return array|null Sanitised webhook, or null when the URL is invalid.
 */
function zignites_chat_sanitize_webhook($args) {
    if (!is_array($args)) return null;

    $url = isset($args['url']) ? esc_url_raw(trim((string) $args['url'])) : '';
    if ($url === '') return null;
    $parts = wp_parse_url($url);
    if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) return null;
    if (!in_array(strtolower($parts['scheme']), ['http', 'https'], true)) return null;

    $valid_events = array_keys(zignites_chat_webhook_event_keys());
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

function zignites_chat_get_webhooks() {
    $list = get_option('zignites_chat_webhooks', []);
    if (!is_array($list)) return [];
    return array_values($list);
}

function zignites_chat_save_webhooks($list) {
    if (!is_array($list)) $list = [];
    update_option('zignites_chat_webhooks', array_values($list), false);
}

function zignites_chat_get_webhook($id) {
    $id = (string) $id;
    if ($id === '') return null;
    foreach (zignites_chat_get_webhooks() as $wh) {
        if (isset($wh['id']) && $wh['id'] === $id) return $wh;
    }
    return null;
}

function zignites_chat_add_webhook($args) {
    $sanitized = zignites_chat_sanitize_webhook($args);
    if (!$sanitized) return null;
    $list = zignites_chat_get_webhooks();
    $list[] = $sanitized;
    zignites_chat_save_webhooks($list);
    return $sanitized;
}

function zignites_chat_delete_webhook($id) {
    $id = (string) $id;
    if ($id === '') return false;
    $list = zignites_chat_get_webhooks();
    $kept = [];
    $found = false;
    foreach ($list as $wh) {
        if (isset($wh['id']) && $wh['id'] === $id) {
            $found = true;
            continue;
        }
        $kept[] = $wh;
    }
    if ($found) zignites_chat_save_webhooks($kept);
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
function zignites_chat_filter_webhooks_for_event($webhooks, $event_name) {
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
 * @param string $event_name One of zignites_chat_webhook_event_keys() keys.
 * @param array  $data       Event-specific payload.
 */
function zignites_chat_dispatch_webhook($event_name, $data = []) {
    $event_name = (string) $event_name;
    if ($event_name === '') return;
    $matches = zignites_chat_filter_webhooks_for_event(zignites_chat_get_webhooks(), $event_name);
    if (empty($matches)) return;
    foreach ($matches as $wh) {
        zignites_chat_send_webhook($wh, $event_name, $data, 1);
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
function zignites_chat_webhook_payload($event_name, $data) {
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
function zignites_chat_send_webhook($webhook, $event_name, $data, $attempt = 1) {
    $url    = isset($webhook['url']) ? (string) $webhook['url'] : '';
    $secret = isset($webhook['secret']) ? (string) $webhook['secret'] : '';
    $id     = isset($webhook['id']) ? (string) $webhook['id'] : '';
    if ($url === '') return ['ok' => false, 'code' => 0, 'error' => 'missing url'];

    $payload = zignites_chat_webhook_payload($event_name, $data);
    $body    = wp_json_encode($payload);
    if ($body === false) $body = '{}';

    $signature = zignites_chat_webhook_signature($body, $secret);

    $response = wp_remote_post($url, [
        'timeout' => (int) apply_filters('zignites_chat_webhook_timeout', 8),
        'headers' => [
            'Content-Type'    => 'application/json',
            'User-Agent'      => 'ZignitesChat/' . (defined('ZIGNITES_CHAT_VERSION') ? ZIGNITES_CHAT_VERSION : 'dev'),
            'X-Zignites-Chat-Event'    => $event_name,
            'X-Zignites-Chat-Signature' => $signature,
        ],
        'body'    => $body,
    ]);

    if (is_wp_error($response)) {
        $error = $response->get_error_message();
        zignites_chat_webhook_log_dispatch($id, $event_name, 'failed', 0, $attempt, $error);
        zignites_chat_maybe_schedule_webhook_retry($id, $event_name, $data, $attempt);
        return ['ok' => false, 'code' => 0, 'error' => $error];
    }

    $code = (int) wp_remote_retrieve_response_code($response);
    if ($code >= 200 && $code < 300) {
        zignites_chat_webhook_log_dispatch($id, $event_name, 'sent', $code, $attempt, '');
        return ['ok' => true, 'code' => $code, 'error' => ''];
    }

    zignites_chat_webhook_log_dispatch($id, $event_name, 'failed', $code, $attempt, '');
    zignites_chat_maybe_schedule_webhook_retry($id, $event_name, $data, $attempt);
    return ['ok' => false, 'code' => $code, 'error' => 'http ' . $code];
}

function zignites_chat_maybe_schedule_webhook_retry($webhook_id, $event_name, $data, $attempt) {
    $max_attempts = (int) apply_filters('zignites_chat_webhook_max_attempts', 3);
    if ($attempt >= $max_attempts) return;

    $backoffs = (array) apply_filters('zignites_chat_webhook_backoff_minutes', [5, 15]);
    $idx = max(0, $attempt - 1);
    $delay = isset($backoffs[$idx]) ? (int) $backoffs[$idx] : (int) end($backoffs);
    if ($delay < 1) $delay = 5;

    wp_schedule_single_event(
        time() + ($delay * MINUTE_IN_SECONDS),
        'zignites_chat_webhook_retry',
        [(string) $webhook_id, (string) $event_name, $data, $attempt + 1]
    );
}

function zignites_chat_webhook_retry_handler($webhook_id, $event_name, $data, $attempt) {
    $webhook = zignites_chat_get_webhook($webhook_id);
    if (!$webhook || empty($webhook['active'])) return;
    zignites_chat_send_webhook($webhook, $event_name, $data, (int) $attempt);
}

/**
 * Append one dispatch row to the FIFO log option.
 *
 * @return void
 */
function zignites_chat_webhook_log_dispatch($webhook_id, $event_name, $status, $code, $attempt, $error = '') {
    $cap = (int) apply_filters('zignites_chat_webhook_log_cap', 100);
    if ($cap < 1) $cap = 100;
    $log = get_option('zignites_chat_webhook_log', []);
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
    update_option('zignites_chat_webhook_log', $log, false);
}

function zignites_chat_webhook_log_recent($webhook_id = '', $limit = 20) {
    $log = get_option('zignites_chat_webhook_log', []);
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

function zignites_chat_webhook_add_handler() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Unauthorized', 'zignites-chat'), '', ['response' => 403]);
    }
    check_admin_referer('zignites_chat_webhook_add', 'zignites_chat_webhook_add_nonce');

    $url    = isset($_POST['zignites_chat_webhook_url']) ? sanitize_text_field(wp_unslash($_POST['zignites_chat_webhook_url'])) : '';
    $events = isset($_POST['zignites_chat_webhook_events']) && is_array($_POST['zignites_chat_webhook_events'])
        ? array_map('sanitize_text_field', wp_unslash($_POST['zignites_chat_webhook_events']))
        : [];

    $created = zignites_chat_add_webhook(['url' => $url, 'events' => $events]);

    $msg = $created ? 'added' : 'invalid';
    wp_safe_redirect(add_query_arg(['page' => 'zignites-chat-webhooks', 'zignites_chat_webhook_msg' => $msg], admin_url('admin.php')));
    exit;
}

function zignites_chat_webhook_delete_handler() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Unauthorized', 'zignites-chat'), '', ['response' => 403]);
    }
    check_admin_referer('zignites_chat_webhook_delete', 'zignites_chat_webhook_delete_nonce');

    $id = isset($_REQUEST['webhook_id']) ? sanitize_text_field(wp_unslash($_REQUEST['webhook_id'])) : '';
    $deleted = zignites_chat_delete_webhook($id);

    wp_safe_redirect(add_query_arg([
        'page'              => 'zignites-chat-webhooks',
        'zignites_chat_webhook_msg'  => $deleted ? 'deleted' : 'invalid',
    ], admin_url('admin.php')));
    exit;
}

function zignites_chat_webhook_test_ajax() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Unauthorized', 'zignites-chat')], 403);
    }
    if (!check_ajax_referer('zignites_chat_webhook_test', 'nonce', false)) {
        wp_send_json_error(['message' => __('Bad nonce', 'zignites-chat')], 400);
    }

    $id = isset($_POST['webhook_id']) ? sanitize_text_field(wp_unslash($_POST['webhook_id'])) : '';
    $webhook = zignites_chat_get_webhook($id);
    if (!$webhook) {
        wp_send_json_error(['message' => __('Webhook not found.', 'zignites-chat')], 404);
    }

    $result = zignites_chat_send_webhook($webhook, 'webhook.test', [
        'note' => 'This is a test event triggered from the Zignites Chat admin.',
    ], 1);

    if (!empty($result['ok'])) {
        wp_send_json_success([
            'code'    => $result['code'],
            'message' => sprintf(
                /* translators: %d is the HTTP response code */
                __('Test fired — receiver responded %d.', 'zignites-chat'),
                (int) $result['code']
            ),
        ]);
    }

    wp_send_json_error([
        'code'    => $result['code'],
        'message' => sprintf(
            /* translators: %s is the failure reason (HTTP code or transport error) */
            __('Test failed: %s', 'zignites-chat'),
            $result['error'] !== '' ? $result['error'] : 'unknown'
        ),
    ], 502);
}
