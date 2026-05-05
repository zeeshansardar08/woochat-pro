<?php
if (!defined('ABSPATH')) exit;

function wcwp_sanitize_yes_no($value) {
    return $value === 'yes' ? 'yes' : 'no';
}

function wcwp_sanitize_text($value) {
    return sanitize_text_field($value);
}

function wcwp_sanitize_textarea($value) {
    return sanitize_textarea_field($value);
}

function wcwp_sanitize_int($value) {
    return absint($value);
}

function wcwp_sanitize_url($value) {
    return esc_url_raw($value);
}

function wcwp_sanitize_provider($value) {
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
function wcwp_get_secret_option_keys() {
    return [
        'wcwp_twilio_sid',
        'wcwp_twilio_auth_token',
        'wcwp_twilio_from',
        'wcwp_cloud_token',
        'wcwp_cloud_phone_id',
        'wcwp_cloud_from',
        'wcwp_cloud_app_secret',
        'wcwp_gpt_api_key',
        'wcwp_gpt_api_endpoint',
        'wcwp_gpt_model',
        'wcwp_optout_webhook_token',
        'wcwp_license_key',
    ];
}

/**
 * Flip autoload to 'no' for any existing rows in the secret-keys list.
 *
 * Idempotent. Uses wp_set_option_autoload_values() on WP 6.4+ for a
 * batch update; falls back to direct $wpdb on older versions.
 */
function wcwp_set_secrets_autoload_no() {
    $keys = wcwp_get_secret_option_keys();

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
function wcwp_ensure_secret_option_rows() {
    foreach (wcwp_get_secret_option_keys() as $key) {
        // Fourth arg: autoload. add_option(name, value, deprecated, autoload).
        add_option($key, '', '', 'no');
    }
}

/**
 * Registry of versioned migration callables keyed by target version.
 *
 * Each callable runs at most once per install: when wcwp_run_migrations()
 * sees a version greater than the stored wcwp_db_version, it invokes the
 * callable and bumps the version. Adding a new migration is a one-line
 * addition here plus a matching wcwp_migration_v<N>_* function.
 *
 * Keep entries sorted by version key. The runner sorts numerically before
 * iterating, so order in the array literal is for human readability only.
 *
 * @return array<int, callable> Map of target_version => callable.
 */
function wcwp_get_migrations() {
    return [
        1 => 'wcwp_migration_v1_secrets_autoload',
        2 => 'wcwp_migration_v2_analytics_to_table',
    ];
}

/**
 * Versioned, run-once migration runner. Safe to invoke on every admin
 * request — the version flag short-circuits after each migration has
 * been applied. The version is bumped inline after each successful
 * step, so a partial failure (one step throws) leaves the install at
 * the highest fully-applied version, not stranded at the original.
 */
function wcwp_run_migrations() {
    $stored = (int) get_option('wcwp_db_version', 0);
    $migrations = wcwp_get_migrations();
    ksort($migrations, SORT_NUMERIC);

    foreach ($migrations as $version => $callable) {
        $version = (int) $version;
        if ($version <= $stored) continue;
        if (!is_callable($callable)) continue;
        call_user_func($callable);
        update_option('wcwp_db_version', $version, false);
        $stored = $version;
    }
}

/**
 * v1 (PR #20): create secret option rows with autoload=no on fresh
 * installs and flip autoload to 'no' for any existing rows.
 */
function wcwp_migration_v1_secrets_autoload() {
    wcwp_ensure_secret_option_rows();
    wcwp_set_secrets_autoload_no();
}

/**
 * v2 (PR #23): copy any legacy wcwp_analytics_events option rows into
 * the {prefix}wcwp_analytics_events table and delete the legacy options.
 */
function wcwp_migration_v2_analytics_to_table() {
    wcwp_migrate_analytics_options_to_table();
}

/**
 * Move any legacy option-store analytics rows into the events table, then
 * drop the option keys. The option-store fallback was retired in favor of
 * a single source of truth (the {prefix}wcwp_analytics_events table); this
 * runs once per install to carry forward any data that accumulated while
 * the table-creation activation hook had not yet fired (e.g. very early
 * installs that activated before the table existed).
 */
function wcwp_migrate_analytics_options_to_table() {
    // Guards kept: the migration runner fires on admin_init priority 5
    // regardless of whether WooCommerce is active, but boot_modules() —
    // which loads analytics.php — only runs when WC is active. So when
    // the v2 migration triggers on a WC-inactive request we may not have
    // wcwp_create_analytics_table / wcwp_analytics_insert_event defined.
    // The migration version is bumped after this returns either way; the
    // table is also (re)created via the activation hook so a later
    // re-activation rebuilds it.
    if (function_exists('wcwp_create_analytics_table')) {
        wcwp_create_analytics_table();
    }

    $events = get_option('wcwp_analytics_events', []);
    if (is_array($events) && !empty($events) && function_exists('wcwp_analytics_insert_event')) {
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
            wcwp_analytics_insert_event($event);
        }
    }

    delete_option('wcwp_analytics_events');
    delete_option('wcwp_analytics_totals');
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
function wcwp_sanitize_hex_color($value, $default = '') {
    if (is_string($value) && preg_match('/^#([A-Fa-f0-9]{3}){1,2}$/', $value)) {
        return $value;
    }
    return $default;
}

function wcwp_is_woocommerce_active() {
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
function wcwp_currency_symbol_text() {
    if (!function_exists('get_woocommerce_currency_symbol')) {
        return '';
    }
    return html_entity_decode(get_woocommerce_currency_symbol(), ENT_QUOTES, 'UTF-8');
}

function wcwp_sanitize_json_faq($value) {
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
function wcwp_get_log_file() {
    $upload_dir = wp_upload_dir();
    $log_dir    = $upload_dir['basedir'] . '/woochat-pro';
    if ( ! file_exists( $log_dir ) ) {
        wp_mkdir_p( $log_dir );
        // Protect against direct browsing.
        @file_put_contents( $log_dir . '/.htaccess', 'deny from all' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
        @file_put_contents( $log_dir . '/index.php', '<?php // Silence is golden.' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
    }
    return $log_dir . '/woochat-pro.log';
}

function wcwp_normalize_phone($phone) {
    $phone = preg_replace('/[^0-9]/', '', (string) $phone);
    return $phone ? $phone : '';
}

function wcwp_mask_phone($phone) {
    $norm = wcwp_normalize_phone($phone);
    if (strlen($norm) <= 4) return $norm;
    return str_repeat('•', max(0, strlen($norm) - 4)) . substr($norm, -4);
}

function wcwp_redact_message($message, $max = 120) {
    $msg = trim((string) $message);
    $msg = preg_replace('/\s+/', ' ', $msg);
    if (strlen($msg) > $max) {
        $msg = substr($msg, 0, $max) . '…';
    }
    return $msg;
}

function wcwp_parse_optout_list($value) {
    if (is_array($value)) {
        $list = $value;
    } else {
        $list = preg_split('/[\n,]+/', (string) $value);
    }
    $normalized = [];
    foreach ($list as $item) {
        $phone = wcwp_normalize_phone($item);
        if ($phone) {
            $normalized[$phone] = true;
        }
    }
    return array_keys($normalized);
}

function wcwp_get_optout_list() {
    $list = get_option('wcwp_optout_list', []);
    if (!is_array($list)) {
        $list = wcwp_parse_optout_list($list);
    }
    return $list;
}

function wcwp_set_optout_list($list) {
    $list = wcwp_parse_optout_list($list);
    update_option('wcwp_optout_list', $list, false);
}

function wcwp_is_opted_out($phone) {
    $phone = wcwp_normalize_phone($phone);
    if (!$phone) return false;
    $list = wcwp_get_optout_list();
    return in_array($phone, $list, true);
}

function wcwp_add_optout($phone) {
    $phone = wcwp_normalize_phone($phone);
    if (!$phone) return false;
    $list = wcwp_get_optout_list();
    if (!in_array($phone, $list, true)) {
        $list[] = $phone;
        update_option('wcwp_optout_list', $list, false);
    }
    return true;
}

function wcwp_sanitize_optout_keywords($value) {
    $parts = preg_split('/[\n,]+/', strtolower((string) $value));
    $out = [];
    foreach ($parts as $p) {
        $p = trim($p);
        if ($p !== '') $out[$p] = true;
    }
    return implode(', ', array_keys($out));
}

function wcwp_optout_keyword_match($message) {
    $keywords = get_option('wcwp_optout_keywords', 'stop, unsubscribe');
    $list = preg_split('/[\n,]+/', strtolower((string) $keywords));
    $text = strtolower((string) $message);
    foreach ($list as $kw) {
        $kw = trim($kw);
        if ($kw === '') continue;
        if (strpos($text, $kw) !== false) return true;
    }
    return false;
}
