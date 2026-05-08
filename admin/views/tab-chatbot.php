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

    <h3><?php esc_html_e('Agents (multi-agent routing)', 'woochat-pro'); ?></h3>
    <p class="description"><?php esc_html_e('Add the team members who can receive customer chats. The chatbot widget routes "Send via WhatsApp" clicks to one of these numbers using the routing mode below.', 'woochat-pro'); ?></p>
    <table class="form-table">
        <tr>
            <th scope="row"><label for="wcwp_agent_routing_mode"><?php esc_html_e('Routing mode', 'woochat-pro'); ?></label></th>
            <td>
                <?php $wcwp_routing_mode = get_option('wcwp_agent_routing_mode', 'single'); ?>
                <select name="wcwp_agent_routing_mode" id="wcwp_agent_routing_mode">
                    <option value="single" <?php selected($wcwp_routing_mode, 'single'); ?>><?php esc_html_e('Single — first agent only', 'woochat-pro'); ?></option>
                    <option value="random" <?php selected($wcwp_routing_mode, 'random'); ?>><?php esc_html_e('Random — load-balance across agents', 'woochat-pro'); ?></option>
                </select>
                <p class="description"><?php esc_html_e('Random is chosen client-side per page load so a full-page cache does not pin every visitor to the same agent.', 'woochat-pro'); ?></p>
            </td>
        </tr>
    </table>

    <table class="widefat striped" id="wcwp-agents-table">
        <thead>
            <tr>
                <th><?php esc_html_e('Agent name', 'woochat-pro'); ?></th>
                <th><?php esc_html_e('WhatsApp number', 'woochat-pro'); ?></th>
                <th class="wcwp-agents-row-actions"></th>
            </tr>
        </thead>
        <tbody>
            <?php
            $wcwp_agents = wcwp_get_agents();
            if (empty($wcwp_agents)) :
                ?>
                <tr class="wcwp-agent-row">
                    <td><input type="text" class="wcwp-agent-name regular-text" placeholder="<?php esc_attr_e('e.g. Sales', 'woochat-pro'); ?>" value="" /></td>
                    <td><input type="text" class="wcwp-agent-phone regular-text" placeholder="<?php esc_attr_e('e.g. +14155550100', 'woochat-pro'); ?>" value="" /></td>
                    <td><button type="button" class="button-link wcwp-agent-remove" aria-label="<?php esc_attr_e('Remove agent', 'woochat-pro'); ?>">&times;</button></td>
                </tr>
            <?php else : foreach ($wcwp_agents as $wcwp_agent) : ?>
                <tr class="wcwp-agent-row">
                    <td><input type="text" class="wcwp-agent-name regular-text" placeholder="<?php esc_attr_e('e.g. Sales', 'woochat-pro'); ?>" value="<?php echo esc_attr($wcwp_agent['name']); ?>" /></td>
                    <td><input type="text" class="wcwp-agent-phone regular-text" placeholder="<?php esc_attr_e('e.g. +14155550100', 'woochat-pro'); ?>" value="<?php echo esc_attr($wcwp_agent['phone']); ?>" /></td>
                    <td><button type="button" class="button-link wcwp-agent-remove" aria-label="<?php esc_attr_e('Remove agent', 'woochat-pro'); ?>">&times;</button></td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
    <p>
        <button type="button" class="button" id="wcwp-agent-add">+ <?php esc_html_e('Add agent', 'woochat-pro'); ?></button>
    </p>
    <input type="hidden" name="wcwp_agents" id="wcwp_agents_input" value="<?php echo esc_attr(get_option('wcwp_agents', '[]')); ?>" />
</div>
