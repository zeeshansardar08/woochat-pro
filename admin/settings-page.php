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
    register_setting('wcwp_settings_group', 'wcwp_test_mode_enabled');
    register_setting('wcwp_settings_group', 'wcwp_api_provider');
    register_setting('wcwp_settings_group', 'wcwp_cloud_token');
    register_setting('wcwp_settings_group', 'wcwp_cloud_phone_id');
    register_setting('wcwp_settings_group', 'wcwp_cloud_from');
    register_setting('wcwp_settings_group', 'wcwp_cart_recovery_delay');
    register_setting('wcwp_settings_group', 'wcwp_cart_recovery_message');
}

function wcwp_render_settings_page() {
    // Enqueue premium admin CSS
    wp_enqueue_style('wcwp-admin-premium-css', WCWP_URL . 'assets/css/admin-premium.css', [], null);
    // Enqueue premium admin JS
    wp_enqueue_script('wcwp-admin-premium-js', WCWP_URL . 'assets/js/admin-premium.js', [], null, true);
    // Enqueue onboarding CSS/JS
    wp_enqueue_style('wcwp-onboarding-css', WCWP_URL . 'assets/css/onboarding.css', [], null);
    wp_enqueue_script('wcwp-onboarding-js', WCWP_URL . 'assets/js/onboarding.js', [], null, true);
    ?>
    <div id="wcwp-onboarding-modal">
        <div class="wcwp-onboarding-content">
            <div class="wcwp-onboarding-progress"><div class="wcwp-onboarding-progress-inner"></div></div>
            <div class="wcwp-onboarding-step"> <h2>Welcome to WooChat Pro!</h2> <p>Let's get you set up in a few easy steps.</p> </div>
            <div class="wcwp-onboarding-step"> <h2>Connect WhatsApp API</h2> <p>Enter your Twilio/Cloud API credentials in the settings.</p> </div>
            <div class="wcwp-onboarding-step"> <h2>Set Your WhatsApp Number</h2> <p>Configure your business WhatsApp number for sending messages.</p> </div>
            <div class="wcwp-onboarding-step"> <h2>Enable Features</h2> <p>Choose which features to enable: order messages, cart recovery, chatbot, and more.</p> </div>
            <div class="wcwp-onboarding-step"> <h2>All Set!</h2> <p>You're ready to start using WooChat Pro. Enjoy 🚀</p> </div>
            <div class="wcwp-onboarding-buttons">
                <button type="button" class="wcwp-onboarding-prev">Back</button>
                <button type="button" class="wcwp-onboarding-next">Next</button>
                <button type="button" class="wcwp-onboarding-finish">Finish</button>
                <button type="button" class="wcwp-onboarding-skip">Skip</button>
            </div>
        </div>
    </div>
    <div class="wcwp-admin-premium-wrap">
        <h1>WooChat – WhatsApp Settings</h1>
        <div class="wcwp-dashboard-widget">
            <div class="wcwp-dashboard-widget-stats">
                <div class="wcwp-dashboard-widget-stat">
                    <span class="dashicons dashicons-format-chat"></span>
                    <div class="wcwp-stat-value">1,234</div>
                    <div class="wcwp-stat-label">Messages Sent</div>
                </div>
                <div class="wcwp-dashboard-widget-stat">
                    <span class="dashicons dashicons-yes"></span>
                    <div class="wcwp-stat-value">87%</div>
                    <div class="wcwp-stat-label">Open Rate</div>
                </div>
                <div class="wcwp-dashboard-widget-stat">
                    <span class="dashicons dashicons-admin-network"></span>
                    <div class="wcwp-stat-value">Active</div>
                    <div class="wcwp-stat-label">License Status</div>
                </div>
            </div>
            <div class="wcwp-dashboard-widget-actions">
                <a href="#wcwp-tab-content-messaging" class="button">Send Test Message</a>
                <a href="#wcwp-tab-content-license" class="button">Manage License</a>
                <a href="https://your-upgrade-link.com" target="_blank" class="button">Upgrade</a>
            </div>
        </div>
        <div class="wcwp-tabs" id="wcwp-tabs">
            <button type="button" class="wcwp-tab active" data-tab="general">General</button>
            <button type="button" class="wcwp-tab" data-tab="messaging">Messaging</button>
            <button type="button" class="wcwp-tab" data-tab="chatbot">Chatbot</button>
            <button type="button" class="wcwp-tab" data-tab="cart-recovery">Cart Recovery</button>
            <button type="button" class="wcwp-tab" data-tab="analytics">Analytics</button>
            <button type="button" class="wcwp-tab" data-tab="license">License</button>
        </div>
        <div class="wcwp-plugin-splash">
            <span class="wcwp-plugin-logo">💬</span>
            <span class="wcwp-plugin-title">WooChat Pro</span>
        </div>
        <form method="post" action="options.php">
            <?php settings_fields('wcwp_settings_group'); ?>
            <?php do_settings_sections('wcwp_settings_group'); ?>
            <div id="wcwp-tab-content-general" class="wcwp-tab-content" style="display:block;">
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="wcwp_twilio_sid">Twilio SID</label><span class="wcwp-help-icon">?<span class="wcwp-tooltip">Your Twilio Account SID. Find it in your Twilio dashboard.</span></span></th>
                        <td><input type="text" name="wcwp_twilio_sid" id="wcwp_twilio_sid" value="<?php echo esc_attr(get_option('wcwp_twilio_sid')); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="wcwp_twilio_auth_token">Twilio Auth Token</label><span class="wcwp-help-icon">?<span class="wcwp-tooltip">Your Twilio Auth Token. Keep this secret!</span></span></th>
                        <td><input type="text" name="wcwp_twilio_auth_token" id="wcwp_twilio_auth_token" value="<?php echo esc_attr(get_option('wcwp_twilio_auth_token')); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="wcwp_twilio_from">WhatsApp From Number</label><span class="wcwp-help-icon">?<span class="wcwp-tooltip">The WhatsApp-enabled number from your Twilio or Cloud API account. Format: whatsapp:+1234567890</span></span></th>
                        <td><input type="text" name="wcwp_twilio_from" id="wcwp_twilio_from" value="<?php echo esc_attr(get_option('wcwp_twilio_from')); ?>" class="regular-text" placeholder="e.g. whatsapp:+14155238886" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="wcwp_api_provider">API Provider</label><span class="wcwp-help-icon">?<span class="wcwp-tooltip">Choose which WhatsApp API to use: Twilio or WhatsApp Cloud (Meta).</span></span></th>
                        <td>
                            <select name="wcwp_api_provider" id="wcwp_api_provider">
                                <option value="twilio" <?php selected(get_option('wcwp_api_provider', 'twilio'), 'twilio'); ?>>Twilio</option>
                                <option value="cloud" <?php selected(get_option('wcwp_api_provider', 'twilio'), 'cloud'); ?>>WhatsApp Cloud</option>
                            </select>
                            <p class="description">Select your WhatsApp API provider.</p>
                        </td>
                    </tr>
                    <tr class="wcwp-cloud-fields" style="display:none;">
                        <th scope="row"><label for="wcwp_cloud_token">Cloud API Token</label><span class="wcwp-help-icon">?<span class="wcwp-tooltip">Your WhatsApp Cloud API access token from Meta.</span></span></th>
                        <td><input type="text" name="wcwp_cloud_token" id="wcwp_cloud_token" value="<?php echo esc_attr(get_option('wcwp_cloud_token')); ?>" class="regular-text" /></td>
                    </tr>
                    <tr class="wcwp-cloud-fields" style="display:none;">
                        <th scope="row"><label for="wcwp_cloud_phone_id">Phone Number ID</label><span class="wcwp-help-icon">?<span class="wcwp-tooltip">Your WhatsApp Cloud API phone number ID.</span></span></th>
                        <td><input type="text" name="wcwp_cloud_phone_id" id="wcwp_cloud_phone_id" value="<?php echo esc_attr(get_option('wcwp_cloud_phone_id')); ?>" class="regular-text" /></td>
                    </tr>
                    <tr class="wcwp-cloud-fields" style="display:none;">
                        <th scope="row"><label for="wcwp_cloud_from">From Number</label><span class="wcwp-help-icon">?<span class="wcwp-tooltip">The WhatsApp-enabled number from your Cloud API account. Format: +1234567890</span></span></th>
                        <td><input type="text" name="wcwp_cloud_from" id="wcwp_cloud_from" value="<?php echo esc_attr(get_option('wcwp_cloud_from')); ?>" class="regular-text" placeholder="e.g. +14155238886" /></td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <div style="background: #fff3cd; color: #856404; border: 1px solid #ffe066; border-radius: 8px; padding: 18px 22px; margin-top: 18px; display: flex; align-items: center; gap: 16px;">
                                <span style="font-size: 2rem;">⚠️</span>
                                <div>
                                    <label style="font-weight: 600; font-size: 1.1rem;" for="wcwp_test_mode_enabled">Test Mode</label>
                                    <input type="checkbox" name="wcwp_test_mode_enabled" value="yes" id="wcwp_test_mode_enabled" <?php checked(get_option('wcwp_test_mode_enabled'), 'yes'); ?> style="margin-left: 10px;" />
                                    <div style="font-size: 0.98rem; margin-top: 4px;">Enable this for safe testing. <b>Messages will be logged, not sent.</b> Don't forget to turn it off in production!</div>
                                </div>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>
            <div id="wcwp-tab-content-messaging" class="wcwp-tab-content" style="display:none;">
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="wcwp_order_message_template">Order Message Template</label><span class="wcwp-help-icon">?<span class="wcwp-tooltip">Customize the WhatsApp message sent for new orders. Use placeholders: {name}, {order_id}, {total}</span></span></th>
                        <td>
                            <textarea name="wcwp_order_message_template" rows="5" class="large-text"><?php echo esc_textarea(get_option('wcwp_order_message_template', 'Hi {name}, thanks for your order #{order_id}! Total: {total} PKR.')); ?></textarea>
                            <p class="description">Use placeholders: {name}, {order_id}, {total}</p>
                        </td>
                    </tr>
                </table>
            </div>
            <div id="wcwp-tab-content-chatbot" class="wcwp-tab-content" style="display:none;">
                <div class="wcwp-chatbot-customizer">
                    <div class="wcwp-chatbot-customizer-controls">
                        <label for="wcwp-chatbot-bg">Chatbot Bubble Color</label>
                        <input type="color" id="wcwp-chatbot-bg" value="#1c7c54">
                        <label for="wcwp-chatbot-color">Text Color</label>
                        <input type="color" id="wcwp-chatbot-color" value="#ffffff">
                        <label for="wcwp-chatbot-icon">Icon Color</label>
                        <input type="color" id="wcwp-chatbot-icon" value="#2ec4b6">
                        <label>Choose Icon</label>
                        <div class="wcwp-icon-select">
                            <span class="wcwp-icon-option selected">💬</span>
                            <span class="wcwp-icon-option">🤖</span>
                            <span class="wcwp-icon-option">🟢</span>
                            <span class="wcwp-icon-option">📞</span>
                        </div>
                        <label for="wcwp-chatbot-welcome">Welcome Message</label>
                        <input type="text" id="wcwp-chatbot-welcome" value="Hi! How can I help you?">
                    </div>
                    <div class="wcwp-chatbot-customizer-preview">
                        <div class="wcwp-chatbot-preview-icon">💬</div>
                        <div class="wcwp-chatbot-preview-bubble"><span id="wcwp-chatbot-preview-welcome">Hi! How can I help you?</span></div>
                    </div>
                </div>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="wcwp_chatbot_enabled">Enable Chatbot</label><span class="wcwp-help-icon">?<span class="wcwp-tooltip">Toggle the floating WhatsApp chatbot widget on your site.</span></span></th>
                        <td>
                            <select name="wcwp_chatbot_enabled" id="wcwp_chatbot_enabled">
                                <option value="yes" <?php selected(get_option('wcwp_chatbot_enabled'), 'yes'); ?>>Yes</option>
                                <option value="no" <?php selected(get_option('wcwp_chatbot_enabled'), 'no'); ?>>No</option>
                            </select>
                            <p class="description">Toggle the floating WhatsApp chatbot on your site.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="wcwp_faq_pairs">FAQ Rules (JSON)</label><span class="wcwp-help-icon">?<span class="wcwp-tooltip">Add question/answer pairs for the chatbot. Format: JSON array.</span></span></th>
                        <td>
                            <textarea name="wcwp_faq_pairs" rows="6" class="large-text"><?php echo esc_textarea(get_option('wcwp_faq_pairs', '[{"question":"order","answer":"You can track your order here: yourdomain.com/track"},{"question":"return","answer":"We offer 7-day easy returns."}]')); ?></textarea>
                            <p class="description">Enter question/answer pairs as JSON array.</p>
                        </td>
                    </tr>
                </table>
            </div>
            <div id="wcwp-tab-content-cart-recovery" class="wcwp-tab-content" style="display:none;">
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="wcwp_cart_recovery_enabled">Enable Cart Recovery</label><span class="wcwp-help-icon">?<span class="wcwp-tooltip">Automatically remind users via WhatsApp if they abandon their cart.</span></span></th>
                        <td>
                            <select name="wcwp_cart_recovery_enabled" id="wcwp_cart_recovery_enabled">
                                <option value="yes" <?php selected(get_option('wcwp_cart_recovery_enabled'), 'yes'); ?>>Yes</option>
                                <option value="no" <?php selected(get_option('wcwp_cart_recovery_enabled'), 'no'); ?>>No</option>
                            </select>
                            <p class="description">Automatically remind users via WhatsApp if they abandon the cart.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="wcwp_cart_recovery_delay">Reminder Delay (minutes)</label><span class="wcwp-help-icon">?<span class="wcwp-tooltip">How many minutes after cart abandonment should the WhatsApp reminder be sent?</span></span></th>
                        <td><input type="number" min="1" name="wcwp_cart_recovery_delay" id="wcwp_cart_recovery_delay" value="<?php echo esc_attr(get_option('wcwp_cart_recovery_delay', 20)); ?>" class="small-text" />
                        <p class="description">Default: 20 minutes</p></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="wcwp_cart_recovery_message">Cart Recovery Message</label><span class="wcwp-help-icon">?<span class="wcwp-tooltip">Customize the WhatsApp message sent for cart recovery. Use placeholders: {items}, {total}, {cart_url}</span></span></th>
                        <td>
                            <textarea name="wcwp_cart_recovery_message" id="wcwp_cart_recovery_message" rows="5" class="large-text"><?php echo esc_textarea(get_option('wcwp_cart_recovery_message', "👋 Hey! You left items in your cart:\n\n{items}\n\nTotal: {total} PKR\nClick here to complete your order: {cart_url}")); ?></textarea>
                            <p class="description">Use placeholders: {items}, {total}, {cart_url}</p>
                        </td>
                    </tr>
                </table>
                <?php
                // After the cart recovery settings table, show the last 10 recovery attempts
                $attempts = get_transient('wcwp_cart_recovery_attempts') ?: [];
                $attempts = array_slice(array_reverse($attempts), 0, 10);
                if (!empty($attempts)) {
                    echo '<h3 style="margin-top:32px;">Recent Cart Recovery Attempts</h3>';
                    echo '<table class="widefat striped" style="margin-top:10px;">';
                    echo '<thead><tr><th>Time</th><th>Phone</th><th>Items</th><th>Total</th><th>Message</th></tr></thead><tbody>';
                    foreach ($attempts as $a) {
                        echo '<tr>';
                        echo '<td>' . esc_html($a['time']) . '</td>';
                        echo '<td>' . esc_html($a['phone']) . '</td>';
                        echo '<td><pre style="white-space:pre-line;font-size:0.97em;">' . esc_html(implode("\n", $a['items'])) . '</pre></td>';
                        echo '<td>' . esc_html($a['total']) . '</td>';
                        echo '<td><pre style="white-space:pre-line;font-size:0.97em;max-width:320px;overflow-x:auto;">' . esc_html($a['message']) . '</pre></td>';
                        echo '</tr>';
                    }
                    echo '</tbody></table>';
                }
                ?>
            </div>
            <div id="wcwp-tab-content-analytics" class="wcwp-tab-content" style="display:none;">
                <div class="wcwp-pro-banner"><span class="dashicons dashicons-chart-bar"></span> <strong>Analytics Dashboard</strong> is available in <b>WooChat Pro</b>. <button type="button" class="wcwp-open-upgrade-modal" style="margin-left:12px;">Upgrade</button></div>
                <div class="wcwp-pro-locked">
                    <div class="wcwp-pro-locked-message">Upgrade to Pro to unlock analytics!</div>
                    <div class="wcwp-empty-illustration"><span>📊</span>No analytics data yet.<br>Upgrade to Pro to see your message stats!</div>
                </div>
            </div>
            <div id="wcwp-tab-content-license" class="wcwp-tab-content" style="display:none;">
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="wcwp_license_key">License Key</label><span class="wcwp-help-icon">?<span class="wcwp-tooltip">Enter your Pro license key to unlock premium features.</span></span></th>
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
            </div>
            <?php submit_button(); ?>
        </form>
    </div>
    <div id="wcwp-upgrade-modal">
        <div class="wcwp-upgrade-modal-content">
            <button class="wcwp-upgrade-modal-close" aria-label="Close">&times;</button>
            <h2>Upgrade to WooChat Pro</h2>
            <p>Unlock all premium features and maximize your store's potential!</p>
            <table class="wcwp-comparison-table">
                <tr><th>Feature</th><th>Free</th><th class="pro">Pro</th></tr>
                <tr><td>Order Confirmation via WhatsApp</td><td>✔️</td><td class="pro">✔️</td></tr>
                <tr><td>Manual Message Button</td><td>✔️</td><td class="pro">✔️</td></tr>
                <tr><td>Cart Recovery</td><td></td><td class="pro">✔️</td></tr>
                <tr><td>Smart Chatbot Widget</td><td></td><td class="pro">✔️</td></tr>
                <tr><td>Chatbot Customizer</td><td></td><td class="pro">✔️</td></tr>
                <tr><td>Scheduled Messages</td><td></td><td class="pro">✔️</td></tr>
                <tr><td>GPT/AI Auto Replies</td><td></td><td class="pro">✔️</td></tr>
                <tr><td>Usage Analytics</td><td></td><td class="pro">✔️</td></tr>
                <tr><td>Widget Shortcodes</td><td></td><td class="pro">✔️</td></tr>
                <tr><td>Premium Support</td><td></td><td class="pro">✔️</td></tr>
            </table>
            <a href="https://your-upgrade-link.com" target="_blank"><button class="wcwp-upgrade-btn">Upgrade Now</button></a>
            <p style="margin-top:10px;font-size:0.97rem;color:#888;">Already have a license? Enter it in the License tab.</p>
        </div>
    </div>
    <div class="wcwp-support-form" id="wcwp-support-form">
        <h2>Contact Support</h2>
        <form id="wcwp-support-contact-form" method="post" action="#" onsubmit="event.preventDefault();document.getElementById('wcwp-support-success').style.display='block';">
            <label for="wcwp-support-name">Your Name</label>
            <input type="text" id="wcwp-support-name" name="wcwp-support-name" required>
            <label for="wcwp-support-email">Your Email</label>
            <input type="email" id="wcwp-support-email" name="wcwp-support-email" required>
            <label for="wcwp-support-message">Message</label>
            <textarea id="wcwp-support-message" name="wcwp-support-message" rows="4" required></textarea>
            <button type="submit" class="button">Send Message</button>
            <div class="wcwp-support-success" id="wcwp-support-success" style="display:none;">Thank you! Your message has been sent. Our team will get back to you soon.</div>
        </form>
        <div style="margin-top:12px;font-size:0.97rem;color:#888;">
            Or email us at <a href="mailto:support@woochatpro.com">support@woochatpro.com</a><br>
            <a href="https://your-feature-request-link.com" target="_blank">Suggest a Feature</a> &nbsp;|&nbsp; <a href="https://your-bug-report-link.com" target="_blank">Report a Bug</a>
        </div>
    </div>
    <?php
}
