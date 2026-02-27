<?php
if (!defined('ABSPATH')) exit;

// Add menu to WP admin
add_action('admin_menu', 'wcwp_register_settings_page');
function wcwp_register_settings_page() {
    add_menu_page(
        __('WooChat Settings', 'woochat-pro'),
        __('WooChat', 'woochat-pro'),
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
    register_setting('wcwp_settings_group', 'wcwp_twilio_sid', ['sanitize_callback' => 'wcwp_sanitize_text']);
    register_setting('wcwp_settings_group', 'wcwp_twilio_auth_token', ['sanitize_callback' => 'wcwp_sanitize_text']);
    register_setting('wcwp_settings_group', 'wcwp_twilio_from', ['sanitize_callback' => 'wcwp_sanitize_text']);
    register_setting('wcwp_settings_group', 'wcwp_order_message_template', ['sanitize_callback' => 'wcwp_sanitize_textarea']);
    register_setting('wcwp_settings_group', 'wcwp_cart_recovery_enabled', ['sanitize_callback' => 'wcwp_sanitize_yes_no']);
    register_setting('wcwp_settings_group', 'wcwp_chatbot_enabled', ['sanitize_callback' => 'wcwp_sanitize_yes_no']);
    register_setting('wcwp_settings_group', 'wcwp_faq_pairs', ['sanitize_callback' => 'wcwp_sanitize_json_faq']);
    register_setting('wcwp_settings_group', 'wcwp_license_key', ['sanitize_callback' => 'wcwp_sanitize_text']);
    register_setting('wcwp_settings_group', 'wcwp_test_mode_enabled', ['sanitize_callback' => 'wcwp_sanitize_yes_no']);
    register_setting('wcwp_settings_group', 'wcwp_api_provider', ['sanitize_callback' => 'wcwp_sanitize_provider']);
    register_setting('wcwp_settings_group', 'wcwp_cloud_token', ['sanitize_callback' => 'wcwp_sanitize_text']);
    register_setting('wcwp_settings_group', 'wcwp_cloud_phone_id', ['sanitize_callback' => 'wcwp_sanitize_text']);
    register_setting('wcwp_settings_group', 'wcwp_cloud_from', ['sanitize_callback' => 'wcwp_sanitize_text']);
    register_setting('wcwp_settings_group', 'wcwp_cart_recovery_delay', ['sanitize_callback' => 'wcwp_sanitize_int']);
    register_setting('wcwp_settings_group', 'wcwp_cart_recovery_message', ['sanitize_callback' => 'wcwp_sanitize_textarea']);
    register_setting('wcwp_settings_group', 'wcwp_cart_recovery_require_consent', ['sanitize_callback' => 'wcwp_sanitize_yes_no']);
    register_setting('wcwp_settings_group', 'wcwp_followup_enabled', ['sanitize_callback' => 'wcwp_sanitize_yes_no']);
    register_setting('wcwp_settings_group', 'wcwp_followup_delay_minutes', ['sanitize_callback' => 'wcwp_sanitize_int']);
    register_setting('wcwp_settings_group', 'wcwp_followup_template', ['sanitize_callback' => 'wcwp_sanitize_textarea']);
    register_setting('wcwp_settings_group', 'wcwp_followup_use_gpt', ['sanitize_callback' => 'wcwp_sanitize_yes_no']);
    register_setting('wcwp_settings_group', 'wcwp_gpt_api_endpoint', ['sanitize_callback' => 'wcwp_sanitize_url']);
    register_setting('wcwp_settings_group', 'wcwp_gpt_api_key', ['sanitize_callback' => 'wcwp_sanitize_text']);
    register_setting('wcwp_settings_group', 'wcwp_gpt_model', ['sanitize_callback' => 'wcwp_sanitize_text']);
    register_setting('wcwp_settings_group', 'wcwp_chatbot_bg', ['sanitize_callback' => 'wcwp_sanitize_text']);
    register_setting('wcwp_settings_group', 'wcwp_chatbot_text', ['sanitize_callback' => 'wcwp_sanitize_text']);
    register_setting('wcwp_settings_group', 'wcwp_chatbot_icon_color', ['sanitize_callback' => 'wcwp_sanitize_text']);
    register_setting('wcwp_settings_group', 'wcwp_chatbot_icon', ['sanitize_callback' => 'wcwp_sanitize_text']);
    register_setting('wcwp_settings_group', 'wcwp_chatbot_welcome', ['sanitize_callback' => 'wcwp_sanitize_text']);
    register_setting('wcwp_settings_group', 'wcwp_data_retention_days', ['sanitize_callback' => 'wcwp_sanitize_int']);
    register_setting('wcwp_settings_group', 'wcwp_delete_data_on_uninstall', ['sanitize_callback' => 'wcwp_sanitize_yes_no']);
    register_setting('wcwp_settings_group', 'wcwp_optout_keywords', ['sanitize_callback' => 'wcwp_sanitize_optout_keywords']);
    register_setting('wcwp_settings_group', 'wcwp_optout_list', ['sanitize_callback' => 'wcwp_parse_optout_list']);
    register_setting('wcwp_settings_group', 'wcwp_optout_webhook_token', ['sanitize_callback' => 'wcwp_sanitize_text']);
    register_setting('wcwp_settings_group', 'wcwp_test_phone', ['sanitize_callback' => 'wcwp_sanitize_text']);
    register_setting('wcwp_settings_group', 'wcwp_test_message', ['sanitize_callback' => 'wcwp_sanitize_textarea']);
}

function wcwp_render_settings_page() {
    // Enqueue premium admin CSS
    wp_enqueue_style('wcwp-admin-premium-css', WCWP_URL . 'assets/css/admin-premium.css', [], WCWP_VERSION);
    // Enqueue premium admin JS
    wp_enqueue_script('wcwp-admin-premium-js', WCWP_URL . 'assets/js/admin-premium.js', [], WCWP_VERSION, true);
    wp_localize_script('wcwp-admin-premium-js', 'wcwpAdminData', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'resendNonce' => wp_create_nonce('wcwp_resend_cart'),
        'licenseNonce' => wp_create_nonce('wcwp_license_nonce'),
        'testNonce' => wp_create_nonce('wcwp_test_message'),
    ]);
    // Enqueue onboarding CSS/JS
    wp_enqueue_style('wcwp-onboarding-css', WCWP_URL . 'assets/css/onboarding.css', [], WCWP_VERSION);
    wp_enqueue_script('wcwp-onboarding-js', WCWP_URL . 'assets/js/onboarding.js', [], WCWP_VERSION, true);
    ?>
    <div id="wcwp-onboarding-modal">
        <div class="wcwp-onboarding-content">
            <div class="wcwp-onboarding-progress"><div class="wcwp-onboarding-progress-inner"></div></div>
            <div class="wcwp-onboarding-step"> <h2><?php esc_html_e('Welcome to WooChat Pro!', 'woochat-pro'); ?></h2> <p><?php esc_html_e("Let's get you set up in a few easy steps.", 'woochat-pro'); ?></p> </div>
            <div class="wcwp-onboarding-step"> <h2><?php esc_html_e('Connect WhatsApp API', 'woochat-pro'); ?></h2> <p><?php esc_html_e('Enter your Twilio/Cloud API credentials in the settings.', 'woochat-pro'); ?></p> </div>
            <div class="wcwp-onboarding-step"> <h2><?php esc_html_e('Set Your WhatsApp Number', 'woochat-pro'); ?></h2> <p><?php esc_html_e('Configure your business WhatsApp number for sending messages.', 'woochat-pro'); ?></p> </div>
            <div class="wcwp-onboarding-step"> <h2><?php esc_html_e('Enable Features', 'woochat-pro'); ?></h2> <p><?php esc_html_e('Choose which features to enable: order messages, cart recovery, chatbot, and more.', 'woochat-pro'); ?></p> </div>
            <div class="wcwp-onboarding-step"> <h2><?php esc_html_e('All Set!', 'woochat-pro'); ?></h2> <p><?php esc_html_e('You\'re ready to start using WooChat Pro. Enjoy!', 'woochat-pro'); ?></p> </div>
            <div class="wcwp-onboarding-buttons">
                <button type="button" class="wcwp-onboarding-prev"><?php esc_html_e('Back', 'woochat-pro'); ?></button>
                <button type="button" class="wcwp-onboarding-next"><?php esc_html_e('Next', 'woochat-pro'); ?></button>
                <button type="button" class="wcwp-onboarding-finish"><?php esc_html_e('Finish', 'woochat-pro'); ?></button>
                <button type="button" class="wcwp-onboarding-skip"><?php esc_html_e('Skip', 'woochat-pro'); ?></button>
            </div>
        </div>
    </div>
    <div class="wcwp-admin-premium-wrap">
        <h1><?php esc_html_e('WooChat – WhatsApp Settings', 'woochat-pro'); ?></h1>
        <div class="wcwp-dashboard-widget">
            <?php
            $dash_totals = function_exists('wcwp_analytics_get_totals') ? wcwp_analytics_get_totals() : ['sent' => 0, 'delivered' => 0, 'clicked' => 0];
            $dash_license = get_option('wcwp_license_status', 'inactive');
            $dash_open_rate = $dash_totals['sent'] > 0 ? round(($dash_totals['delivered'] / $dash_totals['sent']) * 100) . '%' : '—';
            ?>
            <div class="wcwp-dashboard-widget-stats">
                <div class="wcwp-dashboard-widget-stat">
                    <span class="dashicons dashicons-format-chat"></span>
                    <div class="wcwp-stat-value"><?php echo esc_html(number_format_i18n($dash_totals['sent'])); ?></div>
                    <div class="wcwp-stat-label"><?php esc_html_e('Messages Sent', 'woochat-pro'); ?></div>
                </div>
                <div class="wcwp-dashboard-widget-stat">
                    <span class="dashicons dashicons-yes"></span>
                    <div class="wcwp-stat-value"><?php echo esc_html($dash_open_rate); ?></div>
                    <div class="wcwp-stat-label"><?php esc_html_e('Open Rate', 'woochat-pro'); ?></div>
                </div>
                <div class="wcwp-dashboard-widget-stat">
                    <span class="dashicons dashicons-admin-network"></span>
                    <div class="wcwp-stat-value"><?php echo esc_html(ucfirst($dash_license)); ?></div>
                    <div class="wcwp-stat-label"><?php esc_html_e('License Status', 'woochat-pro'); ?></div>
                </div>
            </div>
            <div class="wcwp-dashboard-widget-actions">
                <a href="#wcwp-tab-content-messaging" class="button"><?php esc_html_e('Send Test Message', 'woochat-pro'); ?></a>
                <a href="#wcwp-tab-content-license" class="button"><?php esc_html_e('Manage License', 'woochat-pro'); ?></a>
            </div>
        </div>
        <div class="wcwp-tabs" id="wcwp-tabs">
            <button type="button" class="wcwp-tab active" data-tab="general"><?php esc_html_e('General', 'woochat-pro'); ?></button>
            <button type="button" class="wcwp-tab" data-tab="messaging"><?php esc_html_e('Messaging', 'woochat-pro'); ?></button>
            <button type="button" class="wcwp-tab" data-tab="chatbot"><?php esc_html_e('Chatbot', 'woochat-pro'); ?></button>
            <button type="button" class="wcwp-tab" data-tab="cart-recovery"><?php esc_html_e('Cart Recovery', 'woochat-pro'); ?></button>
            <button type="button" class="wcwp-tab" data-tab="scheduler"><?php esc_html_e('Scheduler', 'woochat-pro'); ?></button>
            <button type="button" class="wcwp-tab" data-tab="analytics"><?php esc_html_e('Analytics', 'woochat-pro'); ?></button>
            <button type="button" class="wcwp-tab" data-tab="license"><?php esc_html_e('License', 'woochat-pro'); ?></button>
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
                        <th scope="row"><label for="wcwp_twilio_sid"><?php esc_html_e('Twilio SID', 'woochat-pro'); ?></label><span class="wcwp-help-icon">?<span class="wcwp-tooltip"><?php esc_html_e('Your Twilio Account SID. Find it in your Twilio dashboard.', 'woochat-pro'); ?></span></span></th>
                        <td><input type="text" name="wcwp_twilio_sid" id="wcwp_twilio_sid" value="<?php echo esc_attr(get_option('wcwp_twilio_sid')); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="wcwp_twilio_auth_token"><?php esc_html_e('Twilio Auth Token', 'woochat-pro'); ?></label><span class="wcwp-help-icon">?<span class="wcwp-tooltip"><?php esc_html_e('Your Twilio Auth Token. Keep this secret!', 'woochat-pro'); ?></span></span></th>
                        <td><input type="password" name="wcwp_twilio_auth_token" id="wcwp_twilio_auth_token" value="<?php echo esc_attr(get_option('wcwp_twilio_auth_token')); ?>" class="regular-text" autocomplete="off" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="wcwp_twilio_from"><?php esc_html_e('WhatsApp From Number', 'woochat-pro'); ?></label><span class="wcwp-help-icon">?<span class="wcwp-tooltip"><?php esc_html_e('The WhatsApp-enabled number from your Twilio or Cloud API account. Format: whatsapp:+1234567890', 'woochat-pro'); ?></span></span></th>
                        <td><input type="text" name="wcwp_twilio_from" id="wcwp_twilio_from" value="<?php echo esc_attr(get_option('wcwp_twilio_from')); ?>" class="regular-text" placeholder="e.g. whatsapp:+14155238886" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="wcwp_api_provider"><?php esc_html_e('API Provider', 'woochat-pro'); ?></label><span class="wcwp-help-icon">?<span class="wcwp-tooltip"><?php esc_html_e('Choose which WhatsApp API to use: Twilio or WhatsApp Cloud (Meta).', 'woochat-pro'); ?></span></span></th>
                        <td>
                            <select name="wcwp_api_provider" id="wcwp_api_provider">
                                <option value="twilio" <?php selected(get_option('wcwp_api_provider', 'twilio'), 'twilio'); ?>><?php esc_html_e('Twilio', 'woochat-pro'); ?></option>
                                <option value="cloud" <?php selected(get_option('wcwp_api_provider', 'twilio'), 'cloud'); ?>><?php esc_html_e('WhatsApp Cloud', 'woochat-pro'); ?></option>
                            </select>
                            <p class="description"><?php esc_html_e('Select your WhatsApp API provider.', 'woochat-pro'); ?></p>
                        </td>
                    </tr>
                    <tr class="wcwp-cloud-fields" style="display:none;">
                        <th scope="row"><label for="wcwp_cloud_token"><?php esc_html_e('Cloud API Token', 'woochat-pro'); ?></label><span class="wcwp-help-icon">?<span class="wcwp-tooltip"><?php esc_html_e('Your WhatsApp Cloud API access token from Meta.', 'woochat-pro'); ?></span></span></th>
                        <td><input type="password" name="wcwp_cloud_token" id="wcwp_cloud_token" value="<?php echo esc_attr(get_option('wcwp_cloud_token')); ?>" class="regular-text" autocomplete="off" /></td>
                    </tr>
                    <tr class="wcwp-cloud-fields" style="display:none;">
                        <th scope="row"><label for="wcwp_cloud_phone_id"><?php esc_html_e('Phone Number ID', 'woochat-pro'); ?></label><span class="wcwp-help-icon">?<span class="wcwp-tooltip"><?php esc_html_e('Your WhatsApp Cloud API phone number ID.', 'woochat-pro'); ?></span></span></th>
                        <td><input type="text" name="wcwp_cloud_phone_id" id="wcwp_cloud_phone_id" value="<?php echo esc_attr(get_option('wcwp_cloud_phone_id')); ?>" class="regular-text" /></td>
                    </tr>
                    <tr class="wcwp-cloud-fields" style="display:none;">
                        <th scope="row"><label for="wcwp_cloud_from"><?php esc_html_e('From Number', 'woochat-pro'); ?></label><span class="wcwp-help-icon">?<span class="wcwp-tooltip"><?php esc_html_e('The WhatsApp-enabled number from your Cloud API account. Format: +1234567890', 'woochat-pro'); ?></span></span></th>
                        <td><input type="text" name="wcwp_cloud_from" id="wcwp_cloud_from" value="<?php echo esc_attr(get_option('wcwp_cloud_from')); ?>" class="regular-text" placeholder="e.g. +14155238886" /></td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <div style="background: #fff3cd; color: #856404; border: 1px solid #ffe066; border-radius: 8px; padding: 18px 22px; margin-top: 18px; display: flex; align-items: center; gap: 16px;">
                                <span style="font-size: 2rem;">⚠️</span>
                                <div>
                                    <label style="font-weight: 600; font-size: 1.1rem;" for="wcwp_test_mode_enabled"><?php esc_html_e('Test Mode', 'woochat-pro'); ?></label>
                                    <input type="hidden" name="wcwp_test_mode_enabled" value="no" />
                                    <input type="checkbox" name="wcwp_test_mode_enabled" value="yes" id="wcwp_test_mode_enabled" <?php checked(get_option('wcwp_test_mode_enabled'), 'yes'); ?> style="margin-left: 10px;" />
                                    <div style="font-size: 0.98rem; margin-top: 4px;"><?php esc_html_e('Enable this for safe testing.', 'woochat-pro'); ?> <b><?php esc_html_e('Messages will be logged, not sent.', 'woochat-pro'); ?></b> <?php esc_html_e("Don't forget to turn it off in production!", 'woochat-pro'); ?></div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="wcwp_data_retention_days"><?php esc_html_e('Data Retention (days)', 'woochat-pro'); ?></label><span class="wcwp-help-icon">?<span class="wcwp-tooltip"><?php esc_html_e('Automatically delete analytics events older than this. Set to 0 to keep indefinitely.', 'woochat-pro'); ?></span></span></th>
                        <td>
                            <input type="number" min="0" name="wcwp_data_retention_days" id="wcwp_data_retention_days" value="<?php echo esc_attr(get_option('wcwp_data_retention_days', 0)); ?>" class="small-text" />
                            <p class="description"><?php esc_html_e('Recommended: 30–180 days.', 'woochat-pro'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="wcwp_delete_data_on_uninstall"><?php esc_html_e('Delete Data on Uninstall', 'woochat-pro'); ?></label><span class="wcwp-help-icon">?<span class="wcwp-tooltip"><?php esc_html_e('If enabled, all plugin data will be removed when the plugin is deleted.', 'woochat-pro'); ?></span></span></th>
                        <td>
                            <select name="wcwp_delete_data_on_uninstall" id="wcwp_delete_data_on_uninstall">
                                <option value="no" <?php selected(get_option('wcwp_delete_data_on_uninstall', 'no'), 'no'); ?>><?php esc_html_e('No', 'woochat-pro'); ?></option>
                                <option value="yes" <?php selected(get_option('wcwp_delete_data_on_uninstall', 'no'), 'yes'); ?>><?php esc_html_e('Yes', 'woochat-pro'); ?></option>
                            </select>
                            <p class="description"><?php esc_html_e('Use for strict compliance requirements.', 'woochat-pro'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="wcwp_optout_keywords"><?php esc_html_e('Opt-out Keywords', 'woochat-pro'); ?></label><span class="wcwp-help-icon">?<span class="wcwp-tooltip"><?php esc_html_e('Incoming messages containing these words will add the number to the suppression list.', 'woochat-pro'); ?></span></span></th>
                        <td>
                            <input type="text" name="wcwp_optout_keywords" id="wcwp_optout_keywords" value="<?php echo esc_attr(get_option('wcwp_optout_keywords', 'stop, unsubscribe')); ?>" class="regular-text" />
                            <p class="description"><?php esc_html_e('Comma-separated. Default: stop, unsubscribe.', 'woochat-pro'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="wcwp_optout_webhook_token"><?php esc_html_e('Opt-out Webhook Token', 'woochat-pro'); ?></label><span class="wcwp-help-icon">?<span class="wcwp-tooltip"><?php esc_html_e('Use this token to secure the opt-out webhook endpoint.', 'woochat-pro'); ?></span></span></th>
                        <td>
                            <input type="text" name="wcwp_optout_webhook_token" id="wcwp_optout_webhook_token" value="<?php echo esc_attr(get_option('wcwp_optout_webhook_token', '')); ?>" class="regular-text" />
                            <p class="description"><?php esc_html_e('Webhook:', 'woochat-pro'); ?> <code><?php echo esc_html(rest_url('wcwp/v1/optout')); ?></code></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="wcwp_optout_list"><?php esc_html_e('Suppression List (opted-out numbers)', 'woochat-pro'); ?></label><span class="wcwp-help-icon">?<span class="wcwp-tooltip"><?php esc_html_e('Numbers in this list will never receive messages.', 'woochat-pro'); ?></span></span></th>
                        <td>
                            <?php $optout_list = function_exists('wcwp_get_optout_list') ? wcwp_get_optout_list() : []; ?>
                            <textarea name="wcwp_optout_list" id="wcwp_optout_list" rows="4" class="large-text"><?php echo esc_textarea(implode("\n", $optout_list)); ?></textarea>
                            <p class="description"><?php esc_html_e('One phone number per line.', 'woochat-pro'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
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
                        <th scope="row"><label for="wcwp_order_message_template"><?php esc_html_e('Order Message Template', 'woochat-pro'); ?></label><span class="wcwp-help-icon">?<span class="wcwp-tooltip"><?php esc_html_e('Customize the WhatsApp message sent for new orders. Use placeholders: {name}, {order_id}, {total}', 'woochat-pro'); ?></span></span></th>
                        <td>
                            <textarea name="wcwp_order_message_template" rows="5" class="large-text"><?php echo esc_textarea(get_option('wcwp_order_message_template', 'Hi {name}, thanks for your order #{order_id}! Total: {total} PKR.')); ?></textarea>
                            <p class="description"><?php
                                /* translators: do not translate placeholders inside curly braces */
                                esc_html_e('Use placeholders: {name}, {order_id}, {total}', 'woochat-pro');
                            ?></p>
                        </td>
                    </tr>
                </table>
            </div>
            <div id="wcwp-tab-content-chatbot" class="wcwp-tab-content" style="display:none;">
                <div class="wcwp-chatbot-customizer">
                    <div class="wcwp-chatbot-customizer-controls">
                        <label for="wcwp-chatbot-bg"><?php esc_html_e('Chatbot Bubble Color', 'woochat-pro'); ?></label>
                        <input type="color" id="wcwp-chatbot-bg" name="wcwp_chatbot_bg" value="<?php echo esc_attr(get_option('wcwp_chatbot_bg', '#1c7c54')); ?>">
                        <label for="wcwp-chatbot-color"><?php esc_html_e('Text Color', 'woochat-pro'); ?></label>
                        <input type="color" id="wcwp-chatbot-color" name="wcwp_chatbot_text" value="<?php echo esc_attr(get_option('wcwp_chatbot_text', '#ffffff')); ?>">
                        <label for="wcwp-chatbot-icon"><?php esc_html_e('Icon Color', 'woochat-pro'); ?></label>
                        <input type="color" id="wcwp-chatbot-icon" name="wcwp_chatbot_icon_color" value="<?php echo esc_attr(get_option('wcwp_chatbot_icon_color', '#2ec4b6')); ?>">
                        <label><?php esc_html_e('Choose Icon', 'woochat-pro'); ?></label>
                        <div class="wcwp-icon-select">
                            <?php $icon_option = get_option('wcwp_chatbot_icon', '💬'); ?>
                            <span class="wcwp-icon-option <?php echo $icon_option === '💬' ? 'selected' : ''; ?>">💬</span>
                            <span class="wcwp-icon-option <?php echo $icon_option === '🤖' ? 'selected' : ''; ?>">🤖</span>
                            <span class="wcwp-icon-option <?php echo $icon_option === '🟢' ? 'selected' : ''; ?>">🟢</span>
                            <span class="wcwp-icon-option <?php echo $icon_option === '📞' ? 'selected' : ''; ?>">📞</span>
                        </div>
                        <input type="hidden" id="wcwp-chatbot-icon-value" name="wcwp_chatbot_icon" value="<?php echo esc_attr($icon_option); ?>" />
                        <label for="wcwp-chatbot-welcome"><?php esc_html_e('Welcome Message', 'woochat-pro'); ?></label>
                        <input type="text" id="wcwp-chatbot-welcome" name="wcwp_chatbot_welcome" value="<?php echo esc_attr(get_option('wcwp_chatbot_welcome', 'Hi! How can I help you?')); ?>">
                    </div>
                    <div class="wcwp-chatbot-customizer-preview">
                        <div class="wcwp-chatbot-preview-icon">💬</div>
                        <div class="wcwp-chatbot-preview-bubble"><span id="wcwp-chatbot-preview-welcome">Hi! How can I help you?</span></div>
                    </div>
                </div>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="wcwp_chatbot_enabled"><?php esc_html_e('Enable Chatbot', 'woochat-pro'); ?></label><span class="wcwp-help-icon">?<span class="wcwp-tooltip"><?php esc_html_e('Toggle the floating WhatsApp chatbot widget on your site.', 'woochat-pro'); ?></span></span></th>
                        <td>
                            <select name="wcwp_chatbot_enabled" id="wcwp_chatbot_enabled">
                                <option value="yes" <?php selected(get_option('wcwp_chatbot_enabled'), 'yes'); ?>><?php esc_html_e('Yes', 'woochat-pro'); ?></option>
                                <option value="no" <?php selected(get_option('wcwp_chatbot_enabled'), 'no'); ?>><?php esc_html_e('No', 'woochat-pro'); ?></option>
                            </select>
                            <p class="description"><?php esc_html_e('Toggle the floating WhatsApp chatbot on your site.', 'woochat-pro'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="wcwp_faq_pairs"><?php esc_html_e('FAQ Rules (JSON)', 'woochat-pro'); ?></label><span class="wcwp-help-icon">?<span class="wcwp-tooltip"><?php esc_html_e('Add question/answer pairs for the chatbot. Format: JSON array.', 'woochat-pro'); ?></span></span></th>
                        <td>
                            <textarea name="wcwp_faq_pairs" rows="6" class="large-text"><?php echo esc_textarea(get_option('wcwp_faq_pairs', '[{"question":"order","answer":"You can track your order here: yourdomain.com/track"},{"question":"return","answer":"We offer 7-day easy returns."}]')); ?></textarea>
                            <p class="description"><?php esc_html_e('Enter question/answer pairs as JSON array.', 'woochat-pro'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
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
                        <th scope="row"><label for="wcwp_cart_recovery_message"><?php esc_html_e('Cart Recovery Message', 'woochat-pro'); ?></label><span class="wcwp-help-icon">?<span class="wcwp-tooltip"><?php esc_html_e('Customize the WhatsApp message sent for cart recovery. Use placeholders: {items}, {total}, {cart_url}', 'woochat-pro'); ?></span></span></th>
                        <td>
                            <textarea name="wcwp_cart_recovery_message" id="wcwp_cart_recovery_message" rows="5" class="large-text"><?php echo esc_textarea(get_option('wcwp_cart_recovery_message', "👋 Hey! You left items in your cart:\n\n{items}\n\nTotal: {total} PKR\nClick here to complete your order: {cart_url}")); ?></textarea>
                            <p class="description"><?php
                                /* translators: do not translate placeholders inside curly braces */
                                esc_html_e('Use placeholders: {items}, {total}, {cart_url}', 'woochat-pro');
                            ?></p>
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
            <div id="wcwp-tab-content-analytics" class="wcwp-tab-content" style="display:none;">
                <?php $is_pro = function_exists('wcwp_is_pro_active') && wcwp_is_pro_active(); ?>
                <?php $totals = function_exists('wcwp_analytics_get_totals') ? wcwp_analytics_get_totals() : ['sent' => 0, 'delivered' => 0, 'clicked' => 0]; ?>
                <?php
                $filters = [
                    'type' => isset($_GET['wcwp_type']) ? sanitize_text_field($_GET['wcwp_type']) : '',
                    'status' => isset($_GET['wcwp_status']) ? sanitize_text_field($_GET['wcwp_status']) : '',
                    'phone' => isset($_GET['wcwp_phone']) ? sanitize_text_field($_GET['wcwp_phone']) : '',
                    'date_from' => isset($_GET['wcwp_date_from']) ? sanitize_text_field($_GET['wcwp_date_from']) : '',
                    'date_to' => isset($_GET['wcwp_date_to']) ? sanitize_text_field($_GET['wcwp_date_to']) : '',
                ];
                $events = function_exists('wcwp_analytics_get_events') ? wcwp_analytics_get_events(25, $filters) : [];
                ?>
                <?php if (!$is_pro) : ?>
                    <div class="wcwp-pro-banner"><span class="dashicons dashicons-chart-bar"></span> <strong><?php esc_html_e('Analytics Dashboard', 'woochat-pro'); ?></strong> <?php esc_html_e('is a Pro feature.', 'woochat-pro'); ?> <button type="button" class="wcwp-open-upgrade-modal" style="margin-left:12px;"><?php esc_html_e('Upgrade', 'woochat-pro'); ?></button></div>
                <?php endif; ?>
                <div class="wcwp-analytics-filters" style="margin:16px 0 8px;display:flex;gap:8px;flex-wrap:wrap;align-items:end;">
                    <div>
                        <label for="wcwp_type"><?php esc_html_e('Type', 'woochat-pro'); ?></label><br>
                        <input type="text" id="wcwp_type" name="wcwp_type" value="<?php echo esc_attr($filters['type']); ?>" placeholder="order, cart_recovery" />
                    </div>
                    <div>
                        <label for="wcwp_status"><?php esc_html_e('Status', 'woochat-pro'); ?></label><br>
                        <input type="text" id="wcwp_status" name="wcwp_status" value="<?php echo esc_attr($filters['status']); ?>" placeholder="sent, failed" />
                    </div>
                    <div>
                        <label for="wcwp_phone"><?php esc_html_e('Phone', 'woochat-pro'); ?></label><br>
                        <input type="text" id="wcwp_phone" name="wcwp_phone" value="<?php echo esc_attr($filters['phone']); ?>" placeholder="last 4 digits" />
                    </div>
                    <div>
                        <label for="wcwp_date_from"><?php esc_html_e('From', 'woochat-pro'); ?></label><br>
                        <input type="date" id="wcwp_date_from" name="wcwp_date_from" value="<?php echo esc_attr($filters['date_from']); ?>" />
                    </div>
                    <div>
                        <label for="wcwp_date_to"><?php esc_html_e('To', 'woochat-pro'); ?></label><br>
                        <input type="date" id="wcwp_date_to" name="wcwp_date_to" value="<?php echo esc_attr($filters['date_to']); ?>" />
                    </div>
                    <div>
                        <button type="button" class="button" id="wcwp-analytics-filter-button"><?php esc_html_e('Filter', 'woochat-pro'); ?></button>
                    </div>
                </div>
                <div class="wcwp-analytics-cards" style="display:flex;gap:12px;flex-wrap:wrap;">
                    <div class="wcwp-analytics-card" style="background:#fff;border:1px solid #e5e5e5;border-radius:10px;padding:14px 16px;min-width:140px;">
                        <div class="wcwp-analytics-label"><?php esc_html_e('Sent', 'woochat-pro'); ?></div>
                        <div class="wcwp-analytics-value"><?php echo esc_html($totals['sent']); ?></div>
                    </div>
                    <div class="wcwp-analytics-card" style="background:#fff;border:1px solid #e5e5e5;border-radius:10px;padding:14px 16px;min-width:140px;">
                        <div class="wcwp-analytics-label"><?php esc_html_e('Delivered', 'woochat-pro'); ?></div>
                        <div class="wcwp-analytics-value"><?php echo esc_html($totals['delivered']); ?></div>
                    </div>
                    <div class="wcwp-analytics-card" style="background:#fff;border:1px solid #e5e5e5;border-radius:10px;padding:14px 16px;min-width:140px;">
                        <div class="wcwp-analytics-label"><?php esc_html_e('Clicked', 'woochat-pro'); ?></div>
                        <div class="wcwp-analytics-value"><?php echo esc_html($totals['clicked']); ?></div>
                    </div>
                </div>
                <h3 style="margin-top:20px;"><?php esc_html_e('Recent Events', 'woochat-pro'); ?></h3>
                <table class="widefat striped" style="margin-top:10px;">
                    <thead>
                        <tr><th><?php esc_html_e('Time', 'woochat-pro'); ?></th><th><?php esc_html_e('Type', 'woochat-pro'); ?></th><th><?php esc_html_e('Status', 'woochat-pro'); ?></th><th><?php esc_html_e('Phone', 'woochat-pro'); ?></th><th><?php esc_html_e('Provider', 'woochat-pro'); ?></th><th><?php esc_html_e('Message ID', 'woochat-pro'); ?></th><th><?php esc_html_e('Preview', 'woochat-pro'); ?></th></tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($events)) : ?>
                            <?php foreach ($events as $evt) : ?>
                                <tr>
                                    <td><?php echo esc_html($evt['time'] ?? ''); ?></td>
                                    <td><?php echo esc_html($evt['type'] ?? ''); ?></td>
                                    <td><?php echo esc_html($evt['status'] ?? ''); ?></td>
                                    <td><?php echo esc_html($evt['phone'] ?? ''); ?></td>
                                    <td><?php echo esc_html($evt['provider'] ?? ''); ?></td>
                                    <td><?php echo esc_html($evt['message_id'] ?? ''); ?></td>
                                    <td><pre style="white-space:pre-line;font-size:0.95em;max-width:320px;overflow-x:auto;"><?php echo esc_html($evt['message_preview'] ?? ''); ?></pre></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr><td colspan="7"><?php esc_html_e('No analytics events logged yet.', 'woochat-pro'); ?></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div id="wcwp-tab-content-scheduler" class="wcwp-tab-content" style="display:none;">
                <?php $is_pro = function_exists('wcwp_is_pro_active') && wcwp_is_pro_active(); ?>
                <?php if (!$is_pro) : ?>
                    <div class="wcwp-pro-banner"><span class="dashicons dashicons-clock"></span> <strong><?php esc_html_e('Scheduler', 'woochat-pro'); ?></strong> <?php esc_html_e('is a Pro feature.', 'woochat-pro'); ?> <button type="button" class="wcwp-open-upgrade-modal" style="margin-left:12px;"><?php esc_html_e('Upgrade', 'woochat-pro'); ?></button></div>
                <?php endif; ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="wcwp_followup_enabled"><?php esc_html_e('Enable Follow-up', 'woochat-pro'); ?></label><span class="wcwp-help-icon">?<span class="wcwp-tooltip"><?php esc_html_e('Schedule a WhatsApp follow-up message after an order is placed.', 'woochat-pro'); ?></span></span></th>
                        <td>
                            <select name="wcwp_followup_enabled" id="wcwp_followup_enabled" <?php disabled(!$is_pro); ?>>
                                <option value="yes" <?php selected(get_option('wcwp_followup_enabled', 'no'), 'yes'); ?>><?php esc_html_e('Yes', 'woochat-pro'); ?></option>
                                <option value="no" <?php selected(get_option('wcwp_followup_enabled', 'no'), 'no'); ?>><?php esc_html_e('No', 'woochat-pro'); ?></option>
                            </select>
                            <p class="description"><?php esc_html_e('Requires valid license. Sends one follow-up per order.', 'woochat-pro'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="wcwp_followup_delay_minutes"><?php esc_html_e('Delay (minutes)', 'woochat-pro'); ?></label><span class="wcwp-help-icon">?<span class="wcwp-tooltip"><?php esc_html_e('How long after order completion/processing should the follow-up be sent?', 'woochat-pro'); ?></span></span></th>
                        <td>
                            <input type="number" min="1" name="wcwp_followup_delay_minutes" id="wcwp_followup_delay_minutes" value="<?php echo esc_attr(get_option('wcwp_followup_delay_minutes', 120)); ?>" class="small-text" <?php disabled(!$is_pro); ?> />
                            <p class="description"><?php esc_html_e('Default: 120 minutes.', 'woochat-pro'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="wcwp_followup_template"><?php esc_html_e('Follow-up Template', 'woochat-pro'); ?></label><span class="wcwp-help-icon">?<span class="wcwp-tooltip"><?php esc_html_e('Placeholders: {name}, {order_id}, {total}, {status}, {date}', 'woochat-pro'); ?></span></span></th>
                        <td>
                            <textarea name="wcwp_followup_template" id="wcwp_followup_template" rows="5" class="large-text" <?php disabled(!$is_pro); ?>><?php echo esc_textarea(get_option('wcwp_followup_template', "Hi {name}, thanks again for your order #{order_id}! Reply if you have any questions.")); ?></textarea>
                            <p class="description"><?php esc_html_e('Sent once per order after the delay.', 'woochat-pro'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="wcwp_followup_use_gpt"><?php esc_html_e('Use GPT (optional)', 'woochat-pro'); ?></label><span class="wcwp-help-icon">?<span class="wcwp-tooltip"><?php esc_html_e('Generate the follow-up copy with your GPT endpoint; falls back to the template if the call fails.', 'woochat-pro'); ?></span></span></th>
                        <td>
                            <select name="wcwp_followup_use_gpt" id="wcwp_followup_use_gpt" <?php disabled(!$is_pro); ?>>
                                <option value="no" <?php selected(get_option('wcwp_followup_use_gpt', 'no'), 'no'); ?>><?php esc_html_e('No', 'woochat-pro'); ?></option>
                                <option value="yes" <?php selected(get_option('wcwp_followup_use_gpt', 'no'), 'yes'); ?>><?php esc_html_e('Yes', 'woochat-pro'); ?></option>
                            </select>
                            <p class="description"><?php esc_html_e('Supports free/alt GPT endpoints by configuring the URL and key below.', 'woochat-pro'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="wcwp_gpt_api_endpoint"><?php esc_html_e('GPT API Endpoint', 'woochat-pro'); ?></label></th>
                        <td>
                            <input type="text" name="wcwp_gpt_api_endpoint" id="wcwp_gpt_api_endpoint" value="<?php echo esc_attr(get_option('wcwp_gpt_api_endpoint', 'https://api.openai.com/v1/chat/completions')); ?>" class="regular-text" <?php disabled(!$is_pro); ?> />
                            <p class="description"><?php esc_html_e('Set to your free/alt GPT API endpoint.', 'woochat-pro'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="wcwp_gpt_api_key"><?php esc_html_e('GPT API Key', 'woochat-pro'); ?></label></th>
                        <td>
                            <input type="password" name="wcwp_gpt_api_key" id="wcwp_gpt_api_key" value="<?php echo esc_attr(get_option('wcwp_gpt_api_key', '')); ?>" class="regular-text" autocomplete="off" <?php disabled(!$is_pro); ?> />
                            <p class="description"><?php esc_html_e('Stored in WordPress options; use a non-production key if possible.', 'woochat-pro'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="wcwp_gpt_model"><?php esc_html_e('GPT Model', 'woochat-pro'); ?></label></th>
                        <td>
                            <input type="text" name="wcwp_gpt_model" id="wcwp_gpt_model" value="<?php echo esc_attr(get_option('wcwp_gpt_model', 'gpt-3.5-turbo')); ?>" class="regular-text" <?php disabled(!$is_pro); ?> />
                            <p class="description"><?php esc_html_e('Adjust for your provider (e.g., gpt-3.5-turbo, gpt-4o-mini, or a free-tier model).', 'woochat-pro'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
            <div id="wcwp-tab-content-license" class="wcwp-tab-content" style="display:none;">
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="wcwp_license_key"><?php esc_html_e('License Key', 'woochat-pro'); ?></label><span class="wcwp-help-icon">?<span class="wcwp-tooltip"><?php esc_html_e('Enter your Pro license key to unlock premium features.', 'woochat-pro'); ?></span></span></th>
                        <td>
                            <input type="text" name="wcwp_license_key" id="wcwp_license_key" value="<?php echo esc_attr(get_option('wcwp_license_key')); ?>" class="regular-text" />
                            <div style="margin-top:8px; display:flex; align-items:center; gap:12px;">
                                <?php $status = get_option('wcwp_license_status', 'inactive'); ?>
                                <span id="wcwp-license-status" class="wcwp-badge <?php echo $status === 'valid' ? 'wcwp-badge-success' : 'wcwp-badge-muted'; ?>">
                                    <?php echo $status === 'valid' ? 'Active' : ucfirst($status); ?>
                                </span>
                                <button type="button" class="button button-primary" id="wcwp-activate-license"><?php esc_html_e('Activate', 'woochat-pro'); ?></button>
                                <button type="button" class="button" id="wcwp-deactivate-license"><?php esc_html_e('Deactivate', 'woochat-pro'); ?></button>
                            </div>
                            <?php
                            $expires = get_option('wcwp_license_expires');
                            $message = get_option('wcwp_license_message');
                            if ($expires) {
                                echo '<p class="description">Expires: ' . esc_html($expires) . '</p>';
                            }
                            if ($message) {
                                echo '<p class="description">' . esc_html($message) . '</p>';
                            }
                            ?>
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
            <h2><?php esc_html_e('Upgrade to WooChat Pro', 'woochat-pro'); ?></h2>
            <p><?php esc_html_e('Unlock all premium features and maximize your store\'s potential!', 'woochat-pro'); ?></p>
            <table class="wcwp-comparison-table">
                <tr><th><?php esc_html_e('Feature', 'woochat-pro'); ?></th><th><?php esc_html_e('Free', 'woochat-pro'); ?></th><th class="pro"><?php esc_html_e('Pro', 'woochat-pro'); ?></th></tr>
                <tr><td><?php esc_html_e('Order Confirmation via WhatsApp', 'woochat-pro'); ?></td><td>✔️</td><td class="pro">✔️</td></tr>
                <tr><td><?php esc_html_e('Manual Message Button', 'woochat-pro'); ?></td><td>✔️</td><td class="pro">✔️</td></tr>
                <tr><td><?php esc_html_e('Cart Recovery', 'woochat-pro'); ?></td><td></td><td class="pro">✔️</td></tr>
                <tr><td><?php esc_html_e('Smart Chatbot Widget', 'woochat-pro'); ?></td><td></td><td class="pro">✔️</td></tr>
                <tr><td><?php esc_html_e('Chatbot Customizer', 'woochat-pro'); ?></td><td></td><td class="pro">✔️</td></tr>
                <tr><td><?php esc_html_e('Scheduled Messages', 'woochat-pro'); ?></td><td></td><td class="pro">✔️</td></tr>
                <tr><td><?php esc_html_e('GPT/AI Auto Replies', 'woochat-pro'); ?></td><td></td><td class="pro">✔️</td></tr>
                <tr><td><?php esc_html_e('Usage Analytics', 'woochat-pro'); ?></td><td></td><td class="pro">✔️</td></tr>
                <tr><td><?php esc_html_e('Widget Shortcodes', 'woochat-pro'); ?></td><td></td><td class="pro">✔️</td></tr>
                <tr><td><?php esc_html_e('Premium Support', 'woochat-pro'); ?></td><td></td><td class="pro">✔️</td></tr>
            </table>
            <a href="https://zignites.com/woochat-pro" target="_blank"><button class="wcwp-upgrade-btn"><?php esc_html_e('Upgrade Now', 'woochat-pro'); ?></button></a>
            <p style="margin-top:10px;font-size:0.97rem;color:#888;"><?php esc_html_e('Already have a license? Enter it in the License tab.', 'woochat-pro'); ?></p>
        </div>
    </div>
    <div class="wcwp-support-form" id="wcwp-support-form">
        <h2><?php esc_html_e('Contact Support', 'woochat-pro'); ?></h2>
        <form id="wcwp-support-contact-form" method="post" action="#" onsubmit="event.preventDefault();document.getElementById('wcwp-support-success').style.display='block';">
            <label for="wcwp-support-name"><?php esc_html_e('Your Name', 'woochat-pro'); ?></label>
            <input type="text" id="wcwp-support-name" name="wcwp-support-name" required>
            <label for="wcwp-support-email"><?php esc_html_e('Your Email', 'woochat-pro'); ?></label>
            <input type="email" id="wcwp-support-email" name="wcwp-support-email" required>
            <label for="wcwp-support-message"><?php esc_html_e('Message', 'woochat-pro'); ?></label>
            <textarea id="wcwp-support-message" name="wcwp-support-message" rows="4" required></textarea>
            <button type="submit" class="button"><?php esc_html_e('Send Message', 'woochat-pro'); ?></button>
            <div class="wcwp-support-success" id="wcwp-support-success" style="display:none;"><?php esc_html_e('Thank you! Your message has been sent. Our team will get back to you soon.', 'woochat-pro'); ?></div>
        </form>
        <div style="margin-top:12px;font-size:0.97rem;color:#888;">
            Or email us at <a href="mailto:support@zignites.com"><?php esc_html_e('support@zignites.com', 'woochat-pro'); ?></a><br>
            <a href="https://zignites.com/feature-request" target="_blank"><?php esc_html_e('Suggest a Feature', 'woochat-pro'); ?></a> &nbsp;|&nbsp; <a href="https://zignites.com/bug-report" target="_blank"><?php esc_html_e('Report a Bug', 'woochat-pro'); ?></a>
        </div>
    </div>
    <?php
}
