<?php
if (!defined('ABSPATH')) exit;

// Load chatbot widget in footer
add_action('wp_footer', 'zignites_chat_render_chatbot_widget');
add_shortcode('zignites-chat_chatbot', 'zignites_chat_chatbot_shortcode');
add_action('wp_enqueue_scripts', 'zignites_chat_enqueue_chatbot_assets');

/**
 * Register the frontend chatbot stylesheet when the widget will render.
 *
 * @since 1.0.0
 */
function zignites_chat_enqueue_chatbot_assets() {
    if (is_admin()) {
        return;
    }
    if (get_option('zignites_chat_chatbot_enabled', 'yes') !== 'yes') {
        return;
    }
    wp_enqueue_style('zignites-chat-chatbot-css', ZIGNITES_CHAT_URL . 'assets/css/chatbot-widget.css', [], ZIGNITES_CHAT_VERSION);
}

function zignites_chat_render_chatbot_widget() {
    if (is_admin()) return;

    // The basic chat widget is a free feature; GPT replies, the color/icon
    // customizer and multi-agent routing are gated downstream (Pro only).
    $enabled = get_option('zignites_chat_chatbot_enabled', 'yes');
    if ($enabled !== 'yes') return;

    $settings = zignites_chat_get_chatbot_settings();

    // Prevent double render if shortcode already printed
    if (defined('ZIGNITES_CHAT_CHATBOT_RENDERED') && ZIGNITES_CHAT_CHATBOT_RENDERED) return;

    include ZIGNITES_CHAT_PATH . 'templates/chatbot-widget.php';
    if (!defined('ZIGNITES_CHAT_CHATBOT_RENDERED')) {
        define('ZIGNITES_CHAT_CHATBOT_RENDERED', true);
    }

    wp_enqueue_script('zignites-chat-chatbot-js', ZIGNITES_CHAT_URL . 'assets/js/chatbot.js', ['jquery'], ZIGNITES_CHAT_VERSION, true);
    wp_localize_script('zignites-chat-chatbot-js', 'zignites_chat_chatbot_obj', zignites_chat_chatbot_localized_data());
}

function zignites_chat_chatbot_shortcode() {
    $enabled = get_option('zignites_chat_chatbot_enabled', 'yes');
    if ($enabled !== 'yes') return '';

    ob_start();
    $settings = zignites_chat_get_chatbot_settings();
    include ZIGNITES_CHAT_PATH . 'templates/chatbot-widget.php';
    wp_enqueue_script('zignites-chat-chatbot-js', ZIGNITES_CHAT_URL . 'assets/js/chatbot.js', ['jquery'], ZIGNITES_CHAT_VERSION, true);
    wp_localize_script('zignites-chat-chatbot-js', 'zignites_chat_chatbot_obj', zignites_chat_chatbot_localized_data());
    if (!defined('ZIGNITES_CHAT_CHATBOT_RENDERED')) {
        define('ZIGNITES_CHAT_CHATBOT_RENDERED', true);
    }
    return ob_get_clean();
}

/**
 * Single source of truth for the zignites_chat_chatbot_obj payload.
 *
 * Both the auto-render and shortcode paths call this so a new key
 * (e.g. the GPT-fallback wiring) can't go to one but not the other.
 */
function zignites_chat_chatbot_localized_data() {
    $faq_pairs = json_decode(get_option('zignites_chat_faq_pairs', '[]'), true);
    if (!is_array($faq_pairs)) $faq_pairs = [];

    $is_pro = zignites_chat_is_pro_active();

    // Free tier is single-agent: keep only the first agent and force the
    // 'single' routing mode so multi-agent load-balancing stays Pro-only.
    $agents = zignites_chat_get_agents();
    if (!$is_pro) {
        $agents = array_slice($agents, 0, 1);
    }

    return [
        'faq_pairs'    => $faq_pairs,
        'noAnswerText' => __( "Sorry, I don't have an answer for that.", 'zignites-chat' ),
        'gpt'          => [
            'enabled'  => $is_pro && get_option('zignites_chat_chatbot_gpt_enabled', 'no') === 'yes',
            'url'      => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('zignites_chat_chatbot_gpt'),
            'action'   => 'zignites_chat_chatbot_gpt',
            'thinking' => __('Thinking…', 'zignites-chat'),
        ],
        // Agents are picked client-side so a full-page cache can't pin every
        // visitor to the same agent under 'random' mode.
        'agents'        => $agents,
        'routing_mode'  => ($is_pro && get_option('zignites_chat_agent_routing_mode', 'single') === 'random') ? 'random' : 'single',
    ];
}

function zignites_chat_get_chatbot_settings() {
    $welcome = get_option('zignites_chat_chatbot_welcome', 'Hi! How can I help you?');

    // The color/icon customizer is Pro — the free widget always renders with
    // the default palette regardless of any values saved while on Pro.
    if (!zignites_chat_is_pro_active()) {
        return [
            'bubble_color' => '#1c7c54',
            'text_color'   => '#ffffff',
            'icon_color'   => '#2ec4b6',
            'icon'         => '💬',
            'welcome'      => $welcome,
        ];
    }

    // Hex colors are also validated at write-time via the register_setting
    // sanitize_callback; this is the read-time defense-in-depth that
    // guarantees the chatbot template never sees an invalid color, even
    // for values saved before the validator existed.
    return [
        'bubble_color' => zignites_chat_sanitize_hex_color(get_option('zignites_chat_chatbot_bg', '#1c7c54'), '#1c7c54'),
        'text_color'   => zignites_chat_sanitize_hex_color(get_option('zignites_chat_chatbot_text', '#ffffff'), '#ffffff'),
        'icon_color'   => zignites_chat_sanitize_hex_color(get_option('zignites_chat_chatbot_icon_color', '#2ec4b6'), '#2ec4b6'),
        'icon'         => get_option('zignites_chat_chatbot_icon', '💬'),
        'welcome'      => $welcome,
    ];
}

add_action('wp_ajax_zignites_chat_chatbot_gpt', 'zignites_chat_ajax_chatbot_gpt');
add_action('wp_ajax_nopriv_zignites_chat_chatbot_gpt', 'zignites_chat_ajax_chatbot_gpt');
function zignites_chat_ajax_chatbot_gpt() {
    if (!check_ajax_referer('zignites_chat_chatbot_gpt', 'nonce', false)) {
        wp_send_json_error(['message' => __('Bad nonce', 'zignites-chat')], 400);
    }

    if (get_option('zignites_chat_chatbot_gpt_enabled', 'no') !== 'yes') {
        wp_send_json_error(['message' => __('GPT fallback disabled', 'zignites-chat')], 403);
    }

    // Defense in depth: the widget render paths already gate on Pro, but a
    // direct admin-ajax call would otherwise sail past the front-end gate.
    if (!zignites_chat_is_pro_active()) {
        wp_send_json_error(['message' => __('Pro license required', 'zignites-chat')], 403);
    }

    $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';
    if (!zignites_chat_chatbot_gpt_rate_limit_ok($ip)) {
        wp_send_json_error(['message' => __('Too many requests', 'zignites-chat')], 429);
    }

    $question = isset($_POST['question']) ? sanitize_text_field(wp_unslash($_POST['question'])) : '';
    if ($question === '') {
        wp_send_json_error(['message' => __('Empty question', 'zignites-chat')], 400);
    }

    // Cap input length: keeps the upstream prompt bounded and curbs misuse.
    if (function_exists('mb_substr')) {
        $question = mb_substr($question, 0, 500);
    } else {
        $question = substr($question, 0, 500);
    }

    $reply = zignites_chat_generate_chatbot_reply($question);
    if ($reply === '') {
        wp_send_json_error(['message' => __('No reply available', 'zignites-chat')], 502);
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
function zignites_chat_chatbot_gpt_rate_limit_ok($ip) {
    if (!$ip) return true;

    $limit  = (int) apply_filters('zignites_chat_chatbot_gpt_rate_limit', 10);
    $window = (int) apply_filters('zignites_chat_chatbot_gpt_rate_window', HOUR_IN_SECONDS);

    if ($limit < 1 || $window < 1) return true;

    $key  = 'zignites_chat_chatbot_gpt_rate_' . md5($ip);
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
 * Mirrors the shape of zignites_chat_generate_gpt_followup() in scheduler.php but
 * with a chatbot-appropriate system prompt and a string input rather than
 * a WC_Order. Returns '' on any failure path so the JS can fall back to
 * noAnswerText without the user seeing an error.
 *
 * @param string $user_message
 * @return string
 */
function zignites_chat_generate_chatbot_reply($user_message) {
    $endpoint = trim(get_option('zignites_chat_gpt_api_endpoint', ''));
    $api_key  = trim(get_option('zignites_chat_gpt_api_key', ''));
    $model    = trim(get_option('zignites_chat_gpt_model', zignites_chat_default_gpt_model()));
    if ($model === '') $model = zignites_chat_default_gpt_model();

    if ($endpoint === '' || $api_key === '') {
        zignites_chat_record_gpt_error('chatbot', 'Chatbot GPT fallback enabled but the endpoint or API key is empty.');
        return '';
    }

    $system_prompt = apply_filters(
        'zignites_chat_chatbot_gpt_system_prompt',
        "You are a helpful customer-support assistant for an online store. Keep replies under 280 characters, friendly and direct. If the question is outside store/product/order topics or you don't know the answer, say so briefly and suggest the customer contact human support."
    );

    // Optional store-catalog grounding: when enabled, append a compact product
    // summary so the bot answers product/price questions from real data.
    if (get_option('zignites_chat_chatbot_catalog_context', 'no') === 'yes'
        && function_exists('zignites_chat_get_catalog_context')) {
        $catalog = zignites_chat_get_catalog_context();
        if ($catalog !== '') {
            $system_prompt .= "\n\nProducts currently available in the store (use these to answer product and price questions; do not invent products or prices that are not listed):\n" . $catalog;
        }
    }

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

    if (is_wp_error($response)) {
        zignites_chat_record_gpt_error('chatbot', 'Network error: ' . $response->get_error_message());
        return '';
    }

    $code = wp_remote_retrieve_response_code($response);
    if ($code !== 200) {
        zignites_chat_record_gpt_error('chatbot', sprintf('GPT endpoint returned HTTP %d.', (int) $code));
        return '';
    }

    $data = json_decode(wp_remote_retrieve_body($response), true);
    if (!isset($data['choices'][0]['message']['content'])) {
        zignites_chat_record_gpt_error('chatbot', 'GPT response missing choices[0].message.content.');
        return '';
    }

    return trim($data['choices'][0]['message']['content']);
}
