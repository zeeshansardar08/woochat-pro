<?php if (!defined('ABSPATH')) exit; ?>
<?php
$zignites_chat_cb_bubble = isset($settings['bubble_color']) ? $settings['bubble_color'] : '#1c7c54';
$zignites_chat_cb_text = isset($settings['text_color']) ? $settings['text_color'] : '#ffffff';
$zignites_chat_cb_icon_color = isset($settings['icon_color']) ? $settings['icon_color'] : '#2ec4b6';
$zignites_chat_cb_icon = isset($settings['icon']) ? $settings['icon'] : '💬';
$zignites_chat_cb_welcome = isset($settings['welcome']) ? $settings['welcome'] : 'Hi! How can I help you?';

// Store colors travel as CSS custom properties; the layout rules live in
// assets/css/chatbot-widget.css (enqueued by zignites_chat_enqueue_chatbot_assets).
$zignites_chat_cb_vars = sprintf(
    '--zignites-chat-cb-bubble:%s;--zignites-chat-cb-text:%s;--zignites-chat-cb-icon:%s;',
    $zignites_chat_cb_bubble,
    $zignites_chat_cb_text,
    $zignites_chat_cb_icon_color
);
?>
<div id="zignites-chat-chatbot" style="<?php echo esc_attr($zignites_chat_cb_vars); ?>">
    <div id="zignites-chat-chat-window">
        <h4><span class="zignites-chat-icon"><?php echo esc_html($zignites_chat_cb_icon); ?></span> <?php esc_html_e('Chat with us', 'zignites-chat'); ?></h4>
        <input type="text" id="zignites-chat-user-input" placeholder="<?php esc_attr_e('Ask a question...', 'zignites-chat'); ?>" />
        <div id="zignites-chat-chat-response"><?php echo esc_html($zignites_chat_cb_welcome); ?></div>
        <a id="zignites-chat-send-wa" href="#" target="_blank"><?php esc_html_e('Send via WhatsApp', 'zignites-chat'); ?></a>
    </div>
    <button id="zignites-chat-toggle-chat">
        <span class="zignites-chat-icon"><?php echo esc_html($zignites_chat_cb_icon); ?></span>
        <span><?php esc_html_e('Chat', 'zignites-chat'); ?></span>
    </button>
</div>
