<?php
/**
 * Back-in-stock alerts (Pro) — roadmap Q1.
 *
 * Lets shoppers subscribe on an out-of-stock product to be notified over
 * WhatsApp when it is restocked. Subscriptions are stored in a custom table;
 * when a product flips to "instock" a background processor sends the alerts
 * (chunked, opt-out-aware, deferred by quiet hours / the rate limiter).
 *
 * The message renderer and status helper are pure and unit-tested; the rest is
 * WooCommerce + storage glue.
 *
 * @package Zignites_Chat
 */

if (!defined('ABSPATH')) exit;

/* -------------------------------------------------------------------------
 * Schema
 * ----------------------------------------------------------------------- */

function zignites_chat_stock_subs_table_name() {
    global $wpdb;
    return $wpdb->prefix . 'zignites_chat_stock_subs';
}

/**
 * dbDelta-based, idempotent. Called from migration v8 and the activation hook.
 */
function zignites_chat_create_stock_subs_table() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    $table = zignites_chat_stock_subs_table_name();

    $sql = "CREATE TABLE {$table} (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        product_id BIGINT UNSIGNED NOT NULL,
        phone VARCHAR(40) NOT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'pending',
        created_at DATETIME NOT NULL,
        notified_at DATETIME NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY product_phone (product_id, phone),
        KEY product_status (product_id, status)
    ) {$charset_collate};";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}

/* -------------------------------------------------------------------------
 * Pure helpers (no DB) — unit-tested
 * ----------------------------------------------------------------------- */

/**
 * Whether a WooCommerce stock-status value means "available". Pure.
 *
 * @param string $status
 * @return bool
 */
function zignites_chat_stock_is_instock($status) {
    return (string) $status === 'instock';
}

/**
 * Render the alert template by substituting placeholders. Pure.
 *
 * @param string $template
 * @param array  $values Map of '{placeholder}' => replacement.
 * @return string
 */
function zignites_chat_stock_render_message($template, $values) {
    if (!is_array($values)) {
        return (string) $template;
    }
    return str_replace(array_keys($values), array_values($values), (string) $template);
}

/* -------------------------------------------------------------------------
 * Subscription storage
 * ----------------------------------------------------------------------- */

/**
 * Record (or re-arm) a back-in-stock subscription.
 *
 * @param int    $product_id
 * @param string $phone Customer phone (any format).
 * @return bool True when stored.
 */
function zignites_chat_stock_subscribe($product_id, $phone) {
    global $wpdb;
    $product_id = (int) $product_id;
    $phone = zignites_chat_normalize_phone($phone);
    if ($product_id <= 0 || $phone === '') {
        return false;
    }
    $table = zignites_chat_stock_subs_table_name();
    $now = current_time('mysql');

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$table} WHERE product_id = %d AND phone = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $product_id,
        $phone
    ));

    if ($existing) {
        // Re-arm a previously-notified (or still-pending) subscription.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->update(
            $table,
            ['status' => 'pending', 'created_at' => $now, 'notified_at' => null],
            ['id' => (int) $existing],
            ['%s', '%s', '%s'],
            ['%d']
        );
        return true;
    }

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $wpdb->insert(
        $table,
        ['product_id' => $product_id, 'phone' => $phone, 'status' => 'pending', 'created_at' => $now],
        ['%d', '%s', '%s', '%s']
    );
    return (bool) $wpdb->insert_id;
}

/**
 * Count pending subscriptions for a product.
 *
 * @param int $product_id
 * @return int
 */
function zignites_chat_stock_pending_count($product_id) {
    global $wpdb;
    $table = zignites_chat_stock_subs_table_name();
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    return (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$table} WHERE product_id = %d AND status = 'pending'",
        (int) $product_id
    ));
}

/* -------------------------------------------------------------------------
 * Restock trigger → background processor
 * ----------------------------------------------------------------------- */

add_action('woocommerce_product_set_stock_status', 'zignites_chat_stock_on_status_change', 20, 2);
add_action('woocommerce_variation_set_stock_status', 'zignites_chat_stock_on_status_change', 20, 2);
add_action('zignites_chat_process_stock_alerts', 'zignites_chat_stock_process_alerts');

/**
 * When a product flips to "instock", queue its alert processor.
 *
 * @param int    $product_id
 * @param string $stock_status
 * @return void
 */
function zignites_chat_stock_on_status_change($product_id, $stock_status) {
    if (get_option('zignites_chat_stock_alerts_enabled', 'no') !== 'yes') {
        return;
    }
    if (function_exists('zignites_chat_is_pro_active') && !zignites_chat_is_pro_active()) {
        return;
    }
    if (!zignites_chat_stock_is_instock($stock_status)) {
        return;
    }
    $product_id = (int) $product_id;
    if (zignites_chat_stock_pending_count($product_id) < 1) {
        return;
    }
    if (!wp_next_scheduled('zignites_chat_process_stock_alerts', [$product_id])) {
        wp_schedule_single_event(time() + 30, 'zignites_chat_process_stock_alerts', [$product_id]);
    }
}

/**
 * Send back-in-stock alerts for a product's pending subscribers.
 *
 * Chunked + deferred (quiet hours, rate limiter) so a large list doesn't
 * stall and respects sending policy. Opt-outs are skipped.
 *
 * @param int $product_id
 * @return void
 */
function zignites_chat_stock_process_alerts($product_id) {
    global $wpdb;
    $product_id = (int) $product_id;
    if ($product_id <= 0 || get_option('zignites_chat_stock_alerts_enabled', 'no') !== 'yes') {
        return;
    }
    if (function_exists('zignites_chat_is_pro_active') && !zignites_chat_is_pro_active()) {
        return;
    }
    if (!function_exists('wc_get_product')) {
        return;
    }

    // Quiet hours: resume after the window.
    if (function_exists('zignites_chat_quiet_hours_active') && zignites_chat_quiet_hours_active()) {
        $resume = function_exists('zignites_chat_quiet_hours_resume_seconds') ? zignites_chat_quiet_hours_resume_seconds() : 0;
        wp_schedule_single_event(time() + max(60, $resume), 'zignites_chat_process_stock_alerts', [$product_id]);
        return;
    }

    $product = wc_get_product($product_id);
    // If it went out of stock again before we sent, stop — re-armed when restocked.
    if (!$product || !$product->is_in_stock()) {
        return;
    }

    $table = zignites_chat_stock_subs_table_name();
    $chunk = max(1, min(100, (int) apply_filters('zignites_chat_stock_chunk_size', 20)));
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    $rows = $wpdb->get_results($wpdb->prepare(
        "SELECT id, phone FROM {$table} WHERE product_id = %d AND status = 'pending' ORDER BY id ASC LIMIT %d",
        $product_id,
        $chunk
    ));
    if (!$rows) {
        return;
    }

    $template = get_option(
        'zignites_chat_stock_alert_message',
        __('Good news! {product} is back in stock. Grab it here: {product_url}', 'zignites-chat')
    );
    $values_base = [
        '{product}'     => $product->get_name(),
        '{product_url}' => get_permalink($product_id),
        '{site}'        => function_exists('get_bloginfo') ? get_bloginfo('name') : '',
    ];

    $now = current_time('mysql');
    foreach ($rows as $row) {
        if (zignites_chat_is_opted_out($row->phone)) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->update($table, ['status' => 'opted_out', 'notified_at' => $now], ['id' => (int) $row->id], ['%s', '%s'], ['%d']);
            continue;
        }
        // Shared per-minute budget: stop this run when exhausted; remaining
        // subs stay pending and the reschedule below picks them up.
        if (function_exists('zignites_chat_outbound_rate_acquire') && !zignites_chat_outbound_rate_acquire()) {
            break;
        }

        $message = zignites_chat_stock_render_message($template, $values_base);
        zignites_chat_send_whatsapp_message($row->phone, $message, false, [
            'type'       => 'back_in_stock',
            'product_id' => $product_id,
        ]);
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->update($table, ['status' => 'notified', 'notified_at' => $now], ['id' => (int) $row->id], ['%s', '%s'], ['%d']);
    }

    if (zignites_chat_stock_pending_count($product_id) > 0) {
        wp_schedule_single_event(time() + max(10, (int) apply_filters('zignites_chat_stock_chunk_interval', MINUTE_IN_SECONDS)), 'zignites_chat_process_stock_alerts', [$product_id]);
    }
}

/* -------------------------------------------------------------------------
 * Frontend opt-in form + subscribe AJAX
 * ----------------------------------------------------------------------- */

add_action('woocommerce_single_product_summary', 'zignites_chat_stock_render_form', 35);

/**
 * Render the "notify me on WhatsApp" form on an out-of-stock product page.
 */
function zignites_chat_stock_render_form() {
    if (get_option('zignites_chat_stock_alerts_enabled', 'no') !== 'yes') {
        return;
    }
    if (function_exists('zignites_chat_is_pro_active') && !zignites_chat_is_pro_active()) {
        return;
    }
    global $product;
    if (!is_object($product) || $product->is_in_stock()) {
        return;
    }

    wp_enqueue_script('zignites-chat-back-in-stock', ZIGNITES_CHAT_URL . 'assets/js/back-in-stock.js', [], ZIGNITES_CHAT_VERSION, true);
    wp_localize_script('zignites-chat-back-in-stock', 'zignitesChatStock', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('zignites_chat_stock'),
        'i18n'    => [
            'sending' => __('Sending…', 'zignites-chat'),
            'error'   => __('Could not subscribe. Please try again.', 'zignites-chat'),
            'empty'   => __('Please enter your WhatsApp number.', 'zignites-chat'),
        ],
    ]);

    $heading = get_option('zignites_chat_stock_form_heading', __('Notify me on WhatsApp when it’s back', 'zignites-chat'));
    echo '<div class="zignites-chat-stock-form" data-product="' . esc_attr((string) $product->get_id()) . '">';
    echo '<p class="zignites-chat-stock-heading">' . esc_html($heading) . '</p>';
    echo '<input type="tel" class="zignites-chat-stock-phone" placeholder="' . esc_attr__('e.g. +1 415 555 0100', 'zignites-chat') . '" />';
    echo '<button type="button" class="button zignites-chat-stock-submit">' . esc_html__('Notify me', 'zignites-chat') . '</button>';
    echo '<span class="zignites-chat-stock-msg" role="status"></span>';
    echo '</div>';
}

add_action('wp_ajax_zignites_chat_stock_subscribe', 'zignites_chat_stock_subscribe_ajax');
add_action('wp_ajax_nopriv_zignites_chat_stock_subscribe', 'zignites_chat_stock_subscribe_ajax');

/**
 * Handle a back-in-stock subscription request from the storefront.
 */
function zignites_chat_stock_subscribe_ajax() {
    if (!check_ajax_referer('zignites_chat_stock', 'nonce', false)) {
        wp_send_json_error(['message' => __('Expired — please refresh and try again.', 'zignites-chat')], 400);
    }
    if (get_option('zignites_chat_stock_alerts_enabled', 'no') !== 'yes'
        || (function_exists('zignites_chat_is_pro_active') && !zignites_chat_is_pro_active())) {
        wp_send_json_error(['message' => __('Unavailable', 'zignites-chat')], 403);
    }

    $product_id = isset($_POST['product_id']) ? (int) $_POST['product_id'] : 0;
    $phone      = isset($_POST['phone']) ? zignites_chat_normalize_phone(wp_unslash($_POST['phone'])) : '';
    if ($product_id <= 0 || strlen($phone) < 7) {
        wp_send_json_error(['message' => __('Please enter a valid WhatsApp number.', 'zignites-chat')], 422);
    }

    if (!zignites_chat_stock_subscribe($product_id, $phone)) {
        wp_send_json_error(['message' => __('Could not subscribe. Please try again.', 'zignites-chat')], 500);
    }

    wp_send_json_success(['message' => __('Done! We’ll WhatsApp you when it’s back in stock.', 'zignites-chat')]);
}
