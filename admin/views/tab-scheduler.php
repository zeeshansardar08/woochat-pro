<?php
if (!defined('ABSPATH')) exit;
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- View partial: these variables are scoped to the render function that includes this file, not the global namespace.
?>
<?php $is_pro = zignites_chat_is_pro_active(); ?>
    <?php if (!$is_pro) : ?>
        <div class="zignites-chat-pro-banner"><span class="dashicons dashicons-clock"></span> <strong><?php esc_html_e('Scheduler', 'zignites-chat'); ?></strong> <?php esc_html_e('is a Pro feature.', 'zignites-chat'); ?> <button type="button" class="zignites-chat-open-upgrade-modal" style="margin-left:12px;"><?php esc_html_e('Upgrade', 'zignites-chat'); ?></button></div>
    <?php endif; ?>
    <table class="form-table">
        <tr>
            <th scope="row"><label for="zignites_chat_followup_enabled"><?php esc_html_e('Enable Follow-up', 'zignites-chat'); ?></label><span class="zignites-chat-help-icon">?<span class="zignites-chat-tooltip"><?php esc_html_e('Schedule a WhatsApp follow-up message after an order is placed.', 'zignites-chat'); ?></span></span></th>
            <td>
                <select name="zignites_chat_followup_enabled" id="zignites_chat_followup_enabled" <?php disabled(!$is_pro); ?>>
                    <option value="yes" <?php selected(get_option('zignites_chat_followup_enabled', 'no'), 'yes'); ?>><?php esc_html_e('Yes', 'zignites-chat'); ?></option>
                    <option value="no" <?php selected(get_option('zignites_chat_followup_enabled', 'no'), 'no'); ?>><?php esc_html_e('No', 'zignites-chat'); ?></option>
                </select>
                <p class="description"><?php esc_html_e('Requires valid license. Sends one follow-up per order.', 'zignites-chat'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="zignites_chat_followup_delay_minutes"><?php esc_html_e('Delay (minutes)', 'zignites-chat'); ?></label><span class="zignites-chat-help-icon">?<span class="zignites-chat-tooltip"><?php esc_html_e('How long after order completion/processing should the follow-up be sent?', 'zignites-chat'); ?></span></span></th>
            <td>
                <input type="number" min="1" name="zignites_chat_followup_delay_minutes" id="zignites_chat_followup_delay_minutes" value="<?php echo esc_attr(get_option('zignites_chat_followup_delay_minutes', 120)); ?>" class="small-text" <?php disabled(!$is_pro); ?> />
                <p class="description"><?php esc_html_e('Default: 120 minutes.', 'zignites-chat'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="zignites_chat_followup_template"><?php esc_html_e('Follow-up Template', 'zignites-chat'); ?></label><span class="zignites-chat-help-icon">?<span class="zignites-chat-tooltip"><?php esc_html_e('Placeholders: {name}, {order_id}, {total}, {currency_symbol}, {status}, {date}', 'zignites-chat'); ?></span></span></th>
            <td>
                <textarea name="zignites_chat_followup_template" id="zignites_chat_followup_template" rows="5" class="large-text" <?php disabled(!$is_pro); ?>><?php echo esc_textarea(get_option('zignites_chat_followup_template', "Hi {name}, thanks again for your order #{order_id}! Reply if you have any questions.")); ?></textarea>
                <p class="description"><?php esc_html_e('Sent once per order after the delay.', 'zignites-chat'); ?></p>
                <p style="margin-top:6px;">
                    <button type="button" class="button zignites-chat-browse-templates" data-target="zignites_chat_followup_template" data-kind="followup" <?php disabled(!$is_pro); ?>>
                        <span class="dashicons dashicons-book" style="vertical-align:middle;line-height:28px;"></span>
                        <?php esc_html_e('Browse template library', 'zignites-chat'); ?>
                    </button>
                </p>
            </td>
        </tr>
        <?php
        $followup_ab_enabled = get_option('zignites_chat_followup_ab_enabled', 'no');
        $followup_template_b = get_option('zignites_chat_followup_template_b', '');
        ?>
        <tr class="zignites-chat-ab-row" data-ab-kind="followup">
            <th scope="row"><label for="zignites_chat_followup_ab_enabled"><?php esc_html_e('A/B test this message', 'zignites-chat'); ?></label><span class="zignites-chat-help-icon">?<span class="zignites-chat-tooltip"><?php esc_html_e('Send a 50/50 split between this template and Variant B. Each order is assigned a variant deterministically. View results on the Analytics tab.', 'zignites-chat'); ?></span></span></th>
            <td>
                <select name="zignites_chat_followup_ab_enabled" id="zignites_chat_followup_ab_enabled" class="zignites-chat-ab-toggle" data-ab-target="zignites-chat-ab-variant-b-followup" <?php disabled(!$is_pro); ?>>
                    <option value="no" <?php selected($followup_ab_enabled, 'no'); ?>><?php esc_html_e('No', 'zignites-chat'); ?></option>
                    <option value="yes" <?php selected($followup_ab_enabled, 'yes'); ?>><?php esc_html_e('Yes', 'zignites-chat'); ?></option>
                </select>
                <p class="description"><?php esc_html_e('When GPT generation is enabled, the variant is still tagged on the event but the message body comes from GPT.', 'zignites-chat'); ?></p>
            </td>
        </tr>
        <tr class="zignites-chat-ab-variant-b" id="zignites-chat-ab-variant-b-followup" style="<?php echo esc_attr( $followup_ab_enabled === 'yes' ? '' : 'display:none;' ); ?>">
            <th scope="row"><label for="zignites_chat_followup_template_b"><?php esc_html_e('Variant B', 'zignites-chat'); ?></label></th>
            <td>
                <textarea id="zignites_chat_followup_template_b" name="zignites_chat_followup_template_b" rows="5" class="large-text" <?php disabled(!$is_pro); ?>><?php echo esc_textarea($followup_template_b); ?></textarea>
                <p class="description"><?php esc_html_e('Sent in place of the main template for half of orders when A/B is on.', 'zignites-chat'); ?></p>
                <p style="margin-top:6px;">
                    <button type="button" class="button zignites-chat-browse-templates" data-target="zignites_chat_followup_template_b" data-kind="followup" <?php disabled(!$is_pro); ?>>
                        <span class="dashicons dashicons-book" style="vertical-align:middle;line-height:28px;"></span>
                        <?php esc_html_e('Browse template library', 'zignites-chat'); ?>
                    </button>
                </p>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="zignites_chat_followup_use_gpt"><?php esc_html_e('Use GPT (optional)', 'zignites-chat'); ?></label><span class="zignites-chat-help-icon">?<span class="zignites-chat-tooltip"><?php esc_html_e('Generate the follow-up copy with your GPT endpoint; falls back to the template if the call fails.', 'zignites-chat'); ?></span></span></th>
            <td>
                <select name="zignites_chat_followup_use_gpt" id="zignites_chat_followup_use_gpt" <?php disabled(!$is_pro); ?>>
                    <option value="no" <?php selected(get_option('zignites_chat_followup_use_gpt', 'no'), 'no'); ?>><?php esc_html_e('No', 'zignites-chat'); ?></option>
                    <option value="yes" <?php selected(get_option('zignites_chat_followup_use_gpt', 'no'), 'yes'); ?>><?php esc_html_e('Yes', 'zignites-chat'); ?></option>
                </select>
                <p class="description"><?php esc_html_e('Supports free/alt GPT endpoints by configuring the URL and key below.', 'zignites-chat'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="zignites_chat_gpt_api_endpoint"><?php esc_html_e('GPT API Endpoint', 'zignites-chat'); ?></label></th>
            <td>
                <input type="text" name="zignites_chat_gpt_api_endpoint" id="zignites_chat_gpt_api_endpoint" value="<?php echo esc_attr(get_option('zignites_chat_gpt_api_endpoint', 'https://api.openai.com/v1/chat/completions')); ?>" class="regular-text" <?php disabled(!$is_pro); ?> />
                <p class="description"><?php esc_html_e('Set to your free/alt GPT API endpoint.', 'zignites-chat'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="zignites_chat_gpt_api_key"><?php esc_html_e('GPT API Key', 'zignites-chat'); ?></label></th>
            <td>
                <input type="password" name="zignites_chat_gpt_api_key" id="zignites_chat_gpt_api_key" value="<?php echo esc_attr(get_option('zignites_chat_gpt_api_key', '')); ?>" class="regular-text" autocomplete="off" <?php disabled(!$is_pro); ?> />
                <p class="description"><?php esc_html_e('Stored in WordPress options; use a non-production key if possible.', 'zignites-chat'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="zignites_chat_gpt_model"><?php esc_html_e('GPT Model', 'zignites-chat'); ?></label></th>
            <td>
                <input type="text" name="zignites_chat_gpt_model" id="zignites_chat_gpt_model" value="<?php echo esc_attr(get_option('zignites_chat_gpt_model', 'gpt-3.5-turbo')); ?>" class="regular-text" <?php disabled(!$is_pro); ?> />
                <p class="description"><?php esc_html_e('Adjust for your provider (e.g., gpt-3.5-turbo, gpt-4o-mini, or a free-tier model).', 'zignites-chat'); ?></p>
            </td>
        </tr>
    </table>
