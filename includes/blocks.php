<?php
/**
 * Gutenberg block registration for the chatbot widget and the WhatsApp
 * call-to-action button. Both blocks are server-rendered via PHP render
 * callbacks so attribute changes are picked up without a static save().
 */

if (!defined('ABSPATH')) exit;

add_action('init', 'wcwp_register_blocks');

function wcwp_register_blocks() {
    if (!function_exists('register_block_type')) return;

    wp_register_style(
        'wcwp-blocks-css',
        WCWP_URL . 'assets/css/blocks.css',
        [],
        WCWP_VERSION
    );

    register_block_type(WCWP_PATH . 'blocks/chatbot', [
        'render_callback' => 'wcwp_render_chatbot_block',
    ]);

    register_block_type(WCWP_PATH . 'blocks/whatsapp-button', [
        'render_callback' => 'wcwp_render_whatsapp_button_block',
    ]);
}

/**
 * Chatbot block — defers to the existing shortcode renderer so the widget
 * stays a single source of truth (FAQ matching, GPT fallback, agent
 * routing all happen in chatbot-engine.php).
 */
function wcwp_render_chatbot_block($attributes = []) {
    if (!function_exists('wcwp_chatbot_shortcode')) return '';
    return (string) wcwp_chatbot_shortcode();
}

/**
 * WhatsApp button block — emits a static anchor pointing at wa.me.
 *
 * Phone is normalized to digits-only (wa.me ignores punctuation). Empty
 * phone is allowed and renders the legacy contact-picker URL — useful
 * during page authoring before the admin has a number to plug in.
 */
function wcwp_render_whatsapp_button_block($attributes = []) {
    $phone   = isset($attributes['phone']) ? wcwp_normalize_phone((string) $attributes['phone']) : '';
    $text    = isset($attributes['text']) && $attributes['text'] !== ''
        ? sanitize_text_field((string) $attributes['text'])
        : __('Chat on WhatsApp', 'woochat-pro');
    $message = isset($attributes['message']) ? sanitize_text_field((string) $attributes['message']) : '';

    $url = 'https://wa.me/' . $phone;
    if ($message !== '') {
        // add_query_arg URL-encodes the value.
        $url = add_query_arg('text', $message, $url);
    }

    $align = isset($attributes['align']) ? sanitize_html_class((string) $attributes['align']) : '';
    $wrapper_class = 'wcwp-whatsapp-button-block';
    if ($align !== '') {
        $wrapper_class .= ' align' . $align;
    }

    wp_enqueue_style('wcwp-blocks-css');

    return sprintf(
        '<p class="%s"><a class="wcwp-whatsapp-button" href="%s" target="_blank" rel="noopener nofollow">%s</a></p>',
        esc_attr($wrapper_class),
        esc_url($url),
        esc_html($text)
    );
}
