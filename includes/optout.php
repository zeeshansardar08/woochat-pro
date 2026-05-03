<?php
if (!defined('ABSPATH')) exit;

add_action('rest_api_init', function () {
    register_rest_route('wcwp/v1', '/optout', [
        'methods' => 'POST',
        'callback' => 'wcwp_optout_webhook_handler',
        'permission_callback' => '__return_true',
    ]);
});

/**
 * Per-IP rate limit for the public opt-out webhook.
 *
 * The endpoint is reachable without a WordPress login, so even with a
 * 32-char random token the prudent thing is to bound how many tries any
 * single source can make. Filterable for sites that legitimately receive
 * heavy webhook traffic.
 *
 * @param string $ip Caller's IP.
 * @return bool True if request is within the limit, false if blocked.
 */
function wcwp_optout_rate_limit_ok($ip) {
    if (!$ip) return true;

    /** @var int $limit  Max requests per window. Default 30. Filtered to 0 disables the limiter. */
    $limit  = (int) apply_filters('wcwp_optout_rate_limit', 30);
    /** @var int $window Window length in seconds. Default HOUR_IN_SECONDS. */
    $window = (int) apply_filters('wcwp_optout_rate_window', HOUR_IN_SECONDS);

    if ($limit < 1 || $window < 1) return true;

    $key  = 'wcwp_optout_rate_' . md5($ip);
    $data = get_transient($key);

    if (!is_array($data) || !isset($data['count'], $data['start'])) {
        set_transient($key, ['count' => 1, 'start' => time()], $window);
        return true;
    }

    if ((time() - (int) $data['start']) > $window) {
        set_transient($key, ['count' => 1, 'start' => time()], $window);
        return true;
    }

    if ((int) $data['count'] >= $limit) {
        return false;
    }

    $data['count'] = (int) $data['count'] + 1;
    set_transient($key, $data, $window);
    return true;
}

/**
 * Verify a Twilio webhook signature.
 *
 * Twilio computes X-Twilio-Signature as
 *   base64(HMAC-SHA1(auth_token, full_url + sorted_post_params_concat))
 * where sorted_post_params_concat is "k1v1k2v2..." with keys sorted.
 *
 * Returns:
 *   - null  : not a Twilio request (no header), or no auth token configured
 *             (we cannot verify and don't want to falsely reject).
 *   - true  : signature verified.
 *   - false : signature header present but does not match — forged or misrouted.
 *
 * @param WP_REST_Request $request
 * @return bool|null
 */
function wcwp_verify_twilio_signature(WP_REST_Request $request) {
    $sig = (string) $request->get_header('x-twilio-signature');
    if ($sig === '') return null;

    $auth_token = (string) get_option('wcwp_twilio_auth_token', '');
    if ($auth_token === '') return null;

    $scheme = is_ssl() ? 'https' : 'http';
    $host   = isset($_SERVER['HTTP_HOST']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_HOST'])) : '';
    $uri    = isset($_SERVER['REQUEST_URI']) ? wp_unslash($_SERVER['REQUEST_URI']) : '';
    $url    = $scheme . '://' . $host . $uri;

    /**
     * Filter the URL used to compute the Twilio signature.
     *
     * Useful when WordPress sits behind a reverse proxy / load balancer
     * that rewrites the host or path before Twilio's request reaches PHP.
     */
    $url = (string) apply_filters('wcwp_optout_twilio_verify_url', $url, $request);

    $params = $request->get_body_params();
    if (!is_array($params)) $params = [];
    ksort($params);

    $data = $url;
    foreach ($params as $k => $v) {
        $data .= $k . (is_scalar($v) ? (string) $v : '');
    }

    $expected = base64_encode(hash_hmac('sha1', $data, $auth_token, true));
    return hash_equals($expected, $sig);
}

/**
 * Verify a Meta Cloud webhook signature.
 *
 * Meta sends X-Hub-Signature-256 as "sha256=<hex>" where the HMAC is over
 * the raw request body, keyed by the App Secret (NOT the access token).
 *
 * Returns null if the header / app secret are absent, true on match,
 * false on mismatch.
 *
 * @param WP_REST_Request $request
 * @return bool|null
 */
function wcwp_verify_meta_signature(WP_REST_Request $request) {
    $sig_header = (string) $request->get_header('x-hub-signature-256');
    if ($sig_header === '') return null;

    $secret = (string) get_option('wcwp_cloud_app_secret', '');
    if ($secret === '') return null;

    if (strpos($sig_header, 'sha256=') !== 0) return false;
    $sig = substr($sig_header, 7);

    $body     = (string) $request->get_body();
    $expected = hash_hmac('sha256', $body, $secret);

    return hash_equals($expected, $sig);
}

function wcwp_optout_webhook_handler(WP_REST_Request $request) {
    // Rate-limit before any other work — keeps token / signature checks
    // from being a probing oracle.
    $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';
    if (!wcwp_optout_rate_limit_ok($ip)) {
        return new WP_REST_Response(['message' => __('Rate limited', 'woochat-pro')], 429);
    }

    // Provider-signature verification. If a known provider's signature
    // header is present we MUST verify it; this prevents a forged request
    // that simply sends a guessed token from succeeding when the caller
    // claims to be Twilio / Meta.
    $twilio = wcwp_verify_twilio_signature($request);
    if ($twilio === false) {
        return new WP_REST_Response(['message' => __('Invalid signature', 'woochat-pro')], 401);
    }
    $meta = wcwp_verify_meta_signature($request);
    if ($meta === false) {
        return new WP_REST_Response(['message' => __('Invalid signature', 'woochat-pro')], 401);
    }

    // If no provider signature was attempted, fall back to the static
    // token (preserves existing custom-integration behavior).
    if ($twilio === null && $meta === null) {
        $token = (string) $request->get_param('token');
        if (!$token) {
            $header_token = $request->get_header('x-wcwp-token');
            if ($header_token) {
                $token = (string) $header_token;
            }
        }
        $expected = (string) get_option('wcwp_optout_webhook_token', '');
        if (!$expected || !hash_equals($expected, $token)) {
            return new WP_REST_Response(['message' => __('Unauthorized', 'woochat-pro')], 401);
        }
    }

    $from = (string) $request->get_param('from');
    $body = (string) $request->get_param('body');

    if (!$from || !$body) {
        $raw = (string) $request->get_body();
        if ($raw) {
            $json = json_decode($raw, true);
            if (is_array($json)) {
                if (isset($json['From']) || isset($json['Body'])) {
                    $from = $from ?: (string) ($json['From'] ?? '');
                    $body = $body ?: (string) ($json['Body'] ?? '');
                }
                if (!$from || !$body) {
                    $meta_from = $json['entry'][0]['changes'][0]['value']['messages'][0]['from'] ?? '';
                    $meta_body = $json['entry'][0]['changes'][0]['value']['messages'][0]['text']['body'] ?? '';
                    if ($meta_from) $from = $from ?: (string) $meta_from;
                    if ($meta_body) $body = $body ?: (string) $meta_body;
                }
            }
        }
    }

    if (!$from || !$body) {
        return new WP_REST_Response(['message' => __('Missing data', 'woochat-pro')], 400);
    }

    if (!wcwp_optout_keyword_match($body)) {
        return new WP_REST_Response(['message' => __('No opt-out keyword detected', 'woochat-pro')], 200);
    }

    $saved = wcwp_add_optout($from);
    return new WP_REST_Response([
        'message' => $saved ? __('Opted out', 'woochat-pro') : __('Invalid phone', 'woochat-pro'),
    ], $saved ? 200 : 400);
}
