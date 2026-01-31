<?php
if (!defined('ABSPATH')) exit;

// Inject JS into footer to track cart activity
add_action('wp_footer', 'wcwp_cart_recovery_script');

function wcwp_cart_recovery_script() {
    if (is_admin()) return;

    if (!function_exists('wcwp_is_pro_active') || !wcwp_is_pro_active()) return;

    $enabled = get_option('wcwp_cart_recovery_enabled', 'yes');
    if ($enabled !== 'yes') return;

    wp_enqueue_script('wcwp-cart-tracker', plugin_dir_url(__FILE__) . '../assets/js/cart-tracker.js', ['jquery'], null, true);

    wp_localize_script('wcwp-cart-tracker', 'wcwp_ajax_obj', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('wcwp_cart_nonce'),
    ]);
    wp_localize_script('wcwp-cart-tracker', 'wcwp_cart_recovery_delay', get_option('wcwp_cart_recovery_delay', 20));
    wp_localize_script('wcwp-cart-tracker', 'wcwp_cart_consent_required', get_option('wcwp_cart_recovery_require_consent', 'no'));
}

// Optional consent checkbox on checkout
add_action('woocommerce_after_checkout_billing_form', 'wcwp_cart_recovery_consent_field');
function wcwp_cart_recovery_consent_field() {
    if (get_option('wcwp_cart_recovery_require_consent', 'no') !== 'yes') return;
    echo '<div class="wcwp-cart-consent" style="margin-top:12px;">';
    echo '<label style="display:flex;align-items:center;gap:8px;">';
    echo '<input type="checkbox" id="wcwp-cart-consent" name="wcwp-cart-consent" value="yes" />';
    echo '<span>Send me WhatsApp cart reminders</span>';
    echo '</label>';
    echo '</div>';
}

// Handle AJAX for cart tracking
add_action('wp_ajax_nopriv_wcwp_save_cart', 'wcwp_save_cart_ajax');
add_action('wp_ajax_wcwp_save_cart', 'wcwp_save_cart_ajax');

add_filter('cron_schedules', function($schedules) {
    if (!isset($schedules['wcwp_five_minutes'])) {
        $schedules['wcwp_five_minutes'] = [
            'interval' => 5 * MINUTE_IN_SECONDS,
            'display' => 'Every 5 Minutes',
        ];
    }
    return $schedules;
});

add_action('init', 'wcwp_schedule_cart_recovery_cron');
add_action('wcwp_process_cart_recovery_queue', 'wcwp_process_cart_recovery_queue');

function wcwp_get_cart_table_name() {
    global $wpdb;
    return $wpdb->prefix . 'wcwp_abandoned_carts';
}

function wcwp_create_cart_recovery_table() {
    global $wpdb;
    $table = wcwp_get_cart_table_name();
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE {$table} (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        phone VARCHAR(32) NOT NULL,
        cart_hash CHAR(32) NOT NULL,
        cart_json LONGTEXT NOT NULL,
        total DECIMAL(12,2) NOT NULL DEFAULT 0,
        items_count INT NOT NULL DEFAULT 0,
        status VARCHAR(20) NOT NULL DEFAULT 'pending',
        attempts INT NOT NULL DEFAULT 0,
        last_error TEXT NULL,
        consent VARCHAR(3) NOT NULL DEFAULT 'no',
        consent_time DATETIME NULL,
        event_id VARCHAR(64) NULL,
        next_send_at DATETIME NOT NULL,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        PRIMARY KEY (id),
        KEY phone_status (phone, status),
        KEY next_send_at (next_send_at)
    ) {$charset_collate};";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}

function wcwp_schedule_cart_recovery_cron() {
    if (!wp_next_scheduled('wcwp_process_cart_recovery_queue')) {
        wp_schedule_event(time() + 60, 'wcwp_five_minutes', 'wcwp_process_cart_recovery_queue');
    }
}

function wcwp_unschedule_cart_recovery_cron() {
    $timestamp = wp_next_scheduled('wcwp_process_cart_recovery_queue');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'wcwp_process_cart_recovery_queue');
    }
}

function wcwp_save_cart_ajax() {
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wcwp_cart_nonce')) {
        wp_send_json_error(['message' => 'Unauthorized']);
        return;
    }

    if (get_option('wcwp_cart_recovery_enabled', 'yes') !== 'yes') {
        wp_send_json_error(['message' => 'Disabled'], 403);
        return;
    }

    $phone = sanitize_text_field($_POST['phone'] ?? '');
    $phone = function_exists('wcwp_normalize_phone') ? wcwp_normalize_phone($phone) : $phone;
    $cart_items = json_decode(stripslashes($_POST['cart'] ?? ''), true);
    $consent = sanitize_text_field($_POST['consent'] ?? 'no');
    $consent_time = current_time('mysql');

    if (!wcwp_cart_rate_limit_ok($phone)) {
        wp_send_json_error(['message' => 'Rate limited'], 429);
        return;
    }

    if (get_option('wcwp_cart_recovery_require_consent', 'no') === 'yes' && $consent !== 'yes') {
        wp_send_json_error(['message' => 'Consent missing']);
        return;
    }

    if (!$phone || empty($cart_items)) {
        wp_send_json_error(['message' => 'Missing data']);
        return;
    }

    if (function_exists('wcwp_is_opted_out') && wcwp_is_opted_out($phone)) {
        wp_send_json_error(['message' => 'Opted out'], 403);
        return;
    }

    $scheduled = wcwp_queue_cart_recovery($phone, $cart_items, $consent === 'yes' ? 'yes' : 'no', $consent_time);
    if (!$scheduled) {
        wp_send_json_error(['message' => 'Failed to schedule'], 500);
        return;
    }
    wp_send_json_success(['message' => 'Reminder scheduled']);
}

function wcwp_queue_cart_recovery($phone, $cart_items, $consent = 'no', $consent_time = '') {
    global $wpdb;
    $table = wcwp_get_cart_table_name();
    $delay = absint(get_option('wcwp_cart_recovery_delay', 20));
    if ($delay < 1) $delay = 20;

    $cart_json = wp_json_encode($cart_items);
    $cart_hash = md5($cart_json);
    $next_send_at = date('Y-m-d H:i:s', time() + ($delay * MINUTE_IN_SECONDS));

    $total = 0;
    $items_count = 0;
    foreach ($cart_items as $item) {
        $price = floatval($item['price'] ?? 0);
        $qty = intval($item['qty'] ?? 0);
        $total += $price * $qty;
        $items_count += $qty;
    }

    $existing = $wpdb->get_row($wpdb->prepare(
        "SELECT id, cart_hash FROM {$table} WHERE phone = %s AND status IN ('pending','retry') ORDER BY updated_at DESC LIMIT 1",
        $phone
    ));

    $now = current_time('mysql');
    if ($existing) {
        $updated = $wpdb->update(
            $table,
            [
                'cart_hash' => $cart_hash,
                'cart_json' => $cart_json,
                'total' => $total,
                'items_count' => $items_count,
                'consent' => $consent,
                'consent_time' => $consent_time,
                'next_send_at' => $next_send_at,
                'updated_at' => $now,
            ],
            ['id' => $existing->id],
            ['%s','%s','%f','%d','%s','%s','%s','%s'],
            ['%d']
        );
        return $updated !== false;
    }

    $inserted = $wpdb->insert(
        $table,
        [
            'phone' => $phone,
            'cart_hash' => $cart_hash,
            'cart_json' => $cart_json,
            'total' => $total,
            'items_count' => $items_count,
            'status' => 'pending',
            'attempts' => 0,
            'consent' => $consent,
            'consent_time' => $consent_time,
            'next_send_at' => $next_send_at,
            'created_at' => $now,
            'updated_at' => $now,
        ],
        ['%s','%s','%s','%f','%d','%s','%d','%s','%s','%s','%s','%s']
    );

    return $inserted !== false;
}

function wcwp_send_cart_recovery_whatsapp($phone, $cart_items, $consent = 'no', $consent_time = '', $context = []) {
    $total = 0;
    $items = [];
    $attempt_id = uniqid('wcwp_cart_', true);
    $event_id = $context['event_id'] ?? null;

    foreach ($cart_items as $item) {
        $name  = sanitize_text_field($item['name']);
        $price = floatval($item['price']);
        $qty   = intval($item['qty']);
        $total += $price * $qty;
        $items[] = "- $name × $qty";
    }

    $body = implode("\n", $items);
    $cart_url = wc_get_cart_url();
    if (function_exists('wcwp_analytics_log_event')) {
        if (!$event_id) {
            $event_id = wcwp_analytics_log_event('sent', [
                'status' => 'pending',
                'phone' => $phone,
                'message_preview' => '',
                'meta' => ['items' => $items, 'total' => $total, 'source' => 'cart_recovery'],
            ]);
        }
    }
    $tracked_cart_url = ($event_id && function_exists('wcwp_analytics_tracking_url')) ? wcwp_analytics_tracking_url($event_id, $cart_url) : $cart_url;
    $template = get_option('wcwp_cart_recovery_message', "👋 Hey! You left items in your cart:\n\n{items}\n\nTotal: {total} PKR\nClick here to complete your order: {cart_url}");
    $message = str_replace(
        ['{items}', '{total}', '{cart_url}'],
        [$body, $total, $tracked_cart_url],
        $template
    );
    $preview = function_exists('wcwp_redact_message') ? wcwp_redact_message($message) : $message;

    // Log all attempts
    $log_file = WCWP_PATH . 'woochat-pro.log';
    $safe_to = function_exists('wcwp_mask_phone') ? wcwp_mask_phone($phone) : $phone;
    $safe_msg = function_exists('wcwp_redact_message') ? wcwp_redact_message($message) : $message;
    $log_msg = "[WooChat Pro - Cart Recovery] Attempt {$attempt_id} to $safe_to: $safe_msg\n";
    @error_log($log_msg, 3, $log_file);

    // Store attempt in transient for admin UI
    $attempts = get_transient('wcwp_cart_recovery_attempts') ?: [];
    $attempts[] = [
        'id' => $attempt_id,
        'time' => current_time('mysql'),
        'phone' => $phone,
        'message' => $message,
        'items' => $items,
        'total' => $total,
        'consent' => $consent,
        'consent_time' => $consent_time
    ];
    set_transient('wcwp_cart_recovery_attempts', $attempts, DAY_IN_SECONDS);

    // Check if test mode is enabled
    $test_mode = get_option('wcwp_test_mode_enabled', 'no');
    if ($test_mode === 'yes') {
        $log_msg = "[WooChat Pro - Cart Recovery TEST MODE] {$attempt_id} to $safe_to: $safe_msg\n";
        @error_log($log_msg, 3, $log_file);
        if ($event_id && function_exists('wcwp_analytics_update_event')) {
            wcwp_analytics_update_event($event_id, ['status' => 'test', 'message_preview' => $preview]);
        }
        return 'test';
    }

    if (function_exists('wcwp_is_opted_out') && wcwp_is_opted_out($phone)) {
        if ($event_id && function_exists('wcwp_analytics_update_event')) {
            wcwp_analytics_update_event($event_id, ['status' => 'opted_out', 'message_preview' => $message]);
        }
        return 'opted_out';
    }

    if (function_exists('wcwp_send_whatsapp_message')) {
        $result = wcwp_send_whatsapp_message($phone, $message, false, ['type' => 'cart_recovery', 'event_id' => $event_id]);
        if ($event_id && function_exists('wcwp_analytics_update_event')) {
            wcwp_analytics_update_event($event_id, ['status' => $result === true ? 'sent' : 'failed', 'message_preview' => $preview]);
        }
        return $result === true ? true : false;
    }

    return false;
}

function wcwp_process_cart_recovery_queue() {
    if (get_option('wcwp_cart_recovery_enabled', 'yes') !== 'yes') return;
    if (!function_exists('wcwp_is_pro_active') || !wcwp_is_pro_active()) return;

    global $wpdb;
    $table = wcwp_get_cart_table_name();
    $now = current_time('mysql');
    $max_attempts = 3;

    $rows = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$table} WHERE status IN ('pending','retry') AND next_send_at <= %s ORDER BY next_send_at ASC LIMIT 20",
        $now
    ));

    if (!$rows) return;

    foreach ($rows as $row) {
        $phone = $row->phone;
        $cart_items = json_decode($row->cart_json, true);
        if (!is_array($cart_items) || empty($cart_items)) {
            $wpdb->update($table, ['status' => 'invalid', 'updated_at' => $now], ['id' => $row->id], ['%s','%s'], ['%d']);
            continue;
        }

        if (function_exists('wcwp_is_opted_out') && wcwp_is_opted_out($phone)) {
            $wpdb->update($table, ['status' => 'opted_out', 'updated_at' => $now], ['id' => $row->id], ['%s','%s'], ['%d']);
            continue;
        }

        $result = wcwp_send_cart_recovery_whatsapp($phone, $cart_items, $row->consent, $row->consent_time, ['event_id' => $row->event_id]);

        if ($result === true) {
            $wpdb->update($table, ['status' => 'sent', 'updated_at' => $now], ['id' => $row->id], ['%s','%s'], ['%d']);
            continue;
        }

        if ($result === 'test') {
            $wpdb->update($table, ['status' => 'test', 'updated_at' => $now], ['id' => $row->id], ['%s','%s'], ['%d']);
            continue;
        }

        $attempts = intval($row->attempts) + 1;
        if ($attempts >= $max_attempts) {
            $wpdb->update(
                $table,
                ['status' => 'failed', 'attempts' => $attempts, 'updated_at' => $now],
                ['id' => $row->id],
                ['%s','%d','%s'],
                ['%d']
            );
        } else {
            $retry_at = date('Y-m-d H:i:s', time() + (15 * MINUTE_IN_SECONDS));
            $wpdb->update(
                $table,
                ['status' => 'retry', 'attempts' => $attempts, 'next_send_at' => $retry_at, 'updated_at' => $now],
                ['id' => $row->id],
                ['%s','%d','%s','%s'],
                ['%d']
            );
        }
    }
}

function wcwp_cart_rate_limit_ok($phone) {
    $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : '';
    $key = 'wcwp_rate_' . md5($ip . '|' . $phone);
    $data = get_transient($key);
    $limit = 5;
    $window = HOUR_IN_SECONDS;

    if (!$data || !is_array($data)) {
        set_transient($key, ['count' => 1, 'start' => time()], $window);
        return true;
    }

    if (!isset($data['count'], $data['start'])) {
        set_transient($key, ['count' => 1, 'start' => time()], $window);
        return true;
    }

    if ((time() - $data['start']) > $window) {
        set_transient($key, ['count' => 1, 'start' => time()], $window);
        return true;
    }

    if ($data['count'] >= $limit) {
        return false;
    }

    $data['count']++;
    set_transient($key, $data, $window);
    return true;
}

add_action('woocommerce_checkout_update_order_meta', function($order_id) {
    $consent = isset($_POST['wcwp-cart-consent']) && $_POST['wcwp-cart-consent'] === 'yes' ? 'yes' : 'no';
    update_post_meta($order_id, '_wcwp_cart_consent', $consent);
    update_post_meta($order_id, '_wcwp_cart_consent_time', current_time('mysql'));
});

// Helper to fetch attempts
function wcwp_get_cart_recovery_attempts() {
    $attempts = get_transient('wcwp_cart_recovery_attempts') ?: [];
    return array_slice(array_reverse($attempts), 0, 25);
}

// Admin resend handler
add_action('wp_ajax_wcwp_resend_cart_recovery', function() {
    if (!current_user_can('manage_woocommerce')) wp_send_json_error(['message' => 'Unauthorized'], 403);
    if (!check_ajax_referer('wcwp_resend_cart', 'nonce', false)) wp_send_json_error(['message' => 'Bad nonce'], 400);

    $attempt_id = sanitize_text_field($_POST['attempt_id'] ?? '');
    if (!$attempt_id) wp_send_json_error(['message' => 'Missing attempt id'], 400);

    $attempts = get_transient('wcwp_cart_recovery_attempts') ?: [];
    $found = null;
    foreach ($attempts as $a) {
        if (isset($a['id']) && $a['id'] === $attempt_id) {
            $found = $a;
            break;
        }
    }

    if (!$found) wp_send_json_error(['message' => 'Attempt not found'], 404);

    if (function_exists('wcwp_send_whatsapp_message')) {
        $result = wcwp_send_whatsapp_message($found['phone'], $found['message'], true);
        if ($result === true) {
            wp_send_json_success(['message' => 'Resent']);
        }
        wp_send_json_error(['message' => 'Send failed']);
    }

    wp_send_json_error(['message' => 'Messaging unavailable'], 500);
});
