<?php
if (!defined('ABSPATH')) exit;
?>
<div id="wcwp-tab-content-cart-recovery" class="wcwp-tab-content" style="display:none;">
    <table class="form-table">
        <tr>
            <th scope="row"><label for="wcwp_cart_recovery_enabled"><?php esc_html_e('Enable Cart Recovery', 'woochat-pro'); ?></label><span class="wcwp-help-icon">?<span class="wcwp-tooltip"><?php esc_html_e('Automatically remind users via WhatsApp if they abandon their cart.', 'woochat-pro'); ?></span></span></th>
            <td>
                <select name="wcwp_cart_recovery_enabled" id="wcwp_cart_recovery_enabled">
                    <option value="yes" <?php selected(get_option('wcwp_cart_recovery_enabled'), 'yes'); ?>><?php esc_html_e('Yes', 'woochat-pro'); ?></option>
                    <option value="no" <?php selected(get_option('wcwp_cart_recovery_enabled'), 'no'); ?>><?php esc_html_e('No', 'woochat-pro'); ?></option>
                </select>
                <p class="description"><?php esc_html_e('Automatically remind users via WhatsApp if they abandon the cart.', 'woochat-pro'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="wcwp_cart_recovery_delay"><?php esc_html_e('Reminder Delay (minutes)', 'woochat-pro'); ?></label><span class="wcwp-help-icon">?<span class="wcwp-tooltip"><?php esc_html_e('How many minutes after cart abandonment should the WhatsApp reminder be sent?', 'woochat-pro'); ?></span></span></th>
            <td><input type="number" min="1" name="wcwp_cart_recovery_delay" id="wcwp_cart_recovery_delay" value="<?php echo esc_attr(get_option('wcwp_cart_recovery_delay', 20)); ?>" class="small-text" />
            <p class="description"><?php esc_html_e('Default: 20 minutes', 'woochat-pro'); ?></p></td>
        </tr>
        <tr>
            <th scope="row"><label for="wcwp_cart_recovery_require_consent"><?php esc_html_e('Require Consent (checkout)', 'woochat-pro'); ?></label><span class="wcwp-help-icon">?<span class="wcwp-tooltip"><?php esc_html_e('Show a consent checkbox on checkout and only send reminders when checked.', 'woochat-pro'); ?></span></span></th>
            <td>
                <select name="wcwp_cart_recovery_require_consent" id="wcwp_cart_recovery_require_consent">
                    <option value="no" <?php selected(get_option('wcwp_cart_recovery_require_consent', 'no'), 'no'); ?>><?php esc_html_e('No', 'woochat-pro'); ?></option>
                    <option value="yes" <?php selected(get_option('wcwp_cart_recovery_require_consent', 'no'), 'yes'); ?>><?php esc_html_e('Yes', 'woochat-pro'); ?></option>
                </select>
                <p class="description"><?php esc_html_e('Recommended for compliance; captures user opt-in on checkout.', 'woochat-pro'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="wcwp_cart_recovery_message"><?php esc_html_e('Cart Recovery Message', 'woochat-pro'); ?></label><span class="wcwp-help-icon">?<span class="wcwp-tooltip"><?php esc_html_e('Customize the WhatsApp message sent for cart recovery. Use placeholders: {items}, {total}, {currency_symbol}, {cart_url}', 'woochat-pro'); ?></span></span></th>
            <td>
                <textarea name="wcwp_cart_recovery_message" id="wcwp_cart_recovery_message" rows="5" class="large-text"><?php echo esc_textarea(get_option('wcwp_cart_recovery_message', "👋 Hey! You left items in your cart:\n\n{items}\n\nTotal: {total} {currency_symbol}\nClick here to complete your order: {cart_url}")); ?></textarea>
                <p class="description"><?php
                    /* translators: do not translate placeholders inside curly braces */
                    esc_html_e('Use placeholders: {items}, {total}, {currency_symbol}, {cart_url}', 'woochat-pro');
                ?></p>
                <p style="margin-top:6px;">
                    <button type="button" class="button wcwp-browse-templates" data-target="wcwp_cart_recovery_message" data-kind="cart_recovery">
                        <span class="dashicons dashicons-book" style="vertical-align:middle;line-height:28px;"></span>
                        <?php esc_html_e('Browse template library', 'woochat-pro'); ?>
                    </button>
                </p>
            </td>
        </tr>
        <?php
        $cart_ab_enabled = get_option('wcwp_cart_recovery_ab_enabled', 'no');
        $cart_message_b  = get_option('wcwp_cart_recovery_message_b', '');
        ?>
        <tr class="wcwp-ab-row" data-ab-kind="cart_recovery">
            <th scope="row"><label for="wcwp_cart_recovery_ab_enabled"><?php esc_html_e('A/B test this message', 'woochat-pro'); ?></label><span class="wcwp-help-icon">?<span class="wcwp-tooltip"><?php esc_html_e('Send a 50/50 split between this template and Variant B. Each abandoned cart is assigned a variant deterministically by phone. View results on the Analytics tab.', 'woochat-pro'); ?></span></span></th>
            <td>
                <select name="wcwp_cart_recovery_ab_enabled" id="wcwp_cart_recovery_ab_enabled" class="wcwp-ab-toggle" data-ab-target="wcwp-ab-variant-b-cart-recovery">
                    <option value="no" <?php selected($cart_ab_enabled, 'no'); ?>><?php esc_html_e('No', 'woochat-pro'); ?></option>
                    <option value="yes" <?php selected($cart_ab_enabled, 'yes'); ?>><?php esc_html_e('Yes', 'woochat-pro'); ?></option>
                </select>
                <p class="description"><?php esc_html_e('Resends from the Recent Attempts table always use Variant A regardless of this setting.', 'woochat-pro'); ?></p>
            </td>
        </tr>
        <tr class="wcwp-ab-variant-b" id="wcwp-ab-variant-b-cart-recovery" style="<?php echo $cart_ab_enabled === 'yes' ? '' : 'display:none;'; ?>">
            <th scope="row"><label for="wcwp_cart_recovery_message_b"><?php esc_html_e('Variant B', 'woochat-pro'); ?></label></th>
            <td>
                <textarea id="wcwp_cart_recovery_message_b" name="wcwp_cart_recovery_message_b" rows="5" class="large-text"><?php echo esc_textarea($cart_message_b); ?></textarea>
                <p class="description"><?php
                    /* translators: do not translate placeholders inside curly braces */
                    esc_html_e('Use placeholders: {items}, {total}, {currency_symbol}, {cart_url}', 'woochat-pro');
                ?></p>
                <p style="margin-top:6px;">
                    <button type="button" class="button wcwp-browse-templates" data-target="wcwp_cart_recovery_message_b" data-kind="cart_recovery">
                        <span class="dashicons dashicons-book" style="vertical-align:middle;line-height:28px;"></span>
                        <?php esc_html_e('Browse template library', 'woochat-pro'); ?>
                    </button>
                </p>
            </td>
        </tr>
    </table>
    <?php
    // After the cart recovery settings table, show the last 10 recovery attempts
    $attempts = wcwp_get_cart_recovery_attempts();
    if (!empty($attempts)) {
        echo '<h3 style="margin-top:32px;">' . esc_html__('Recent Cart Recovery Attempts', 'woochat-pro') . '</h3>';
        echo '<table class="widefat striped" style="margin-top:10px;">';
        echo '<thead><tr><th>' . esc_html__('Time', 'woochat-pro') . '</th><th>' . esc_html__('Phone', 'woochat-pro') . '</th><th>' . esc_html__('Items', 'woochat-pro') . '</th><th>' . esc_html__('Total', 'woochat-pro') . '</th><th>' . esc_html__('Message', 'woochat-pro') . '</th><th>' . esc_html__('Actions', 'woochat-pro') . '</th></tr></thead><tbody>';
        foreach ($attempts as $a) {
            echo '<tr>';
            echo '<td>' . esc_html($a['time']) . '</td>';
            echo '<td>' . esc_html($a['phone']) . '</td>';
            echo '<td><pre style="white-space:pre-line;font-size:0.97em;">' . esc_html(implode("\n", $a['items'])) . '</pre></td>';
            echo '<td>' . esc_html($a['total']) . '</td>';
            echo '<td><pre style="white-space:pre-line;font-size:0.97em;max-width:320px;overflow-x:auto;">' . esc_html($a['message']) . '</pre></td>';
            if (!empty($a['id'])) {
                echo '<td><button type="button" class="button wcwp-resend-cart" data-attempt="' . esc_attr($a['id']) . '">' . esc_html__('Resend', 'woochat-pro') . '</button></td>';
            } else {
                echo '<td><em>' . esc_html__('N/A', 'woochat-pro') . '</em></td>';
            }
            echo '</tr>';
        }
        echo '</tbody></table>';
    }
    ?>
</div>
