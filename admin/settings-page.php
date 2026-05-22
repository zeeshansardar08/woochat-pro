<?php
if (!defined('ABSPATH')) exit;

/* ---------------------------------------------------------------------------
 * Settings registration — one option group per submenu page.
 *
 * Each submenu form posts to options.php, which writes every option that
 * belongs to the submitted group. An option that is registered in the group
 * but absent from the form gets overwritten with an empty value. Splitting the
 * former single "wcwp_settings_group" into per-page groups keeps a Save on one
 * page from wiping options that live on another page.
 * ------------------------------------------------------------------------ */
add_action('admin_init', 'wcwp_register_settings');
function wcwp_register_settings() {
    // General Settings.
    register_setting('wcwp_general_group', 'wcwp_twilio_sid', ['sanitize_callback' => 'wcwp_sanitize_text']);
    register_setting('wcwp_general_group', 'wcwp_twilio_auth_token', ['sanitize_callback' => 'wcwp_sanitize_text']);
    register_setting('wcwp_general_group', 'wcwp_twilio_from', ['sanitize_callback' => 'wcwp_sanitize_text']);
    register_setting('wcwp_general_group', 'wcwp_api_provider', ['sanitize_callback' => 'wcwp_sanitize_provider']);
    register_setting('wcwp_general_group', 'wcwp_cloud_token', ['sanitize_callback' => 'wcwp_sanitize_text']);
    register_setting('wcwp_general_group', 'wcwp_cloud_phone_id', ['sanitize_callback' => 'wcwp_sanitize_text']);
    register_setting('wcwp_general_group', 'wcwp_cloud_from', ['sanitize_callback' => 'wcwp_sanitize_text']);
    register_setting('wcwp_general_group', 'wcwp_cloud_app_secret', ['sanitize_callback' => 'wcwp_sanitize_text']);
    register_setting('wcwp_general_group', 'wcwp_test_mode_enabled', ['sanitize_callback' => 'wcwp_sanitize_yes_no']);
    register_setting('wcwp_general_group', 'wcwp_data_retention_days', ['sanitize_callback' => 'wcwp_sanitize_int']);
    register_setting('wcwp_general_group', 'wcwp_delete_data_on_uninstall', ['sanitize_callback' => 'wcwp_sanitize_yes_no']);
    register_setting('wcwp_general_group', 'wcwp_optout_keywords', ['sanitize_callback' => 'wcwp_sanitize_optout_keywords']);
    register_setting('wcwp_general_group', 'wcwp_optout_list', ['sanitize_callback' => 'wcwp_parse_optout_list']);
    register_setting('wcwp_general_group', 'wcwp_optout_webhook_token', ['sanitize_callback' => 'wcwp_sanitize_text']);

    // Messaging.
    register_setting('wcwp_messaging_group', 'wcwp_test_phone', ['sanitize_callback' => 'wcwp_sanitize_text']);
    register_setting('wcwp_messaging_group', 'wcwp_test_message', ['sanitize_callback' => 'wcwp_sanitize_textarea']);
    register_setting('wcwp_messaging_group', 'wcwp_order_message_template', ['sanitize_callback' => 'wcwp_sanitize_textarea']);
    register_setting('wcwp_messaging_group', 'wcwp_order_message_template_b', ['sanitize_callback' => 'wcwp_sanitize_textarea']);
    register_setting('wcwp_messaging_group', 'wcwp_order_message_ab_enabled', ['sanitize_callback' => 'wcwp_sanitize_yes_no']);

    // Chatbot.
    register_setting('wcwp_chatbot_group', 'wcwp_chatbot_enabled', ['sanitize_callback' => 'wcwp_sanitize_yes_no']);
    register_setting('wcwp_chatbot_group', 'wcwp_chatbot_gpt_enabled', ['sanitize_callback' => 'wcwp_sanitize_yes_no']);
    register_setting('wcwp_chatbot_group', 'wcwp_faq_pairs', ['sanitize_callback' => 'wcwp_sanitize_json_faq']);
    register_setting('wcwp_chatbot_group', 'wcwp_agents', ['sanitize_callback' => 'wcwp_sanitize_agents_json']);
    register_setting('wcwp_chatbot_group', 'wcwp_agent_routing_mode', ['sanitize_callback' => 'wcwp_sanitize_agent_routing_mode']);
    register_setting('wcwp_chatbot_group', 'wcwp_chatbot_bg', ['sanitize_callback' => 'wcwp_sanitize_hex_color']);
    register_setting('wcwp_chatbot_group', 'wcwp_chatbot_text', ['sanitize_callback' => 'wcwp_sanitize_hex_color']);
    register_setting('wcwp_chatbot_group', 'wcwp_chatbot_icon_color', ['sanitize_callback' => 'wcwp_sanitize_hex_color']);
    register_setting('wcwp_chatbot_group', 'wcwp_chatbot_icon', ['sanitize_callback' => 'wcwp_sanitize_text']);
    register_setting('wcwp_chatbot_group', 'wcwp_chatbot_welcome', ['sanitize_callback' => 'wcwp_sanitize_text']);

    // Cart Recovery.
    register_setting('wcwp_cart_recovery_group', 'wcwp_cart_recovery_enabled', ['sanitize_callback' => 'wcwp_sanitize_yes_no']);
    register_setting('wcwp_cart_recovery_group', 'wcwp_cart_recovery_delay', ['sanitize_callback' => 'wcwp_sanitize_int']);
    register_setting('wcwp_cart_recovery_group', 'wcwp_cart_recovery_message', ['sanitize_callback' => 'wcwp_sanitize_textarea']);
    register_setting('wcwp_cart_recovery_group', 'wcwp_cart_recovery_message_b', ['sanitize_callback' => 'wcwp_sanitize_textarea']);
    register_setting('wcwp_cart_recovery_group', 'wcwp_cart_recovery_require_consent', ['sanitize_callback' => 'wcwp_sanitize_yes_no']);
    register_setting('wcwp_cart_recovery_group', 'wcwp_cart_recovery_ab_enabled', ['sanitize_callback' => 'wcwp_sanitize_yes_no']);

    // Scheduler.
    register_setting('wcwp_scheduler_group', 'wcwp_followup_enabled', ['sanitize_callback' => 'wcwp_sanitize_yes_no']);
    register_setting('wcwp_scheduler_group', 'wcwp_followup_delay_minutes', ['sanitize_callback' => 'wcwp_sanitize_int']);
    register_setting('wcwp_scheduler_group', 'wcwp_followup_template', ['sanitize_callback' => 'wcwp_sanitize_textarea']);
    register_setting('wcwp_scheduler_group', 'wcwp_followup_template_b', ['sanitize_callback' => 'wcwp_sanitize_textarea']);
    register_setting('wcwp_scheduler_group', 'wcwp_followup_ab_enabled', ['sanitize_callback' => 'wcwp_sanitize_yes_no']);
    register_setting('wcwp_scheduler_group', 'wcwp_followup_use_gpt', ['sanitize_callback' => 'wcwp_sanitize_yes_no']);
    register_setting('wcwp_scheduler_group', 'wcwp_gpt_api_endpoint', ['sanitize_callback' => 'wcwp_sanitize_url']);
    register_setting('wcwp_scheduler_group', 'wcwp_gpt_api_key', ['sanitize_callback' => 'wcwp_sanitize_text']);
    register_setting('wcwp_scheduler_group', 'wcwp_gpt_model', ['sanitize_callback' => 'wcwp_sanitize_text']);

    // License.
    register_setting('wcwp_license_group', 'wcwp_license_key', ['sanitize_callback' => 'wcwp_sanitize_text']);
}

/* ---------------------------------------------------------------------------
 * Admin menu — one top-level menu with a submenu page per feature area.
 * ------------------------------------------------------------------------ */
add_action('admin_menu', 'wcwp_register_admin_menus');
function wcwp_register_admin_menus() {
    add_menu_page(
        __('WooChat', 'woochat'),
        __('WooChat', 'woochat'),
        'manage_options',
        'wcwp-dashboard',
        'wcwp_render_dashboard_page',
        'dashicons-format-chat',
        66
    );

    $pro_star = wcwp_is_pro_active() ? '' : ' ★';

    $submenus = [
        ['wcwp-dashboard',     __('Dashboard', 'woochat'),        'wcwp_render_dashboard_page'],
        ['wcwp-general',       __('General Settings', 'woochat'), 'wcwp_render_general_page'],
        ['wcwp-messaging',     __('Messaging', 'woochat'),        'wcwp_render_messaging_page'],
        ['wcwp-chatbot',       __('Chatbot', 'woochat'),          'wcwp_render_chatbot_page'],
        ['wcwp-cart-recovery', __('Cart Recovery', 'woochat'),    'wcwp_render_cart_recovery_page', true],
        ['wcwp-scheduler',     __('Scheduler', 'woochat'),        'wcwp_render_scheduler_page',     true],
        ['wcwp-campaigns',     __('Campaigns', 'woochat'),        'wcwp_render_campaigns_page',     true],
        ['wcwp-analytics',     __('Analytics', 'woochat'),        'wcwp_render_analytics_page',     true],
        ['wcwp-logs',          __('Logs', 'woochat'),             'wcwp_render_logs_page'],
        ['wcwp-webhooks',      __('Webhooks', 'woochat'),         'wcwp_render_webhooks_page',      true],
        ['wcwp-license',       __('License', 'woochat'),          'wcwp_render_license_page'],
    ];

    foreach ($submenus as $submenu) {
        list($slug, $title, $callback) = $submenu;
        $is_pro_page = !empty($submenu[3]);
        // Mark Pro-only pages with a star for free users so the upsell is
        // visible straight from the menu.
        $menu_title = ($is_pro_page && !wcwp_is_pro_active())
            ? $title . ' ★'
            : $title;

        add_submenu_page(
            'wcwp-dashboard',
            $title,
            $menu_title,
            'manage_options',
            $slug,
            $callback
        );
    }
}

/* ---------------------------------------------------------------------------
 * Asset loading — common admin CSS/JS on every WooChat page, plus the
 * page-specific scripts only where they are used.
 * ------------------------------------------------------------------------ */
add_action('admin_enqueue_scripts', 'wcwp_enqueue_admin_scripts');
function wcwp_enqueue_admin_scripts($hook) {
    if (strpos($hook, 'wcwp-') === false) {
        return;
    }

    wp_enqueue_style('wcwp-admin-premium-css', WCWP_URL . 'assets/css/admin-premium.css', [], WCWP_VERSION);
    wp_enqueue_script('wcwp-admin-premium-js', WCWP_URL . 'assets/js/admin-premium.js', [], WCWP_VERSION, true);
    wp_localize_script('wcwp-admin-premium-js', 'wcwpAdminData', [
        'ajaxUrl'      => admin_url('admin-ajax.php'),
        'resendNonce'  => wp_create_nonce('wcwp_resend_cart'),
        'licenseNonce' => wp_create_nonce('wcwp_license_nonce'),
        'testNonce'    => wp_create_nonce('wcwp_test_message'),
        'licenseLabels' => [
            'keyRequired'        => __('Key required', 'woochat'),
            'activating'         => __('Activating…', 'woochat'),
            'active'             => __('Active', 'woochat'),
            'activationFailed'   => __('Activation failed', 'woochat'),
            'deactivating'       => __('Deactivating…', 'woochat'),
            'inactive'           => __('Inactive', 'woochat'),
            'deactivationFailed' => __('Deactivation failed', 'woochat'),
        ],
        'logClearConfirm'      => __('Clear the log file? This cannot be undone.', 'woochat'),
        'webhookTestNonce'     => wp_create_nonce('wcwp_webhook_test'),
        'webhookDeleteConfirm' => __('Delete this webhook? Receivers will stop getting events immediately.', 'woochat'),
        'webhookTesting'       => __('Testing…', 'woochat'),
        'webhookTestLabel'     => __('Test fire', 'woochat'),
    ]);

    // Dashboard — first-run onboarding wizard, shown once per install.
    if (strpos($hook, 'wcwp-dashboard') !== false && get_option('wcwp_onboarding_completed', 'no') !== 'yes') {
        wp_enqueue_style('wcwp-onboarding-css', WCWP_URL . 'assets/css/onboarding.css', [], WCWP_VERSION);
        wp_enqueue_script('wcwp-onboarding-js', WCWP_URL . 'assets/js/onboarding.js', [], WCWP_VERSION, true);
        wp_localize_script('wcwp-onboarding-js', 'wcwpOnboarding', [
            'ajaxUrl'      => admin_url('admin-ajax.php'),
            'dismissNonce' => wp_create_nonce('wcwp_dismiss_onboarding'),
            'saveNonce'    => wp_create_nonce('wcwp_save_onboarding'),
            'i18n'         => [
                'saveError'    => __('Could not save. Please check the highlighted fields.', 'woochat'),
                'networkError' => __('Network error. Please try again.', 'woochat'),
                'saving'       => __('Saving…', 'woochat'),
                'next'         => __('Next', 'woochat'),
            ],
        ]);
    }

    // Campaigns page.
    if (strpos($hook, 'wcwp-campaigns') !== false) {
        wp_enqueue_script('wcwp-campaigns-js', WCWP_URL . 'assets/js/campaigns.js', [], WCWP_VERSION, true);
        wp_localize_script('wcwp-campaigns-js', 'wcwpCampaigns', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('wcwp_campaigns'),
            'i18n'    => [
                'submitting'   => __('Creating campaign…', 'woochat'),
                'create'       => __('Create campaign', 'woochat'),
                'genericError' => __('Could not create campaign. Please try again.', 'woochat'),
                'completed'    => __('Completed', 'woochat'),
                'running'      => __('Running', 'woochat'),
                'queued'       => __('Queued', 'woochat'),
            ],
        ]);
    }

    // Pages that expose the template-library browser.
    if (strpos($hook, 'wcwp-messaging') !== false
        || strpos($hook, 'wcwp-cart-recovery') !== false
        || strpos($hook, 'wcwp-scheduler') !== false) {
        wp_enqueue_script('wcwp-template-library-js', WCWP_URL . 'assets/js/template-library.js', [], WCWP_VERSION, true);
        wp_localize_script('wcwp-template-library-js', 'wcwpTemplateLibraryI18n', [
            'empty' => __('No templates available for this section yet.', 'woochat'),
        ]);
    }
}

/* ---------------------------------------------------------------------------
 * Shared page chrome.
 * ------------------------------------------------------------------------ */

/**
 * Open the standard WooChat admin page wrapper.
 *
 * @param string $title Page heading, already translated.
 */
function wcwp_admin_page_open($title) {
    echo '<div class="wrap wcwp-admin-premium-wrap wcwp-admin-wrap">';
    echo '<h1>' . esc_html($title) . '</h1>';
}

function wcwp_admin_page_close() {
    echo '</div>';
}

/**
 * Render a settings submenu: heading, options.php form, and the section view.
 *
 * @param string $title             Page heading.
 * @param string $group             Registered settings group for settings_fields().
 * @param string $view              View file name inside admin/views/.
 * @param bool   $with_template_lib Whether to append the template-library modal.
 */
function wcwp_render_settings_view($title, $group, $view, $with_template_lib = false) {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Unauthorized', 'woochat'));
    }
    wcwp_admin_page_open($title);
    echo '<form method="post" action="options.php">';
    settings_fields($group);
    require WCWP_PATH . 'admin/views/' . $view;
    submit_button();
    echo '</form>';
    if ($with_template_lib) {
        require WCWP_PATH . 'admin/views/template-library-modal.php';
    }
    wcwp_admin_page_close();
}

/* ---------------------------------------------------------------------------
 * Page render callbacks.
 * ------------------------------------------------------------------------ */

function wcwp_render_dashboard_page() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Unauthorized', 'woochat'));
    }
    wcwp_admin_page_open(__('WooChat Dashboard', 'woochat'));
    require WCWP_PATH . 'admin/views/dashboard.php';
    wcwp_admin_page_close();
}

function wcwp_render_general_page() {
    wcwp_render_settings_view(__('General Settings', 'woochat'), 'wcwp_general_group', 'tab-general.php');
}

function wcwp_render_messaging_page() {
    wcwp_render_settings_view(__('Messaging', 'woochat'), 'wcwp_messaging_group', 'tab-messaging.php', true);
}

function wcwp_render_chatbot_page() {
    wcwp_render_settings_view(__('Chatbot', 'woochat'), 'wcwp_chatbot_group', 'tab-chatbot.php');
}

function wcwp_render_license_page() {
    wcwp_render_settings_view(__('License', 'woochat'), 'wcwp_license_group', 'tab-license.php');
}

function wcwp_render_cart_recovery_page() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Unauthorized', 'woochat'));
    }
    wcwp_admin_page_open(__('Cart Recovery', 'woochat'));
    if (!wcwp_is_pro_active()) {
        wcwp_render_pro_upgrade_notice('cart-recovery');
        wcwp_admin_page_close();
        return;
    }
    echo '<form method="post" action="options.php">';
    settings_fields('wcwp_cart_recovery_group');
    require WCWP_PATH . 'admin/views/tab-cart-recovery.php';
    submit_button();
    echo '</form>';
    require WCWP_PATH . 'admin/views/template-library-modal.php';
    wcwp_admin_page_close();
}

function wcwp_render_scheduler_page() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Unauthorized', 'woochat'));
    }
    wcwp_admin_page_open(__('Scheduler', 'woochat'));
    if (!wcwp_is_pro_active()) {
        wcwp_render_pro_upgrade_notice('scheduler');
        wcwp_admin_page_close();
        return;
    }
    echo '<form method="post" action="options.php">';
    settings_fields('wcwp_scheduler_group');
    require WCWP_PATH . 'admin/views/tab-scheduler.php';
    submit_button();
    echo '</form>';
    require WCWP_PATH . 'admin/views/template-library-modal.php';
    wcwp_admin_page_close();
}

function wcwp_render_campaigns_page() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Unauthorized', 'woochat'));
    }
    wcwp_admin_page_open(__('Campaigns', 'woochat'));
    if (!wcwp_is_pro_active()) {
        wcwp_render_pro_upgrade_notice('campaigns');
        wcwp_admin_page_close();
        return;
    }
    require WCWP_PATH . 'admin/views/tab-campaigns.php';
    wcwp_admin_page_close();
}

function wcwp_render_analytics_page() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Unauthorized', 'woochat'));
    }
    wcwp_admin_page_open(__('Analytics', 'woochat'));
    if (!wcwp_is_pro_active()) {
        wcwp_render_pro_upgrade_notice('analytics');
        wcwp_admin_page_close();
        return;
    }
    require WCWP_PATH . 'admin/views/tab-analytics.php';
    wcwp_admin_page_close();
}

function wcwp_render_webhooks_page() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Unauthorized', 'woochat'));
    }
    wcwp_admin_page_open(__('Webhooks', 'woochat'));
    if (!wcwp_is_pro_active()) {
        wcwp_render_pro_upgrade_notice('webhooks');
        wcwp_admin_page_close();
        return;
    }
    require WCWP_PATH . 'admin/views/tab-webhooks.php';
    wcwp_admin_page_close();
}

function wcwp_render_logs_page() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Unauthorized', 'woochat'));
    }
    wcwp_admin_page_open(__('Logs', 'woochat'));
    require WCWP_PATH . 'admin/views/tab-logs.php';
    wcwp_admin_page_close();
}

/* ---------------------------------------------------------------------------
 * Pro upsell — reusable upgrade card shown on Pro-gated pages.
 * ------------------------------------------------------------------------ */

/**
 * Print the Pro upgrade card for a gated feature.
 *
 * @param string $feature Feature key: cart-recovery, scheduler, campaigns,
 *                        analytics, or webhooks.
 */
function wcwp_render_pro_upgrade_notice($feature = '') {
    $features = [
        'cart-recovery' => [
            'title'       => __('Cart Recovery via WhatsApp', 'woochat'),
            'description' => __('Recover abandoned carts by sending automated WhatsApp reminders. WhatsApp has 90%+ open rates vs 20% for email.', 'woochat'),
            'benefits'    => [
                __('Automated cart abandonment detection', 'woochat'),
                __('Customizable recovery message templates', 'woochat'),
                __('Smart timing with configurable delays', 'woochat'),
                __('Recovery analytics and conversion tracking', 'woochat'),
                __('A/B testing for recovery messages', 'woochat'),
            ],
        ],
        'scheduler' => [
            'title'       => __('Follow-up Scheduler', 'woochat'),
            'description' => __('Automatically send follow-up messages after orders to boost reviews, repeat purchases, and customer loyalty.', 'woochat'),
            'benefits'    => [
                __('Post-purchase follow-up automation', 'woochat'),
                __('Configurable delay timing', 'woochat'),
                __('GPT-powered personalized messages', 'woochat'),
                __('A/B testing for follow-up templates', 'woochat'),
            ],
        ],
        'campaigns' => [
            'title'       => __('Bulk Campaigns', 'woochat'),
            'description' => __('Send targeted WhatsApp campaigns to customer segments. Perfect for promotions, announcements, and re-engagement.', 'woochat'),
            'benefits'    => [
                __('Customer segmentation (all customers, recent orders)', 'woochat'),
                __('Campaign performance tracking', 'woochat'),
                __('Rate-limited sending to protect your number', 'woochat'),
                __('Template-based campaign messages', 'woochat'),
            ],
        ],
        'analytics' => [
            'title'       => __('Analytics Dashboard', 'woochat'),
            'description' => __('Track every WhatsApp message with delivery rates, click tracking, conversion attribution, and A/B test results.', 'woochat'),
            'benefits'    => [
                __('Message delivery and open rate tracking', 'woochat'),
                __('Click-through rate monitoring', 'woochat'),
                __('Revenue attribution from WhatsApp messages', 'woochat'),
                __('A/B test performance comparison', 'woochat'),
                __('Exportable reports', 'woochat'),
            ],
        ],
        'webhooks' => [
            'title'       => __('Webhooks & Integrations', 'woochat'),
            'description' => __('Connect WooChat to external services with webhooks. Receive delivery status callbacks and integrate with your stack.', 'woochat'),
            'benefits'    => [
                __('Incoming webhook processing', 'woochat'),
                __('Delivery status callbacks', 'woochat'),
                __('Custom webhook endpoints', 'woochat'),
                __('Opt-out processing via webhook', 'woochat'),
            ],
        ],
    ];

    $f = isset($features[$feature]) ? $features[$feature] : $features['cart-recovery'];
    ?>
    <div class="wcwp-pro-upgrade-card">
        <div class="wcwp-pro-upgrade-icon">🔒</div>
        <h2><?php echo esc_html($f['title']); ?></h2>
        <p class="wcwp-pro-upgrade-desc"><?php echo esc_html($f['description']); ?></p>
        <ul class="wcwp-pro-upgrade-benefits">
            <?php foreach ($f['benefits'] as $benefit) : ?>
                <li><?php echo esc_html($benefit); ?></li>
            <?php endforeach; ?>
        </ul>
        <a href="https://zignites.com/woochat" class="button button-primary button-hero" target="_blank" rel="noopener">
            <?php esc_html_e('Upgrade to WooChat Pro', 'woochat'); ?>
        </a>
        <p class="wcwp-pro-upgrade-price">
            <?php esc_html_e('Starting at $49/year for a single site', 'woochat'); ?>
        </p>
    </div>
    <?php
}

/**
 * Print the upgrade comparison modal. Hooked into admin_footer so any
 * .wcwp-open-upgrade-modal trigger on a WooChat page can open it.
 */
function wcwp_render_upgrade_modal() {
    ?>
    <div id="wcwp-upgrade-modal">
        <div class="wcwp-upgrade-modal-content">
            <button class="wcwp-upgrade-modal-close" aria-label="<?php esc_attr_e('Close', 'woochat'); ?>">&times;</button>
            <h2><?php esc_html_e('Upgrade to WooChat Pro', 'woochat'); ?></h2>
            <p><?php esc_html_e("Unlock all premium features and maximize your store's potential!", 'woochat'); ?></p>
            <table class="wcwp-comparison-table">
                <tr><th><?php esc_html_e('Feature', 'woochat'); ?></th><th><?php esc_html_e('Free', 'woochat'); ?></th><th class="pro"><?php esc_html_e('Pro', 'woochat'); ?></th></tr>
                <tr><td><?php esc_html_e('Order Notifications via WhatsApp', 'woochat'); ?></td><td>✔️</td><td class="pro">✔️</td></tr>
                <tr><td><?php esc_html_e('Manual Message Button', 'woochat'); ?></td><td>✔️</td><td class="pro">✔️</td></tr>
                <tr><td><?php esc_html_e('WhatsApp Chat Widget', 'woochat'); ?></td><td>✔️</td><td class="pro">✔️</td></tr>
                <tr><td><?php esc_html_e('Gutenberg Blocks', 'woochat'); ?></td><td>✔️</td><td class="pro">✔️</td></tr>
                <tr><td><?php esc_html_e('Twilio &amp; Cloud API', 'woochat'); ?></td><td>✔️</td><td class="pro">✔️</td></tr>
                <tr><td><?php esc_html_e('Message Logs', 'woochat'); ?></td><td><?php esc_html_e('Last 50', 'woochat'); ?></td><td class="pro"><?php esc_html_e('Unlimited + export', 'woochat'); ?></td></tr>
                <tr><td><?php esc_html_e('Cart Recovery', 'woochat'); ?></td><td></td><td class="pro">✔️</td></tr>
                <tr><td><?php esc_html_e('Follow-up Scheduler', 'woochat'); ?></td><td></td><td class="pro">✔️</td></tr>
                <tr><td><?php esc_html_e('Bulk Campaigns', 'woochat'); ?></td><td></td><td class="pro">✔️</td></tr>
                <tr><td><?php esc_html_e('Analytics Dashboard', 'woochat'); ?></td><td></td><td class="pro">✔️</td></tr>
                <tr><td><?php esc_html_e('A/B Testing', 'woochat'); ?></td><td></td><td class="pro">✔️</td></tr>
                <tr><td><?php esc_html_e('GPT/AI Chatbot Replies', 'woochat'); ?></td><td></td><td class="pro">✔️</td></tr>
                <tr><td><?php esc_html_e('Chatbot Customizer &amp; Multi-Agent', 'woochat'); ?></td><td></td><td class="pro">✔️</td></tr>
                <tr><td><?php esc_html_e('Webhooks &amp; Integrations', 'woochat'); ?></td><td></td><td class="pro">✔️</td></tr>
                <tr><td><?php esc_html_e('Premium Support', 'woochat'); ?></td><td></td><td class="pro">✔️</td></tr>
            </table>
            <a href="https://zignites.com/woochat" target="_blank" rel="noopener"><button class="wcwp-upgrade-btn"><?php esc_html_e('Upgrade Now', 'woochat'); ?></button></a>
            <p style="margin-top:10px;font-size:0.97rem;color:#888;"><?php esc_html_e('Already have a license? Enter it on the License page.', 'woochat'); ?></p>
        </div>
    </div>
    <?php
}

add_action('admin_footer', 'wcwp_render_admin_footer_modals');
function wcwp_render_admin_footer_modals() {
    $screen = get_current_screen();
    if (!$screen || strpos($screen->id, 'wcwp-') === false) {
        return;
    }
    wcwp_render_upgrade_modal();
}

/**
 * One-time, dismissible Pro upsell notice shown after activation.
 */
add_action('admin_notices', 'wcwp_pro_upsell_admin_notice');
function wcwp_pro_upsell_admin_notice() {
    if (!current_user_can('manage_options') || wcwp_is_pro_active()) {
        return;
    }
    if (get_option('wcwp_pro_notice_dismissed') === 'yes') {
        return;
    }

    $dismiss_url = wp_nonce_url(
        add_query_arg('wcwp_dismiss_pro_notice', '1'),
        'wcwp_dismiss_pro_notice'
    );
    ?>
    <div class="notice notice-info wcwp-pro-upsell-notice">
        <p>
            <strong><?php esc_html_e('WooChat is active.', 'woochat'); ?></strong>
            <?php esc_html_e('Upgrade to WooChat Pro for cart recovery, analytics, bulk campaigns and A/B testing.', 'woochat'); ?>
        </p>
        <p>
            <a href="https://zignites.com/woochat" class="button button-primary" target="_blank" rel="noopener"><?php esc_html_e('Upgrade Now', 'woochat'); ?></a>
            <a href="<?php echo esc_url($dismiss_url); ?>" class="button"><?php esc_html_e('Dismiss', 'woochat'); ?></a>
        </p>
    </div>
    <?php
}

/**
 * Persist dismissal of the Pro upsell notice.
 */
add_action('admin_init', 'wcwp_handle_pro_notice_dismiss');
function wcwp_handle_pro_notice_dismiss() {
    if (!isset($_GET['wcwp_dismiss_pro_notice']) || !current_user_can('manage_options')) {
        return;
    }
    $nonce = isset($_GET['_wpnonce']) ? sanitize_text_field(wp_unslash($_GET['_wpnonce'])) : '';
    if (!wp_verify_nonce($nonce, 'wcwp_dismiss_pro_notice')) {
        return;
    }
    update_option('wcwp_pro_notice_dismissed', 'yes', false);
    wp_safe_redirect(remove_query_arg(['wcwp_dismiss_pro_notice', '_wpnonce']));
    exit;
}

/**
 * Print the first-run onboarding wizard modal. Rendered on the Dashboard
 * until the admin completes or skips it.
 */
function wcwp_render_onboarding_modal() {
    if (get_option('wcwp_onboarding_completed', 'no') === 'yes') {
        return;
    }

    // Prefill from existing options so a re-run of the wizard (e.g. after a
    // clean reinstall with kept data) doesn't force the admin to retype.
    $ob_provider = get_option('wcwp_api_provider', 'twilio');
    if (!in_array($ob_provider, ['twilio', 'cloud'], true)) {
        $ob_provider = 'twilio';
    }
    $ob_twilio_sid   = get_option('wcwp_twilio_sid', '');
    $ob_twilio_token = get_option('wcwp_twilio_auth_token', '');
    $ob_twilio_from  = get_option('wcwp_twilio_from', '');
    $ob_cloud_token  = get_option('wcwp_cloud_token', '');
    $ob_cloud_phone  = get_option('wcwp_cloud_phone_id', '');
    $ob_cloud_from   = get_option('wcwp_cloud_from', '');
    ?>
    <div id="wcwp-onboarding-modal">
        <div class="wcwp-onboarding-content">
            <div class="wcwp-onboarding-progress"><div class="wcwp-onboarding-progress-inner"></div></div>

            <div class="wcwp-onboarding-step" data-step="welcome">
                <h2><?php esc_html_e('Welcome to WooChat!', 'woochat'); ?></h2>
                <p><?php esc_html_e("Let's connect your WhatsApp account in a couple of steps.", 'woochat'); ?></p>
            </div>

            <div class="wcwp-onboarding-step" data-step="provider">
                <h2><?php esc_html_e('Choose your WhatsApp provider', 'woochat'); ?></h2>
                <p><?php esc_html_e('Pick the API you have an account with. You can change this later.', 'woochat'); ?></p>
                <div class="wcwp-onboarding-provider-choices">
                    <label class="wcwp-onboarding-provider-choice">
                        <input type="radio" name="wcwp_ob_provider" value="twilio" <?php checked($ob_provider, 'twilio'); ?> />
                        <span class="wcwp-onboarding-provider-title"><?php esc_html_e('Twilio', 'woochat'); ?></span>
                        <span class="wcwp-onboarding-provider-desc"><?php esc_html_e('WhatsApp via Twilio Programmable Messaging.', 'woochat'); ?></span>
                    </label>
                    <label class="wcwp-onboarding-provider-choice">
                        <input type="radio" name="wcwp_ob_provider" value="cloud" <?php checked($ob_provider, 'cloud'); ?> />
                        <span class="wcwp-onboarding-provider-title"><?php esc_html_e('Meta Cloud API', 'woochat'); ?></span>
                        <span class="wcwp-onboarding-provider-desc"><?php esc_html_e('WhatsApp Business Platform direct from Meta.', 'woochat'); ?></span>
                    </label>
                </div>
            </div>

            <div class="wcwp-onboarding-step" data-step="credentials">
                <h2><?php esc_html_e('Enter your credentials', 'woochat'); ?></h2>
                <p class="wcwp-onboarding-step-hint" data-provider-hint="twilio"><?php esc_html_e('Find these in your Twilio Console.', 'woochat'); ?></p>
                <p class="wcwp-onboarding-step-hint" data-provider-hint="cloud"><?php esc_html_e('Find these in your Meta for Developers app.', 'woochat'); ?></p>

                <div class="wcwp-onboarding-fields" data-provider-fields="twilio">
                    <div class="wcwp-onboarding-field">
                        <label for="wcwp_ob_twilio_sid"><?php esc_html_e('Account SID', 'woochat'); ?></label>
                        <input type="text" id="wcwp_ob_twilio_sid" name="twilio_sid" value="<?php echo esc_attr($ob_twilio_sid); ?>" autocomplete="off" />
                        <span class="wcwp-onboarding-field-error" data-error-for="twilio_sid"></span>
                    </div>
                    <div class="wcwp-onboarding-field">
                        <label for="wcwp_ob_twilio_token"><?php esc_html_e('Auth Token', 'woochat'); ?></label>
                        <input type="password" id="wcwp_ob_twilio_token" name="twilio_token" value="<?php echo esc_attr($ob_twilio_token); ?>" autocomplete="off" />
                        <span class="wcwp-onboarding-field-error" data-error-for="twilio_token"></span>
                    </div>
                    <div class="wcwp-onboarding-field">
                        <label for="wcwp_ob_twilio_from"><?php esc_html_e('From Number', 'woochat'); ?></label>
                        <input type="text" id="wcwp_ob_twilio_from" name="twilio_from" value="<?php echo esc_attr($ob_twilio_from); ?>" placeholder="whatsapp:+14155238886" autocomplete="off" />
                        <span class="wcwp-onboarding-field-error" data-error-for="twilio_from"></span>
                    </div>
                </div>

                <div class="wcwp-onboarding-fields" data-provider-fields="cloud">
                    <div class="wcwp-onboarding-field">
                        <label for="wcwp_ob_cloud_token"><?php esc_html_e('Access Token', 'woochat'); ?></label>
                        <input type="password" id="wcwp_ob_cloud_token" name="cloud_token" value="<?php echo esc_attr($ob_cloud_token); ?>" autocomplete="off" />
                        <span class="wcwp-onboarding-field-error" data-error-for="cloud_token"></span>
                    </div>
                    <div class="wcwp-onboarding-field">
                        <label for="wcwp_ob_cloud_phone_id"><?php esc_html_e('Phone Number ID', 'woochat'); ?></label>
                        <input type="text" id="wcwp_ob_cloud_phone_id" name="cloud_phone_id" value="<?php echo esc_attr($ob_cloud_phone); ?>" autocomplete="off" />
                        <span class="wcwp-onboarding-field-error" data-error-for="cloud_phone_id"></span>
                    </div>
                    <div class="wcwp-onboarding-field">
                        <label for="wcwp_ob_cloud_from"><?php esc_html_e('From Number', 'woochat'); ?></label>
                        <input type="text" id="wcwp_ob_cloud_from" name="cloud_from" value="<?php echo esc_attr($ob_cloud_from); ?>" placeholder="+14155238886" autocomplete="off" />
                        <span class="wcwp-onboarding-field-error" data-error-for="cloud_from"></span>
                    </div>
                </div>

                <div class="wcwp-onboarding-form-error" role="alert"></div>
            </div>

            <div class="wcwp-onboarding-step" data-step="done">
                <h2><?php esc_html_e('All set!', 'woochat'); ?></h2>
                <p><?php esc_html_e('Your provider is connected. Use the menu on the left to enable the chatbot and customize your messages.', 'woochat'); ?></p>
            </div>

            <div class="wcwp-onboarding-buttons">
                <button type="button" class="wcwp-onboarding-prev"><?php esc_html_e('Back', 'woochat'); ?></button>
                <button type="button" class="wcwp-onboarding-next"><?php esc_html_e('Next', 'woochat'); ?></button>
                <button type="button" class="wcwp-onboarding-finish"><?php esc_html_e('Finish', 'woochat'); ?></button>
                <button type="button" class="wcwp-onboarding-skip"><?php esc_html_e('Skip', 'woochat'); ?></button>
            </div>
        </div>
    </div>
    <?php
}

/* ---------------------------------------------------------------------------
 * AJAX handlers.
 * ------------------------------------------------------------------------ */

add_action('wp_ajax_wcwp_dismiss_onboarding', 'wcwp_ajax_dismiss_onboarding');
function wcwp_ajax_dismiss_onboarding() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Unauthorized', 'woochat')], 403);
    }
    if (!check_ajax_referer('wcwp_dismiss_onboarding', 'nonce', false)) {
        wp_send_json_error(['message' => __('Bad nonce', 'woochat')], 400);
    }
    update_option('wcwp_onboarding_completed', 'yes', false);
    wp_send_json_success();
}

add_action('wp_ajax_wcwp_create_campaign', 'wcwp_ajax_create_campaign');
function wcwp_ajax_create_campaign() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Unauthorized', 'woochat')], 403);
    }
    if (!check_ajax_referer('wcwp_campaigns', 'nonce', false)) {
        wp_send_json_error(['message' => __('Bad nonce', 'woochat')], 400);
    }

    $name         = isset($_POST['name']) ? wcwp_sanitize_text(wp_unslash($_POST['name'])) : '';
    $template     = isset($_POST['template']) ? wcwp_sanitize_textarea(wp_unslash($_POST['template'])) : '';
    $segment_type = isset($_POST['segment_type']) ? sanitize_key(wp_unslash($_POST['segment_type'])) : '';
    $days         = isset($_POST['segment_days']) ? max(1, (int) $_POST['segment_days']) : 30;

    $segment_meta = $segment_type === 'recent_orders' ? ['days' => $days] : [];

    $result = wcwp_campaign_create([
        'name'         => $name,
        'template'     => $template,
        'segment_type' => $segment_type,
        'segment_meta' => $segment_meta,
    ]);

    if (is_wp_error($result)) {
        wp_send_json_error(['message' => $result->get_error_message()], 422);
    }

    wp_send_json_success(['campaign_id' => (int) $result]);
}

add_action('wp_ajax_wcwp_campaign_status', 'wcwp_ajax_campaign_status');
function wcwp_ajax_campaign_status() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Unauthorized', 'woochat')], 403);
    }
    if (!check_ajax_referer('wcwp_campaigns', 'nonce', false)) {
        wp_send_json_error(['message' => __('Bad nonce', 'woochat')], 400);
    }

    $id = isset($_GET['campaign_id']) ? (int) $_GET['campaign_id'] : 0;
    $campaign = wcwp_campaign_get($id);
    if (!$campaign) {
        wp_send_json_error(['message' => __('Not found', 'woochat')], 404);
    }

    wp_send_json_success([
        'id'            => (int) $campaign['id'],
        'status'        => $campaign['status'],
        'total_count'   => (int) $campaign['total_count'],
        'sent_count'    => (int) $campaign['sent_count'],
        'failed_count'  => (int) $campaign['failed_count'],
        'skipped_count' => (int) $campaign['skipped_count'],
    ]);
}

add_action('wp_ajax_wcwp_save_onboarding_credentials', 'wcwp_ajax_save_onboarding_credentials');
function wcwp_ajax_save_onboarding_credentials() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Unauthorized', 'woochat')], 403);
    }
    if (!check_ajax_referer('wcwp_save_onboarding', 'nonce', false)) {
        wp_send_json_error(['message' => __('Bad nonce', 'woochat')], 400);
    }

    $provider_raw = isset($_POST['provider']) ? sanitize_text_field(wp_unslash($_POST['provider'])) : '';
    if (!in_array($provider_raw, ['twilio', 'cloud'], true)) {
        wp_send_json_error(['message' => __('Choose a provider before continuing.', 'woochat')], 422);
    }

    $errors = [];

    if ($provider_raw === 'twilio') {
        $sid   = isset($_POST['twilio_sid'])   ? wcwp_sanitize_text(wp_unslash($_POST['twilio_sid']))   : '';
        $token = isset($_POST['twilio_token']) ? wcwp_sanitize_text(wp_unslash($_POST['twilio_token'])) : '';
        $from  = isset($_POST['twilio_from'])  ? wcwp_sanitize_text(wp_unslash($_POST['twilio_from']))  : '';

        if ($sid === '')   { $errors['twilio_sid']   = __('Twilio Account SID is required.', 'woochat'); }
        if ($token === '') { $errors['twilio_token'] = __('Twilio Auth Token is required.', 'woochat'); }
        if ($from === '')  { $errors['twilio_from']  = __('From Number is required.', 'woochat'); }

        if (!empty($errors)) {
            wp_send_json_error(['fields' => $errors], 422);
        }

        update_option('wcwp_api_provider', 'twilio', false);
        update_option('wcwp_twilio_sid', $sid, false);
        update_option('wcwp_twilio_auth_token', $token, false);
        update_option('wcwp_twilio_from', $from, false);
    } else {
        $token = isset($_POST['cloud_token'])    ? wcwp_sanitize_text(wp_unslash($_POST['cloud_token']))    : '';
        $phone = isset($_POST['cloud_phone_id']) ? wcwp_sanitize_text(wp_unslash($_POST['cloud_phone_id'])) : '';
        $from  = isset($_POST['cloud_from'])     ? wcwp_sanitize_text(wp_unslash($_POST['cloud_from']))     : '';

        if ($token === '') { $errors['cloud_token']    = __('Access Token is required.', 'woochat'); }
        if ($phone === '') { $errors['cloud_phone_id'] = __('Phone Number ID is required.', 'woochat'); }
        if ($from === '')  { $errors['cloud_from']     = __('From Number is required.', 'woochat'); }

        if (!empty($errors)) {
            wp_send_json_error(['fields' => $errors], 422);
        }

        update_option('wcwp_api_provider', 'cloud', false);
        update_option('wcwp_cloud_token', $token, false);
        update_option('wcwp_cloud_phone_id', $phone, false);
        update_option('wcwp_cloud_from', $from, false);
    }

    wp_send_json_success();
}
