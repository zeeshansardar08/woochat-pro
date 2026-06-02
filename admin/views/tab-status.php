<?php
if (!defined('ABSPATH')) exit;
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- View partial: variables are scoped to the render function that includes this file.

$zignites_chat_status_enabled  = get_option('zignites_chat_status_notify_enabled', 'no');
$zignites_chat_status_config   = zignites_chat_status_get_config();
$zignites_chat_status_list     = zignites_chat_status_eligible_statuses();
?>
<h2><?php esc_html_e('Order Status & Tracking Notifications', 'zignites-chat'); ?></h2>
<p class="description">
    <?php esc_html_e('Send a WhatsApp message whenever an order moves into a status you enable below. Leave a status disabled to send nothing. Note: Processing and Completed may already be covered by the order confirmation on the Messaging tab — enabling them here too will send a second message.', 'zignites-chat'); ?>
</p>

<table class="form-table">
    <tr>
        <th scope="row"><label for="zignites_chat_status_notify_enabled"><?php esc_html_e('Enable status notifications', 'zignites-chat'); ?></label></th>
        <td>
            <select name="zignites_chat_status_notify_enabled" id="zignites_chat_status_notify_enabled">
                <option value="no" <?php selected($zignites_chat_status_enabled, 'no'); ?>><?php esc_html_e('No', 'zignites-chat'); ?></option>
                <option value="yes" <?php selected($zignites_chat_status_enabled, 'yes'); ?>><?php esc_html_e('Yes', 'zignites-chat'); ?></option>
            </select>
        </td>
    </tr>
</table>

<p class="description">
    <?php esc_html_e('Placeholders: {name}, {order_id}, {total}, {status}, {currency_symbol}, {tracking_number}, {tracking_url}, {carrier}. Tracking values come from WooCommerce Shipment Tracking / Advanced Shipment Tracking when present.', 'zignites-chat'); ?>
</p>

<table class="widefat striped" style="max-width:900px;">
    <thead>
        <tr>
            <th style="width:60px;"><?php esc_html_e('Send', 'zignites-chat'); ?></th>
            <th style="width:180px;"><?php esc_html_e('Status', 'zignites-chat'); ?></th>
            <th><?php esc_html_e('Message template', 'zignites-chat'); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($zignites_chat_status_list as $zignites_chat_slug => $zignites_chat_label) :
            $zignites_chat_entry    = isset($zignites_chat_status_config[$zignites_chat_slug]) && is_array($zignites_chat_status_config[$zignites_chat_slug]) ? $zignites_chat_status_config[$zignites_chat_slug] : [];
            $zignites_chat_on       = isset($zignites_chat_entry['enabled']) && $zignites_chat_entry['enabled'] === 'yes';
            $zignites_chat_tmpl     = isset($zignites_chat_entry['template']) ? $zignites_chat_entry['template'] : '';
            ?>
            <tr>
                <td style="text-align:center;">
                    <input type="checkbox" name="zignites_chat_status_notifications[<?php echo esc_attr($zignites_chat_slug); ?>][enabled]" value="yes" <?php checked($zignites_chat_on); ?> />
                </td>
                <td>
                    <strong><?php echo esc_html($zignites_chat_label); ?></strong><br />
                    <code><?php echo esc_html($zignites_chat_slug); ?></code>
                </td>
                <td>
                    <textarea name="zignites_chat_status_notifications[<?php echo esc_attr($zignites_chat_slug); ?>][template]" rows="2" class="large-text" placeholder="<?php esc_attr_e('e.g. Hi {name}, your order #{order_id} is now {status}. Track it here: {tracking_url}', 'zignites-chat'); ?>"><?php echo esc_textarea($zignites_chat_tmpl); ?></textarea>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
