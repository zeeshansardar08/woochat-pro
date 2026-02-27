<?php
if (!defined('ABSPATH')) exit;

add_action('rest_api_init', function () {
    register_rest_route('wcwp/v1', '/optout', [
        'methods' => 'POST',
        'callback' => 'wcwp_optout_webhook_handler',
        'permission_callback' => '__return_true',
    ]);
});

function wcwp_optout_webhook_handler(WP_REST_Request $request) {
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
