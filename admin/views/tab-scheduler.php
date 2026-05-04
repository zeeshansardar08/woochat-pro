<?php
if (!defined('ABSPATH')) exit;
?>
<div id="wcwp-tab-content-scheduler" class="wcwp-tab-content" style="display:none;">
    <?php $is_pro = function_exists('wcwp_is_pro_active') && wcwp_is_pro_active(); ?>
    <?php if (!$is_pro) : ?>
        <div class="wcwp-pro-banner"><span class="dashicons dashicons-clock"></span> <strong><?php esc_html_e('Scheduler', 'woochat-pro'); ?></strong> <?php esc_html_e('is a Pro feature.', 'woochat-pro'); ?> <button type="button" class="wcwp-open-upgrade-modal" style="margin-left:12px;"><?php esc_html_e('Upgrade', 'woochat-pro'); ?></button></div>
    <?php endif; ?>
    <table class="form-table">
        <tr>
            <th scope="row"><label for="wcwp_followup_enabled"><?php esc_html_e('Enable Follow-up', 'woochat-pro'); ?></label><span class="wcwp-help-icon">?<span class="wcwp-tooltip"><?php esc_html_e('Schedule a WhatsApp follow-up message after an order is placed.', 'woochat-pro'); ?></span></span></th>
            <td>
                <select name="wcwp_followup_enabled" id="wcwp_followup_enabled" <?php disabled(!$is_pro); ?>>
                    <option value="yes" <?php selected(get_option('wcwp_followup_enabled', 'no'), 'yes'); ?>><?php esc_html_e('Yes', 'woochat-pro'); ?></option>
                    <option value="no" <?php selected(get_option('wcwp_followup_enabled', 'no'), 'no'); ?>><?php esc_html_e('No', 'woochat-pro'); ?></option>
                </select>
                <p class="description"><?php esc_html_e('Requires valid license. Sends one follow-up per order.', 'woochat-pro'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="wcwp_followup_delay_minutes"><?php esc_html_e('Delay (minutes)', 'woochat-pro'); ?></label><span class="wcwp-help-icon">?<span class="wcwp-tooltip"><?php esc_html_e('How long after order completion/processing should the follow-up be sent?', 'woochat-pro'); ?></span></span></th>
            <td>
                <input type="number" min="1" name="wcwp_followup_delay_minutes" id="wcwp_followup_delay_minutes" value="<?php echo esc_attr(get_option('wcwp_followup_delay_minutes', 120)); ?>" class="small-text" <?php disabled(!$is_pro); ?> />
                <p class="description"><?php esc_html_e('Default: 120 minutes.', 'woochat-pro'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="wcwp_followup_template"><?php esc_html_e('Follow-up Template', 'woochat-pro'); ?></label><span class="wcwp-help-icon">?<span class="wcwp-tooltip"><?php esc_html_e('Placeholders: {name}, {order_id}, {total}, {status}, {date}', 'woochat-pro'); ?></span></span></th>
            <td>
                <textarea name="wcwp_followup_template" id="wcwp_followup_template" rows="5" class="large-text" <?php disabled(!$is_pro); ?>><?php echo esc_textarea(get_option('wcwp_followup_template', "Hi {name}, thanks again for your order #{order_id}! Reply if you have any questions.")); ?></textarea>
                <p class="description"><?php esc_html_e('Sent once per order after the delay.', 'woochat-pro'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="wcwp_followup_use_gpt"><?php esc_html_e('Use GPT (optional)', 'woochat-pro'); ?></label><span class="wcwp-help-icon">?<span class="wcwp-tooltip"><?php esc_html_e('Generate the follow-up copy with your GPT endpoint; falls back to the template if the call fails.', 'woochat-pro'); ?></span></span></th>
            <td>
                <select name="wcwp_followup_use_gpt" id="wcwp_followup_use_gpt" <?php disabled(!$is_pro); ?>>
                    <option value="no" <?php selected(get_option('wcwp_followup_use_gpt', 'no'), 'no'); ?>><?php esc_html_e('No', 'woochat-pro'); ?></option>
                    <option value="yes" <?php selected(get_option('wcwp_followup_use_gpt', 'no'), 'yes'); ?>><?php esc_html_e('Yes', 'woochat-pro'); ?></option>
                </select>
                <p class="description"><?php esc_html_e('Supports free/alt GPT endpoints by configuring the URL and key below.', 'woochat-pro'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="wcwp_gpt_api_endpoint"><?php esc_html_e('GPT API Endpoint', 'woochat-pro'); ?></label></th>
            <td>
                <input type="text" name="wcwp_gpt_api_endpoint" id="wcwp_gpt_api_endpoint" value="<?php echo esc_attr(get_option('wcwp_gpt_api_endpoint', 'https://api.openai.com/v1/chat/completions')); ?>" class="regular-text" <?php disabled(!$is_pro); ?> />
                <p class="description"><?php esc_html_e('Set to your free/alt GPT API endpoint.', 'woochat-pro'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="wcwp_gpt_api_key"><?php esc_html_e('GPT API Key', 'woochat-pro'); ?></label></th>
            <td>
                <input type="password" name="wcwp_gpt_api_key" id="wcwp_gpt_api_key" value="<?php echo esc_attr(get_option('wcwp_gpt_api_key', '')); ?>" class="regular-text" autocomplete="off" <?php disabled(!$is_pro); ?> />
                <p class="description"><?php esc_html_e('Stored in WordPress options; use a non-production key if possible.', 'woochat-pro'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="wcwp_gpt_model"><?php esc_html_e('GPT Model', 'woochat-pro'); ?></label></th>
            <td>
                <input type="text" name="wcwp_gpt_model" id="wcwp_gpt_model" value="<?php echo esc_attr(get_option('wcwp_gpt_model', 'gpt-3.5-turbo')); ?>" class="regular-text" <?php disabled(!$is_pro); ?> />
                <p class="description"><?php esc_html_e('Adjust for your provider (e.g., gpt-3.5-turbo, gpt-4o-mini, or a free-tier model).', 'woochat-pro'); ?></p>
            </td>
        </tr>
    </table>
</div>
