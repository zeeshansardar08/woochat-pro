<?php
if (!defined('ABSPATH')) exit;

// Add menu to WP admin
add_action('admin_menu', 'wcwp_register_settings_page');
function wcwp_register_settings_page() {
    add_menu_page(
        'WooChat Settings',
        'WooChat',
        'manage_options',
        'wcwp-settings',
        'wcwp_render_settings_page',
        'dashicons-format-chat',
        66
    );
}

// Register settings
add_action('admin_init', 'wcwp_register_settings');
function wcwp_register_settings() {
    register_setting('wcwp_settings_group', 'wcwp_twilio_sid');
    register_setting('wcwp_settings_group', 'wcwp_twilio_auth_token');
    register_setting('wcwp_settings_group', 'wcwp_twilio_from');
    register_setting('wcwp_settings_group', 'wcwp_order_message_template');
    register_setting('wcwp_settings_group', 'wcwp_cart_recovery_enabled');
    register_setting('wcwp_settings_group', 'wcwp_chatbot_enabled');
    register_setting('wcwp_settings_group', 'wcwp_faq_pairs');
    register_setting('wcwp_settings_group', 'wcwp_license_key');
}

function wcwp_render_settings_page() {
    ?>
    <div class="wrap">
        <h1>WooChat – WhatsApp Settings</h1>
        <form method="post" action="options.php">
            <?php settings_fields('wcwp_settings_group'); ?>
            <?php do_settings_sections('wcwp_settings_group'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="wcwp_twilio_sid">Twilio SID</label></th>
                    <td><input type="text" name="wcwp_twilio_sid" id="wcwp_twilio_sid" value="<?php echo esc_attr(get_option('wcwp_twilio_sid')); ?>" class="regular-text" /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="wcwp_twilio_auth_token">Twilio Auth Token</label></th>
                    <td><input type="text" name="wcwp_twilio_auth_token" id="wcwp_twilio_auth_token" value="<?php echo esc_attr(get_option('wcwp_twilio_auth_token')); ?>" class="regular-text" /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="wcwp_twilio_from">WhatsApp From Number</label></th>
                    <td><input type="text" name="wcwp_twilio_from" id="wcwp_twilio_from" value="<?php echo esc_attr(get_option('wcwp_twilio_from')); ?>" class="regular-text" placeholder="e.g. whatsapp:+14155238886" /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="wcwp_order_message_template">Order Message Template</label></th>
                    <td>
                        <textarea name="wcwp_order_message_template" rows="5" class="large-text"><?php echo esc_textarea(get_option('wcwp_order_message_template', 'Hi {name}, thanks for your order #{order_id}! Total: {total} PKR.')); ?></textarea>
                        <p class="description">Use placeholders: {name}, {order_id}, {total}</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="wcwp_cart_recovery_enabled">Enable Cart Recovery</label></th>
                    <td>
                        <select name="wcwp_cart_recovery_enabled" id="wcwp_cart_recovery_enabled">
                            <option value="yes" <?php selected(get_option('wcwp_cart_recovery_enabled'), 'yes'); ?>>Yes</option>
                            <option value="no" <?php selected(get_option('wcwp_cart_recovery_enabled'), 'no'); ?>>No</option>
                        </select>
                        <p class="description">Automatically remind users via WhatsApp if they abandon the cart.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="wcwp_chatbot_enabled">Enable Chatbot</label></th>
                    <td>
                        <select name="wcwp_chatbot_enabled" id="wcwp_chatbot_enabled">
                            <option value="yes" <?php selected(get_option('wcwp_chatbot_enabled'), 'yes'); ?>>Yes</option>
                            <option value="no" <?php selected(get_option('wcwp_chatbot_enabled'), 'no'); ?>>No</option>
                        </select>
                        <p class="description">Toggle the floating WhatsApp chatbot on your site.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="wcwp_faq_pairs">FAQ Rules (JSON)</label></th>
                    <td>
                        <textarea name="wcwp_faq_pairs" rows="6" class="large-text"><?php echo esc_textarea(get_option('wcwp_faq_pairs', '[{"question":"order","answer":"You can track your order here: yourdomain.com/track"},{"question":"return","answer":"We offer 7-day easy returns."}]')); ?></textarea>
                        <p class="description">Enter question/answer pairs as JSON array.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="wcwp_license_key">License Key</label></th>
                    <td>
                        <input type="text" name="wcwp_license_key" id="wcwp_license_key" value="<?php echo esc_attr(get_option('wcwp_license_key')); ?>" class="regular-text" />
                        <p class="description">
                            <?php
                            $status = get_option('wcwp_license_status', 'not checked');
                            if ($status === 'valid') {
                                echo '<span style="color:green;">✅ License is active</span>';
                            } elseif ($status === 'invalid') {
                                echo '<span style="color:red;">❌ Invalid License</span>';
                            } else {
                                echo 'Enter your Pro license key to enable premium features.';
                            }
                            ?>
                        </p>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}
