<?php
if (!defined('ABSPATH')) exit;

// Inject JS into footer to track cart activity
add_action('wp_footer', 'wcwp_cart_recovery_script');

function wcwp_cart_recovery_script() {
    if (is_admin()) return;

    if (!wcwp_is_pro_active()) return;

    $enabled = get_option('wcwp_cart_recovery_enabled', 'yes');
    if ($enabled !== 'yes') return;

    wp_enqueue_script('wcwp-cart-tracker', plugin_dir_url(__FILE__) . '../assets/js/cart-tracker.js', ['jquery'], WCWP_VERSION, true);

    wp_localize_script('wcwp-cart-tracker', 'wcwp_ajax_obj', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('wcwp_cart_nonce'),
        'cart_url' => rest_url('wc/store/v1/cart'),
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
    echo '<span>' . esc_html__('Send me WhatsApp cart reminders', 'woochat-pro') . '</span>';
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
    if (!check_ajax_referer('wcwp_cart_nonce', 'nonce', false)) {
        wp_send_json_error(['message' => __('Unauthorized', 'woochat-pro')]);
        return;
    }

    if (get_option('wcwp_cart_recovery_enabled', 'yes') !== 'yes') {
        wp_send_json_error(['message' => __('Disabled', 'woochat-pro')], 403);
        return;
    }

    $phone = isset($_POST['phone']) ? sanitize_text_field(wp_unslash($_POST['phone'])) : '';
    $phone = wcwp_normalize_phone($phone);
    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- JSON cart payload, validated and decoded via json_decode below; nonce verified at function entry.
    $cart_raw = isset($_POST['cart']) ? wp_unslash($_POST['cart']) : '';
    $cart_items = json_decode(is_string($cart_raw) ? $cart_raw : '', true);
    $consent = isset($_POST['consent']) ? sanitize_text_field(wp_unslash($_POST['consent'])) : 'no';
    $consent_time = current_time('mysql');

    if (!wcwp_cart_rate_limit_ok($phone)) {
        wp_send_json_error(['message' => __('Rate limited', 'woochat-pro')], 429);
        return;
    }

    if (get_option('wcwp_cart_recovery_require_consent', 'no') === 'yes' && $consent !== 'yes') {
        wp_send_json_error(['message' => __('Consent missing', 'woochat-pro')]);
        return;
    }

    if (!$phone || empty($cart_items)) {
        wp_send_json_error(['message' => __('Missing data', 'woochat-pro')]);
        return;
    }

    if (wcwp_is_opted_out($phone)) {
        wp_send_json_error(['message' => __('Opted out', 'woochat-pro')], 403);
        return;
    }

    $scheduled = wcwp_queue_cart_recovery($phone, $cart_items, $consent === 'yes' ? 'yes' : 'no', $consent_time);
    if (!$scheduled) {
        wp_send_json_error(['message' => __('Failed to schedule', 'woochat-pro')], 500);
        return;
    }
    wp_send_json_success(['message' => __('Reminder scheduled', 'woochat-pro')]);
}

function wcwp_queue_cart_recovery($phone, $cart_items, $consent = 'no', $consent_time = '') {
    global $wpdb;
    $table = wcwp_get_cart_table_name();
    $delay = absint(get_option('wcwp_cart_recovery_delay', 20));
    if ($delay < 1) $delay = 20;

    // Sanitize individual cart item fields (H7).
    $sanitized_items = [];
    $total = 0;
    $items_count = 0;
    foreach ($cart_items as $item) {
        $s_item = [
            'name'  => sanitize_text_field( $item['name'] ?? '' ),
            'price' => floatval( $item['price'] ?? 0 ),
            'qty'   => absint( $item['qty'] ?? 0 ),
        ];
        $total += $s_item['price'] * $s_item['qty'];
        $items_count += $s_item['qty'];
        $sanitized_items[] = $s_item;
    }
    $cart_json = wp_json_encode( $sanitized_items );
    $cart_hash = md5($cart_json);
    $next_send_at = date('Y-m-d H:i:s', time() + ($delay * MINUTE_IN_SECONDS));

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
    $event_id = $context['event_id'] ?? null;

    $items = wcwp_format_cart_items_list($cart_items);
    $total = wcwp_sum_cart_items_total($cart_items);

    $cart_url = wc_get_cart_url();
    if (!$event_id) {
        $event_id = wcwp_analytics_log_event('cart_recovery', [
            'status' => 'pending',
            'phone' => $phone,
            'message_preview' => '',
            'meta' => ['items' => $items, 'total' => $total, 'source' => 'cart_recovery'],
        ]);
    }
    $tracked_cart_url = $event_id ? wcwp_analytics_tracking_url($event_id, $cart_url) : $cart_url;
    // Variant selection happens here, not at the render call site, so the
    // resend admin button + Recent Attempts view stay on variant A. Only
    // the automated cart-recovery worker participates in the A/B test.
    $picked  = wcwp_ab_get_template('cart_recovery', $phone);
    $message = wcwp_render_cart_recovery_message($items, $total, $tracked_cart_url, $picked['template']);
    $preview = wcwp_redact_message($message);

    $log_file = wcwp_get_log_file();
    $safe_to = wcwp_mask_phone($phone);
    $safe_msg = $preview;
    $log_tag = $event_id ?: 'no-event';
    @error_log("[WooChat Pro - Cart Recovery] Attempt {$log_tag} to $safe_to: $safe_msg\n", 3, $log_file); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log

    $test_mode = get_option('wcwp_test_mode_enabled', 'no');
    if ($test_mode === 'yes') {
        @error_log("[WooChat Pro - Cart Recovery TEST MODE] {$log_tag} to $safe_to: $safe_msg\n", 3, $log_file); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
        if ($event_id) {
            wcwp_analytics_update_event($event_id, ['status' => 'test', 'message_preview' => $preview]);
        }
        return 'test';
    }

    if (wcwp_is_opted_out($phone)) {
        if ($event_id) {
            wcwp_analytics_update_event($event_id, ['status' => 'opted_out', 'message_preview' => $message]);
        }
        return 'opted_out';
    }

    $result = wcwp_send_whatsapp_message($phone, $message, false, [
        'type'       => 'cart_recovery',
        'event_id'   => $event_id,
        'ab_variant' => $picked['variant'],
    ]);
    if ($event_id) {
        wcwp_analytics_update_event($event_id, ['status' => $result === true ? 'sent' : 'failed', 'message_preview' => $preview]);
    }
    return $result === true ? true : false;
}

function wcwp_process_cart_recovery_queue() {
    if (get_option('wcwp_cart_recovery_enabled', 'yes') !== 'yes') return;
    if (!wcwp_is_pro_active()) return;

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

        if (wcwp_is_opted_out($phone)) {
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
    $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';
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

add_action('woocommerce_checkout_order_processed', function($order_id) {
    // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Hook fires inside WooCommerce's nonce-validated checkout pipeline; relying on WC's own verification.
    $consent_raw = isset($_POST['wcwp-cart-consent']) ? sanitize_text_field(wp_unslash($_POST['wcwp-cart-consent'])) : '';
    $consent = $consent_raw === 'yes' ? 'yes' : 'no';
    $order = wc_get_order( $order_id );
    if ( ! $order ) {
        return;
    }
    $order->update_meta_data( '_wcwp_cart_consent', $consent );
    $order->update_meta_data( '_wcwp_cart_consent_time', current_time( 'mysql' ) );
    $order->save();
});

/**
 * Format raw cart-item dicts into the "- name × qty" lines used both in the
 * WhatsApp message body and the admin "recent attempts" preview.
 */
function wcwp_format_cart_items_list($cart_items) {
    $items = [];
    if (!is_array($cart_items)) return $items;
    foreach ($cart_items as $item) {
        $name = sanitize_text_field($item['name'] ?? '');
        $qty  = intval($item['qty'] ?? 0);
        $items[] = "- $name × $qty";
    }
    return $items;
}

function wcwp_sum_cart_items_total($cart_items) {
    $total = 0.0;
    if (!is_array($cart_items)) return $total;
    foreach ($cart_items as $item) {
        $price = floatval($item['price'] ?? 0);
        $qty   = intval($item['qty'] ?? 0);
        $total += $price * $qty;
    }
    return $total;
}

/**
 * Render the cart-recovery WhatsApp message from cart contents using the
 * current admin-configured template. Used by the queue processor (with a
 * tracking-wrapped cart URL) and by the admin "recent attempts" view (with
 * the bare cart URL).
 */
function wcwp_render_cart_recovery_message($items, $total, $cart_url, $template = null) {
    if ($template === null || $template === '') {
        $template = get_option('wcwp_cart_recovery_message', "👋 Hey! You left items in your cart:\n\n{items}\n\nTotal: {total} {currency_symbol}\nClick here to complete your order: {cart_url}");
    }
    return str_replace(
        ['{items}', '{total}', '{cart_url}', '{currency_symbol}'],
        [implode("\n", $items), $total, $cart_url, wcwp_currency_symbol_text()],
        $template
    );
}

/**
 * Fetch the latest cart-recovery rows for the admin "Recent Attempts" view.
 *
 * Reads the {prefix}wcwp_abandoned_carts table directly — there is one row
 * per phone+cart, regardless of how many retry attempts were made (the row's
 * `attempts` counter tracks that). Each rendered record uses the current
 * template, so admins see what would actually be sent if they hit Resend now.
 */
function wcwp_get_cart_recovery_attempts() {
    global $wpdb;
    $table = wcwp_get_cart_table_name();
    $rows = $wpdb->get_results($wpdb->prepare(
        "SELECT id, phone, cart_json, total, consent, consent_time, status, attempts, updated_at FROM {$table} ORDER BY updated_at DESC LIMIT %d",
        25
    ));
    if (!$rows) return [];

    $cart_url = wc_get_cart_url();
    $attempts = [];
    foreach ($rows as $row) {
        $cart_items = json_decode($row->cart_json, true);
        $items = wcwp_format_cart_items_list($cart_items);
        $message = wcwp_render_cart_recovery_message($items, $row->total, $cart_url);
        $attempts[] = [
            'id'           => (string) $row->id,
            'time'         => $row->updated_at,
            'phone'        => $row->phone,
            'message'      => $message,
            'items'        => $items,
            'total'        => $row->total,
            'consent'      => $row->consent,
            'consent_time' => $row->consent_time,
            'status'       => $row->status,
            'attempts'     => intval($row->attempts),
        ];
    }
    return $attempts;
}

// Admin resend handler
add_action('wp_ajax_wcwp_resend_cart_recovery', function() {
    if (!current_user_can('manage_woocommerce')) wp_send_json_error(['message' => __('Unauthorized', 'woochat-pro')], 403);
    if (!check_ajax_referer('wcwp_resend_cart', 'nonce', false)) wp_send_json_error(['message' => __('Bad nonce', 'woochat-pro')], 400);

    $attempt_id = isset($_POST['attempt_id']) ? absint(wp_unslash($_POST['attempt_id'])) : 0;
    if (!$attempt_id) wp_send_json_error(['message' => __('Missing attempt id', 'woochat-pro')], 400);

    global $wpdb;
    $table = wcwp_get_cart_table_name();
    $row = $wpdb->get_row($wpdb->prepare("SELECT phone, cart_json, total FROM {$table} WHERE id = %d", $attempt_id));
    if (!$row) wp_send_json_error(['message' => __('Attempt not found', 'woochat-pro')], 404);

    $cart_items = json_decode($row->cart_json, true);
    if (!is_array($cart_items) || empty($cart_items)) wp_send_json_error(['message' => __('Invalid cart data', 'woochat-pro')], 400);

    $items = wcwp_format_cart_items_list($cart_items);
    $message = wcwp_render_cart_recovery_message($items, $row->total, wc_get_cart_url());

    $result = wcwp_send_whatsapp_message($row->phone, $message, true, ['type' => 'cart_recovery']);
    if ($result === true) {
        wp_send_json_success(['message' => __('Resent', 'woochat-pro')]);
    }
    wp_send_json_error(['message' => __('Send failed', 'woochat-pro')]);
});
