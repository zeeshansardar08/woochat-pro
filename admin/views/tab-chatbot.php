<?php
if (!defined('ABSPATH')) exit;

// The Chatbot page is reachable on the free plan, but the color/icon
// customizer, GPT fallback and multi-agent routing are Pro features. For
// non-Pro users those controls render disabled; a hidden input preserves any
// previously saved value so a Save on this page never wipes it.
$wcwp_cb_pro = wcwp_is_pro_active();
?>
<?php if (!$wcwp_cb_pro) : ?>
    <div class="wcwp-pro-banner"><span class="dashicons dashicons-admin-customizer"></span> <?php esc_html_e('The chatbot color/icon customizer, GPT auto-replies and multi-agent routing are Pro features. The basic chat widget and FAQ replies are included free.', 'woochat'); ?> <button type="button" class="wcwp-open-upgrade-modal" style="margin-left:12px;"><?php esc_html_e('Upgrade', 'woochat'); ?></button></div>
<?php endif; ?>
<div class="wcwp-chatbot-customizer">
        <div class="wcwp-chatbot-customizer-controls">
            <label for="wcwp-chatbot-bg"><?php esc_html_e('Chatbot Bubble Color', 'woochat'); ?><?php if (!$wcwp_cb_pro) : ?> <span class="wcwp-pro-tag"><?php esc_html_e('Pro', 'woochat'); ?></span><?php endif; ?></label>
            <input type="color" id="wcwp-chatbot-bg" <?php echo $wcwp_cb_pro ? 'name="wcwp_chatbot_bg"' : ''; ?> value="<?php echo esc_attr(get_option('wcwp_chatbot_bg', '#1c7c54')); ?>" <?php disabled(!$wcwp_cb_pro); ?>>
            <label for="wcwp-chatbot-color"><?php esc_html_e('Text Color', 'woochat'); ?><?php if (!$wcwp_cb_pro) : ?> <span class="wcwp-pro-tag"><?php esc_html_e('Pro', 'woochat'); ?></span><?php endif; ?></label>
            <input type="color" id="wcwp-chatbot-color" <?php echo $wcwp_cb_pro ? 'name="wcwp_chatbot_text"' : ''; ?> value="<?php echo esc_attr(get_option('wcwp_chatbot_text', '#ffffff')); ?>" <?php disabled(!$wcwp_cb_pro); ?>>
            <label for="wcwp-chatbot-icon"><?php esc_html_e('Icon Color', 'woochat'); ?><?php if (!$wcwp_cb_pro) : ?> <span class="wcwp-pro-tag"><?php esc_html_e('Pro', 'woochat'); ?></span><?php endif; ?></label>
            <input type="color" id="wcwp-chatbot-icon" <?php echo $wcwp_cb_pro ? 'name="wcwp_chatbot_icon_color"' : ''; ?> value="<?php echo esc_attr(get_option('wcwp_chatbot_icon_color', '#2ec4b6')); ?>" <?php disabled(!$wcwp_cb_pro); ?>>
            <label><?php esc_html_e('Choose Icon', 'woochat'); ?><?php if (!$wcwp_cb_pro) : ?> <span class="wcwp-pro-tag"><?php esc_html_e('Pro', 'woochat'); ?></span><?php endif; ?></label>
            <div class="wcwp-icon-select <?php echo esc_attr($wcwp_cb_pro ? '' : 'wcwp-pro-locked'); ?>">
                <?php $icon_option = get_option('wcwp_chatbot_icon', '💬'); ?>
                <span class="wcwp-icon-option <?php echo esc_attr( $icon_option === '💬' ? 'selected' : '' ); ?>">💬</span>
                <span class="wcwp-icon-option <?php echo esc_attr( $icon_option === '🤖' ? 'selected' : '' ); ?>">🤖</span>
                <span class="wcwp-icon-option <?php echo esc_attr( $icon_option === '🟢' ? 'selected' : '' ); ?>">🟢</span>
                <span class="wcwp-icon-option <?php echo esc_attr( $icon_option === '📞' ? 'selected' : '' ); ?>">📞</span>
            </div>
            <?php if ($wcwp_cb_pro) : ?>
                <input type="hidden" id="wcwp-chatbot-icon-value" name="wcwp_chatbot_icon" value="<?php echo esc_attr($icon_option); ?>" />
            <?php else : ?>
                <input type="hidden" id="wcwp-chatbot-icon-value" value="<?php echo esc_attr($icon_option); ?>" />
            <?php endif; ?>
            <label for="wcwp-chatbot-welcome"><?php esc_html_e('Welcome Message', 'woochat'); ?></label>
            <input type="text" id="wcwp-chatbot-welcome" name="wcwp_chatbot_welcome" value="<?php echo esc_attr(get_option('wcwp_chatbot_welcome', 'Hi! How can I help you?')); ?>">
        </div>
        <div class="wcwp-chatbot-customizer-preview">
            <div class="wcwp-chatbot-preview-icon">💬</div>
            <div class="wcwp-chatbot-preview-bubble"><span id="wcwp-chatbot-preview-welcome">Hi! How can I help you?</span></div>
        </div>
    </div>
    <table class="form-table">
        <tr>
            <th scope="row"><label for="wcwp_chatbot_enabled"><?php esc_html_e('Enable Chatbot', 'woochat'); ?></label><span class="wcwp-help-icon">?<span class="wcwp-tooltip"><?php esc_html_e('Toggle the floating WhatsApp chatbot widget on your site.', 'woochat'); ?></span></span></th>
            <td>
                <select name="wcwp_chatbot_enabled" id="wcwp_chatbot_enabled">
                    <option value="yes" <?php selected(get_option('wcwp_chatbot_enabled'), 'yes'); ?>><?php esc_html_e('Yes', 'woochat'); ?></option>
                    <option value="no" <?php selected(get_option('wcwp_chatbot_enabled'), 'no'); ?>><?php esc_html_e('No', 'woochat'); ?></option>
                </select>
                <p class="description"><?php esc_html_e('Toggle the floating WhatsApp chatbot on your site.', 'woochat'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="wcwp_faq_pairs"><?php esc_html_e('FAQ Rules (JSON)', 'woochat'); ?></label><span class="wcwp-help-icon">?<span class="wcwp-tooltip"><?php esc_html_e('Add question/answer pairs for the chatbot. Format: JSON array.', 'woochat'); ?></span></span></th>
            <td>
                <textarea name="wcwp_faq_pairs" rows="6" class="large-text"><?php echo esc_textarea(get_option('wcwp_faq_pairs', '[{"question":"order","answer":"You can track your order here: yourdomain.com/track"},{"question":"return","answer":"We offer 7-day easy returns."}]')); ?></textarea>
                <p class="description"><?php esc_html_e('Enter question/answer pairs as JSON array.', 'woochat'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="wcwp_chatbot_gpt_enabled"><?php esc_html_e('GPT Fallback', 'woochat'); ?><?php if (!$wcwp_cb_pro) : ?> <span class="wcwp-pro-tag"><?php esc_html_e('Pro', 'woochat'); ?></span><?php endif; ?></label><span class="wcwp-help-icon">?<span class="wcwp-tooltip"><?php esc_html_e('When the FAQ matcher finds no answer, ask the configured GPT endpoint for a reply. Requires GPT API endpoint and key set on the Scheduler page.', 'woochat'); ?></span></span></th>
            <td>
                <?php $wcwp_gpt_enabled = get_option('wcwp_chatbot_gpt_enabled', 'no'); ?>
                <select <?php echo $wcwp_cb_pro ? 'name="wcwp_chatbot_gpt_enabled"' : ''; ?> id="wcwp_chatbot_gpt_enabled" <?php disabled(!$wcwp_cb_pro); ?>>
                    <option value="no" <?php selected($wcwp_gpt_enabled, 'no'); ?>><?php esc_html_e('No', 'woochat'); ?></option>
                    <option value="yes" <?php selected($wcwp_gpt_enabled, 'yes'); ?>><?php esc_html_e('Yes', 'woochat'); ?></option>
                </select>
                <?php if (!$wcwp_cb_pro) : ?>
                    <input type="hidden" name="wcwp_chatbot_gpt_enabled" value="<?php echo esc_attr($wcwp_gpt_enabled); ?>" />
                    <p class="description"><?php esc_html_e('GPT-powered auto-replies are a Pro feature.', 'woochat'); ?></p>
                <?php else : ?>
                    <p class="description"><?php esc_html_e('Each call costs your GPT account. Rate-limited to 10 requests per hour per visitor IP.', 'woochat'); ?></p>
                <?php endif; ?>
            </td>
        </tr>
    </table>

    <h3><?php esc_html_e('Agents', 'woochat'); ?><?php if (!$wcwp_cb_pro) : ?> <span class="wcwp-pro-tag"><?php esc_html_e('Multi-agent is Pro', 'woochat'); ?></span><?php endif; ?></h3>
    <p class="description">
        <?php if ($wcwp_cb_pro) : ?>
            <?php esc_html_e('Add the team members who can receive customer chats. The chatbot widget routes "Send via WhatsApp" clicks to one of these numbers using the routing mode below.', 'woochat'); ?>
        <?php else : ?>
            <?php esc_html_e('The free plan routes every chat to a single agent. Upgrade to Pro to add more agents and load-balance between them.', 'woochat'); ?>
        <?php endif; ?>
    </p>
    <?php if ($wcwp_cb_pro) : ?>
    <table class="form-table">
        <tr>
            <th scope="row"><label for="wcwp_agent_routing_mode"><?php esc_html_e('Routing mode', 'woochat'); ?></label></th>
            <td>
                <?php $wcwp_routing_mode = get_option('wcwp_agent_routing_mode', 'single'); ?>
                <select name="wcwp_agent_routing_mode" id="wcwp_agent_routing_mode">
                    <option value="single" <?php selected($wcwp_routing_mode, 'single'); ?>><?php esc_html_e('Single — first agent only', 'woochat'); ?></option>
                    <option value="random" <?php selected($wcwp_routing_mode, 'random'); ?>><?php esc_html_e('Random — load-balance across agents', 'woochat'); ?></option>
                </select>
                <p class="description"><?php esc_html_e('Random is chosen client-side per page load so a full-page cache does not pin every visitor to the same agent.', 'woochat'); ?></p>
            </td>
        </tr>
    </table>
    <?php else : ?>
        <input type="hidden" name="wcwp_agent_routing_mode" value="<?php echo esc_attr(get_option('wcwp_agent_routing_mode', 'single')); ?>" />
    <?php endif; ?>

    <?php
    $wcwp_agents = wcwp_get_agents();
    // Free plan edits a single agent only.
    if (!$wcwp_cb_pro) {
        $wcwp_agents = array_slice($wcwp_agents, 0, 1);
    }
    ?>
    <table class="widefat striped" id="wcwp-agents-table">
        <thead>
            <tr>
                <th><?php esc_html_e('Agent name', 'woochat'); ?></th>
                <th><?php esc_html_e('WhatsApp number', 'woochat'); ?></th>
                <th class="wcwp-agents-row-actions"></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($wcwp_agents)) : ?>
                <tr class="wcwp-agent-row">
                    <td><input type="text" class="wcwp-agent-name regular-text" placeholder="<?php esc_attr_e('e.g. Sales', 'woochat'); ?>" value="" /></td>
                    <td><input type="text" class="wcwp-agent-phone regular-text" placeholder="<?php esc_attr_e('e.g. +14155550100', 'woochat'); ?>" value="" /></td>
                    <td><?php if ($wcwp_cb_pro) : ?><button type="button" class="button-link wcwp-agent-remove" aria-label="<?php esc_attr_e('Remove agent', 'woochat'); ?>">&times;</button><?php endif; ?></td>
                </tr>
            <?php else : foreach ($wcwp_agents as $wcwp_agent) : ?>
                <tr class="wcwp-agent-row">
                    <td><input type="text" class="wcwp-agent-name regular-text" placeholder="<?php esc_attr_e('e.g. Sales', 'woochat'); ?>" value="<?php echo esc_attr($wcwp_agent['name']); ?>" /></td>
                    <td><input type="text" class="wcwp-agent-phone regular-text" placeholder="<?php esc_attr_e('e.g. +14155550100', 'woochat'); ?>" value="<?php echo esc_attr($wcwp_agent['phone']); ?>" /></td>
                    <td><?php if ($wcwp_cb_pro) : ?><button type="button" class="button-link wcwp-agent-remove" aria-label="<?php esc_attr_e('Remove agent', 'woochat'); ?>">&times;</button><?php endif; ?></td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
    <?php if ($wcwp_cb_pro) : ?>
    <p>
        <button type="button" class="button" id="wcwp-agent-add">+ <?php esc_html_e('Add agent', 'woochat'); ?></button>
    </p>
    <?php endif; ?>
    <input type="hidden" name="wcwp_agents" id="wcwp_agents_input" value="<?php echo esc_attr(get_option('wcwp_agents', '[]')); ?>" />
