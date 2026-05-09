<?php
if (!defined('ABSPATH')) exit;
?>
<div id="wcwp-tab-content-messaging" class="wcwp-tab-content" style="display:none;">
    <table class="form-table">
        <tr>
            <th scope="row"><label for="wcwp_test_phone"><?php esc_html_e('Send Test Message', 'woochat-pro'); ?></label><span class="wcwp-help-icon">?<span class="wcwp-tooltip"><?php esc_html_e('Send a one-off message to verify your setup. Test mode logs instead of sending.', 'woochat-pro'); ?></span></span></th>
            <td>
                <input type="text" id="wcwp_test_phone" class="regular-text" placeholder="e.g. +14155238886" value="<?php echo esc_attr(get_option('wcwp_test_phone', '')); ?>" />
                <p class="description"><?php esc_html_e('Phone number to receive the test message.', 'woochat-pro'); ?></p>
                <textarea id="wcwp_test_message" rows="4" class="large-text" placeholder="Type your test message here..."><?php echo esc_textarea(get_option('wcwp_test_message', 'Hello! This is a test message from WooChat Pro.')); ?></textarea>
                <div style="margin-top:8px;display:flex;align-items:center;gap:10px;">
                    <button type="button" class="button button-primary" id="wcwp-send-test-message"><?php esc_html_e('Send Test Message', 'woochat-pro'); ?></button>
                    <span id="wcwp-test-mode-badge" style="display:none;background:#fff3cd;color:#856404;border:1px solid #ffe066;border-radius:12px;padding:2px 8px;font-size:12px;font-weight:600;"><?php esc_html_e('Test Mode ON', 'woochat-pro'); ?></span>
                    <span id="wcwp-test-status" style="font-weight:600;"></span>
                </div>
                <p id="wcwp-test-log-hint" class="description" style="margin-top:6px;<?php echo get_option('wcwp_test_mode_enabled', 'no') === 'yes' ? '' : 'display:none;'; ?>"><?php esc_html_e('Test Mode is enabled. Messages are logged to wp-content/uploads/woochat-pro/woochat-pro.log.', 'woochat-pro'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="wcwp_order_message_template"><?php esc_html_e('Order Message Template', 'woochat-pro'); ?></label><span class="wcwp-help-icon">?<span class="wcwp-tooltip"><?php esc_html_e('Customize the WhatsApp message sent for new orders. Use placeholders: {name}, {order_id}, {total}, {currency_symbol}', 'woochat-pro'); ?></span></span></th>
            <td>
                <textarea id="wcwp_order_message_template" name="wcwp_order_message_template" rows="5" class="large-text"><?php echo esc_textarea(get_option('wcwp_order_message_template', 'Hi {name}, thanks for your order #{order_id}! Total: {total} {currency_symbol}.')); ?></textarea>
                <p class="description"><?php
                    /* translators: do not translate placeholders inside curly braces */
                    esc_html_e('Use placeholders: {name}, {order_id}, {total}, {currency_symbol}', 'woochat-pro');
                ?></p>
                <p style="margin-top:6px;">
                    <button type="button" class="button wcwp-browse-templates" data-target="wcwp_order_message_template" data-kind="order">
                        <span class="dashicons dashicons-book" style="vertical-align:middle;line-height:28px;"></span>
                        <?php esc_html_e('Browse template library', 'woochat-pro'); ?>
                    </button>
                </p>
            </td>
        </tr>
    </table>
</div>
