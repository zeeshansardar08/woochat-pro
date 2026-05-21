<?php if (!defined('ABSPATH')) exit; ?>
<?php
$bubble = isset($settings['bubble_color']) ? $settings['bubble_color'] : '#1c7c54';
$text = isset($settings['text_color']) ? $settings['text_color'] : '#ffffff';
$iconColor = isset($settings['icon_color']) ? $settings['icon_color'] : '#2ec4b6';
$icon = isset($settings['icon']) ? $settings['icon'] : '💬';
$welcome = isset($settings['welcome']) ? $settings['welcome'] : 'Hi! How can I help you?';

// Store colors travel as CSS custom properties; the layout rules live in
// assets/css/chatbot-widget.css (enqueued by wcwp_enqueue_chatbot_assets).
$wcwp_cb_vars = sprintf(
    '--wcwp-cb-bubble:%s;--wcwp-cb-text:%s;--wcwp-cb-icon:%s;',
    $bubble,
    $text,
    $iconColor
);
?>
<div id="wcwp-chatbot" style="<?php echo esc_attr($wcwp_cb_vars); ?>">
    <div id="wcwp-chat-window">
        <h4><span class="wcwp-icon"><?php echo esc_html($icon); ?></span> <?php esc_html_e('Chat with us', 'woochat'); ?></h4>
        <input type="text" id="wcwp-user-input" placeholder="<?php esc_attr_e('Ask a question...', 'woochat'); ?>" />
        <div id="wcwp-chat-response"><?php echo esc_html($welcome); ?></div>
        <a id="wcwp-send-wa" href="#" target="_blank"><?php esc_html_e('Send via WhatsApp', 'woochat'); ?></a>
    </div>
    <button id="wcwp-toggle-chat">
        <span class="wcwp-icon"><?php echo esc_html($icon); ?></span>
        <span><?php esc_html_e('Chat', 'woochat'); ?></span>
    </button>
</div>
