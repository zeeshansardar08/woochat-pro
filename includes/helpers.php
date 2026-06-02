<?php
if (!defined('ABSPATH')) exit;

/*
 * Direct SQL in this file runs against the plugin's own custom tables.
 * User-supplied values are bound through $wpdb->prepare(); the only values
 * interpolated into query strings are table names derived from $wpdb->prefix.
 */
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter

function zignites_chat_sanitize_yes_no($value) {
    return $value === 'yes' ? 'yes' : 'no';
}

function zignites_chat_sanitize_text($value) {
    return sanitize_text_field($value);
}

function zignites_chat_sanitize_textarea($value) {
    return sanitize_textarea_field($value);
}

function zignites_chat_sanitize_int($value) {
    return absint($value);
}

function zignites_chat_sanitize_url($value) {
    return esc_url_raw($value);
}

function zignites_chat_sanitize_provider($value) {
    return in_array($value, ['twilio', 'cloud'], true) ? $value : 'twilio';
}

/**
 * List of option keys that hold credentials / integration config.
 *
 * These are only read when sending a message, on the admin settings
 * page, or by the opt-out / license webhooks — never on every frontend
 * page load. Keeping them OUT of the autoload set means fewer bytes
 * pulled into wp-cache 'alloptions' on every WordPress request, and
 * avoids unnecessarily exposing secret material in process memory.
 *
 * @return string[]
 */
function zignites_chat_get_secret_option_keys() {
    return [
        'zignites_chat_twilio_sid',
        'zignites_chat_twilio_auth_token',
        'zignites_chat_twilio_from',
        'zignites_chat_cloud_token',
        'zignites_chat_cloud_phone_id',
        'zignites_chat_cloud_from',
        'zignites_chat_cloud_app_secret',
        'zignites_chat_gpt_api_key',
        'zignites_chat_gpt_api_endpoint',
        'zignites_chat_gpt_model',
        'zignites_chat_optout_webhook_token',
        'zignites_chat_license_key',
        'zignites_chat_webhooks',
        'zignites_chat_webhook_log',
    ];
}

/**
 * Flip autoload to 'no' for any existing rows in the secret-keys list.
 *
 * Idempotent. Uses wp_set_option_autoload_values() on WP 6.4+ for a
 * batch update; falls back to direct $wpdb on older versions.
 */
function zignites_chat_set_secrets_autoload_no() {
    $keys = zignites_chat_get_secret_option_keys();

    if (function_exists('wp_set_option_autoload_values')) {
        wp_set_option_autoload_values(array_fill_keys($keys, 'no'));
        return;
    }

    global $wpdb;
    $placeholders = implode(',', array_fill(0, count($keys), '%s'));
    $sql = "UPDATE {$wpdb->options} SET autoload = 'no' WHERE autoload != 'no' AND option_name IN ($placeholders)";
    $wpdb->query($wpdb->prepare($sql, $keys));
    wp_cache_delete('alloptions', 'options');
}

/**
 * Ensure fresh installs create secret option rows with autoload=no
 * before WordPress's settings.php save flow ever sees them. add_option
 * is a no-op when the option already exists, so this is safe to call
 * repeatedly.
 */
function zignites_chat_ensure_secret_option_rows() {
    foreach (zignites_chat_get_secret_option_keys() as $key) {
        // Fourth arg: autoload. add_option(name, value, deprecated, autoload).
        add_option($key, '', '', 'no');
    }
}

/**
 * Registry of versioned migration callables keyed by target version.
 *
 * Each callable runs at most once per install: when zignites_chat_run_migrations()
 * sees a version greater than the stored zignites_chat_db_version, it invokes the
 * callable and bumps the version. Adding a new migration is a one-line
 * addition here plus a matching zignites_chat_migration_v<N>_* function.
 *
 * Keep entries sorted by version key. The runner sorts numerically before
 * iterating, so order in the array literal is for human readability only.
 *
 * @return array<int, callable> Map of target_version => callable.
 */
function zignites_chat_get_migrations() {
    return [
        1 => 'zignites_chat_migration_v1_secrets_autoload',
        2 => 'zignites_chat_migration_v2_analytics_to_table',
        3 => 'zignites_chat_migration_v3_campaign_tables',
        4 => 'zignites_chat_migration_v4_campaign_scheduled_at',
        5 => 'zignites_chat_migration_v5_campaign_media',
        6 => 'zignites_chat_migration_v6_inbox_tables',
    ];
}

/**
 * Versioned, run-once migration runner. Safe to invoke on every admin
 * request — the version flag short-circuits after each migration has
 * been applied. The version is bumped inline after each successful
 * step, so a partial failure (one step throws) leaves the install at
 * the highest fully-applied version, not stranded at the original.
 */
function zignites_chat_run_migrations() {
    $stored = (int) get_option('zignites_chat_db_version', 0);
    $migrations = zignites_chat_get_migrations();
    ksort($migrations, SORT_NUMERIC);

    foreach ($migrations as $version => $callable) {
        $version = (int) $version;
        if ($version <= $stored) continue;
        if (!is_callable($callable)) continue;
        call_user_func($callable);
        update_option('zignites_chat_db_version', $version, false);
        $stored = $version;
    }
}

/**
 * v1 (PR #20): create secret option rows with autoload=no on fresh
 * installs and flip autoload to 'no' for any existing rows.
 */
function zignites_chat_migration_v1_secrets_autoload() {
    zignites_chat_ensure_secret_option_rows();
    zignites_chat_set_secrets_autoload_no();
}

/**
 * v2 (PR #23): copy any legacy zignites_chat_analytics_events option rows into
 * the {prefix}zignites_chat_analytics_events table and delete the legacy options.
 */
function zignites_chat_migration_v2_analytics_to_table() {
    zignites_chat_migrate_analytics_options_to_table();
}

/**
 * v3: create the campaigns + campaign_recipients tables for bulk-messaging.
 *
 * Same guard shape as v2 — the migration runner fires on admin_init priority
 * 5 regardless of WC state, but boot_modules() (which loads campaigns.php)
 * only runs when WC is active. The activation hook also runs the table
 * creator, so a re-activation rebuilds the tables if WC is added later.
 */
function zignites_chat_migration_v3_campaign_tables() {
    if (function_exists('zignites_chat_create_campaign_tables')) {
        zignites_chat_create_campaign_tables();
    }
}

/**
 * v4: add the campaigns.scheduled_at column for send-at scheduling.
 *
 * Re-runs the dbDelta-based table creator, which is idempotent and adds the
 * new column to existing installs without touching data. Same WC-state guard
 * shape as v3 — the function is only defined once campaigns.php is loaded.
 */
function zignites_chat_migration_v4_campaign_scheduled_at() {
    if (function_exists('zignites_chat_create_campaign_tables')) {
        zignites_chat_create_campaign_tables();
    }
}

/**
 * v5: add the campaigns.media_url / media_type columns for image/document
 * campaign attachments. Idempotent dbDelta re-run, same guard shape as v4.
 */
function zignites_chat_migration_v5_campaign_media() {
    if (function_exists('zignites_chat_create_campaign_tables')) {
        zignites_chat_create_campaign_tables();
    }
}

/**
 * v6: create the two-way inbox tables (zignites_chat_conversations +
 * zignites_chat_messages). dbDelta-based and idempotent; same WC-state guard
 * shape as the campaign migrations — inbox.php is only loaded once
 * boot_modules() runs, and the activation hook re-creates the tables too.
 */
function zignites_chat_migration_v6_inbox_tables() {
    if (function_exists('zignites_chat_create_inbox_tables')) {
        zignites_chat_create_inbox_tables();
    }
}

/**
 * Move any legacy option-store analytics rows into the events table, then
 * drop the option keys. The option-store fallback was retired in favor of
 * a single source of truth (the {prefix}zignites_chat_analytics_events table); this
 * runs once per install to carry forward any data that accumulated while
 * the table-creation activation hook had not yet fired (e.g. very early
 * installs that activated before the table existed).
 */
function zignites_chat_migrate_analytics_options_to_table() {
    // Guards kept: the migration runner fires on admin_init priority 5
    // regardless of whether WooCommerce is active, but boot_modules() —
    // which loads analytics.php — only runs when WC is active. So when
    // the v2 migration triggers on a WC-inactive request we may not have
    // zignites_chat_create_analytics_table / zignites_chat_analytics_insert_event defined.
    // The migration version is bumped after this returns either way; the
    // table is also (re)created via the activation hook so a later
    // re-activation rebuilds it.
    if (function_exists('zignites_chat_create_analytics_table')) {
        zignites_chat_create_analytics_table();
    }

    $events = get_option('zignites_chat_analytics_events', []);
    if (is_array($events) && !empty($events) && function_exists('zignites_chat_analytics_insert_event')) {
        foreach ($events as $event) {
            if (!is_array($event) || empty($event['id']) || empty($event['type'])) continue;
            $event = wp_parse_args($event, [
                'id' => '',
                'type' => '',
                'time' => current_time('mysql'),
                'status' => 'pending',
                'phone' => '',
                'order_id' => 0,
                'message_preview' => '',
                'provider' => '',
                'message_id' => '',
                'meta' => [],
            ]);
            zignites_chat_analytics_insert_event($event);
        }
    }

    delete_option('zignites_chat_analytics_events');
    delete_option('zignites_chat_analytics_totals');
}

/**
 * Validate a value as a 3- or 6-digit hex color (with leading #).
 *
 * Used both as a register_setting() sanitize_callback (write-time, single
 * argument) and at render-time with an explicit default (read-time defense
 * for any legacy values that were saved before this validator existed).
 *
 * @param mixed  $value   Incoming value.
 * @param string $default Fallback when the value is not a valid hex color.
 * @return string Valid hex color (e.g. '#1c7c54') or the supplied default.
 */
function zignites_chat_sanitize_hex_color($value, $default = '') {
    if (is_string($value) && preg_match('/^#([A-Fa-f0-9]{3}){1,2}$/', $value)) {
        return $value;
    }
    return $default;
}

/**
 * Whether WooCommerce is active on the site or network.
 *
 * @return bool
 */
function zignites_chat_is_woocommerce_active() {
    if (class_exists('WooCommerce')) return true;
    if (!function_exists('is_plugin_active')) {
        include_once ABSPATH . 'wp-admin/includes/plugin.php';
    }
    if (function_exists('is_plugin_active') && is_plugin_active('woocommerce/woocommerce.php')) {
        return true;
    }
    if (function_exists('is_plugin_active_for_network') && is_multisite() && is_plugin_active_for_network('woocommerce/woocommerce.php')) {
        return true;
    }
    return false;
}

// WC returns HTML entities (e.g. `&#36;` for USD) — decode for plain-text WhatsApp output.
function zignites_chat_currency_symbol_text() {
    if (!function_exists('get_woocommerce_currency_symbol')) {
        return '';
    }
    return html_entity_decode(get_woocommerce_currency_symbol(), ENT_QUOTES, 'UTF-8');
}

/**
 * Default GPT model for chatbot/follow-up generation.
 *
 * Used as the fallback when the zignites_chat_gpt_model option is unset or
 * empty, so a single edit (and the filter) moves every call site forward as
 * models evolve. Existing installs that saved an explicit model keep it.
 *
 * @return string Model id (default 'gpt-4o-mini').
 */
function zignites_chat_default_gpt_model() {
    return (string) apply_filters('zignites_chat_default_gpt_model', 'gpt-4o-mini');
}

/**
 * Sanitize a JSON-encoded list of {name, phone} agents.
 *
 * Drops rows where either field is empty (a half-filled agent breaks
 * the chatbot routing — better to lose the row than ship a broken
 * wa.me link). Phone is normalized to digits only via
 * zignites_chat_normalize_phone(); name is run through sanitize_text_field.
 * Stored shape matches what the chatbot localizer reads back.
 */
function zignites_chat_sanitize_agents_json($value) {
    $decoded = is_array($value) ? $value : json_decode((string) $value, true);
    if (!is_array($decoded)) return '[]';

    $sanitized = [];
    foreach ($decoded as $agent) {
        if (!is_array($agent)) continue;
        $name  = isset($agent['name'])  ? sanitize_text_field($agent['name'])  : '';
        $phone = isset($agent['phone']) ? zignites_chat_normalize_phone($agent['phone']) : '';
        if ($name === '' || $phone === '') continue;
        $sanitized[] = ['name' => $name, 'phone' => $phone];
    }
    return wp_json_encode($sanitized, JSON_UNESCAPED_UNICODE);
}

/**
 * Read the stored multi-agent routing list.
 *
 * Rows with an empty name or phone are dropped so callers never get a
 * half-filled agent.
 *
 * @return array<int, array{name:string, phone:string}>
 */
function zignites_chat_get_agents() {
    $raw = get_option('zignites_chat_agents', '[]');
    $list = is_array($raw) ? $raw : json_decode((string) $raw, true);
    if (!is_array($list)) return [];

    $clean = [];
    foreach ($list as $agent) {
        if (!is_array($agent)) continue;
        $name  = isset($agent['name'])  ? (string) $agent['name']  : '';
        $phone = isset($agent['phone']) ? (string) $agent['phone'] : '';
        if ($name === '' || $phone === '') continue;
        $clean[] = ['name' => $name, 'phone' => $phone];
    }
    return $clean;
}

function zignites_chat_sanitize_agent_routing_mode($value) {
    return in_array($value, ['single', 'random'], true) ? $value : 'single';
}

function zignites_chat_sanitize_json_faq($value) {
    $decoded = json_decode($value, true);
    if (!is_array($decoded)) {
        return '[]';
    }
    $sanitized = [];
    foreach ($decoded as $pair) {
        if (!is_array($pair)) continue;
        $q = isset($pair['question']) ? sanitize_text_field($pair['question']) : '';
        $a = isset($pair['answer']) ? sanitize_text_field($pair['answer']) : '';
        if ($q === '' && $a === '') continue;
        $sanitized[] = [
            'question' => $q,
            'answer' => $a,
        ];
    }
    return wp_json_encode($sanitized, JSON_UNESCAPED_UNICODE);
}

/**
 * Return the path to the plugin log file (inside uploads, not plugin dir).
 */
function zignites_chat_get_log_file() {
    $upload_dir = wp_upload_dir();
    $log_dir    = $upload_dir['basedir'] . '/zignites-chat';
    if ( ! file_exists( $log_dir ) ) {
        wp_mkdir_p( $log_dir );
        // Protect against direct browsing — written via WP_Filesystem so the
        // plugin does not call the raw filesystem functions WordPress.org flags.
        global $wp_filesystem;
        if ( empty( $wp_filesystem ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            WP_Filesystem();
        }
        if ( ! empty( $wp_filesystem ) ) {
            $wp_filesystem->put_contents( $log_dir . '/.htaccess', 'deny from all', FS_CHMOD_FILE );
            $wp_filesystem->put_contents( $log_dir . '/index.php', '<?php // Silence is golden.', FS_CHMOD_FILE );
        }
    }
    return $log_dir . '/zignites-chat.log';
}

/**
 * Record a GPT API failure so the admin can see it.
 *
 * Stored as a short-lived transient (24h) and appended to the plugin log
 * file. The admin notice in settings-page.php picks up the transient on
 * the next admin page load — so the silent "GPT returned 401" failures
 * that used to vanish into thin air now surface with a dismissible
 * banner.
 *
 * @param string $context     Pro feature that triggered the call: 'followup' or 'chatbot'.
 * @param string $error_text  Short, actionable error description (HTTP code,
 *                            timeout, missing creds, etc.). Already sanitized
 *                            by callers — never embed raw API response bodies
 *                            because they may contain secrets.
 */
function zignites_chat_record_gpt_error($context, $error_text) {
    $context = sanitize_text_field((string) $context);
    $error_text = sanitize_text_field((string) $error_text);
    if ($error_text === '') return;

    set_transient(
        'zignites_chat_last_gpt_error',
        array(
            'time'    => time(),
            'context' => $context,
            'message' => $error_text,
        ),
        DAY_IN_SECONDS
    );

    $log_file = zignites_chat_get_log_file();
    @error_log( // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
        '[Zignites Chat][GPT][' . $context . '] ' . $error_text . "\n",
        3,
        $log_file
    );
}

/**
 * Return the most recent GPT error (or null if none in the last 24h).
 *
 * Shape: ['time' => int, 'context' => string, 'message' => string].
 *
 * @return array{time:int, context:string, message:string}|null
 */
function zignites_chat_get_last_gpt_error() {
    $entry = get_transient('zignites_chat_last_gpt_error');
    if (!is_array($entry) || !isset($entry['time'], $entry['message'])) {
        return null;
    }
    return $entry;
}

/**
 * Reduce a phone number to digits only.
 *
 * @param string $phone Raw phone input.
 * @return string Digits-only phone, or '' when none are present.
 */
function zignites_chat_normalize_phone($phone) {
    $phone = preg_replace('/[^0-9]/', '', (string) $phone);
    return $phone ? $phone : '';
}

/**
 * Mask all but the last four digits of a phone number for display/logs.
 *
 * @param string $phone Raw phone input.
 * @return string Masked phone (e.g. "••••••1234").
 */
function zignites_chat_mask_phone($phone) {
    $norm = zignites_chat_normalize_phone($phone);
    if (strlen($norm) <= 4) return $norm;
    return str_repeat('•', max(0, strlen($norm) - 4)) . substr($norm, -4);
}

/**
 * Collapse whitespace and truncate a message for a compact log preview.
 *
 * @param string $message Message body.
 * @param int    $max     Maximum length before truncation. Default 120.
 * @return string
 */
function zignites_chat_redact_message($message, $max = 120) {
    $msg = trim((string) $message);
    $msg = preg_replace('/\s+/', ' ', $msg);
    if (strlen($msg) > $max) {
        $msg = substr($msg, 0, $max) . '…';
    }
    return $msg;
}

/**
 * Parse a comma/newline separated opt-out list into unique normalized phones.
 *
 * @param string|array $value Raw textarea value or an existing array.
 * @return string[] Unique digits-only phone numbers.
 */
function zignites_chat_parse_optout_list($value) {
    if (is_array($value)) {
        $list = $value;
    } else {
        $list = preg_split('/[\n,]+/', (string) $value);
    }
    $normalized = [];
    foreach ($list as $item) {
        $phone = zignites_chat_normalize_phone($item);
        if ($phone) {
            $normalized[$phone] = true;
        }
    }
    return array_keys($normalized);
}

/**
 * Return the current opt-out (suppression) list.
 *
 * @return string[] Normalized phone numbers.
 */
function zignites_chat_get_optout_list() {
    $list = get_option('zignites_chat_optout_list', []);
    if (!is_array($list)) {
        $list = zignites_chat_parse_optout_list($list);
    }
    return $list;
}

/**
 * Replace the stored opt-out list.
 *
 * @param string|array $list Raw or array list of phone numbers.
 */
function zignites_chat_set_optout_list($list) {
    $list = zignites_chat_parse_optout_list($list);
    update_option('zignites_chat_optout_list', $list, false);
}

/**
 * Whether a phone number is on the opt-out list.
 *
 * @param string $phone Phone number (any format).
 * @return bool
 */
function zignites_chat_is_opted_out($phone) {
    $phone = zignites_chat_normalize_phone($phone);
    if (!$phone) return false;
    $list = zignites_chat_get_optout_list();
    return in_array($phone, $list, true);
}

/**
 * Add a phone number to the opt-out list and fire the opt-out webhook.
 *
 * @param string $phone Phone number (any format).
 * @return bool False when the phone is empty, true otherwise.
 */
function zignites_chat_add_optout($phone) {
    $phone = zignites_chat_normalize_phone($phone);
    if (!$phone) return false;
    $list = zignites_chat_get_optout_list();
    $newly_added = false;
    if (!in_array($phone, $list, true)) {
        $list[] = $phone;
        update_option('zignites_chat_optout_list', $list, false);
        $newly_added = true;
    }
    if ($newly_added && function_exists('zignites_chat_dispatch_webhook')) {
        zignites_chat_dispatch_webhook('customer.opted_out', ['phone' => $phone]);
    }
    return true;
}

function zignites_chat_sanitize_optout_keywords($value) {
    $parts = preg_split('/[\n,]+/', strtolower((string) $value));
    $out = [];
    foreach ($parts as $p) {
        $p = trim($p);
        if ($p !== '') $out[$p] = true;
    }
    return implode(', ', array_keys($out));
}

/**
 * Whether an inbound message contains a configured opt-out keyword.
 *
 * @param string $message Inbound message text.
 * @return bool
 */
function zignites_chat_optout_keyword_match($message) {
    $keywords = get_option('zignites_chat_optout_keywords', 'stop, unsubscribe');
    $list = preg_split('/[\n,]+/', strtolower((string) $keywords));
    $text = strtolower((string) $message);
    foreach ($list as $kw) {
        $kw = trim($kw);
        if ($kw === '') continue;
        if (strpos($text, $kw) !== false) return true;
    }
    return false;
}
