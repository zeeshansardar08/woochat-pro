<?php
if (!defined('ABSPATH')) exit;
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- View partial: these variables are scoped to the render function that includes this file, not the global namespace.
?>
<table class="form-table">
        <tr>
            <th scope="row"><label for="zignites_chat_cart_recovery_enabled"><?php esc_html_e('Enable Cart Recovery', 'zignites-chat'); ?></label><span class="zignites-chat-help-icon">?<span class="zignites-chat-tooltip"><?php esc_html_e('Automatically remind users via WhatsApp if they abandon their cart.', 'zignites-chat'); ?></span></span></th>
            <td>
                <select name="zignites_chat_cart_recovery_enabled" id="zignites_chat_cart_recovery_enabled">
                    <option value="yes" <?php selected(get_option('zignites_chat_cart_recovery_enabled'), 'yes'); ?>><?php esc_html_e('Yes', 'zignites-chat'); ?></option>
                    <option value="no" <?php selected(get_option('zignites_chat_cart_recovery_enabled'), 'no'); ?>><?php esc_html_e('No', 'zignites-chat'); ?></option>
                </select>
                <p class="description"><?php esc_html_e('Automatically remind users via WhatsApp if they abandon the cart.', 'zignites-chat'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="zignites_chat_cart_recovery_delay"><?php esc_html_e('Reminder Delay (minutes)', 'zignites-chat'); ?></label><span class="zignites-chat-help-icon">?<span class="zignites-chat-tooltip"><?php esc_html_e('How many minutes after cart abandonment should the WhatsApp reminder be sent?', 'zignites-chat'); ?></span></span></th>
            <td><input type="number" min="1" name="zignites_chat_cart_recovery_delay" id="zignites_chat_cart_recovery_delay" value="<?php echo esc_attr(get_option('zignites_chat_cart_recovery_delay', 20)); ?>" class="small-text" />
            <p class="description"><?php esc_html_e('Default: 20 minutes', 'zignites-chat'); ?></p></td>
        </tr>
        <tr>
            <th scope="row"><label for="zignites_chat_cart_recovery_require_consent"><?php esc_html_e('Require Consent (checkout)', 'zignites-chat'); ?></label><span class="zignites-chat-help-icon">?<span class="zignites-chat-tooltip"><?php esc_html_e('Show a consent checkbox on checkout and only send reminders when checked.', 'zignites-chat'); ?></span></span></th>
            <td>
                <select name="zignites_chat_cart_recovery_require_consent" id="zignites_chat_cart_recovery_require_consent">
                    <option value="no" <?php selected(get_option('zignites_chat_cart_recovery_require_consent', 'no'), 'no'); ?>><?php esc_html_e('No', 'zignites-chat'); ?></option>
                    <option value="yes" <?php selected(get_option('zignites_chat_cart_recovery_require_consent', 'no'), 'yes'); ?>><?php esc_html_e('Yes', 'zignites-chat'); ?></option>
                </select>
                <p class="description"><?php esc_html_e('Recommended for compliance; captures user opt-in on checkout.', 'zignites-chat'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="zignites_chat_cart_recovery_message"><?php esc_html_e('Cart Recovery Message', 'zignites-chat'); ?></label><span class="zignites-chat-help-icon">?<span class="zignites-chat-tooltip"><?php esc_html_e('Customize the WhatsApp message sent for cart recovery. Use placeholders: {items}, {total}, {currency_symbol}, {cart_url}', 'zignites-chat'); ?></span></span></th>
            <td>
                <textarea name="zignites_chat_cart_recovery_message" id="zignites_chat_cart_recovery_message" rows="5" class="large-text"><?php echo esc_textarea(get_option('zignites_chat_cart_recovery_message', "👋 Hey! You left items in your cart:\n\n{items}\n\nTotal: {total} {currency_symbol}\nClick here to complete your order: {cart_url}")); ?></textarea>
                <p class="description"><?php
                    /* translators: do not translate placeholders inside curly braces */
                    esc_html_e('Use placeholders: {items}, {total}, {currency_symbol}, {cart_url}', 'zignites-chat');
                ?></p>
                <p style="margin-top:6px;">
                    <button type="button" class="button zignites-chat-browse-templates" data-target="zignites_chat_cart_recovery_message" data-kind="cart_recovery">
                        <span class="dashicons dashicons-book" style="vertical-align:middle;line-height:28px;"></span>
                        <?php esc_html_e('Browse template library', 'zignites-chat'); ?>
                    </button>
                </p>
            </td>
        </tr>
        <?php
        $cart_ab_enabled = get_option('zignites_chat_cart_recovery_ab_enabled', 'no');
        $cart_message_b  = get_option('zignites_chat_cart_recovery_message_b', '');
        ?>
        <tr class="zignites-chat-ab-row" data-ab-kind="cart_recovery">
            <th scope="row"><label for="zignites_chat_cart_recovery_ab_enabled"><?php esc_html_e('A/B test this message', 'zignites-chat'); ?></label><span class="zignites-chat-help-icon">?<span class="zignites-chat-tooltip"><?php esc_html_e('Send a 50/50 split between this template and Variant B. Each abandoned cart is assigned a variant deterministically by phone. View results on the Analytics tab.', 'zignites-chat'); ?></span></span></th>
            <td>
                <select name="zignites_chat_cart_recovery_ab_enabled" id="zignites_chat_cart_recovery_ab_enabled" class="zignites-chat-ab-toggle" data-ab-target="zignites-chat-ab-variant-b-cart-recovery">
                    <option value="no" <?php selected($cart_ab_enabled, 'no'); ?>><?php esc_html_e('No', 'zignites-chat'); ?></option>
                    <option value="yes" <?php selected($cart_ab_enabled, 'yes'); ?>><?php esc_html_e('Yes', 'zignites-chat'); ?></option>
                </select>
                <p class="description"><?php esc_html_e('Resends from the Recent Attempts table always use Variant A regardless of this setting.', 'zignites-chat'); ?></p>
            </td>
        </tr>
        <tr class="zignites-chat-ab-variant-b" id="zignites-chat-ab-variant-b-cart-recovery" style="<?php echo esc_attr( $cart_ab_enabled === 'yes' ? '' : 'display:none;' ); ?>">
            <th scope="row"><label for="zignites_chat_cart_recovery_message_b"><?php esc_html_e('Variant B', 'zignites-chat'); ?></label></th>
            <td>
                <textarea id="zignites_chat_cart_recovery_message_b" name="zignites_chat_cart_recovery_message_b" rows="5" class="large-text"><?php echo esc_textarea($cart_message_b); ?></textarea>
                <p class="description"><?php
                    /* translators: do not translate placeholders inside curly braces */
                    esc_html_e('Use placeholders: {items}, {total}, {currency_symbol}, {cart_url}', 'zignites-chat');
                ?></p>
                <p style="margin-top:6px;">
                    <button type="button" class="button zignites-chat-browse-templates" data-target="zignites_chat_cart_recovery_message_b" data-kind="cart_recovery">
                        <span class="dashicons dashicons-book" style="vertical-align:middle;line-height:28px;"></span>
                        <?php esc_html_e('Browse template library', 'zignites-chat'); ?>
                    </button>
                </p>
            </td>
        </tr>
    </table>
    <?php
    // After the cart recovery settings table, show the last 10 recovery attempts
    $attempts = zignites_chat_get_cart_recovery_attempts();
    if (!empty($attempts)) {
        echo '<h3 style="margin-top:32px;">' . esc_html__('Recent Cart Recovery Attempts', 'zignites-chat') . '</h3>';
        echo '<table class="widefat striped" style="margin-top:10px;">';
        echo '<thead><tr><th>' . esc_html__('Time', 'zignites-chat') . '</th><th>' . esc_html__('Phone', 'zignites-chat') . '</th><th>' . esc_html__('Items', 'zignites-chat') . '</th><th>' . esc_html__('Total', 'zignites-chat') . '</th><th>' . esc_html__('Message', 'zignites-chat') . '</th><th>' . esc_html__('Actions', 'zignites-chat') . '</th></tr></thead><tbody>';
        foreach ($attempts as $a) {
            echo '<tr>';
            echo '<td>' . esc_html($a['time']) . '</td>';
            echo '<td>' . esc_html($a['phone']) . '</td>';
            echo '<td><pre style="white-space:pre-line;font-size:0.97em;">' . esc_html(implode("\n", $a['items'])) . '</pre></td>';
            echo '<td>' . esc_html($a['total']) . '</td>';
            echo '<td><pre style="white-space:pre-line;font-size:0.97em;max-width:320px;overflow-x:auto;">' . esc_html($a['message']) . '</pre></td>';
            if (!empty($a['id'])) {
                echo '<td><button type="button" class="button zignites-chat-resend-cart" data-attempt="' . esc_attr($a['id']) . '">' . esc_html__('Resend', 'zignites-chat') . '</button></td>';
            } else {
                echo '<td><em>' . esc_html__('N/A', 'zignites-chat') . '</em></td>';
            }
            echo '</tr>';
        }
        echo '</tbody></table>';
    }
    ?>
