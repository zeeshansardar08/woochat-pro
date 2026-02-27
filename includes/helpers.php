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
