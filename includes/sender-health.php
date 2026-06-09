<?php
/**
 * Sender health panel (Pro) — roadmap Q5.
 *
 * Surfaces the two numbers a WhatsApp sender most needs to watch on the
 * dashboard: the phone number's **quality rating** (GREEN / YELLOW / RED) and
 * its **messaging limit tier** (how many unique customers it may start
 * conversations with per 24h). Both live on the WhatsApp Cloud API phone
 * number node, so the panel only applies to the Cloud provider — Twilio
 * exposes no equivalent and the panel stays hidden for it.
 *
 * It reuses the Q4 template-sync plumbing: the saved Cloud token +
 * `zignites_chat_cloud_phone_id`, a single Graph read, and a cached result the
 * dashboard renders. A "Refresh" button re-pulls on demand (AJAX); there is no
 * cron — health changes slowly and an on-demand check keeps it simple.
 *
 * Storage:
 *   option `zignites_chat_sender_health` — last normalized snapshot
 *                                          (autoload off), with `checked_at`.
 *
 * The endpoint builder, response normaliser, quality-rating meta and tier
 * label are pure and unit-tested; the rest is Graph API + AJAX + render glue.
 *
 * @package Zignites_Chat
 */

if (!defined('ABSPATH')) exit;

/** Graph API version — shared with the Cloud provider / template sync (Q4). */
if (!defined('ZIGNITES_CHAT_GRAPH_VERSION')) {
    define('ZIGNITES_CHAT_GRAPH_VERSION', 'v19.0');
}

/* -------------------------------------------------------------------------
 * Pure helpers (no network, no DB) — unit-tested
 * ----------------------------------------------------------------------- */

/**
 * Build the phone-number Graph endpoint carrying the health fields. Pure.
 *
 * @param string $phone_id WhatsApp Cloud API phone number ID.
 * @return string Endpoint URL, or '' when the phone ID is empty.
 */
function zignites_chat_health_endpoint($phone_id) {
    $phone_id = trim((string) $phone_id);
    if ($phone_id === '') {
        return '';
    }
    return 'https://graph.facebook.com/' . ZIGNITES_CHAT_GRAPH_VERSION . '/'
        . rawurlencode($phone_id)
        . '?fields=quality_rating,messaging_limit_tier,display_phone_number,verified_name,name_status';
}

/**
 * Normalise a Graph phone-number node into the stable shape the panel renders.
 * Pure. Missing rating / tier collapse to 'UNKNOWN' so the caller never has to
 * special-case absent fields.
 *
 * @param mixed $data Decoded JSON node.
 * @return array{quality_rating:string, messaging_tier:string, display_phone_number:string, verified_name:string, name_status:string}
 */
function zignites_chat_health_normalize($data) {
    if (!is_array($data)) {
        $data = array();
    }
    $rating = strtoupper(trim((string) ($data['quality_rating'] ?? '')));
    $tier   = strtoupper(trim((string) ($data['messaging_limit_tier'] ?? '')));

    return array(
        'quality_rating'       => $rating !== '' ? $rating : 'UNKNOWN',
        'messaging_tier'       => $tier !== '' ? $tier : 'UNKNOWN',
        'display_phone_number' => sanitize_text_field((string) ($data['display_phone_number'] ?? '')),
        'verified_name'        => sanitize_text_field((string) ($data['verified_name'] ?? '')),
        'name_status'          => strtoupper(trim((string) ($data['name_status'] ?? ''))),
    );
}

/**
 * Map a quality rating to a display level, label and colour. Pure.
 *
 * @param string $rating GREEN | YELLOW | RED | anything else.
 * @return array{level:string, label:string, color:string}
 */
function zignites_chat_health_quality_meta($rating) {
    switch (strtoupper(trim((string) $rating))) {
        case 'GREEN':
            return array('level' => 'green', 'label' => __('High', 'zignites-chat'), 'color' => '#46b450');
        case 'YELLOW':
            return array('level' => 'yellow', 'label' => __('Medium', 'zignites-chat'), 'color' => '#dba617');
        case 'RED':
            return array('level' => 'red', 'label' => __('Low', 'zignites-chat'), 'color' => '#d63638');
        default:
            return array('level' => 'unknown', 'label' => __('Unknown', 'zignites-chat'), 'color' => '#8c8f94');
    }
}

/**
 * Turn a messaging-limit tier into a human label. Pure.
 *
 * @param string $tier TIER_250 | TIER_1K | TIER_10K | TIER_100K | TIER_UNLIMITED | …
 * @return string
 */
function zignites_chat_health_tier_label($tier) {
    $tier = strtoupper(trim((string) $tier));
    if ($tier === 'TIER_UNLIMITED') {
        return __('Unlimited', 'zignites-chat');
    }
    $counts = array(
        'TIER_50'   => '50',
        'TIER_250'  => '250',
        'TIER_1K'   => '1,000',
        'TIER_10K'  => '10,000',
        'TIER_100K' => '100,000',
    );
    if (isset($counts[$tier])) {
        return sprintf(
            /* translators: %s: number of unique customers the sender may message per 24 hours */
            __('%s customers / 24h', 'zignites-chat'),
            $counts[$tier]
        );
    }
    return __('Unknown', 'zignites-chat');
}

/* -------------------------------------------------------------------------
 * Stored snapshot
 * ----------------------------------------------------------------------- */

/**
 * Read the cached health snapshot.
 *
 * @return array Empty array when nothing has been fetched yet.
 */
function zignites_chat_health_get_cached() {
    $stored = get_option('zignites_chat_sender_health', array());
    return is_array($stored) ? $stored : array();
}

/* -------------------------------------------------------------------------
 * Fetch from the Graph API
 * ----------------------------------------------------------------------- */

/**
 * Pull the phone number's health from the Graph API and cache it.
 *
 * @param string $phone_id Phone number ID (falls back to the saved option).
 * @return array Normalised snapshot (with `checked_at`), or ['error' => …].
 */
function zignites_chat_health_fetch($phone_id = '') {
    $token    = trim((string) get_option('zignites_chat_cloud_token', ''));
    $phone_id = trim((string) $phone_id);
    if ($phone_id === '') {
        $phone_id = trim((string) get_option('zignites_chat_cloud_phone_id', ''));
    }

    if ($token === '' || $phone_id === '') {
        return array('error' => __('Add your Cloud API access token and Phone Number ID (General Settings) to see sender health.', 'zignites-chat'));
    }

    $response = wp_remote_get(zignites_chat_health_endpoint($phone_id), array(
        'timeout' => 20,
        'headers' => array('Authorization' => 'Bearer ' . $token),
    ));

    if (is_wp_error($response)) {
        return array('error' => $response->get_error_message());
    }

    $code = (int) wp_remote_retrieve_response_code($response);
    $data = json_decode(wp_remote_retrieve_body($response), true);

    if ($code !== 200) {
        $message = is_array($data) && isset($data['error']['message'])
            ? (string) $data['error']['message']
            : sprintf(
                /* translators: %d: HTTP status code returned by the Graph API */
                __('Meta Graph API returned HTTP %d. Check the access token and Phone Number ID.', 'zignites-chat'),
                $code
            );
        return array('error' => $message);
    }

    $snapshot               = zignites_chat_health_normalize($data);
    $snapshot['checked_at'] = current_time('mysql');
    update_option('zignites_chat_sender_health', $snapshot, false);

    return $snapshot;
}

/* -------------------------------------------------------------------------
 * AJAX — "Refresh" button on the dashboard
 * ----------------------------------------------------------------------- */

add_action('wp_ajax_zignites_chat_refresh_sender_health', 'zignites_chat_ajax_refresh_sender_health');

/**
 * Handle the dashboard refresh request.
 *
 * Capability: manage_options. Nonce: zignites_chat_sender_health.
 */
function zignites_chat_ajax_refresh_sender_health() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => __('Unauthorized', 'zignites-chat')), 403);
    }
    if (!check_ajax_referer('zignites_chat_sender_health', 'nonce', false)) {
        wp_send_json_error(array('message' => __('Bad nonce', 'zignites-chat')), 400);
    }
    if (function_exists('zignites_chat_is_pro_active') && !zignites_chat_is_pro_active()) {
        wp_send_json_error(array('message' => __('Pro required', 'zignites-chat')), 403);
    }

    $result = zignites_chat_health_fetch();

    if (!empty($result['error'])) {
        wp_send_json_error(array('message' => (string) $result['error']));
    }

    wp_send_json_success(array('message' => __('Sender health refreshed.', 'zignites-chat')));
}

/* -------------------------------------------------------------------------
 * Dashboard panel
 * ----------------------------------------------------------------------- */

/**
 * Render the sender-health panel on the dashboard. No-op unless this is a Pro
 * build on the Cloud provider — quality rating / messaging tier are Cloud-only
 * concepts.
 *
 * @return void
 */
function zignites_chat_render_sender_health_panel() {
    if (function_exists('zignites_chat_is_pro_active') && !zignites_chat_is_pro_active()) {
        return;
    }
    if (get_option('zignites_chat_api_provider', 'twilio') !== 'cloud') {
        return;
    }

    $health    = zignites_chat_health_get_cached();
    $has_data  = !empty($health) && isset($health['quality_rating']);
    $quality   = zignites_chat_health_quality_meta($has_data ? $health['quality_rating'] : 'UNKNOWN');
    $tier      = zignites_chat_health_tier_label($has_data ? $health['messaging_tier'] : 'UNKNOWN');
    $phone     = $has_data ? (string) ($health['display_phone_number'] ?? '') : '';
    $checked   = $has_data ? (string) ($health['checked_at'] ?? '') : '';
    ?>
    <h2 style="margin-top:28px;"><?php esc_html_e('Sender Health', 'zignites-chat'); ?></h2>
    <div class="zignites-chat-dashboard-widget" id="zignites-chat-sender-health">
        <div class="zignites-chat-dashboard-widget-stats">
            <div class="zignites-chat-dashboard-widget-stat">
                <span class="dashicons dashicons-shield-alt" style="color:<?php echo esc_attr($quality['color']); ?>;"></span>
                <div class="zignites-chat-stat-value" style="color:<?php echo esc_attr($quality['color']); ?>;"><?php echo esc_html($quality['label']); ?></div>
                <div class="zignites-chat-stat-label"><?php esc_html_e('Quality Rating', 'zignites-chat'); ?></div>
            </div>
            <div class="zignites-chat-dashboard-widget-stat">
                <span class="dashicons dashicons-chart-bar"></span>
                <div class="zignites-chat-stat-value"><?php echo esc_html($tier); ?></div>
                <div class="zignites-chat-stat-label"><?php esc_html_e('Messaging Limit', 'zignites-chat'); ?></div>
            </div>
            <div class="zignites-chat-dashboard-widget-stat">
                <span class="dashicons dashicons-whatsapp"></span>
                <div class="zignites-chat-stat-value"><?php echo esc_html($phone !== '' ? $phone : '—'); ?></div>
                <div class="zignites-chat-stat-label"><?php esc_html_e('Sender Number', 'zignites-chat'); ?></div>
            </div>
        </div>
        <div class="zignites-chat-dashboard-widget-actions">
            <button type="button" class="button" id="zignites-chat-refresh-sender-health"><?php esc_html_e('Refresh', 'zignites-chat'); ?></button>
            <span class="description" id="zignites-chat-sender-health-status" style="margin-left:10px;">
                <?php
                if ($checked !== '') {
                    printf(
                        /* translators: %s: human-readable time since the last health check */
                        esc_html__('Last checked %s ago', 'zignites-chat'),
                        esc_html(human_time_diff(strtotime($checked), current_time('timestamp')))
                    );
                } else {
                    esc_html_e('Not checked yet — click Refresh.', 'zignites-chat');
                }
                ?>
            </span>
        </div>
    </div>
    <?php
}
