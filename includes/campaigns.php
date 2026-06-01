<?php
/**
 * Bulk-messaging campaigns.
 *
 * One-shot bulk WhatsApp send to a customer segment, throttled via
 * WP-Cron. Each recipient is persisted as its own row in
 * {prefix}zignites_chat_campaign_recipients so progress survives crashes,
 * page reloads, and concurrent admin sessions. The dispatcher
 * (zignites_chat_send_whatsapp_message) is reused per recipient so opt-out,
 * test mode, analytics, and provider routing are handled centrally.
 */

if (!defined('ABSPATH')) exit;

/*
 * Direct SQL below runs against the plugin's own custom tables. Every
 * user-supplied value is bound through $wpdb->prepare(); the only values
 * interpolated into query strings are table names derived from
 * $wpdb->prefix. This transactional data is not object-cached.
 */
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, PluginCheck.Security.DirectDB.UnescapedDBParameter

add_action('zignites_chat_process_campaign', 'zignites_chat_campaign_process_chunk');

/* -------------------------------------------------------------------------
 * Schema
 * ----------------------------------------------------------------------- */

function zignites_chat_campaigns_table_name() {
    global $wpdb;
    return $wpdb->prefix . 'zignites_chat_campaigns';
}

function zignites_chat_campaign_recipients_table_name() {
    global $wpdb;
    return $wpdb->prefix . 'zignites_chat_campaign_recipients';
}

/**
 * dbDelta-based, idempotent. Called from migration v3 and from the
 * activation hook so a fresh install lands the tables before the first
 * admin_init migration tick.
 */
function zignites_chat_create_campaign_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    $campaigns       = zignites_chat_campaigns_table_name();
    $recipients      = zignites_chat_campaign_recipients_table_name();

    $sql_campaigns = "CREATE TABLE {$campaigns} (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        name VARCHAR(190) NOT NULL,
        template TEXT NOT NULL,
        segment_type VARCHAR(40) NOT NULL,
        segment_meta TEXT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'queued',
        total_count INT UNSIGNED NOT NULL DEFAULT 0,
        sent_count INT UNSIGNED NOT NULL DEFAULT 0,
        failed_count INT UNSIGNED NOT NULL DEFAULT 0,
        skipped_count INT UNSIGNED NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL,
        completed_at DATETIME NULL,
        PRIMARY KEY  (id),
        KEY status (status)
    ) {$charset_collate};";

    $sql_recipients = "CREATE TABLE {$recipients} (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        campaign_id BIGINT UNSIGNED NOT NULL,
        phone VARCHAR(40) NOT NULL,
        customer_name VARCHAR(190) NULL DEFAULT '',
        status VARCHAR(20) NOT NULL DEFAULT 'pending',
        attempt_count TINYINT UNSIGNED NOT NULL DEFAULT 0,
        last_error VARCHAR(255) NULL DEFAULT '',
        sent_at DATETIME NULL,
        PRIMARY KEY  (id),
        KEY campaign_status (campaign_id, status)
    ) {$charset_collate};";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql_campaigns);
    dbDelta($sql_recipients);
}

/* -------------------------------------------------------------------------
 * Campaign CRUD
 * ----------------------------------------------------------------------- */

/**
 * Create a campaign and enqueue its recipients.
 *
 * @param array $args {
 *   @type string $name         Required.
 *   @type string $template     Required. Body with {name}/{site}/{currency_symbol} placeholders.
 *   @type string $segment_type Required. One of the keys returned by zignites_chat_campaign_segment_types().
 *   @type array  $segment_meta Optional. Segment-specific filters (e.g. {days: 30}).
 * }
 * @return int|WP_Error Campaign id, or WP_Error on validation failure.
 */
function zignites_chat_campaign_create($args) {
    global $wpdb;

    $name         = isset($args['name']) ? sanitize_text_field($args['name']) : '';
    $template     = isset($args['template']) ? zignites_chat_sanitize_textarea($args['template']) : '';
    $segment_type = isset($args['segment_type']) ? sanitize_key($args['segment_type']) : '';
    $segment_meta = isset($args['segment_meta']) && is_array($args['segment_meta']) ? $args['segment_meta'] : [];

    if ($name === '')     return new WP_Error('zignites_chat_campaign_name_required', __('Campaign name is required.', 'zignites-chat'));
    if ($template === '') return new WP_Error('zignites_chat_campaign_template_required', __('Message template is required.', 'zignites-chat'));
    if (!array_key_exists($segment_type, zignites_chat_campaign_segment_types())) {
        return new WP_Error('zignites_chat_campaign_segment_invalid', __('Unknown segment.', 'zignites-chat'));
    }

    $recipients = zignites_chat_campaign_resolve_segment($segment_type, $segment_meta);
    if (empty($recipients)) {
        return new WP_Error('zignites_chat_campaign_no_recipients', __('No matching customers — campaign not created.', 'zignites-chat'));
    }

    $now = current_time('mysql');
    $wpdb->insert(zignites_chat_campaigns_table_name(), [
        'name'         => $name,
        'template'     => $template,
        'segment_type' => $segment_type,
        'segment_meta' => wp_json_encode($segment_meta),
        'status'       => 'queued',
        'total_count'  => count($recipients),
        'created_at'   => $now,
    ], ['%s', '%s', '%s', '%s', '%s', '%d', '%s']);

    $campaign_id = (int) $wpdb->insert_id;
    if ($campaign_id <= 0) {
        return new WP_Error('zignites_chat_campaign_insert_failed', __('Could not create campaign.', 'zignites-chat'));
    }

    $rt = zignites_chat_campaign_recipients_table_name();
    foreach ($recipients as $r) {
        $wpdb->insert($rt, [
            'campaign_id'   => $campaign_id,
            'phone'         => $r['phone'],
            'customer_name' => isset($r['name']) ? $r['name'] : '',
            'status'        => 'pending',
            'attempt_count' => 0,
        ], ['%d', '%s', '%s', '%s', '%d']);
    }

    // Kick off the first chunk shortly after return so the admin sees
    // immediate progress without sitting through an HTTP fan-out.
    wp_schedule_single_event(time() + 5, 'zignites_chat_process_campaign', [$campaign_id]);

    return $campaign_id;
}

/**
 * Fetch a single campaign row by id.
 *
 * @param int $campaign_id
 * @return array|null Campaign row, or null when not found.
 */
function zignites_chat_campaign_get($campaign_id) {
    global $wpdb;
    $campaign_id = (int) $campaign_id;
    if ($campaign_id <= 0) return null;
    $row = $wpdb->get_row(
        $wpdb->prepare("SELECT * FROM " . zignites_chat_campaigns_table_name() . " WHERE id = %d", $campaign_id),
        ARRAY_A
    );
    return $row ?: null;
}

/**
 * List recent campaigns, newest first.
 *
 * @param int $limit Maximum rows to return (clamped 1-200). Default 20.
 * @return array<int, array> Campaign rows.
 */
function zignites_chat_campaign_list($limit = 20) {
    global $wpdb;
    $limit = max(1, min(200, (int) $limit));
    return $wpdb->get_results(
        $wpdb->prepare(
            'SELECT * FROM ' . zignites_chat_campaigns_table_name() . ' ORDER BY id DESC LIMIT %d',
            $limit
        ),
        ARRAY_A
    ) ?: [];
}

/* -------------------------------------------------------------------------
 * Segments
 * ----------------------------------------------------------------------- */

function zignites_chat_campaign_segment_types() {
    return [
        'all_customers' => __('All customers with phone', 'zignites-chat'),
        'recent_orders' => __('Customers who ordered recently', 'zignites-chat'),
    ];
}

/**
 * Walk WC orders in pages, dedupe by normalized phone, drop opt-outs.
 *
 * @return array<int, array{phone:string, name:string}>
 */
function zignites_chat_campaign_resolve_segment($segment_type, $segment_meta = []) {
    if (!function_exists('wc_get_orders')) return [];

    $query = [
        'limit'  => 500,
        'paged'  => 1,
        'return' => 'objects',
    ];

    if ($segment_type === 'recent_orders') {
        $days = isset($segment_meta['days']) ? max(1, (int) $segment_meta['days']) : 30;
        $cutoff = gmdate('Y-m-d 00:00:00', time() - ($days * DAY_IN_SECONDS));
        $query['date_created'] = '>=' . $cutoff;
    }

    $seen = [];
    $recipients = [];
    while (true) {
        $orders = wc_get_orders($query);
        if (empty($orders) || !is_array($orders)) break;

        foreach ($orders as $order) {
            $phone = zignites_chat_normalize_phone($order->get_billing_phone());
            if (!$phone || isset($seen[$phone])) continue;
            if (zignites_chat_is_opted_out($phone)) continue;
            $seen[$phone] = true;
            $recipients[] = [
                'phone' => $phone,
                'name'  => sanitize_text_field($order->get_billing_first_name()),
            ];
        }

        if (count($orders) < $query['limit']) break;
        $query['paged']++;

        // Hard cap to prevent runaway memory on giant stores; campaigns
        // with > 25k recipients are out of scope for this UI.
        if (count($recipients) >= 25000) break;
    }

    return $recipients;
}

/* -------------------------------------------------------------------------
 * Template
 * ----------------------------------------------------------------------- */

/**
 * Substitute the campaign-supported placeholders in a template.
 *
 * Available: {name}, {site}, {currency_symbol}. No order/cart context
 * is available for bulk sends, so the order-related placeholders that
 * scheduler.php and cart-recovery.php support are deliberately absent.
 */
function zignites_chat_campaign_render_message($template, $name = '') {
    $site = function_exists('get_bloginfo') ? get_bloginfo('name') : '';
    $currency = function_exists('zignites_chat_currency_symbol_text') ? zignites_chat_currency_symbol_text() : '';
    return str_replace(
        ['{name}', '{site}', '{currency_symbol}'],
        [(string) $name, (string) $site, (string) $currency],
        (string) $template
    );
}

/* -------------------------------------------------------------------------
 * Cron-driven sender
 * ----------------------------------------------------------------------- */

/**
 * Process one chunk of pending recipients for a campaign and reschedule
 * if more remain.
 *
 * Chunk size and inter-chunk delay are filterable so high-volume sites
 * can throttle harder; low-volume sites can burst. Defaults sized for
 * a typical Twilio rate limit (~1 msg/sec) — 10 per minute is safe.
 *
 * @param int $campaign_id
 */
function zignites_chat_campaign_process_chunk($campaign_id) {
    global $wpdb;
    $campaign_id = (int) $campaign_id;
    if ($campaign_id <= 0) return;

    $campaign = zignites_chat_campaign_get($campaign_id);
    if (!$campaign) return;
    if (in_array($campaign['status'], ['completed', 'failed'], true)) return;

    $chunk_size = (int) apply_filters('zignites_chat_campaign_chunk_size', 10);
    $chunk_size = max(1, min(100, $chunk_size));

    $rt = zignites_chat_campaign_recipients_table_name();
    $rows = $wpdb->get_results($wpdb->prepare(
        "SELECT id, phone, customer_name FROM {$rt} WHERE campaign_id = %d AND status = 'pending' ORDER BY id ASC LIMIT %d",
        $campaign_id,
        $chunk_size
    ), ARRAY_A);

    if (!$rows) {
        zignites_chat_campaign_finalize($campaign_id);
        return;
    }

    if ($campaign['status'] !== 'running') {
        $wpdb->update(
            zignites_chat_campaigns_table_name(),
            ['status' => 'running'],
            ['id' => $campaign_id],
            ['%s'], ['%d']
        );
    }

    $sent = $failed = $skipped = 0;
    foreach ($rows as $row) {
        $phone = $row['phone'];

        if (zignites_chat_is_opted_out($phone)) {
            $wpdb->update($rt, [
                'status'  => 'skipped',
                'sent_at' => current_time('mysql'),
            ], ['id' => $row['id']], ['%s', '%s'], ['%d']);
            $skipped++;
            continue;
        }

        $message = zignites_chat_campaign_render_message($campaign['template'], $row['customer_name']);

        $context = zignites_chat_maybe_apply_template('bulk', [
            '{name}'            => $row['customer_name'],
            '{site}'            => function_exists('get_bloginfo') ? get_bloginfo('name') : '',
            '{currency_symbol}' => function_exists('zignites_chat_currency_symbol_text') ? zignites_chat_currency_symbol_text() : '',
        ], [
            'type' => 'bulk',
            'campaign_id' => $campaign_id,
        ]);

        $ok = zignites_chat_send_whatsapp_message($phone, $message, false, $context);

        if ($ok === true) {
            $wpdb->update($rt, [
                'status'        => 'sent',
                'attempt_count' => (int) $row['attempt_count'] + 1,
                'sent_at'       => current_time('mysql'),
            ], ['id' => $row['id']], ['%s', '%d', '%s'], ['%d']);
            $sent++;
        } else {
            $wpdb->update($rt, [
                'status'        => 'failed',
                'attempt_count' => (int) $row['attempt_count'] + 1,
                'last_error'    => __('Provider returned failure.', 'zignites-chat'),
            ], ['id' => $row['id']], ['%s', '%d', '%s'], ['%d']);
            $failed++;
        }
    }

    if ($sent || $failed || $skipped) {
        $wpdb->query($wpdb->prepare(
            "UPDATE " . zignites_chat_campaigns_table_name() . "
             SET sent_count = sent_count + %d,
                 failed_count = failed_count + %d,
                 skipped_count = skipped_count + %d
             WHERE id = %d",
            $sent, $failed, $skipped, $campaign_id
        ));
    }

    $remaining = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$rt} WHERE campaign_id = %d AND status = 'pending'",
        $campaign_id
    ));

    if ($remaining > 0) {
        $delay = (int) apply_filters('zignites_chat_campaign_chunk_interval', MINUTE_IN_SECONDS);
        $delay = max(10, min(HOUR_IN_SECONDS, $delay));
        wp_schedule_single_event(time() + $delay, 'zignites_chat_process_campaign', [$campaign_id]);
    } else {
        zignites_chat_campaign_finalize($campaign_id);
    }
}

/**
 * Mark a campaign complete once every recipient chunk has been processed.
 *
 * @param int $campaign_id
 */
function zignites_chat_campaign_finalize($campaign_id) {
    global $wpdb;
    $wpdb->update(
        zignites_chat_campaigns_table_name(),
        [
            'status'       => 'completed',
            'completed_at' => current_time('mysql'),
        ],
        ['id' => (int) $campaign_id],
        ['%s', '%s'],
        ['%d']
    );
}
