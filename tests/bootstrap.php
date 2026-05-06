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

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/cart-recovery.php';
