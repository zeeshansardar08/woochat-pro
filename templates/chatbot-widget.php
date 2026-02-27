<?php if (!defined('ABSPATH')) exit; ?>
<?php
$bubble = isset($settings['bubble_color']) ? $settings['bubble_color'] : '#1c7c54';
$text = isset($settings['text_color']) ? $settings['text_color'] : '#ffffff';
$iconColor = isset($settings['icon_color']) ? $settings['icon_color'] : '#2ec4b6';
$icon = isset($settings['icon']) ? $settings['icon'] : '💬';
$welcome = isset($settings['welcome']) ? $settings['welcome'] : 'Hi! How can I help you?';
?>
<div id="wcwp-chatbot" style="position:fixed;bottom:30px;right:30px;z-index:9999;font-family:inherit;">
    <style>
        #wcwp-chat-window { background:#fff;border-radius:10px;width:300px;box-shadow:0 10px 30px rgba(0,0,0,0.15);padding:15px;display:none; }
        #wcwp-toggle-chat { background: <?php echo esc_attr($bubble); ?>; color: <?php echo esc_attr($text); ?>; border:none; padding:12px 16px; border-radius:50px; cursor:pointer; box-shadow:0 10px 25px rgba(0,0,0,0.18); display:flex; align-items:center; gap:8px; font-size:16px; }
        #wcwp-toggle-chat .wcwp-icon { color: <?php echo esc_attr($iconColor); ?>; }
        #wcwp-chat-window h4 { margin-top:0; margin-bottom:8px; display:flex; align-items:center; gap:6px; }
        #wcwp-user-input { width:100%; margin-top:10px; padding:8px 10px; border:1px solid #e3e3e3; border-radius:6px; }
        #wcwp-chat-response { margin-top:10px; font-size:14px; line-height:1.5; color:#333; min-height:40px; }
        #wcwp-send-wa { display:none; margin-top:10px; background: <?php echo esc_attr($bubble); ?>; color: <?php echo esc_attr($text); ?>; padding:9px 12px; border-radius:6px; text-decoration:none; font-weight:600; }
    </style>
    <div id="wcwp-chat-window">
        <h4><span class="wcwp-icon" style="color:<?php echo esc_attr($iconColor); ?>;"><?php echo esc_html($icon); ?></span> <?php esc_html_e('Chat with us', 'woochat-pro'); ?></h4>
        <input type="text" id="wcwp-user-input" placeholder="<?php esc_attr_e('Ask a question...', 'woochat-pro'); ?>" />
        <div id="wcwp-chat-response"><?php echo esc_html($welcome); ?></div>
        <a id="wcwp-send-wa" href="#" target="_blank"><?php esc_html_e('Send via WhatsApp', 'woochat-pro'); ?></a>
    </div>
    <button id="wcwp-toggle-chat">
        <span class="wcwp-icon"><?php echo esc_html($icon); ?></span>
        <span><?php esc_html_e('Chat', 'woochat-pro'); ?></span>
    </button>
</div>
