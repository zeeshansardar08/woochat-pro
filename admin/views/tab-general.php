<?php
if (!defined('ABSPATH')) exit;
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- View partial: these variables are scoped to the render function that includes this file, not the global namespace.
?>
<table class="form-table">
        <tr>
            <th scope="row"><label for="zignites_chat_twilio_sid"><?php esc_html_e('Twilio SID', 'zignites-chat'); ?></label><span class="zignites-chat-help-icon">?<span class="zignites-chat-tooltip"><?php esc_html_e('Your Twilio Account SID. Find it in your Twilio dashboard.', 'zignites-chat'); ?></span></span></th>
            <td><input type="text" name="zignites_chat_twilio_sid" id="zignites_chat_twilio_sid" value="<?php echo esc_attr(get_option('zignites_chat_twilio_sid')); ?>" class="regular-text" /></td>
        </tr>
        <tr>
            <th scope="row"><label for="zignites_chat_twilio_auth_token"><?php esc_html_e('Twilio Auth Token', 'zignites-chat'); ?></label><span class="zignites-chat-help-icon">?<span class="zignites-chat-tooltip"><?php esc_html_e('Your Twilio Auth Token. Keep this secret!', 'zignites-chat'); ?></span></span></th>
            <td><input type="password" name="zignites_chat_twilio_auth_token" id="zignites_chat_twilio_auth_token" value="<?php echo esc_attr(get_option('zignites_chat_twilio_auth_token')); ?>" class="regular-text" autocomplete="off" /></td>
        </tr>
        <tr>
            <th scope="row"><label for="zignites_chat_twilio_from"><?php esc_html_e('WhatsApp From Number', 'zignites-chat'); ?></label><span class="zignites-chat-help-icon">?<span class="zignites-chat-tooltip"><?php esc_html_e('The WhatsApp-enabled number from your Twilio or Cloud API account. Format: whatsapp:+1234567890', 'zignites-chat'); ?></span></span></th>
            <td><input type="text" name="zignites_chat_twilio_from" id="zignites_chat_twilio_from" value="<?php echo esc_attr(get_option('zignites_chat_twilio_from')); ?>" class="regular-text" placeholder="e.g. whatsapp:+14155238886" /></td>
        </tr>
        <tr>
            <th scope="row"><label for="zignites_chat_api_provider"><?php esc_html_e('API Provider', 'zignites-chat'); ?></label><span class="zignites-chat-help-icon">?<span class="zignites-chat-tooltip"><?php esc_html_e('Choose which WhatsApp API to use: Twilio or WhatsApp Cloud (Meta).', 'zignites-chat'); ?></span></span></th>
            <td>
                <select name="zignites_chat_api_provider" id="zignites_chat_api_provider">
                    <option value="twilio" <?php selected(get_option('zignites_chat_api_provider', 'twilio'), 'twilio'); ?>><?php esc_html_e('Twilio', 'zignites-chat'); ?></option>
                    <option value="cloud" <?php selected(get_option('zignites_chat_api_provider', 'twilio'), 'cloud'); ?>><?php esc_html_e('WhatsApp Cloud', 'zignites-chat'); ?></option>
                </select>
                <p class="description"><?php esc_html_e('Select your WhatsApp API provider.', 'zignites-chat'); ?></p>
            </td>
        </tr>
        <tr class="zignites-chat-cloud-fields" style="display:none;">
            <th scope="row"><label for="zignites_chat_cloud_token"><?php esc_html_e('Cloud API Token', 'zignites-chat'); ?></label><span class="zignites-chat-help-icon">?<span class="zignites-chat-tooltip"><?php esc_html_e('Your WhatsApp Cloud API access token from Meta.', 'zignites-chat'); ?></span></span></th>
            <td><input type="password" name="zignites_chat_cloud_token" id="zignites_chat_cloud_token" value="<?php echo esc_attr(get_option('zignites_chat_cloud_token')); ?>" class="regular-text" autocomplete="off" /></td>
        </tr>
        <tr class="zignites-chat-cloud-fields" style="display:none;">
            <th scope="row"><label for="zignites_chat_cloud_phone_id"><?php esc_html_e('Phone Number ID', 'zignites-chat'); ?></label><span class="zignites-chat-help-icon">?<span class="zignites-chat-tooltip"><?php esc_html_e('Your WhatsApp Cloud API phone number ID.', 'zignites-chat'); ?></span></span></th>
            <td><input type="text" name="zignites_chat_cloud_phone_id" id="zignites_chat_cloud_phone_id" value="<?php echo esc_attr(get_option('zignites_chat_cloud_phone_id')); ?>" class="regular-text" /></td>
        </tr>
        <tr class="zignites-chat-cloud-fields" style="display:none;">
            <th scope="row"><label for="zignites_chat_cloud_from"><?php esc_html_e('From Number', 'zignites-chat'); ?></label><span class="zignites-chat-help-icon">?<span class="zignites-chat-tooltip"><?php esc_html_e('The WhatsApp-enabled number from your Cloud API account. Format: +1234567890', 'zignites-chat'); ?></span></span></th>
            <td><input type="text" name="zignites_chat_cloud_from" id="zignites_chat_cloud_from" value="<?php echo esc_attr(get_option('zignites_chat_cloud_from')); ?>" class="regular-text" placeholder="e.g. +14155238886" /></td>
        </tr>
        <tr class="zignites-chat-cloud-fields" style="display:none;">
            <th scope="row"><label for="zignites_chat_cloud_app_secret"><?php esc_html_e('Cloud App Secret', 'zignites-chat'); ?></label><span class="zignites-chat-help-icon">?<span class="zignites-chat-tooltip"><?php esc_html_e('Optional. Meta App Secret used to verify X-Hub-Signature-256 on incoming opt-out webhooks. Different from the access token.', 'zignites-chat'); ?></span></span></th>
            <td><input type="password" name="zignites_chat_cloud_app_secret" id="zignites_chat_cloud_app_secret" value="<?php echo esc_attr(get_option('zignites_chat_cloud_app_secret')); ?>" class="regular-text" autocomplete="off" />
                <p class="description"><?php esc_html_e('Leave blank to skip Meta webhook signature verification (token check still applies).', 'zignites-chat'); ?></p></td>
        </tr>
        <tr class="zignites-chat-cloud-fields" style="display:none;">
            <th scope="row"><label for="zignites_chat_meta_verify_token"><?php esc_html_e('Webhook Verify Token', 'zignites-chat'); ?></label><span class="zignites-chat-help-icon">?<span class="zignites-chat-tooltip"><?php esc_html_e('A token you choose. Paste the same value into Meta when subscribing the webhook so delivery/read receipts and opt-out replies reach this site.', 'zignites-chat'); ?></span></span></th>
            <td>
                <input type="text" name="zignites_chat_meta_verify_token" id="zignites_chat_meta_verify_token" value="<?php echo esc_attr(get_option('zignites_chat_meta_verify_token')); ?>" class="regular-text" autocomplete="off" />
                <p class="description">
                    <?php esc_html_e('Callback URL to register in Meta (Webhooks → WhatsApp Business Account):', 'zignites-chat'); ?>
                    <code><?php echo esc_html(rest_url('zignites-chat/v1/optout')); ?></code>
                </p>
            </td>
        </tr>
        <tr>
            <th scope="row"><?php esc_html_e('Test Connection', 'zignites-chat'); ?><span class="zignites-chat-help-icon">?<span class="zignites-chat-tooltip"><?php esc_html_e('Validate the credentials on this page against the provider API without sending a message. Uses the values currently in the form, even before you save.', 'zignites-chat'); ?></span></span></th>
            <td>
                <button type="button" class="button button-secondary" id="zignites-chat-test-connection"><?php esc_html_e('Test Connection', 'zignites-chat'); ?></button>
                <span id="zignites-chat-test-connection-status" style="margin-left:10px;font-weight:600;"></span>
                <p class="description"><?php esc_html_e('A successful check confirms the SID/token (Twilio) or token + Phone Number ID (Meta) are accepted. The "From" number is verified the first time you send a message.', 'zignites-chat'); ?></p>
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <div style="background: #fff3cd; color: #856404; border: 1px solid #ffe066; border-radius: 8px; padding: 18px 22px; margin-top: 18px; display: flex; align-items: center; gap: 16px;">
                    <span style="font-size: 2rem;">⚠️</span>
                    <div>
                        <label style="font-weight: 600; font-size: 1.1rem;" for="zignites_chat_test_mode_enabled"><?php esc_html_e('Test Mode', 'zignites-chat'); ?></label>
                        <input type="hidden" name="zignites_chat_test_mode_enabled" value="no" />
                        <input type="checkbox" name="zignites_chat_test_mode_enabled" value="yes" id="zignites_chat_test_mode_enabled" <?php checked(get_option('zignites_chat_test_mode_enabled'), 'yes'); ?> style="margin-left: 10px;" />
                        <div style="font-size: 0.98rem; margin-top: 4px;"><?php esc_html_e('Enable this for safe testing.', 'zignites-chat'); ?> <b><?php esc_html_e('Messages will be logged, not sent.', 'zignites-chat'); ?></b> <?php esc_html_e("Don't forget to turn it off in production!", 'zignites-chat'); ?></div>
                    </div>
                </div>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="zignites_chat_data_retention_days"><?php esc_html_e('Data Retention (days)', 'zignites-chat'); ?></label><span class="zignites-chat-help-icon">?<span class="zignites-chat-tooltip"><?php esc_html_e('Automatically delete analytics events older than this. Set to 0 to keep indefinitely.', 'zignites-chat'); ?></span></span></th>
            <td>
                <input type="number" min="0" name="zignites_chat_data_retention_days" id="zignites_chat_data_retention_days" value="<?php echo esc_attr(get_option('zignites_chat_data_retention_days', 0)); ?>" class="small-text" />
                <p class="description"><?php esc_html_e('Recommended: 30–180 days.', 'zignites-chat'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="zignites_chat_delete_data_on_uninstall"><?php esc_html_e('Delete Data on Uninstall', 'zignites-chat'); ?></label><span class="zignites-chat-help-icon">?<span class="zignites-chat-tooltip"><?php esc_html_e('If enabled, all plugin data will be removed when the plugin is deleted.', 'zignites-chat'); ?></span></span></th>
            <td>
                <select name="zignites_chat_delete_data_on_uninstall" id="zignites_chat_delete_data_on_uninstall">
                    <option value="no" <?php selected(get_option('zignites_chat_delete_data_on_uninstall', 'no'), 'no'); ?>><?php esc_html_e('No', 'zignites-chat'); ?></option>
                    <option value="yes" <?php selected(get_option('zignites_chat_delete_data_on_uninstall', 'no'), 'yes'); ?>><?php esc_html_e('Yes', 'zignites-chat'); ?></option>
                </select>
                <p class="description"><?php esc_html_e('Use for strict compliance requirements.', 'zignites-chat'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="zignites_chat_optout_keywords"><?php esc_html_e('Opt-out Keywords', 'zignites-chat'); ?></label><span class="zignites-chat-help-icon">?<span class="zignites-chat-tooltip"><?php esc_html_e('Incoming messages containing these words will add the number to the suppression list.', 'zignites-chat'); ?></span></span></th>
            <td>
                <input type="text" name="zignites_chat_optout_keywords" id="zignites_chat_optout_keywords" value="<?php echo esc_attr(get_option('zignites_chat_optout_keywords', 'stop, unsubscribe')); ?>" class="regular-text" />
                <p class="description"><?php esc_html_e('Comma-separated. Default: stop, unsubscribe.', 'zignites-chat'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="zignites_chat_optout_webhook_token"><?php esc_html_e('Opt-out Webhook Token', 'zignites-chat'); ?></label><span class="zignites-chat-help-icon">?<span class="zignites-chat-tooltip"><?php esc_html_e('Use this token to secure the opt-out webhook endpoint.', 'zignites-chat'); ?></span></span></th>
            <td>
                <input type="text" name="zignites_chat_optout_webhook_token" id="zignites_chat_optout_webhook_token" value="<?php echo esc_attr(get_option('zignites_chat_optout_webhook_token', '')); ?>" class="regular-text" />
                <p class="description"><?php esc_html_e('Webhook:', 'zignites-chat'); ?> <code><?php echo esc_html(rest_url('zignites-chat/v1/optout')); ?></code></p>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="zignites_chat_optout_list"><?php esc_html_e('Suppression List (opted-out numbers)', 'zignites-chat'); ?></label><span class="zignites-chat-help-icon">?<span class="zignites-chat-tooltip"><?php esc_html_e('Numbers in this list will never receive messages.', 'zignites-chat'); ?></span></span></th>
            <td>
                <?php $optout_list = zignites_chat_get_optout_list(); ?>
                <textarea name="zignites_chat_optout_list" id="zignites_chat_optout_list" rows="4" class="large-text"><?php echo esc_textarea(implode("\n", $optout_list)); ?></textarea>
                <p class="description"><?php esc_html_e('One phone number per line.', 'zignites-chat'); ?></p>
            </td>
        </tr>
    </table>
