<?php
if (!defined('ABSPATH')) exit;
?>
<div id="wcwp-tab-content-general" class="wcwp-tab-content" style="display:block;">
    <table class="form-table">
        <tr>
            <th scope="row"><label for="wcwp_twilio_sid"><?php esc_html_e('Twilio SID', 'woochat-pro'); ?></label><span class="wcwp-help-icon">?<span class="wcwp-tooltip"><?php esc_html_e('Your Twilio Account SID. Find it in your Twilio dashboard.', 'woochat-pro'); ?></span></span></th>
            <td><input type="text" name="wcwp_twilio_sid" id="wcwp_twilio_sid" value="<?php echo esc_attr(get_option('wcwp_twilio_sid')); ?>" class="regular-text" /></td>
        </tr>
        <tr>
            <th scope="row"><label for="wcwp_twilio_auth_token"><?php esc_html_e('Twilio Auth Token', 'woochat-pro'); ?></label><span class="wcwp-help-icon">?<span class="wcwp-tooltip"><?php esc_html_e('Your Twilio Auth Token. Keep this secret!', 'woochat-pro'); ?></span></span></th>
            <td><input type="password" name="wcwp_twilio_auth_token" id="wcwp_twilio_auth_token" value="<?php echo esc_attr(get_option('wcwp_twilio_auth_token')); ?>" class="regular-text" autocomplete="off" /></td>
        </tr>
        <tr>
            <th scope="row"><label for="wcwp_twilio_from"><?php esc_html_e('WhatsApp From Number', 'woochat-pro'); ?></label><span class="wcwp-help-icon">?<span class="wcwp-tooltip"><?php esc_html_e('The WhatsApp-enabled number from your Twilio or Cloud API account. Format: whatsapp:+1234567890', 'woochat-pro'); ?></span></span></th>
            <td><input type="text" name="wcwp_twilio_from" id="wcwp_twilio_from" value="<?php echo esc_attr(get_option('wcwp_twilio_from')); ?>" class="regular-text" placeholder="e.g. whatsapp:+14155238886" /></td>
        </tr>
        <tr>
            <th scope="row"><label for="wcwp_api_provider"><?php esc_html_e('API Provider', 'woochat-pro'); ?></label><span class="wcwp-help-icon">?<span class="wcwp-tooltip"><?php esc_html_e('Choose which WhatsApp API to use: Twilio or WhatsApp Cloud (Meta).', 'woochat-pro'); ?></span></span></th>
            <td>
                <select name="wcwp_api_provider" id="wcwp_api_provider">
                    <option value="twilio" <?php selected(get_option('wcwp_api_provider', 'twilio'), 'twilio'); ?>><?php esc_html_e('Twilio', 'woochat-pro'); ?></option>
                    <option value="cloud" <?php selected(get_option('wcwp_api_provider', 'twilio'), 'cloud'); ?>><?php esc_html_e('WhatsApp Cloud', 'woochat-pro'); ?></option>
                </select>
                <p class="description"><?php esc_html_e('Select your WhatsApp API provider.', 'woochat-pro'); ?></p>
            </td>
        </tr>
        <tr class="wcwp-cloud-fields" style="display:none;">
            <th scope="row"><label for="wcwp_cloud_token"><?php esc_html_e('Cloud API Token', 'woochat-pro'); ?></label><span class="wcwp-help-icon">?<span class="wcwp-tooltip"><?php esc_html_e('Your WhatsApp Cloud API access token from Meta.', 'woochat-pro'); ?></span></span></th>
            <td><input type="password" name="wcwp_cloud_token" id="wcwp_cloud_token" value="<?php echo esc_attr(get_option('wcwp_cloud_token')); ?>" class="regular-text" autocomplete="off" /></td>
        </tr>
        <tr class="wcwp-cloud-fields" style="display:none;">
            <th scope="row"><label for="wcwp_cloud_phone_id"><?php esc_html_e('Phone Number ID', 'woochat-pro'); ?></label><span class="wcwp-help-icon">?<span class="wcwp-tooltip"><?php esc_html_e('Your WhatsApp Cloud API phone number ID.', 'woochat-pro'); ?></span></span></th>
            <td><input type="text" name="wcwp_cloud_phone_id" id="wcwp_cloud_phone_id" value="<?php echo esc_attr(get_option('wcwp_cloud_phone_id')); ?>" class="regular-text" /></td>
        </tr>
        <tr class="wcwp-cloud-fields" style="display:none;">
            <th scope="row"><label for="wcwp_cloud_from"><?php esc_html_e('From Number', 'woochat-pro'); ?></label><span class="wcwp-help-icon">?<span class="wcwp-tooltip"><?php esc_html_e('The WhatsApp-enabled number from your Cloud API account. Format: +1234567890', 'woochat-pro'); ?></span></span></th>
            <td><input type="text" name="wcwp_cloud_from" id="wcwp_cloud_from" value="<?php echo esc_attr(get_option('wcwp_cloud_from')); ?>" class="regular-text" placeholder="e.g. +14155238886" /></td>
        </tr>
        <tr class="wcwp-cloud-fields" style="display:none;">
            <th scope="row"><label for="wcwp_cloud_app_secret"><?php esc_html_e('Cloud App Secret', 'woochat-pro'); ?></label><span class="wcwp-help-icon">?<span class="wcwp-tooltip"><?php esc_html_e('Optional. Meta App Secret used to verify X-Hub-Signature-256 on incoming opt-out webhooks. Different from the access token.', 'woochat-pro'); ?></span></span></th>
            <td><input type="password" name="wcwp_cloud_app_secret" id="wcwp_cloud_app_secret" value="<?php echo esc_attr(get_option('wcwp_cloud_app_secret')); ?>" class="regular-text" autocomplete="off" />
                <p class="description"><?php esc_html_e('Leave blank to skip Meta webhook signature verification (token check still applies).', 'woochat-pro'); ?></p></td>
        </tr>
        <tr>
            <td colspan="2">
                <div style="background: #fff3cd; color: #856404; border: 1px solid #ffe066; border-radius: 8px; padding: 18px 22px; margin-top: 18px; display: flex; align-items: center; gap: 16px;">
                    <span style="font-size: 2rem;">⚠️</span>
                    <div>
                        <label style="font-weight: 600; font-size: 1.1rem;" for="wcwp_test_mode_enabled"><?php esc_html_e('Test Mode', 'woochat-pro'); ?></label>
                        <input type="hidden" name="wcwp_test_mode_enabled" value="no" />
                        <input type="checkbox" name="wcwp_test_mode_enabled" value="yes" id="wcwp_test_mode_enabled" <?php checked(get_option('wcwp_test_mode_enabled'), 'yes'); ?> style="margin-left: 10px;" />
                        <div style="font-size: 0.98rem; margin-top: 4px;"><?php esc_html_e('Enable this for safe testing.', 'woochat-pro'); ?> <b><?php esc_html_e('Messages will be logged, not sent.', 'woochat-pro'); ?></b> <?php esc_html_e("Don't forget to turn it off in production!", 'woochat-pro'); ?></div>
                    </div>
                </div>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="wcwp_data_retention_days"><?php esc_html_e('Data Retention (days)', 'woochat-pro'); ?></label><span class="wcwp-help-icon">?<span class="wcwp-tooltip"><?php esc_html_e('Automatically delete analytics events older than this. Set to 0 to keep indefinitely.', 'woochat-pro'); ?></span></span></th>
            <td>
                <input type="number" min="0" name="wcwp_data_retention_days" id="wcwp_data_retention_days" value="<?php echo esc_attr(get_option('wcwp_data_retention_days', 0)); ?>" class="small-text" />
                <p class="description"><?php esc_html_e('Recommended: 30–180 days.', 'woochat-pro'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="wcwp_delete_data_on_uninstall"><?php esc_html_e('Delete Data on Uninstall', 'woochat-pro'); ?></label><span class="wcwp-help-icon">?<span class="wcwp-tooltip"><?php esc_html_e('If enabled, all plugin data will be removed when the plugin is deleted.', 'woochat-pro'); ?></span></span></th>
            <td>
                <select name="wcwp_delete_data_on_uninstall" id="wcwp_delete_data_on_uninstall">
                    <option value="no" <?php selected(get_option('wcwp_delete_data_on_uninstall', 'no'), 'no'); ?>><?php esc_html_e('No', 'woochat-pro'); ?></option>
                    <option value="yes" <?php selected(get_option('wcwp_delete_data_on_uninstall', 'no'), 'yes'); ?>><?php esc_html_e('Yes', 'woochat-pro'); ?></option>
                </select>
                <p class="description"><?php esc_html_e('Use for strict compliance requirements.', 'woochat-pro'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="wcwp_optout_keywords"><?php esc_html_e('Opt-out Keywords', 'woochat-pro'); ?></label><span class="wcwp-help-icon">?<span class="wcwp-tooltip"><?php esc_html_e('Incoming messages containing these words will add the number to the suppression list.', 'woochat-pro'); ?></span></span></th>
            <td>
                <input type="text" name="wcwp_optout_keywords" id="wcwp_optout_keywords" value="<?php echo esc_attr(get_option('wcwp_optout_keywords', 'stop, unsubscribe')); ?>" class="regular-text" />
                <p class="description"><?php esc_html_e('Comma-separated. Default: stop, unsubscribe.', 'woochat-pro'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="wcwp_optout_webhook_token"><?php esc_html_e('Opt-out Webhook Token', 'woochat-pro'); ?></label><span class="wcwp-help-icon">?<span class="wcwp-tooltip"><?php esc_html_e('Use this token to secure the opt-out webhook endpoint.', 'woochat-pro'); ?></span></span></th>
            <td>
                <input type="text" name="wcwp_optout_webhook_token" id="wcwp_optout_webhook_token" value="<?php echo esc_attr(get_option('wcwp_optout_webhook_token', '')); ?>" class="regular-text" />
                <p class="description"><?php esc_html_e('Webhook:', 'woochat-pro'); ?> <code><?php echo esc_html(rest_url('wcwp/v1/optout')); ?></code></p>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="wcwp_optout_list"><?php esc_html_e('Suppression List (opted-out numbers)', 'woochat-pro'); ?></label><span class="wcwp-help-icon">?<span class="wcwp-tooltip"><?php esc_html_e('Numbers in this list will never receive messages.', 'woochat-pro'); ?></span></span></th>
            <td>
                <?php $optout_list = wcwp_get_optout_list(); ?>
                <textarea name="wcwp_optout_list" id="wcwp_optout_list" rows="4" class="large-text"><?php echo esc_textarea(implode("\n", $optout_list)); ?></textarea>
                <p class="description"><?php esc_html_e('One phone number per line.', 'woochat-pro'); ?></p>
            </td>
        </tr>
    </table>
</div>
