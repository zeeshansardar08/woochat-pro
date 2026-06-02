<?php
if (!defined('ABSPATH')) exit;
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- View partial: variables are scoped to the render function that includes this file.

$zignites_chat_cod_enabled  = get_option('zignites_chat_cod_enabled', 'no');
$zignites_chat_cod_gateways = zignites_chat_cod_gateways();
$zignites_chat_cod_template = get_option(
    'zignites_chat_cod_message_template',
    'Hi {name}, please confirm your cash-on-delivery order #{order_id} for {total} {currency_symbol}. Reply CONFIRM to proceed or CANCEL to cancel.'
);
$zignites_chat_cod_confirm  = get_option('zignites_chat_cod_confirm_keywords', 'confirm,yes,1');
$zignites_chat_cod_cancel   = get_option('zignites_chat_cod_cancel_keywords', 'cancel,no,2');
$zignites_chat_cod_on_confirm = get_option('zignites_chat_cod_on_confirm_status', 'processing');
$zignites_chat_cod_on_cancel  = get_option('zignites_chat_cod_on_cancel_status', 'cancelled');

// Available payment gateways for the COD-gateway picker.
$zignites_chat_cod_all_gateways = [];
if (function_exists('WC') && WC()->payment_gateways()) {
    foreach (WC()->payment_gateways()->payment_gateways() as $zignites_chat_gw) {
        $zignites_chat_cod_all_gateways[$zignites_chat_gw->id] = $zignites_chat_gw->get_title() ?: $zignites_chat_gw->id;
    }
}
// WC order statuses for the on-confirm / on-cancel pickers.
$zignites_chat_cod_statuses = function_exists('wc_get_order_statuses') ? wc_get_order_statuses() : [];
?>
<h2><?php esc_html_e('COD Order Confirmation', 'zignites-chat'); ?></h2>
<p class="description">
    <?php esc_html_e('Automatically ask customers to confirm a new cash-on-delivery order over WhatsApp. The confirmation goes out as your approved WhatsApp template (map it under WhatsApp Templates → COD order confirmation, ideally with Confirm / Cancel quick-reply buttons). The customer’s reply updates the order status.', 'zignites-chat'); ?>
</p>

<table class="form-table">
    <tr>
        <th scope="row"><label for="zignites_chat_cod_enabled"><?php esc_html_e('Enable COD confirmation', 'zignites-chat'); ?></label></th>
        <td>
            <select name="zignites_chat_cod_enabled" id="zignites_chat_cod_enabled">
                <option value="no" <?php selected($zignites_chat_cod_enabled, 'no'); ?>><?php esc_html_e('No', 'zignites-chat'); ?></option>
                <option value="yes" <?php selected($zignites_chat_cod_enabled, 'yes'); ?>><?php esc_html_e('Yes', 'zignites-chat'); ?></option>
            </select>
        </td>
    </tr>
    <tr>
        <th scope="row"><?php esc_html_e('COD payment methods', 'zignites-chat'); ?></th>
        <td>
            <?php if (!empty($zignites_chat_cod_all_gateways)) : ?>
                <?php foreach ($zignites_chat_cod_all_gateways as $zignites_chat_gw_id => $zignites_chat_gw_title) : ?>
                    <label style="display:block; margin-bottom:4px;">
                        <input type="checkbox" name="zignites_chat_cod_gateways[]"
                               value="<?php echo esc_attr($zignites_chat_gw_id); ?>"
                               <?php checked(in_array($zignites_chat_gw_id, $zignites_chat_cod_gateways, true)); ?> />
                        <?php echo esc_html($zignites_chat_gw_title); ?>
                        <code><?php echo esc_html($zignites_chat_gw_id); ?></code>
                    </label>
                <?php endforeach; ?>
                <p class="description"><?php esc_html_e('Select which payment methods are treated as cash-on-delivery.', 'zignites-chat'); ?></p>
            <?php else : ?>
                <input type="text" name="zignites_chat_cod_gateways[]" value="<?php echo esc_attr(implode(',', $zignites_chat_cod_gateways)); ?>" class="regular-text" />
                <p class="description"><?php esc_html_e('Enter the COD gateway id (default: cod).', 'zignites-chat'); ?></p>
            <?php endif; ?>
        </td>
    </tr>
    <tr>
        <th scope="row"><label for="zignites_chat_cod_message_template"><?php esc_html_e('Fallback message', 'zignites-chat'); ?></label></th>
        <td>
            <textarea name="zignites_chat_cod_message_template" id="zignites_chat_cod_message_template" rows="3" class="large-text"><?php echo esc_textarea($zignites_chat_cod_template); ?></textarea>
            <p class="description"><?php esc_html_e('Preview/fallback text. The actual business-initiated send uses your approved COD template. Placeholders: {name}, {order_id}, {total}, {currency_symbol}.', 'zignites-chat'); ?></p>
        </td>
    </tr>
    <tr>
        <th scope="row"><label for="zignites_chat_cod_confirm_keywords"><?php esc_html_e('Confirm keywords', 'zignites-chat'); ?></label></th>
        <td>
            <input type="text" name="zignites_chat_cod_confirm_keywords" id="zignites_chat_cod_confirm_keywords" value="<?php echo esc_attr($zignites_chat_cod_confirm); ?>" class="regular-text" />
            <p class="description"><?php esc_html_e('Comma-separated. Matched against the reply / button title (e.g. confirm,yes,1).', 'zignites-chat'); ?></p>
        </td>
    </tr>
    <tr>
        <th scope="row"><label for="zignites_chat_cod_cancel_keywords"><?php esc_html_e('Cancel keywords', 'zignites-chat'); ?></label></th>
        <td>
            <input type="text" name="zignites_chat_cod_cancel_keywords" id="zignites_chat_cod_cancel_keywords" value="<?php echo esc_attr($zignites_chat_cod_cancel); ?>" class="regular-text" />
            <p class="description"><?php esc_html_e('Comma-separated (e.g. cancel,no,2).', 'zignites-chat'); ?></p>
        </td>
    </tr>
    <tr>
        <th scope="row"><label for="zignites_chat_cod_on_confirm_status"><?php esc_html_e('Status on confirm', 'zignites-chat'); ?></label></th>
        <td>
            <?php if (!empty($zignites_chat_cod_statuses)) : ?>
                <select name="zignites_chat_cod_on_confirm_status" id="zignites_chat_cod_on_confirm_status">
                    <?php foreach ($zignites_chat_cod_statuses as $zignites_chat_st_key => $zignites_chat_st_label) :
                        $zignites_chat_st_slug = preg_replace('/^wc-/', '', $zignites_chat_st_key); ?>
                        <option value="<?php echo esc_attr($zignites_chat_st_slug); ?>" <?php selected($zignites_chat_cod_on_confirm, $zignites_chat_st_slug); ?>><?php echo esc_html($zignites_chat_st_label); ?></option>
                    <?php endforeach; ?>
                </select>
            <?php else : ?>
                <input type="text" name="zignites_chat_cod_on_confirm_status" value="<?php echo esc_attr($zignites_chat_cod_on_confirm); ?>" class="regular-text" />
            <?php endif; ?>
        </td>
    </tr>
    <tr>
        <th scope="row"><label for="zignites_chat_cod_on_cancel_status"><?php esc_html_e('Status on cancel', 'zignites-chat'); ?></label></th>
        <td>
            <?php if (!empty($zignites_chat_cod_statuses)) : ?>
                <select name="zignites_chat_cod_on_cancel_status" id="zignites_chat_cod_on_cancel_status">
                    <?php foreach ($zignites_chat_cod_statuses as $zignites_chat_st_key => $zignites_chat_st_label) :
                        $zignites_chat_st_slug = preg_replace('/^wc-/', '', $zignites_chat_st_key); ?>
                        <option value="<?php echo esc_attr($zignites_chat_st_slug); ?>" <?php selected($zignites_chat_cod_on_cancel, $zignites_chat_st_slug); ?>><?php echo esc_html($zignites_chat_st_label); ?></option>
                    <?php endforeach; ?>
                </select>
            <?php else : ?>
                <input type="text" name="zignites_chat_cod_on_cancel_status" value="<?php echo esc_attr($zignites_chat_cod_on_cancel); ?>" class="regular-text" />
            <?php endif; ?>
            <p class="description"><?php esc_html_e('Order status applied automatically when the customer replies CONFIRM or CANCEL on WhatsApp.', 'zignites-chat'); ?></p>
        </td>
    </tr>
</table>
