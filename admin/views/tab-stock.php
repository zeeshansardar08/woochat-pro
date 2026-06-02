<?php
if (!defined('ABSPATH')) exit;
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- View partial: variables are scoped to the render function that includes this file.

$zignites_chat_stock_on      = get_option('zignites_chat_stock_alerts_enabled', 'no');
$zignites_chat_stock_msg     = get_option('zignites_chat_stock_alert_message', __('Good news! {product} is back in stock. Grab it here: {product_url}', 'zignites-chat'));
$zignites_chat_stock_heading = get_option('zignites_chat_stock_form_heading', __('Notify me on WhatsApp when it’s back', 'zignites-chat'));
?>
<h2><?php esc_html_e('Back-in-Stock Alerts', 'zignites-chat'); ?></h2>
<p class="description">
    <?php esc_html_e('Shows a “notify me on WhatsApp” form on out-of-stock product pages. When the product is restocked, subscribers get a WhatsApp message automatically (throttled, opt-out-aware, and respecting quiet hours).', 'zignites-chat'); ?>
</p>

<table class="form-table">
    <tr>
        <th scope="row"><label for="zignites_chat_stock_alerts_enabled"><?php esc_html_e('Enable back-in-stock alerts', 'zignites-chat'); ?></label></th>
        <td>
            <select name="zignites_chat_stock_alerts_enabled" id="zignites_chat_stock_alerts_enabled">
                <option value="no" <?php selected($zignites_chat_stock_on, 'no'); ?>><?php esc_html_e('No', 'zignites-chat'); ?></option>
                <option value="yes" <?php selected($zignites_chat_stock_on, 'yes'); ?>><?php esc_html_e('Yes', 'zignites-chat'); ?></option>
            </select>
        </td>
    </tr>
    <tr>
        <th scope="row"><label for="zignites_chat_stock_form_heading"><?php esc_html_e('Form heading', 'zignites-chat'); ?></label></th>
        <td><input type="text" name="zignites_chat_stock_form_heading" id="zignites_chat_stock_form_heading" value="<?php echo esc_attr($zignites_chat_stock_heading); ?>" class="large-text" /></td>
    </tr>
    <tr>
        <th scope="row"><label for="zignites_chat_stock_alert_message"><?php esc_html_e('Alert message', 'zignites-chat'); ?></label></th>
        <td>
            <textarea name="zignites_chat_stock_alert_message" id="zignites_chat_stock_alert_message" rows="3" class="large-text"><?php echo esc_textarea($zignites_chat_stock_msg); ?></textarea>
            <p class="description"><?php esc_html_e('Placeholders: {product}, {product_url}, {site}.', 'zignites-chat'); ?></p>
        </td>
    </tr>
</table>
