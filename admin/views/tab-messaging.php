<?php
if (!defined('ABSPATH')) exit;
?>
<table class="form-table">
        <tr>
            <th scope="row"><label for="wcwp_test_phone"><?php esc_html_e('Send Test Message', 'woochat'); ?></label><span class="wcwp-help-icon">?<span class="wcwp-tooltip"><?php esc_html_e('Send a one-off message to verify your setup. Test mode logs instead of sending.', 'woochat'); ?></span></span></th>
            <td>
                <input type="text" id="wcwp_test_phone" class="regular-text" placeholder="e.g. +14155238886" value="<?php echo esc_attr(get_option('wcwp_test_phone', '')); ?>" />
                <p class="description"><?php esc_html_e('Phone number to receive the test message.', 'woochat'); ?></p>
                <textarea id="wcwp_test_message" rows="4" class="large-text" placeholder="Type your test message here..."><?php echo esc_textarea(get_option('wcwp_test_message', 'Hello! This is a test message from WooChat.')); ?></textarea>
                <div style="margin-top:8px;display:flex;align-items:center;gap:10px;">
                    <button type="button" class="button button-primary" id="wcwp-send-test-message"><?php esc_html_e('Send Test Message', 'woochat'); ?></button>
                    <span id="wcwp-test-mode-badge" style="display:none;background:#fff3cd;color:#856404;border:1px solid #ffe066;border-radius:12px;padding:2px 8px;font-size:12px;font-weight:600;"><?php esc_html_e('Test Mode ON', 'woochat'); ?></span>
                    <span id="wcwp-test-status" style="font-weight:600;"></span>
                </div>
                <p id="wcwp-test-log-hint" class="description" style="<?php echo esc_attr( 'margin-top:6px;' . ( get_option('wcwp_test_mode_enabled', 'no') === 'yes' ? '' : 'display:none;' ) ); ?>"><?php esc_html_e('Test Mode is enabled. Messages are logged to wp-content/uploads/woochat/woochat.log.', 'woochat'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="wcwp_order_message_template"><?php esc_html_e('Order Message Template', 'woochat'); ?></label><span class="wcwp-help-icon">?<span class="wcwp-tooltip"><?php esc_html_e('Customize the WhatsApp message sent for new orders. Use placeholders: {name}, {order_id}, {total}, {currency_symbol}', 'woochat'); ?></span></span></th>
            <td>
                <textarea id="wcwp_order_message_template" name="wcwp_order_message_template" rows="5" class="large-text"><?php echo esc_textarea(get_option('wcwp_order_message_template', 'Hi {name}, thanks for your order #{order_id}! Total: {total} {currency_symbol}.')); ?></textarea>
                <p class="description"><?php
                    /* translators: do not translate placeholders inside curly braces */
                    esc_html_e('Use placeholders: {name}, {order_id}, {total}, {currency_symbol}', 'woochat');
                ?></p>
                <p style="margin-top:6px;">
                    <button type="button" class="button wcwp-browse-templates" data-target="wcwp_order_message_template" data-kind="order">
                        <span class="dashicons dashicons-book" style="vertical-align:middle;line-height:28px;"></span>
                        <?php esc_html_e('Browse template library', 'woochat'); ?>
                    </button>
                </p>
            </td>
        </tr>
        <?php
        $order_ab_enabled = get_option('wcwp_order_message_ab_enabled', 'no');
        $order_template_b = get_option('wcwp_order_message_template_b', '');
        $wcwp_msg_pro     = wcwp_is_pro_active();
        ?>
        <?php if (!$wcwp_msg_pro) : ?>
        <tr>
            <th scope="row"><?php esc_html_e('A/B test this message', 'woochat'); ?> <span class="wcwp-pro-tag"><?php esc_html_e('Pro', 'woochat'); ?></span></th>
            <td>
                <p class="description"><?php esc_html_e('A/B testing compares two message templates and tracks which converts better. Upgrade to WooChat Pro to split-test your messages.', 'woochat'); ?></p>
                <p><button type="button" class="button wcwp-open-upgrade-modal"><?php esc_html_e('Learn about Pro', 'woochat'); ?></button></p>
                <?php // Force A/B off on the free plan and preserve any template B saved while on Pro. ?>
                <input type="hidden" name="wcwp_order_message_ab_enabled" value="no" />
                <input type="hidden" name="wcwp_order_message_template_b" value="<?php echo esc_attr($order_template_b); ?>" />
            </td>
        </tr>
        <?php else : ?>
        <tr class="wcwp-ab-row" data-ab-kind="order">
            <th scope="row"><label for="wcwp_order_message_ab_enabled"><?php esc_html_e('A/B test this message', 'woochat'); ?></label><span class="wcwp-help-icon">?<span class="wcwp-tooltip"><?php esc_html_e('Send a 50/50 split between this template and Variant B. Each customer is assigned the same variant deterministically. View results on the Analytics tab.', 'woochat'); ?></span></span></th>
            <td>
                <select name="wcwp_order_message_ab_enabled" id="wcwp_order_message_ab_enabled" class="wcwp-ab-toggle" data-ab-target="wcwp-ab-variant-b-order">
                    <option value="no" <?php selected($order_ab_enabled, 'no'); ?>><?php esc_html_e('No', 'woochat'); ?></option>
                    <option value="yes" <?php selected($order_ab_enabled, 'yes'); ?>><?php esc_html_e('Yes', 'woochat'); ?></option>
                </select>
                <p class="description"><?php esc_html_e('When enabled and Variant B is non-empty, automatic order confirmations split 50/50 between A and B.', 'woochat'); ?></p>
            </td>
        </tr>
        <tr class="wcwp-ab-variant-b" id="wcwp-ab-variant-b-order" style="<?php echo esc_attr( $order_ab_enabled === 'yes' ? '' : 'display:none;' ); ?>">
            <th scope="row"><label for="wcwp_order_message_template_b"><?php esc_html_e('Variant B', 'woochat'); ?></label></th>
            <td>
                <textarea id="wcwp_order_message_template_b" name="wcwp_order_message_template_b" rows="5" class="large-text"><?php echo esc_textarea($order_template_b); ?></textarea>
                <p class="description"><?php
                    /* translators: do not translate placeholders inside curly braces */
                    esc_html_e('Use placeholders: {name}, {order_id}, {total}, {currency_symbol}', 'woochat');
                ?></p>
                <p style="margin-top:6px;">
                    <button type="button" class="button wcwp-browse-templates" data-target="wcwp_order_message_template_b" data-kind="order">
                        <span class="dashicons dashicons-book" style="vertical-align:middle;line-height:28px;"></span>
                        <?php esc_html_e('Browse template library', 'woochat'); ?>
                    </button>
                </p>
            </td>
        </tr>
        <?php endif; ?>
    </table>
