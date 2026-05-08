<?php
if (!defined('ABSPATH')) exit;
?>
<div id="wcwp-tab-content-chatbot" class="wcwp-tab-content" style="display:none;">
    <div class="wcwp-chatbot-customizer">
        <div class="wcwp-chatbot-customizer-controls">
            <label for="wcwp-chatbot-bg"><?php esc_html_e('Chatbot Bubble Color', 'woochat-pro'); ?></label>
            <input type="color" id="wcwp-chatbot-bg" name="wcwp_chatbot_bg" value="<?php echo esc_attr(get_option('wcwp_chatbot_bg', '#1c7c54')); ?>">
            <label for="wcwp-chatbot-color"><?php esc_html_e('Text Color', 'woochat-pro'); ?></label>
            <input type="color" id="wcwp-chatbot-color" name="wcwp_chatbot_text" value="<?php echo esc_attr(get_option('wcwp_chatbot_text', '#ffffff')); ?>">
            <label for="wcwp-chatbot-icon"><?php esc_html_e('Icon Color', 'woochat-pro'); ?></label>
            <input type="color" id="wcwp-chatbot-icon" name="wcwp_chatbot_icon_color" value="<?php echo esc_attr(get_option('wcwp_chatbot_icon_color', '#2ec4b6')); ?>">
            <label><?php esc_html_e('Choose Icon', 'woochat-pro'); ?></label>
            <div class="wcwp-icon-select">
                <?php $icon_option = get_option('wcwp_chatbot_icon', '💬'); ?>
                <span class="wcwp-icon-option <?php echo $icon_option === '💬' ? 'selected' : ''; ?>">💬</span>
                <span class="wcwp-icon-option <?php echo $icon_option === '🤖' ? 'selected' : ''; ?>">🤖</span>
                <span class="wcwp-icon-option <?php echo $icon_option === '🟢' ? 'selected' : ''; ?>">🟢</span>
                <span class="wcwp-icon-option <?php echo $icon_option === '📞' ? 'selected' : ''; ?>">📞</span>
            </div>
            <input type="hidden" id="wcwp-chatbot-icon-value" name="wcwp_chatbot_icon" value="<?php echo esc_attr($icon_option); ?>" />
            <label for="wcwp-chatbot-welcome"><?php esc_html_e('Welcome Message', 'woochat-pro'); ?></label>
            <input type="text" id="wcwp-chatbot-welcome" name="wcwp_chatbot_welcome" value="<?php echo esc_attr(get_option('wcwp_chatbot_welcome', 'Hi! How can I help you?')); ?>">
        </div>
        <div class="wcwp-chatbot-customizer-preview">
            <div class="wcwp-chatbot-preview-icon">💬</div>
            <div class="wcwp-chatbot-preview-bubble"><span id="wcwp-chatbot-preview-welcome">Hi! How can I help you?</span></div>
        </div>
    </div>
    <table class="form-table">
        <tr>
            <th scope="row"><label for="wcwp_chatbot_enabled"><?php esc_html_e('Enable Chatbot', 'woochat-pro'); ?></label><span class="wcwp-help-icon">?<span class="wcwp-tooltip"><?php esc_html_e('Toggle the floating WhatsApp chatbot widget on your site.', 'woochat-pro'); ?></span></span></th>
            <td>
                <select name="wcwp_chatbot_enabled" id="wcwp_chatbot_enabled">
                    <option value="yes" <?php selected(get_option('wcwp_chatbot_enabled'), 'yes'); ?>><?php esc_html_e('Yes', 'woochat-pro'); ?></option>
                    <option value="no" <?php selected(get_option('wcwp_chatbot_enabled'), 'no'); ?>><?php esc_html_e('No', 'woochat-pro'); ?></option>
                </select>
                <p class="description"><?php esc_html_e('Toggle the floating WhatsApp chatbot on your site.', 'woochat-pro'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="wcwp_faq_pairs"><?php esc_html_e('FAQ Rules (JSON)', 'woochat-pro'); ?></label><span class="wcwp-help-icon">?<span class="wcwp-tooltip"><?php esc_html_e('Add question/answer pairs for the chatbot. Format: JSON array.', 'woochat-pro'); ?></span></span></th>
            <td>
                <textarea name="wcwp_faq_pairs" rows="6" class="large-text"><?php echo esc_textarea(get_option('wcwp_faq_pairs', '[{"question":"order","answer":"You can track your order here: yourdomain.com/track"},{"question":"return","answer":"We offer 7-day easy returns."}]')); ?></textarea>
                <p class="description"><?php esc_html_e('Enter question/answer pairs as JSON array.', 'woochat-pro'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="wcwp_chatbot_gpt_enabled"><?php esc_html_e('GPT Fallback', 'woochat-pro'); ?></label><span class="wcwp-help-icon">?<span class="wcwp-tooltip"><?php esc_html_e('When the FAQ matcher finds no answer, ask the configured GPT endpoint for a reply. Requires GPT API endpoint and key set under the Scheduler tab.', 'woochat-pro'); ?></span></span></th>
            <td>
                <select name="wcwp_chatbot_gpt_enabled" id="wcwp_chatbot_gpt_enabled">
                    <option value="no" <?php selected(get_option('wcwp_chatbot_gpt_enabled', 'no'), 'no'); ?>><?php esc_html_e('No', 'woochat-pro'); ?></option>
                    <option value="yes" <?php selected(get_option('wcwp_chatbot_gpt_enabled', 'no'), 'yes'); ?>><?php esc_html_e('Yes', 'woochat-pro'); ?></option>
                </select>
                <p class="description"><?php esc_html_e('Each call costs your GPT account. Rate-limited to 10 requests per hour per visitor IP.', 'woochat-pro'); ?></p>
            </td>
        </tr>
    </table>
</div>
