<?php
/**
 * PHPUnit bootstrap.
 *
 * Tests run in isolation — no WordPress install, no database. The minimum WP
 * API surface the helpers under test actually call is stubbed below; tests
 * inject behaviour by writing to `$GLOBALS['wcwp_test_*']` between cases.
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/');
}

if (!function_exists('add_action')) {
    function add_action(...$args) { /* no-op for unit tests */ }
}
if (!function_exists('add_filter')) {
    function add_filter(...$args) { /* no-op for unit tests */ }
}
if (!function_exists('apply_filters')) {
    function apply_filters($tag, $value) { return $value; }
}
if (!function_exists('get_option')) {
    function get_option($key, $default = false) {
        return $GLOBALS['wcwp_test_options'][$key] ?? $default;
    }
}
if (!function_exists('get_woocommerce_currency_symbol')) {
    function get_woocommerce_currency_symbol($currency = '') {
        return $GLOBALS['wcwp_test_currency_symbol'] ?? '&#36;';
    }
}
if (!function_exists('get_bloginfo')) {
    function get_bloginfo($key = '') {
        return $GLOBALS['wcwp_test_bloginfo'][$key] ?? '';
    }
}
if (!function_exists('__')) {
    function __($text, $domain = '') { return $text; }
}
if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($value) { return is_string($value) ? trim($value) : ''; }
}
if (!function_exists('sanitize_textarea_field')) {
    function sanitize_textarea_field($value) { return is_string($value) ? trim($value) : ''; }
}
if (!function_exists('wp_json_encode')) {
    function wp_json_encode($data, $options = 0, $depth = 512) {
        return json_encode($data, $options, $depth);
    }
}
if (!function_exists('esc_url')) {
    function esc_url($url) { return $url; }
}
if (!function_exists('esc_attr')) {
    function esc_attr($value) { return $value; }
}
if (!function_exists('esc_html')) {
    function esc_html($value) { return $value; }
}
if (!function_exists('sanitize_html_class')) {
    function sanitize_html_class($class) { return preg_replace('/[^A-Za-z0-9_-]/', '', (string) $class); }
}
if (!function_exists('wp_enqueue_style')) {
    function wp_enqueue_style(...$args) { /* no-op for unit tests */ }
}
if (!function_exists('add_query_arg')) {
    // Minimal three-arg form (key, value, url) — enough for the block render
    // tests. The real WP function handles arrays + various url shapes.
    function add_query_arg($key, $value, $url) {
        $sep = strpos($url, '?') === false ? '?' : '&';
        return $url . $sep . $key . '=' . urlencode((string) $value);
    }
}

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/cart-recovery.php';
require_once __DIR__ . '/../includes/campaigns.php';
require_once __DIR__ . '/../includes/blocks.php';
