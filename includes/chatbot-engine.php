<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'wp_footer', 'zignites_chat_render_chatbot_widget' );
add_shortcode( 'zignites-chat_chatbot', 'zignites_chat_chatbot_shortcode' );
add_action( 'wp_enqueue_scripts', 'zignites_chat_enqueue_chatbot_assets' );

function zignites_chat_enqueue_chatbot_assets() {
	if ( is_admin() ) {
		return;
	}
	if ( get_option( 'zignites_chat_chatbot_enabled', 'yes' ) !== 'yes' ) {
		return;
	}
	wp_enqueue_style( 'zignites-chat-chatbot-css', ZIGNITES_CHAT_URL . 'assets/css/chatbot-widget.css', [], ZIGNITES_CHAT_VERSION );
}

function zignites_chat_render_chatbot_widget() {
	if ( is_admin() ) return;

	$enabled = get_option( 'zignites_chat_chatbot_enabled', 'yes' );
	if ( $enabled !== 'yes' ) return;

	$settings = zignites_chat_get_chatbot_settings();

	if ( defined( 'ZIGNITES_CHAT_CHATBOT_RENDERED' ) && ZIGNITES_CHAT_CHATBOT_RENDERED ) return;

	include ZIGNITES_CHAT_PATH . 'templates/chatbot-widget.php';
	if ( ! defined( 'ZIGNITES_CHAT_CHATBOT_RENDERED' ) ) {
		define( 'ZIGNITES_CHAT_CHATBOT_RENDERED', true );
	}

	wp_enqueue_script( 'zignites-chat-chatbot-js', ZIGNITES_CHAT_URL . 'assets/js/chatbot.js', ['jquery'], ZIGNITES_CHAT_VERSION, true );
	wp_localize_script( 'zignites-chat-chatbot-js', 'zignites_chat_chatbot_obj', zignites_chat_chatbot_localized_data() );
}

function zignites_chat_chatbot_shortcode() {
	$enabled = get_option( 'zignites_chat_chatbot_enabled', 'yes' );
	if ( $enabled !== 'yes' ) return '';

	ob_start();
	$settings = zignites_chat_get_chatbot_settings();
	include ZIGNITES_CHAT_PATH . 'templates/chatbot-widget.php';
	wp_enqueue_script( 'zignites-chat-chatbot-js', ZIGNITES_CHAT_URL . 'assets/js/chatbot.js', ['jquery'], ZIGNITES_CHAT_VERSION, true );
	wp_localize_script( 'zignites-chat-chatbot-js', 'zignites_chat_chatbot_obj', zignites_chat_chatbot_localized_data() );
	if ( ! defined( 'ZIGNITES_CHAT_CHATBOT_RENDERED' ) ) {
		define( 'ZIGNITES_CHAT_CHATBOT_RENDERED', true );
	}
	return ob_get_clean();
}

/**
 * Single source of truth for the chatbot JS payload.
 * Single-agent only in the free version.
 */
function zignites_chat_chatbot_localized_data() {
	$faq_pairs = json_decode( get_option( 'zignites_chat_faq_pairs', '[]' ), true );
	if ( ! is_array( $faq_pairs ) ) $faq_pairs = [];

	$agents = array_slice( zignites_chat_get_agents(), 0, 1 );

	return [
		'faq_pairs'    => $faq_pairs,
		'noAnswerText' => __( "Sorry, I don't have an answer for that.", 'zignites-chat' ),
		'agents'       => $agents,
		'routing_mode' => 'single',
	];
}

/**
 * Returns chatbot display settings. Free version always uses defaults.
 */
function zignites_chat_get_chatbot_settings() {
	return [
		'bubble_color' => '#1c7c54',
		'text_color'   => '#ffffff',
		'icon_color'   => '#2ec4b6',
		'icon'         => '💬',
		'welcome'      => get_option( 'zignites_chat_chatbot_welcome', 'Hi! How can I help you?' ),
	];
}
