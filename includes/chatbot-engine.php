<?php
if (!defined('ABSPATH')) exit;

// Load chatbot widget in footer
add_action('wp_footer', 'wcwp_render_chatbot_widget');
add_shortcode('woochat_chatbot', 'wcwp_chatbot_shortcode');

function wcwp_render_chatbot_widget() {
    if (is_admin()) return;

    if (!wcwp_is_pro_active()) return;

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
    wp_localize_script('wcwp-chatbot-js', 'wcwp_chatbot_obj', wcwp_chatbot_localized_data());
}

function wcwp_chatbot_shortcode() {
    if (!wcwp_is_pro_active()) return '';
    $enabled = get_option('wcwp_chatbot_enabled', 'yes');
    if ($enabled !== 'yes') return '';

    ob_start();
    $settings = wcwp_get_chatbot_settings();
    include WCWP_PATH . 'templates/chatbot-widget.php';
    wp_enqueue_script('wcwp-chatbot-js', WCWP_URL . 'assets/js/chatbot.js', ['jquery'], WCWP_VERSION, true);
    wp_localize_script('wcwp-chatbot-js', 'wcwp_chatbot_obj', wcwp_chatbot_localized_data());
    if (!defined('WCWP_CHATBOT_RENDERED')) {
        define('WCWP_CHATBOT_RENDERED', true);
    }
    return ob_get_clean();
}

/**
 * Single source of truth for the wcwp_chatbot_obj payload.
 *
 * Both the auto-render and shortcode paths call this so a new key
 * (e.g. the GPT-fallback wiring) can't go to one but not the other.
 */
function wcwp_chatbot_localized_data() {
    $faq_pairs = json_decode(get_option('wcwp_faq_pairs', '[]'), true);
    if (!is_array($faq_pairs)) $faq_pairs = [];

    return [
        'faq_pairs'    => $faq_pairs,
        'noAnswerText' => __( "Sorry, I don't have an answer for that.", 'woochat-pro' ),
        'gpt'          => [
            'enabled'  => get_option('wcwp_chatbot_gpt_enabled', 'no') === 'yes',
            'url'      => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('wcwp_chatbot_gpt'),
            'action'   => 'wcwp_chatbot_gpt',
            'thinking' => __('Thinking…', 'woochat-pro'),
        ],
        // Agents are picked client-side so a full-page cache can't pin every
        // visitor to the same agent under 'random' mode.
        'agents'        => wcwp_get_agents(),
        'routing_mode'  => get_option('wcwp_agent_routing_mode', 'single') === 'random' ? 'random' : 'single',
    ];
}

function wcwp_get_chatbot_settings() {
    // Hex colors are also validated at write-time via the register_setting
    // sanitize_callback; this is the read-time defense-in-depth that
    // guarantees the chatbot template never sees an invalid color, even
    // for values saved before the validator existed.
    return [
        'bubble_color' => wcwp_sanitize_hex_color(get_option('wcwp_chatbot_bg', '#1c7c54'), '#1c7c54'),
        'text_color'   => wcwp_sanitize_hex_color(get_option('wcwp_chatbot_text', '#ffffff'), '#ffffff'),
        'icon_color'   => wcwp_sanitize_hex_color(get_option('wcwp_chatbot_icon_color', '#2ec4b6'), '#2ec4b6'),
        'icon'         => get_option('wcwp_chatbot_icon', '💬'),
        'welcome'      => get_option('wcwp_chatbot_welcome', 'Hi! How can I help you?'),
    ];
}

add_action('wp_ajax_wcwp_chatbot_gpt', 'wcwp_ajax_chatbot_gpt');
add_action('wp_ajax_nopriv_wcwp_chatbot_gpt', 'wcwp_ajax_chatbot_gpt');
function wcwp_ajax_chatbot_gpt() {
    if (!check_ajax_referer('wcwp_chatbot_gpt', 'nonce', false)) {
        wp_send_json_error(['message' => __('Bad nonce', 'woochat-pro')], 400);
    }

    if (get_option('wcwp_chatbot_gpt_enabled', 'no') !== 'yes') {
        wp_send_json_error(['message' => __('GPT fallback disabled', 'woochat-pro')], 403);
    }

    // Defense in depth: the widget render paths already gate on Pro, but a
    // direct admin-ajax call would otherwise sail past the front-end gate.
    if (!wcwp_is_pro_active()) {
        wp_send_json_error(['message' => __('Pro license required', 'woochat-pro')], 403);
    }

    $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';
    if (!wcwp_chatbot_gpt_rate_limit_ok($ip)) {
        wp_send_json_error(['message' => __('Too many requests', 'woochat-pro')], 429);
    }

    $question = isset($_POST['question']) ? sanitize_text_field(wp_unslash($_POST['question'])) : '';
    if ($question === '') {
        wp_send_json_error(['message' => __('Empty question', 'woochat-pro')], 400);
    }

    // Cap input length: keeps the upstream prompt bounded and curbs misuse.
    if (function_exists('mb_substr')) {
        $question = mb_substr($question, 0, 500);
    } else {
        $question = substr($question, 0, 500);
    }

    $reply = wcwp_generate_chatbot_reply($question);
    if ($reply === '') {
        wp_send_json_error(['message' => __('No reply available', 'woochat-pro')], 502);
    }

    wp_send_json_success(['reply' => $reply]);
}

/**
 * Per-IP rate limit for the chatbot GPT fallback.
 *
 * Default is tighter than the opt-out limiter (10/hour vs 30) because each
 * call here costs the site owner real money at the GPT endpoint.
 *
 * @param string $ip Caller's IP.
 * @return bool True if within the limit, false if blocked.
 */
function wcwp_chatbot_gpt_rate_limit_ok($ip) {
    if (!$ip) return true;

    $limit  = (int) apply_filters('wcwp_chatbot_gpt_rate_limit', 10);
    $window = (int) apply_filters('wcwp_chatbot_gpt_rate_window', HOUR_IN_SECONDS);

    if ($limit < 1 || $window < 1) return true;

    $key  = 'wcwp_chatbot_gpt_rate_' . md5($ip);
    $data = get_transient($key);

    if (!is_array($data) || !isset($data['count'], $data['start'])) {
        set_transient($key, ['count' => 1, 'start' => time()], $window);
        return true;
    }

    if ((time() - (int) $data['start']) > $window) {
        set_transient($key, ['count' => 1, 'start' => time()], $window);
        return true;
    }

    if ((int) $data['count'] >= $limit) {
        return false;
    }

    $data['count'] = (int) $data['count'] + 1;
    set_transient($key, $data, $window);
    return true;
}

/**
 * Single-turn GPT reply for the chatbot.
 *
 * Mirrors the shape of wcwp_generate_gpt_followup() in scheduler.php but
 * with a chatbot-appropriate system prompt and a string input rather than
 * a WC_Order. Returns '' on any failure path so the JS can fall back to
 * noAnswerText without the user seeing an error.
 *
 * @param string $user_message
 * @return string
 */
function wcwp_generate_chatbot_reply($user_message) {
    $endpoint = trim(get_option('wcwp_gpt_api_endpoint', ''));
    $api_key  = trim(get_option('wcwp_gpt_api_key', ''));
    $model    = trim(get_option('wcwp_gpt_model', 'gpt-3.5-turbo'));
    if ($model === '') $model = 'gpt-3.5-turbo';

    if ($endpoint === '' || $api_key === '') return '';

    $system_prompt = apply_filters(
        'wcwp_chatbot_gpt_system_prompt',
        "You are a helpful customer-support assistant for an online store. Keep replies under 280 characters, friendly and direct. If the question is outside store/product/order topics or you don't know the answer, say so briefly and suggest the customer contact human support."
    );

    $body = [
        'model'    => $model,
        'messages' => [
            ['role' => 'system', 'content' => $system_prompt],
            ['role' => 'user',   'content' => $user_message],
        ],
        'max_tokens'  => 150,
        'temperature' => 0.5,
    ];

    $response = wp_remote_post($endpoint, [
        'timeout' => 20,
        'headers' => [
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $api_key,
        ],
        'body' => wp_json_encode($body),
    ]);

    if (is_wp_error($response)) return '';

    $code = wp_remote_retrieve_response_code($response);
    if ($code !== 200) return '';

    $data = json_decode(wp_remote_retrieve_body($response), true);
    if (!isset($data['choices'][0]['message']['content'])) return '';

    return trim($data['choices'][0]['message']['content']);
}
