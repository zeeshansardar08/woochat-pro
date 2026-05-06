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
    register_setting('wcwp_settings_group', 'wcwp_cloud_app_secret', ['sanitize_callback' => 'wcwp_sanitize_text']);
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
    register_setting('wcwp_settings_group', 'wcwp_chatbot_bg', ['sanitize_callback' => 'wcwp_sanitize_hex_color']);
    register_setting('wcwp_settings_group', 'wcwp_chatbot_text', ['sanitize_callback' => 'wcwp_sanitize_hex_color']);
    register_setting('wcwp_settings_group', 'wcwp_chatbot_icon_color', ['sanitize_callback' => 'wcwp_sanitize_hex_color']);
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

add_action('wp_ajax_wcwp_dismiss_onboarding', 'wcwp_ajax_dismiss_onboarding');
function wcwp_ajax_dismiss_onboarding() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Unauthorized', 'woochat-pro')], 403);
    }
    if (!check_ajax_referer('wcwp_dismiss_onboarding', 'nonce', false)) {
        wp_send_json_error(['message' => __('Bad nonce', 'woochat-pro')], 400);
    }
    update_option('wcwp_onboarding_completed', 'yes', false);
    wp_send_json_success();
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
        'licenseLabels' => [
            'keyRequired'        => __('Key required', 'woochat-pro'),
            'activating'         => __('Activating…', 'woochat-pro'),
            'active'             => __('Active', 'woochat-pro'),
            'activationFailed'   => __('Activation failed', 'woochat-pro'),
            'deactivating'       => __('Deactivating…', 'woochat-pro'),
            'inactive'           => __('Inactive', 'woochat-pro'),
            'deactivationFailed' => __('Deactivation failed', 'woochat-pro'),
        ],
    ]);
    // Onboarding wizard renders once per install — Skip/Finish persists the
    // dismissal flag via admin-ajax, after which the modal stops re-mounting.
    $onboarding_done = get_option('wcwp_onboarding_completed', 'no') === 'yes';
    if (!$onboarding_done) {
        wp_enqueue_style('wcwp-onboarding-css', WCWP_URL . 'assets/css/onboarding.css', [], WCWP_VERSION);
        wp_enqueue_script('wcwp-onboarding-js', WCWP_URL . 'assets/js/onboarding.js', [], WCWP_VERSION, true);
        wp_localize_script('wcwp-onboarding-js', 'wcwpOnboarding', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('wcwp_dismiss_onboarding'),
        ]);
    }

    $views = __DIR__ . '/views';
    ?>
    <?php if (!$onboarding_done) : ?>
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
    <?php endif; ?>
    <div class="wcwp-admin-premium-wrap">
        <h1><?php esc_html_e('WooChat – WhatsApp Settings', 'woochat-pro'); ?></h1>
        <div class="wcwp-dashboard-widget">
            <?php
            $dash_totals = wcwp_analytics_get_totals();
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
                    <div class="wcwp-stat-value"><?php echo esc_html(wcwp_license_status_label($dash_license)); ?></div>
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
            <?php require $views . '/tab-general.php'; ?>
            <?php require $views . '/tab-messaging.php'; ?>
            <?php require $views . '/tab-chatbot.php'; ?>
            <?php require $views . '/tab-cart-recovery.php'; ?>
            <?php require $views . '/tab-analytics.php'; ?>
            <?php require $views . '/tab-scheduler.php'; ?>
            <?php require $views . '/tab-license.php'; ?>
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
        <p><?php esc_html_e('Reach our team using one of the channels below.', 'woochat-pro'); ?></p>
        <div style="margin-top:12px;font-size:0.97rem;color:#888;">
            <?php
            printf(
                /* translators: %s: support email link */
                esc_html__('Email us at %s', 'woochat-pro'),
                '<a href="mailto:support@zignites.com">support@zignites.com</a>'
            );
            ?><br>
            <a href="https://zignites.com/feature-request" target="_blank" rel="noopener"><?php esc_html_e('Suggest a Feature', 'woochat-pro'); ?></a> &nbsp;|&nbsp; <a href="https://zignites.com/bug-report" target="_blank" rel="noopener"><?php esc_html_e('Report a Bug', 'woochat-pro'); ?></a>
        </div>
    </div>
    <?php
}
