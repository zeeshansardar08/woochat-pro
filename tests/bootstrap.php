<?php
/**
 * PHPUnit bootstrap.
 *
 * Tests run in isolation — no WordPress install, no database. The minimum WP
 * API surface the helpers under test actually call is stubbed below; tests
 * inject behaviour by writing to `$GLOBALS['zignites_chat_test_*']` between cases.
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
        return $GLOBALS['zignites_chat_test_options'][$key] ?? $default;
    }
}
if (!function_exists('get_woocommerce_currency_symbol')) {
    function get_woocommerce_currency_symbol($currency = '') {
        return $GLOBALS['zignites_chat_test_currency_symbol'] ?? '&#36;';
    }
}
if (!function_exists('get_bloginfo')) {
    function get_bloginfo($key = '') {
        return $GLOBALS['zignites_chat_test_bloginfo'][$key] ?? '';
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
if (!function_exists('wp_strip_all_tags')) {
    function wp_strip_all_tags($string, $remove_breaks = false) {
        $string = preg_replace('@<(script|style)[^>]*?>.*?</\\1>@si', '', (string) $string);
        $string = strip_tags($string);
        if ($remove_breaks) {
            $string = preg_replace('/[\r\n\t ]+/', ' ', $string);
        }
        return trim($string);
    }
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
if (!function_exists('sanitize_key')) {
    function sanitize_key($key) { return preg_replace('/[^a-z0-9_\-]/', '', strtolower((string) $key)); }
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
if (!function_exists('esc_url_raw')) {
    function esc_url_raw($url) { return is_string($url) ? trim($url) : ''; }
}
if (!function_exists('wp_parse_url')) {
    function wp_parse_url($url, $component = -1) {
        $parts = parse_url((string) $url);
        if ($component === -1) return $parts === false ? false : $parts;
        $key_map = [
            PHP_URL_SCHEME => 'scheme', PHP_URL_HOST => 'host', PHP_URL_PORT => 'port',
            PHP_URL_USER => 'user', PHP_URL_PASS => 'pass', PHP_URL_PATH => 'path',
            PHP_URL_QUERY => 'query', PHP_URL_FRAGMENT => 'fragment',
        ];
        $key = $key_map[$component] ?? null;
        return $key && is_array($parts) && isset($parts[$key]) ? $parts[$key] : null;
    }
}
if (!function_exists('wp_generate_password')) {
    function wp_generate_password($length = 12, $special_chars = true, $extra_special_chars = false) {
        // Deterministic stub: counter-prefixed string up to $length.
        static $counter = 0;
        $counter++;
        $base = 'testpw' . $counter . str_repeat('x', max(0, (int) $length - 8));
        return substr($base, 0, max(1, (int) $length));
    }
}
if (!function_exists('current_time')) {
    function current_time($type = 'mysql', $gmt = 0) {
        return $GLOBALS['zignites_chat_test_current_time'] ?? '2026-05-09 12:34:56';
    }
}
if (!defined('MINUTE_IN_SECONDS')) {
    define('MINUTE_IN_SECONDS', 60);
}
if (!defined('HOUR_IN_SECONDS')) {
    define('HOUR_IN_SECONDS', 3600);
}

if (!defined('DAY_IN_SECONDS')) {
    define('DAY_IN_SECONDS', 86400);
}

// license-manager.php is not loaded in unit tests; stub the Pro gate so
// feature code under test runs. Defaults to true (Pro behaviour); a test can
// set $GLOBALS['zignites_chat_test_is_pro'] = false to exercise the free-tier path.
if (!function_exists('zignites_chat_is_pro_active')) {
    function zignites_chat_is_pro_active() {
        return $GLOBALS['zignites_chat_test_is_pro'] ?? true;
    }
}

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/wa-templates.php';
require_once __DIR__ . '/../includes/wa-template-sync.php';
require_once __DIR__ . '/../includes/sender-health.php';
require_once __DIR__ . '/../includes/media.php';
require_once __DIR__ . '/../includes/cart-recovery.php';
require_once __DIR__ . '/../includes/campaigns.php';
require_once __DIR__ . '/../includes/back-in-stock.php';
require_once __DIR__ . '/../includes/review-request.php';
require_once __DIR__ . '/../includes/drip-sequences.php';
require_once __DIR__ . '/../includes/inbox.php';
require_once __DIR__ . '/../includes/inbox-capture.php';
require_once __DIR__ . '/../includes/inbox-admin.php';
require_once __DIR__ . '/../includes/catalog-context.php';
require_once __DIR__ . '/../includes/rate-limiter.php';
require_once __DIR__ . '/../includes/quiet-hours.php';
require_once __DIR__ . '/../includes/cod-confirmation.php';
require_once __DIR__ . '/../includes/status-notifications.php';
require_once __DIR__ . '/../includes/optin.php';
require_once __DIR__ . '/../includes/optout.php';
require_once __DIR__ . '/../includes/blocks.php';
require_once __DIR__ . '/../includes/analytics.php';
require_once __DIR__ . '/../includes/delivery-receipts.php';
require_once __DIR__ . '/../includes/template-library.php';
require_once __DIR__ . '/../includes/ab-testing.php';
require_once __DIR__ . '/../includes/privacy.php';
require_once __DIR__ . '/../includes/log-viewer.php';
require_once __DIR__ . '/../includes/webhooks.php';
