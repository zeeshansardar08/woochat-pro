<?php
if (!defined('ABSPATH')) exit;

// Load chatbot widget in footer
add_action('wp_footer', 'wcwp_render_chatbot_widget');

function wcwp_render_chatbot_widget() {
    if (is_admin()) return;

    if (!function_exists('wcwp_is_pro_active') || !wcwp_is_pro_active()) return;

    $enabled = get_option('wcwp_chatbot_enabled', 'yes');
    if ($enabled !== 'yes') return;

    include WCWP_PATH . 'templates/chatbot-widget.php';

    wp_enqueue_script('wcwp-chatbot-js', WCWP_URL . 'assets/js/chatbot.js', ['jquery'], null, true);

    $faq_pairs = json_decode(get_option('wcwp_faq_pairs', '[]'), true);
    if (!is_array($faq_pairs)) $faq_pairs = [];

    wp_localize_script('wcwp-chatbot-js', 'wcwp_chatbot_obj', [
        'faq_pairs' => $faq_pairs
    ]);
}
