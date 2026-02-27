<?php
if (!defined('ABSPATH')) exit;

// Load chatbot widget in footer
add_action('wp_footer', 'wcwp_render_chatbot_widget');
add_shortcode('woochat_chatbot', 'wcwp_chatbot_shortcode');

function wcwp_render_chatbot_widget() {
    if (is_admin()) return;

    if (!function_exists('wcwp_is_pro_active') || !wcwp_is_pro_active()) return;

    $enabled = get_option('wcwp_chatbot_enabled', 'yes');
    if ($enabled !== 'yes') return;

    $settings = wcwp_get_chatbot_settings();

    // Prevent double render if shortcode already printed
    if (defined('WCWP_CHATBOT_RENDERED') && WCWP_CHATBOT_RENDERED) return;

    include WCWP_PATH . 'templates/chatbot-widget.php';
    if (!defined('WCWP_CHATBOT_RENDERED')) {
        define('WCWP_CHATBOT_RENDERED', true);
    }

    wp_enqueue_script('wcwp-chatbot-js', WCWP_URL . 'assets/js/chatbot.js', ['jquery'], WCWP_VERSION, true);

    $faq_pairs = json_decode(get_option('wcwp_faq_pairs', '[]'), true);
    if (!is_array($faq_pairs)) $faq_pairs = [];

    wp_localize_script('wcwp-chatbot-js', 'wcwp_chatbot_obj', [
        'faq_pairs' => $faq_pairs
    ]);
}

function wcwp_chatbot_shortcode() {
    if (!function_exists('wcwp_is_pro_active') || !wcwp_is_pro_active()) return '';
    $enabled = get_option('wcwp_chatbot_enabled', 'yes');
    if ($enabled !== 'yes') return '';

    ob_start();
    $settings = wcwp_get_chatbot_settings();
    include WCWP_PATH . 'templates/chatbot-widget.php';
    wp_enqueue_script('wcwp-chatbot-js', WCWP_URL . 'assets/js/chatbot.js', ['jquery'], WCWP_VERSION, true);
    $faq_pairs = json_decode(get_option('wcwp_faq_pairs', '[]'), true);
    if (!is_array($faq_pairs)) $faq_pairs = [];
    wp_localize_script('wcwp-chatbot-js', 'wcwp_chatbot_obj', [
        'faq_pairs' => $faq_pairs
    ]);
    if (!defined('WCWP_CHATBOT_RENDERED')) {
        define('WCWP_CHATBOT_RENDERED', true);
    }
    return ob_get_clean();
}

function wcwp_get_chatbot_settings() {
    return [
        'bubble_color' => get_option('wcwp_chatbot_bg', '#1c7c54'),
        'text_color' => get_option('wcwp_chatbot_text', '#ffffff'),
        'icon_color' => get_option('wcwp_chatbot_icon_color', '#2ec4b6'),
        'icon' => get_option('wcwp_chatbot_icon', '💬'),
        'welcome' => get_option('wcwp_chatbot_welcome', 'Hi! How can I help you?'),
    ];
}
