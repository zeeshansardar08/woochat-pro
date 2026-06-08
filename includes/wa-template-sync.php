<?php
/**
 * WhatsApp template sync from the Meta Graph API (Pro) — roadmap Q4.
 *
 * The WA Templates page (wa-templates.php) maps each message type to an
 * approved template by having the admin type the exact template name +
 * variable count by hand — easy to get wrong. This module pulls the approved
 * templates straight from the WhatsApp Business Account via
 * `GET /{waba_id}/message_templates`, caches them, and feeds the names +
 * expected variable counts back into the mapping UI as an autocomplete +
 * reference list.
 *
 * Storage:
 *   option `zignites_chat_cloud_waba_id`        — the WABA ID (saved with the
 *                                                 WA Templates form).
 *   option `zignites_chat_wa_synced_templates`  — normalized template list
 *                                                 (autoload off).
 *   option `zignites_chat_wa_synced_at`         — last successful sync time.
 *
 * The endpoint builder, response normaliser and body-parameter counter are
 * pure and unit-tested; the rest is Graph API + AJAX glue.
 *
 * @package Zignites_Chat
 */

if (!defined('ABSPATH')) exit;

/** Graph API version used for template reads — matches the Cloud provider. */
if (!defined('ZIGNITES_CHAT_GRAPH_VERSION')) {
    define('ZIGNITES_CHAT_GRAPH_VERSION', 'v19.0');
}

/* -------------------------------------------------------------------------
 * Pure helpers (no network, no DB) — unit-tested
 * ----------------------------------------------------------------------- */

/**
 * Build the message_templates Graph endpoint for a WABA. Pure.
 *
 * @param string $waba_id WhatsApp Business Account ID.
 * @param int    $limit   Page size (capped 1..250).
 * @return string Endpoint URL, or '' when the WABA ID is empty.
 */
function zignites_chat_wa_template_endpoint($waba_id, $limit = 100) {
    $waba_id = trim((string) $waba_id);
    if ($waba_id === '') {
        return '';
    }
    $limit = max(1, min(250, (int) $limit));
    return 'https://graph.facebook.com/' . ZIGNITES_CHAT_GRAPH_VERSION . '/'
        . rawurlencode($waba_id) . '/message_templates'
        . '?fields=name,status,language,category,components&limit=' . $limit;
}

/**
 * Count the body parameters a template expects, by scanning its BODY
 * component text for distinct {{n}} placeholders. Pure.
 *
 * @param mixed $components Decoded `components` array from a template node.
 * @return int Highest {{n}} index found in the body (0 when none).
 */
function zignites_chat_wa_count_body_params($components) {
    if (!is_array($components)) {
        return 0;
    }
    $max = 0;
    foreach ($components as $component) {
        if (!is_array($component)) {
            continue;
        }
        $type = strtoupper((string) ($component['type'] ?? ''));
        if ($type !== 'BODY') {
            continue;
        }
        $text = (string) ($component['text'] ?? '');
        if (preg_match_all('/\{\{\s*(\d+)\s*\}\}/', $text, $matches)) {
            foreach ($matches[1] as $index) {
                $max = max($max, (int) $index);
            }
        }
    }
    return $max;
}

/**
 * Normalise a Graph `message_templates` response page into a flat list of
 * templates. Pure.
 *
 * @param mixed $data Decoded JSON response (expects a `data` array).
 * @return array<int, array{name:string, language:string, status:string, category:string, body_params:int}>
 */
function zignites_chat_wa_sync_normalize_templates($data) {
    if (!is_array($data) || !isset($data['data']) || !is_array($data['data'])) {
        return [];
    }
    $out = [];
    foreach ($data['data'] as $node) {
        if (!is_array($node) || empty($node['name'])) {
            continue;
        }
        $out[] = [
            'name'        => sanitize_text_field((string) $node['name']),
            'language'    => sanitize_text_field((string) ($node['language'] ?? '')),
            'status'      => strtoupper(sanitize_text_field((string) ($node['status'] ?? ''))),
            'category'    => strtoupper(sanitize_text_field((string) ($node['category'] ?? ''))),
            'body_params' => zignites_chat_wa_count_body_params($node['components'] ?? []),
        ];
    }
    return $out;
}

/* -------------------------------------------------------------------------
 * Stored sync results
 * ----------------------------------------------------------------------- */

/**
 * Read the cached synced templates.
 *
 * @return array<int, array{name:string, language:string, status:string, category:string, body_params:int}>
 */
function zignites_chat_wa_get_synced_templates() {
    $stored = get_option('zignites_chat_wa_synced_templates', []);
    return is_array($stored) ? $stored : [];
}

/* -------------------------------------------------------------------------
 * Fetch from the Graph API
 * ----------------------------------------------------------------------- */

/**
 * Pull approved templates from the WABA and cache them. Follows pagination
 * up to a sane page cap.
 *
 * @param string $waba_id WABA ID (falls back to the saved option).
 * @return array{error?:string, templates?:array, synced_at?:string}
 */
function zignites_chat_wa_sync_fetch($waba_id = '') {
    $token   = trim((string) get_option('zignites_chat_cloud_token', ''));
    $waba_id = trim((string) $waba_id);
    if ($waba_id === '') {
        $waba_id = trim((string) get_option('zignites_chat_cloud_waba_id', ''));
    }

    if ($token === '' || $waba_id === '') {
        return ['error' => __('Add your Cloud API access token (General Settings) and your WhatsApp Business Account ID before syncing.', 'zignites-chat')];
    }

    $url       = zignites_chat_wa_template_endpoint($waba_id);
    $templates = [];
    $pages     = 0;
    $max_pages = (int) apply_filters('zignites_chat_wa_sync_max_pages', 10);

    while ($url && $pages < $max_pages) {
        $response = wp_remote_get($url, [
            'timeout' => 20,
            'headers' => ['Authorization' => 'Bearer ' . $token],
        ]);

        if (is_wp_error($response)) {
            return ['error' => $response->get_error_message()];
        }

        $code = (int) wp_remote_retrieve_response_code($response);
        $data = json_decode(wp_remote_retrieve_body($response), true);

        if ($code !== 200) {
            $message = is_array($data) && isset($data['error']['message'])
                ? (string) $data['error']['message']
                : sprintf(
                    /* translators: %d: HTTP status code returned by the Graph API */
                    __('Meta Graph API returned HTTP %d. Check the access token and WhatsApp Business Account ID.', 'zignites-chat'),
                    $code
                );
            return ['error' => $message];
        }

        $templates = array_merge($templates, zignites_chat_wa_sync_normalize_templates($data));
        $url       = (is_array($data) && !empty($data['paging']['next'])) ? esc_url_raw((string) $data['paging']['next']) : '';
        $pages++;
    }

    $synced_at = current_time('mysql');
    update_option('zignites_chat_cloud_waba_id', $waba_id);
    update_option('zignites_chat_wa_synced_templates', $templates, false);
    update_option('zignites_chat_wa_synced_at', $synced_at, false);

    return ['templates' => $templates, 'synced_at' => $synced_at];
}

/* -------------------------------------------------------------------------
 * AJAX — "Sync approved templates" button
 * ----------------------------------------------------------------------- */

add_action('wp_ajax_zignites_chat_sync_templates', 'zignites_chat_ajax_sync_templates');

/**
 * Handle the sync request from the WA Templates page.
 *
 * Capability: manage_options. Nonce: zignites_chat_sync_templates.
 */
function zignites_chat_ajax_sync_templates() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Unauthorized', 'zignites-chat')], 403);
    }
    if (!check_ajax_referer('zignites_chat_sync_templates', 'nonce', false)) {
        wp_send_json_error(['message' => __('Bad nonce', 'zignites-chat')], 400);
    }
    if (function_exists('zignites_chat_is_pro_active') && !zignites_chat_is_pro_active()) {
        wp_send_json_error(['message' => __('Pro required', 'zignites-chat')], 403);
    }

    $waba_id = isset($_POST['waba_id']) ? sanitize_text_field(wp_unslash($_POST['waba_id'])) : '';
    $result  = zignites_chat_wa_sync_fetch($waba_id);

    if (!empty($result['error'])) {
        wp_send_json_error(['message' => (string) $result['error']]);
    }

    $count = isset($result['templates']) ? count($result['templates']) : 0;
    wp_send_json_success([
        'count'   => $count,
        'message' => sprintf(
            /* translators: %d: number of templates pulled from Meta */
            _n('Synced %d template from Meta.', 'Synced %d templates from Meta.', $count, 'zignites-chat'),
            $count
        ),
    ]);
}
