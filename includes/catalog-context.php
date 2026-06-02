<?php
/**
 * Store-catalog context for the GPT chatbot (Pro, opt-in).
 *
 * When enabled, a compact summary of the store's products is injected into the
 * chatbot's system prompt so it can answer "do you sell X / how much is Y"
 * questions grounded in real data instead of guessing.
 *
 * The string builder is pure (and unit-tested); the WooCommerce fetch wraps it
 * and caches the result in a transient so the catalog is not re-queried on
 * every chatbot message.
 *
 * @package Zignites_Chat
 */

if (!defined('ABSPATH')) exit;

/**
 * Build a compact catalog summary string from product rows.
 *
 * Pure: no WooCommerce, no DB. Each product is rendered as "- Name — Price"
 * (price omitted when empty), one per line, capped at $max_chars so the system
 * prompt stays small. Returns '' when there is nothing to summarize.
 *
 * @param array $products  List of ['name' => string, 'price' => string] rows.
 * @param int   $max_chars Soft cap on the returned string length. Default 1200.
 * @return string Catalog summary, or '' when empty.
 */
function zignites_chat_build_catalog_context($products, $max_chars = 1200) {
    if (!is_array($products) || empty($products)) {
        return '';
    }
    $max_chars = max(0, (int) $max_chars);

    $lines = [];
    $length = 0;
    foreach ($products as $product) {
        if (!is_array($product)) {
            continue;
        }
        $name = isset($product['name']) ? trim(wp_strip_all_tags((string) $product['name'])) : '';
        if ($name === '') {
            continue;
        }
        $price = isset($product['price']) ? trim(wp_strip_all_tags((string) $product['price'])) : '';

        $line = '- ' . $name;
        if ($price !== '') {
            $line .= ' — ' . $price;
        }

        // Stop before exceeding the cap (always keep at least the first line).
        if ($max_chars > 0 && !empty($lines) && ($length + strlen($line) + 1) > $max_chars) {
            break;
        }
        $lines[] = $line;
        $length += strlen($line) + 1;
    }

    return implode("\n", $lines);
}

/**
 * Fetch and cache the store-catalog context string.
 *
 * Pulls up to N published products via WooCommerce, maps them to plain
 * name/price rows, and runs them through the pure builder. Cached in a
 * transient (default 1h) so the chatbot reply path stays cheap. Returns ''
 * when WooCommerce is unavailable or there are no products.
 *
 * @return string Catalog summary for the system prompt.
 */
function zignites_chat_get_catalog_context() {
    $cached = get_transient('zignites_chat_catalog_context');
    if (is_string($cached)) {
        return $cached;
    }

    if (!function_exists('wc_get_products')) {
        return '';
    }

    /** @var int $limit Max products to include. */
    $limit = (int) apply_filters('zignites_chat_catalog_context_limit', 20);
    $limit = max(1, min(100, $limit));

    $products = wc_get_products([
        'status'  => 'publish',
        'limit'   => $limit,
        'orderby' => 'popularity',
        'order'   => 'DESC',
    ]);

    $rows = [];
    if (is_array($products)) {
        foreach ($products as $product) {
            if (!is_object($product) || !method_exists($product, 'get_name')) {
                continue;
            }
            $price = method_exists($product, 'get_price') ? (string) $product->get_price() : '';
            if ($price !== '' && function_exists('wc_price')) {
                $price = wp_strip_all_tags(html_entity_decode((string) wc_price($price), ENT_QUOTES, 'UTF-8'));
            }
            $rows[] = [
                'name'  => $product->get_name(),
                'price' => $price,
            ];
        }
    }

    /** @var int $max_chars Soft cap on the summary length. */
    $max_chars = (int) apply_filters('zignites_chat_catalog_context_max_chars', 1200);
    $context = zignites_chat_build_catalog_context($rows, $max_chars);

    /** @var int $ttl Cache lifetime in seconds. */
    $ttl = (int) apply_filters('zignites_chat_catalog_context_ttl', HOUR_IN_SECONDS);
    set_transient('zignites_chat_catalog_context', $context, max(60, $ttl));

    return $context;
}

/**
 * Clear the cached catalog context.
 *
 * Hooked to product saves/deletes and to the toggle being turned on so a
 * change is reflected without waiting for the transient to expire.
 */
function zignites_chat_clear_catalog_context_cache() {
    delete_transient('zignites_chat_catalog_context');
}
add_action('save_post_product', 'zignites_chat_clear_catalog_context_cache');
add_action('woocommerce_update_product', 'zignites_chat_clear_catalog_context_cache');
add_action('woocommerce_new_product', 'zignites_chat_clear_catalog_context_cache');
add_action('update_option_zignites_chat_chatbot_catalog_context', 'zignites_chat_clear_catalog_context_cache');
