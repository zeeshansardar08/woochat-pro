<?php
if (!defined('ABSPATH')) exit;
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- View partial: these variables are scoped to the render function that includes this file, not the global namespace.

// The Chatbot page is reachable on the free plan, but the color/icon
// customizer, GPT fallback and multi-agent routing are Pro features. For
// non-Pro users those controls render disabled; a hidden input preserves any
// previously saved value so a Save on this page never wipes it.
$zignites_chat_cb_pro = zignites_chat_is_pro_active();
?>
<?php if (!$zignites_chat_cb_pro) : ?>
    <div class="zignites-chat-pro-banner"><span class="dashicons dashicons-admin-customizer"></span> <?php esc_html_e('The chatbot color/icon customizer, GPT auto-replies and multi-agent routing are Pro features. The basic chat widget and FAQ replies are included free.', 'zignites-chat'); ?> <button type="button" class="zignites-chat-open-upgrade-modal" style="margin-left:12px;"><?php esc_html_e('Upgrade', 'zignites-chat'); ?></button></div>
<?php endif; ?>
<div class="zignites-chat-chatbot-customizer">
        <div class="zignites-chat-chatbot-customizer-controls">
            <label for="zignites-chat-chatbot-bg"><?php esc_html_e('Chatbot Bubble Color', 'zignites-chat'); ?><?php if (!$zignites_chat_cb_pro) : ?> <span class="zignites-chat-pro-tag"><?php esc_html_e('Pro', 'zignites-chat'); ?></span><?php endif; ?></label>
            <input type="color" id="zignites-chat-chatbot-bg" <?php echo $zignites_chat_cb_pro ? 'name="zignites_chat_chatbot_bg"' : ''; ?> value="<?php echo esc_attr(get_option('zignites_chat_chatbot_bg', '#1c7c54')); ?>" <?php disabled(!$zignites_chat_cb_pro); ?>>
            <label for="zignites-chat-chatbot-color"><?php esc_html_e('Text Color', 'zignites-chat'); ?><?php if (!$zignites_chat_cb_pro) : ?> <span class="zignites-chat-pro-tag"><?php esc_html_e('Pro', 'zignites-chat'); ?></span><?php endif; ?></label>
            <input type="color" id="zignites-chat-chatbot-color" <?php echo $zignites_chat_cb_pro ? 'name="zignites_chat_chatbot_text"' : ''; ?> value="<?php echo esc_attr(get_option('zignites_chat_chatbot_text', '#ffffff')); ?>" <?php disabled(!$zignites_chat_cb_pro); ?>>
            <label for="zignites-chat-chatbot-icon"><?php esc_html_e('Icon Color', 'zignites-chat'); ?><?php if (!$zignites_chat_cb_pro) : ?> <span class="zignites-chat-pro-tag"><?php esc_html_e('Pro', 'zignites-chat'); ?></span><?php endif; ?></label>
            <input type="color" id="zignites-chat-chatbot-icon" <?php echo $zignites_chat_cb_pro ? 'name="zignites_chat_chatbot_icon_color"' : ''; ?> value="<?php echo esc_attr(get_option('zignites_chat_chatbot_icon_color', '#2ec4b6')); ?>" <?php disabled(!$zignites_chat_cb_pro); ?>>
            <label><?php esc_html_e('Choose Icon', 'zignites-chat'); ?><?php if (!$zignites_chat_cb_pro) : ?> <span class="zignites-chat-pro-tag"><?php esc_html_e('Pro', 'zignites-chat'); ?></span><?php endif; ?></label>
            <div class="zignites-chat-icon-select <?php echo esc_attr($zignites_chat_cb_pro ? '' : 'zignites-chat-pro-locked'); ?>">
                <?php $icon_option = get_option('zignites_chat_chatbot_icon', '💬'); ?>
                <span class="zignites-chat-icon-option <?php echo esc_attr( $icon_option === '💬' ? 'selected' : '' ); ?>">💬</span>
                <span class="zignites-chat-icon-option <?php echo esc_attr( $icon_option === '🤖' ? 'selected' : '' ); ?>">🤖</span>
                <span class="zignites-chat-icon-option <?php echo esc_attr( $icon_option === '🟢' ? 'selected' : '' ); ?>">🟢</span>
                <span class="zignites-chat-icon-option <?php echo esc_attr( $icon_option === '📞' ? 'selected' : '' ); ?>">📞</span>
            </div>
            <?php if ($zignites_chat_cb_pro) : ?>
                <input type="hidden" id="zignites-chat-chatbot-icon-value" name="zignites_chat_chatbot_icon" value="<?php echo esc_attr($icon_option); ?>" />
            <?php else : ?>
                <input type="hidden" id="zignites-chat-chatbot-icon-value" value="<?php echo esc_attr($icon_option); ?>" />
            <?php endif; ?>
            <label for="zignites-chat-chatbot-welcome"><?php esc_html_e('Welcome Message', 'zignites-chat'); ?></label>
            <input type="text" id="zignites-chat-chatbot-welcome" name="zignites_chat_chatbot_welcome" value="<?php echo esc_attr(get_option('zignites_chat_chatbot_welcome', 'Hi! How can I help you?')); ?>">
        </div>
        <div class="zignites-chat-chatbot-customizer-preview">
            <div class="zignites-chat-chatbot-preview-icon">💬</div>
            <div class="zignites-chat-chatbot-preview-bubble"><span id="zignites-chat-chatbot-preview-welcome">Hi! How can I help you?</span></div>
        </div>
    </div>
    <table class="form-table">
        <tr>
            <th scope="row"><label for="zignites_chat_chatbot_enabled"><?php esc_html_e('Enable Chatbot', 'zignites-chat'); ?></label><span class="zignites-chat-help-icon">?<span class="zignites-chat-tooltip"><?php esc_html_e('Toggle the floating WhatsApp chatbot widget on your site.', 'zignites-chat'); ?></span></span></th>
            <td>
                <select name="zignites_chat_chatbot_enabled" id="zignites_chat_chatbot_enabled">
                    <option value="yes" <?php selected(get_option('zignites_chat_chatbot_enabled'), 'yes'); ?>><?php esc_html_e('Yes', 'zignites-chat'); ?></option>
                    <option value="no" <?php selected(get_option('zignites_chat_chatbot_enabled'), 'no'); ?>><?php esc_html_e('No', 'zignites-chat'); ?></option>
                </select>
                <p class="description"><?php esc_html_e('Toggle the floating WhatsApp chatbot on your site.', 'zignites-chat'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="zignites_chat_faq_pairs"><?php esc_html_e('FAQ Rules (JSON)', 'zignites-chat'); ?></label><span class="zignites-chat-help-icon">?<span class="zignites-chat-tooltip"><?php esc_html_e('Add question/answer pairs for the chatbot. Format: JSON array.', 'zignites-chat'); ?></span></span></th>
            <td>
                <textarea name="zignites_chat_faq_pairs" rows="6" class="large-text"><?php echo esc_textarea(get_option('zignites_chat_faq_pairs', '[{"question":"order","answer":"You can track your order here: yourdomain.com/track"},{"question":"return","answer":"We offer 7-day easy returns."}]')); ?></textarea>
                <p class="description"><?php esc_html_e('Enter question/answer pairs as JSON array.', 'zignites-chat'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="zignites_chat_chatbot_gpt_enabled"><?php esc_html_e('GPT Fallback', 'zignites-chat'); ?><?php if (!$zignites_chat_cb_pro) : ?> <span class="zignites-chat-pro-tag"><?php esc_html_e('Pro', 'zignites-chat'); ?></span><?php endif; ?></label><span class="zignites-chat-help-icon">?<span class="zignites-chat-tooltip"><?php esc_html_e('When the FAQ matcher finds no answer, ask the configured GPT endpoint for a reply. Requires GPT API endpoint and key set on the Scheduler page.', 'zignites-chat'); ?></span></span></th>
            <td>
                <?php $zignites_chat_gpt_enabled = get_option('zignites_chat_chatbot_gpt_enabled', 'no'); ?>
                <select <?php echo $zignites_chat_cb_pro ? 'name="zignites_chat_chatbot_gpt_enabled"' : ''; ?> id="zignites_chat_chatbot_gpt_enabled" <?php disabled(!$zignites_chat_cb_pro); ?>>
                    <option value="no" <?php selected($zignites_chat_gpt_enabled, 'no'); ?>><?php esc_html_e('No', 'zignites-chat'); ?></option>
                    <option value="yes" <?php selected($zignites_chat_gpt_enabled, 'yes'); ?>><?php esc_html_e('Yes', 'zignites-chat'); ?></option>
                </select>
                <?php if (!$zignites_chat_cb_pro) : ?>
                    <input type="hidden" name="zignites_chat_chatbot_gpt_enabled" value="<?php echo esc_attr($zignites_chat_gpt_enabled); ?>" />
                    <p class="description"><?php esc_html_e('GPT-powered auto-replies are a Pro feature.', 'zignites-chat'); ?></p>
                <?php else : ?>
                    <p class="description"><?php esc_html_e('Each call costs your GPT account. Rate-limited to 10 requests per hour per visitor IP.', 'zignites-chat'); ?></p>
                <?php endif; ?>
            </td>
        </tr>
    </table>

    <h3><?php esc_html_e('Agents', 'zignites-chat'); ?><?php if (!$zignites_chat_cb_pro) : ?> <span class="zignites-chat-pro-tag"><?php esc_html_e('Multi-agent is Pro', 'zignites-chat'); ?></span><?php endif; ?></h3>
    <p class="description">
        <?php if ($zignites_chat_cb_pro) : ?>
            <?php esc_html_e('Add the team members who can receive customer chats. The chatbot widget routes "Send via WhatsApp" clicks to one of these numbers using the routing mode below.', 'zignites-chat'); ?>
        <?php else : ?>
            <?php esc_html_e('The free plan routes every chat to a single agent. Upgrade to Pro to add more agents and load-balance between them.', 'zignites-chat'); ?>
        <?php endif; ?>
    </p>
    <?php if ($zignites_chat_cb_pro) : ?>
    <table class="form-table">
        <tr>
            <th scope="row"><label for="zignites_chat_agent_routing_mode"><?php esc_html_e('Routing mode', 'zignites-chat'); ?></label></th>
            <td>
                <?php $zignites_chat_routing_mode = get_option('zignites_chat_agent_routing_mode', 'single'); ?>
                <select name="zignites_chat_agent_routing_mode" id="zignites_chat_agent_routing_mode">
                    <option value="single" <?php selected($zignites_chat_routing_mode, 'single'); ?>><?php esc_html_e('Single — first agent only', 'zignites-chat'); ?></option>
                    <option value="random" <?php selected($zignites_chat_routing_mode, 'random'); ?>><?php esc_html_e('Random — load-balance across agents', 'zignites-chat'); ?></option>
                </select>
                <p class="description"><?php esc_html_e('Random is chosen client-side per page load so a full-page cache does not pin every visitor to the same agent.', 'zignites-chat'); ?></p>
            </td>
        </tr>
    </table>
    <?php else : ?>
        <input type="hidden" name="zignites_chat_agent_routing_mode" value="<?php echo esc_attr(get_option('zignites_chat_agent_routing_mode', 'single')); ?>" />
    <?php endif; ?>

    <?php
    $zignites_chat_agents = zignites_chat_get_agents();
    // Free plan edits a single agent only.
    if (!$zignites_chat_cb_pro) {
        $zignites_chat_agents = array_slice($zignites_chat_agents, 0, 1);
    }
    ?>
    <table class="widefat striped" id="zignites-chat-agents-table">
        <thead>
            <tr>
                <th><?php esc_html_e('Agent name', 'zignites-chat'); ?></th>
                <th><?php esc_html_e('WhatsApp number', 'zignites-chat'); ?></th>
                <th class="zignites-chat-agents-row-actions"></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($zignites_chat_agents)) : ?>
                <tr class="zignites-chat-agent-row">
                    <td><input type="text" class="zignites-chat-agent-name regular-text" placeholder="<?php esc_attr_e('e.g. Sales', 'zignites-chat'); ?>" value="" /></td>
                    <td><input type="text" class="zignites-chat-agent-phone regular-text" placeholder="<?php esc_attr_e('e.g. +14155550100', 'zignites-chat'); ?>" value="" /></td>
                    <td><?php if ($zignites_chat_cb_pro) : ?><button type="button" class="button-link zignites-chat-agent-remove" aria-label="<?php esc_attr_e('Remove agent', 'zignites-chat'); ?>">&times;</button><?php endif; ?></td>
                </tr>
            <?php else : foreach ($zignites_chat_agents as $zignites_chat_agent) : ?>
                <tr class="zignites-chat-agent-row">
                    <td><input type="text" class="zignites-chat-agent-name regular-text" placeholder="<?php esc_attr_e('e.g. Sales', 'zignites-chat'); ?>" value="<?php echo esc_attr($zignites_chat_agent['name']); ?>" /></td>
                    <td><input type="text" class="zignites-chat-agent-phone regular-text" placeholder="<?php esc_attr_e('e.g. +14155550100', 'zignites-chat'); ?>" value="<?php echo esc_attr($zignites_chat_agent['phone']); ?>" /></td>
                    <td><?php if ($zignites_chat_cb_pro) : ?><button type="button" class="button-link zignites-chat-agent-remove" aria-label="<?php esc_attr_e('Remove agent', 'zignites-chat'); ?>">&times;</button><?php endif; ?></td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
    <?php if ($zignites_chat_cb_pro) : ?>
    <p>
        <button type="button" class="button" id="zignites-chat-agent-add">+ <?php esc_html_e('Add agent', 'zignites-chat'); ?></button>
    </p>
    <?php endif; ?>
    <input type="hidden" name="zignites_chat_agents" id="zignites_chat_agents_input" value="<?php echo esc_attr(get_option('zignites_chat_agents', '[]')); ?>" />
