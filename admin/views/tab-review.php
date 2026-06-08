<?php
if (!defined('ABSPATH')) exit;
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- View partial: variables are scoped to the render function that includes this file.

$zignites_chat_review_on      = get_option('zignites_chat_review_request_enabled', 'no');
$zignites_chat_review_trigger = get_option('zignites_chat_review_trigger_status', 'completed');
$zignites_chat_review_days    = (int) get_option('zignites_chat_review_delay_days', 3);
$zignites_chat_review_url     = get_option('zignites_chat_review_url', '');
$zignites_chat_review_msg     = get_option('zignites_chat_review_message', __('Hi {name}, thanks for your order #{order_id}! How did we do? We’d love a quick review: {review_url}', 'zignites-chat'));

$zignites_chat_review_statuses = function_exists('wc_get_order_statuses') ? wc_get_order_statuses() : ['wc-completed' => __('Completed', 'zignites-chat')];
?>
<h2><?php esc_html_e('Review / NPS Request', 'zignites-chat'); ?></h2>
<p class="description">
    <?php esc_html_e('Automatically ask happy customers for a review a few days after their order is delivered. Sent over WhatsApp, respecting opt-outs, consent and quiet hours.', 'zignites-chat'); ?>
</p>

<table class="form-table">
    <tr>
        <th scope="row"><label for="zignites_chat_review_request_enabled"><?php esc_html_e('Enable review requests', 'zignites-chat'); ?></label></th>
        <td>
            <select name="zignites_chat_review_request_enabled" id="zignites_chat_review_request_enabled">
                <option value="no" <?php selected($zignites_chat_review_on, 'no'); ?>><?php esc_html_e('No', 'zignites-chat'); ?></option>
                <option value="yes" <?php selected($zignites_chat_review_on, 'yes'); ?>><?php esc_html_e('Yes', 'zignites-chat'); ?></option>
            </select>
        </td>
    </tr>
    <tr>
        <th scope="row"><label for="zignites_chat_review_trigger_status"><?php esc_html_e('Send when order is', 'zignites-chat'); ?></label></th>
        <td>
            <select name="zignites_chat_review_trigger_status" id="zignites_chat_review_trigger_status">
                <?php foreach ($zignites_chat_review_statuses as $zignites_chat_review_status_key => $zignites_chat_review_status_label) :
                    $zignites_chat_review_slug = zignites_chat_review_normalize_status($zignites_chat_review_status_key); ?>
                    <option value="<?php echo esc_attr($zignites_chat_review_slug); ?>" <?php selected($zignites_chat_review_trigger, $zignites_chat_review_slug); ?>><?php echo esc_html($zignites_chat_review_status_label); ?></option>
                <?php endforeach; ?>
            </select>
            <p class="description"><?php esc_html_e('The status that marks an order as delivered. Defaults to Completed; pick a custom “Delivered” status if your shipping plugin sets one.', 'zignites-chat'); ?></p>
        </td>
    </tr>
    <tr>
        <th scope="row"><label for="zignites_chat_review_delay_days"><?php esc_html_e('Delay (days)', 'zignites-chat'); ?></label></th>
        <td>
            <input type="number" min="0" step="1" name="zignites_chat_review_delay_days" id="zignites_chat_review_delay_days" value="<?php echo esc_attr((string) $zignites_chat_review_days); ?>" class="small-text" />
            <p class="description"><?php esc_html_e('How long to wait after delivery before asking. 0 sends on the next scheduled run.', 'zignites-chat'); ?></p>
        </td>
    </tr>
    <tr>
        <th scope="row"><label for="zignites_chat_review_url"><?php esc_html_e('Review / NPS link', 'zignites-chat'); ?></label></th>
        <td>
            <input type="url" name="zignites_chat_review_url" id="zignites_chat_review_url" value="<?php echo esc_attr($zignites_chat_review_url); ?>" class="large-text" placeholder="https://" />
            <p class="description"><?php esc_html_e('Where {review_url} points — a Google review link, a product review page, or your NPS survey.', 'zignites-chat'); ?></p>
        </td>
    </tr>
    <tr>
        <th scope="row"><label for="zignites_chat_review_message"><?php esc_html_e('Message', 'zignites-chat'); ?></label></th>
        <td>
            <textarea name="zignites_chat_review_message" id="zignites_chat_review_message" rows="3" class="large-text"><?php echo esc_textarea($zignites_chat_review_msg); ?></textarea>
            <p class="description"><?php esc_html_e('Placeholders: {name}, {order_id}, {product}, {review_url}, {site}.', 'zignites-chat'); ?></p>
        </td>
    </tr>
</table>
