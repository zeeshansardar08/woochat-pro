<?php
if (!defined('ABSPATH')) exit;
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- View partial: variables are scoped to the render function that includes this file.

$zignites_chat_optin_enabled = get_option('zignites_chat_optin_enabled', 'no');
$zignites_chat_optin_label   = get_option('zignites_chat_optin_label', __('Yes, send me order updates and offers on WhatsApp.', 'zignites-chat'));
$zignites_chat_optin_checked = get_option('zignites_chat_optin_default_checked', 'no');
$zignites_chat_optin_req     = get_option('zignites_chat_optin_required', 'no');

$zignites_chat_optin_log     = zignites_chat_get_optin_log();
$zignites_chat_optin_count   = count($zignites_chat_optin_log);
?>
<h2><?php esc_html_e('WhatsApp Opt-in & Consent', 'zignites-chat'); ?></h2>
<p class="description">
    <?php esc_html_e('Capture explicit consent to message customers on WhatsApp, and optionally restrict marketing sends to those who opted in. Transactional messages (order confirmation, COD, status updates) are never affected.', 'zignites-chat'); ?>
</p>

<div class="zignites-chat-optin-stat" style="background:#edfaef; padding:12px 18px; border-radius:6px; display:inline-block; margin:12px 0;">
    <strong style="font-size:20px; display:block;"><?php echo esc_html(number_format_i18n($zignites_chat_optin_count)); ?></strong>
    <span class="description"><?php esc_html_e('Customers opted in', 'zignites-chat'); ?></span>
</div>

<table class="form-table">
    <tr>
        <th scope="row"><label for="zignites_chat_optin_enabled"><?php esc_html_e('Show opt-in at checkout', 'zignites-chat'); ?></label></th>
        <td>
            <select name="zignites_chat_optin_enabled" id="zignites_chat_optin_enabled">
                <option value="no" <?php selected($zignites_chat_optin_enabled, 'no'); ?>><?php esc_html_e('No', 'zignites-chat'); ?></option>
                <option value="yes" <?php selected($zignites_chat_optin_enabled, 'yes'); ?>><?php esc_html_e('Yes', 'zignites-chat'); ?></option>
            </select>
            <p class="description"><?php esc_html_e('Adds a checkbox to the classic checkout. (Block checkout support coming soon.)', 'zignites-chat'); ?></p>
        </td>
    </tr>
    <tr>
        <th scope="row"><label for="zignites_chat_optin_label"><?php esc_html_e('Checkbox label', 'zignites-chat'); ?></label></th>
        <td>
            <input type="text" name="zignites_chat_optin_label" id="zignites_chat_optin_label" value="<?php echo esc_attr($zignites_chat_optin_label); ?>" class="large-text" />
        </td>
    </tr>
    <tr>
        <th scope="row"><label for="zignites_chat_optin_default_checked"><?php esc_html_e('Pre-checked', 'zignites-chat'); ?></label></th>
        <td>
            <select name="zignites_chat_optin_default_checked" id="zignites_chat_optin_default_checked">
                <option value="no" <?php selected($zignites_chat_optin_checked, 'no'); ?>><?php esc_html_e('No (recommended)', 'zignites-chat'); ?></option>
                <option value="yes" <?php selected($zignites_chat_optin_checked, 'yes'); ?>><?php esc_html_e('Yes', 'zignites-chat'); ?></option>
            </select>
            <p class="description"><?php esc_html_e('Many privacy regimes require opt-in to be unchecked by default.', 'zignites-chat'); ?></p>
        </td>
    </tr>
    <tr>
        <th scope="row"><label for="zignites_chat_optin_required"><?php esc_html_e('Require consent for marketing', 'zignites-chat'); ?></label></th>
        <td>
            <select name="zignites_chat_optin_required" id="zignites_chat_optin_required">
                <option value="no" <?php selected($zignites_chat_optin_req, 'no'); ?>><?php esc_html_e('No', 'zignites-chat'); ?></option>
                <option value="yes" <?php selected($zignites_chat_optin_req, 'yes'); ?>><?php esc_html_e('Yes', 'zignites-chat'); ?></option>
            </select>
            <p class="description"><?php esc_html_e('When on, cart recovery, campaigns and follow-ups only message customers who opted in. Order, COD and status messages are unaffected.', 'zignites-chat'); ?></p>
        </td>
    </tr>
</table>
