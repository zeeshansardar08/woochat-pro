<?php
/**
 * Gutenberg block registration for the chatbot widget and the WhatsApp
 * call-to-action button. Both blocks are server-rendered via PHP render
 * callbacks so attribute changes are picked up without a static save().
 */

if (!defined('ABSPATH')) exit;

add_action('init', 'zignites_chat_register_blocks');

function zignites_chat_register_blocks() {
    if (!function_exists('register_block_type')) return;

    wp_register_style(
        'zignites-chat-blocks-css',
        ZIGNITES_CHAT_URL . 'assets/css/blocks.css',
        [],
        ZIGNITES_CHAT_VERSION
    );

    register_block_type(ZIGNITES_CHAT_PATH . 'blocks/chatbot', [
        'render_callback' => 'zignites_chat_render_chatbot_block',
    ]);

    register_block_type(ZIGNITES_CHAT_PATH . 'blocks/whatsapp-button', [
        'render_callback' => 'zignites_chat_render_whatsapp_button_block',
    ]);
}

/**
 * Chatbot block — defers to the existing shortcode renderer so the widget
 * stays a single source of truth (FAQ matching, GPT fallback, agent
 * routing all happen in chatbot-engine.php).
 */
function zignites_chat_render_chatbot_block($attributes = []) {
    if (!function_exists('zignites_chat_chatbot_shortcode')) return '';
    return (string) zignites_chat_chatbot_shortcode();
}

/**
 * WhatsApp button block — emits a static anchor pointing at wa.me.
 *
 * Phone is normalized to digits-only (wa.me ignores punctuation). Empty
 * phone is allowed and renders the legacy contact-picker URL — useful
 * during page authoring before the admin has a number to plug in.
 */
function zignites_chat_render_whatsapp_button_block($attributes = []) {
    $phone   = isset($attributes['phone']) ? zignites_chat_normalize_phone((string) $attributes['phone']) : '';
    $text    = isset($attributes['text']) && $attributes['text'] !== ''
        ? sanitize_text_field((string) $attributes['text'])
        : __('Chat on WhatsApp', 'zignites-chat');
    $message = isset($attributes['message']) ? sanitize_text_field((string) $attributes['message']) : '';

    $url = 'https://wa.me/' . $phone;
    if ($message !== '') {
        // add_query_arg URL-encodes the value.
        $url = add_query_arg('text', $message, $url);
    }

    $align = isset($attributes['align']) ? sanitize_html_class((string) $attributes['align']) : '';
    $wrapper_class = 'zignites-chat-whatsapp-button-block';
    if ($align !== '') {
        $wrapper_class .= ' align' . $align;
    }

    wp_enqueue_style('zignites-chat-blocks-css');

    return sprintf(
        '<p class="%s"><a class="zignites-chat-whatsapp-button" href="%s" target="_blank" rel="noopener nofollow">%s</a></p>',
        esc_attr($wrapper_class),
        esc_url($url),
        esc_html($text)
    );
}
