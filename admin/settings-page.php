<?php
if (!defined('ABSPATH')) exit;

/* ---------------------------------------------------------------------------
 * Settings registration — one option group per submenu page.
 *
 * Each submenu form posts to options.php, which writes every option that
 * belongs to the submitted group. An option that is registered in the group
 * but absent from the form gets overwritten with an empty value. Splitting the
 * former single "zignites_chat_settings_group" into per-page groups keeps a Save on one
 * page from wiping options that live on another page.
 * ------------------------------------------------------------------------ */
add_action('admin_init', 'zignites_chat_register_settings');
function zignites_chat_register_settings() {
    // General Settings.
    register_setting('zignites_chat_general_group', 'zignites_chat_twilio_sid', ['sanitize_callback' => 'zignites_chat_sanitize_text']);
    register_setting('zignites_chat_general_group', 'zignites_chat_twilio_auth_token', ['sanitize_callback' => 'zignites_chat_sanitize_text']);
    register_setting('zignites_chat_general_group', 'zignites_chat_twilio_from', ['sanitize_callback' => 'zignites_chat_sanitize_text']);
    register_setting('zignites_chat_general_group', 'zignites_chat_api_provider', ['sanitize_callback' => 'zignites_chat_sanitize_provider']);
    register_setting('zignites_chat_general_group', 'zignites_chat_cloud_token', ['sanitize_callback' => 'zignites_chat_sanitize_text']);
    register_setting('zignites_chat_general_group', 'zignites_chat_cloud_phone_id', ['sanitize_callback' => 'zignites_chat_sanitize_text']);
    register_setting('zignites_chat_general_group', 'zignites_chat_cloud_from', ['sanitize_callback' => 'zignites_chat_sanitize_text']);
    register_setting('zignites_chat_general_group', 'zignites_chat_cloud_app_secret', ['sanitize_callback' => 'zignites_chat_sanitize_text']);
    register_setting('zignites_chat_general_group', 'zignites_chat_meta_verify_token', ['sanitize_callback' => 'zignites_chat_sanitize_text']);
    register_setting('zignites_chat_general_group', 'zignites_chat_test_mode_enabled', ['sanitize_callback' => 'zignites_chat_sanitize_yes_no']);
    register_setting('zignites_chat_general_group', 'zignites_chat_data_retention_days', ['sanitize_callback' => 'zignites_chat_sanitize_int']);
    register_setting('zignites_chat_general_group', 'zignites_chat_delete_data_on_uninstall', ['sanitize_callback' => 'zignites_chat_sanitize_yes_no']);
    register_setting('zignites_chat_general_group', 'zignites_chat_optout_keywords', ['sanitize_callback' => 'zignites_chat_sanitize_optout_keywords']);
    register_setting('zignites_chat_general_group', 'zignites_chat_optout_list', ['sanitize_callback' => 'zignites_chat_parse_optout_list']);
    register_setting('zignites_chat_general_group', 'zignites_chat_optout_webhook_token', ['sanitize_callback' => 'zignites_chat_sanitize_text']);

    // Messaging.
    register_setting('zignites_chat_messaging_group', 'zignites_chat_test_phone', ['sanitize_callback' => 'zignites_chat_sanitize_text']);
    register_setting('zignites_chat_messaging_group', 'zignites_chat_test_message', ['sanitize_callback' => 'zignites_chat_sanitize_textarea']);
    register_setting('zignites_chat_messaging_group', 'zignites_chat_order_message_template', ['sanitize_callback' => 'zignites_chat_sanitize_textarea']);
    register_setting('zignites_chat_messaging_group', 'zignites_chat_order_message_template_b', ['sanitize_callback' => 'zignites_chat_sanitize_textarea']);
    register_setting('zignites_chat_messaging_group', 'zignites_chat_order_message_ab_enabled', ['sanitize_callback' => 'zignites_chat_sanitize_yes_no']);

    // Chatbot.
    register_setting('zignites_chat_chatbot_group', 'zignites_chat_chatbot_enabled', ['sanitize_callback' => 'zignites_chat_sanitize_yes_no']);
    register_setting('zignites_chat_chatbot_group', 'zignites_chat_chatbot_gpt_enabled', ['sanitize_callback' => 'zignites_chat_sanitize_yes_no']);
    register_setting('zignites_chat_chatbot_group', 'zignites_chat_chatbot_catalog_context', ['sanitize_callback' => 'zignites_chat_sanitize_yes_no']);
    register_setting('zignites_chat_chatbot_group', 'zignites_chat_faq_pairs', ['sanitize_callback' => 'zignites_chat_sanitize_json_faq']);
    register_setting('zignites_chat_chatbot_group', 'zignites_chat_agents', ['sanitize_callback' => 'zignites_chat_sanitize_agents_json']);
    register_setting('zignites_chat_chatbot_group', 'zignites_chat_agent_routing_mode', ['sanitize_callback' => 'zignites_chat_sanitize_agent_routing_mode']);
    register_setting('zignites_chat_chatbot_group', 'zignites_chat_chatbot_bg', ['sanitize_callback' => 'zignites_chat_sanitize_hex_color']);
    register_setting('zignites_chat_chatbot_group', 'zignites_chat_chatbot_text', ['sanitize_callback' => 'zignites_chat_sanitize_hex_color']);
    register_setting('zignites_chat_chatbot_group', 'zignites_chat_chatbot_icon_color', ['sanitize_callback' => 'zignites_chat_sanitize_hex_color']);
    register_setting('zignites_chat_chatbot_group', 'zignites_chat_chatbot_icon', ['sanitize_callback' => 'zignites_chat_sanitize_text']);
    register_setting('zignites_chat_chatbot_group', 'zignites_chat_chatbot_welcome', ['sanitize_callback' => 'zignites_chat_sanitize_text']);

    // Cart Recovery.
    register_setting('zignites_chat_cart_recovery_group', 'zignites_chat_cart_recovery_enabled', ['sanitize_callback' => 'zignites_chat_sanitize_yes_no']);
    register_setting('zignites_chat_cart_recovery_group', 'zignites_chat_cart_recovery_delay', ['sanitize_callback' => 'zignites_chat_sanitize_int']);
    register_setting('zignites_chat_cart_recovery_group', 'zignites_chat_cart_recovery_message', ['sanitize_callback' => 'zignites_chat_sanitize_textarea']);
    register_setting('zignites_chat_cart_recovery_group', 'zignites_chat_cart_recovery_message_b', ['sanitize_callback' => 'zignites_chat_sanitize_textarea']);
    register_setting('zignites_chat_cart_recovery_group', 'zignites_chat_cart_recovery_require_consent', ['sanitize_callback' => 'zignites_chat_sanitize_yes_no']);
    register_setting('zignites_chat_cart_recovery_group', 'zignites_chat_cart_recovery_ab_enabled', ['sanitize_callback' => 'zignites_chat_sanitize_yes_no']);

    // Scheduler.
    register_setting('zignites_chat_scheduler_group', 'zignites_chat_followup_enabled', ['sanitize_callback' => 'zignites_chat_sanitize_yes_no']);
    register_setting('zignites_chat_scheduler_group', 'zignites_chat_followup_delay_minutes', ['sanitize_callback' => 'zignites_chat_sanitize_int']);
    register_setting('zignites_chat_scheduler_group', 'zignites_chat_followup_template', ['sanitize_callback' => 'zignites_chat_sanitize_textarea']);
    register_setting('zignites_chat_scheduler_group', 'zignites_chat_followup_template_b', ['sanitize_callback' => 'zignites_chat_sanitize_textarea']);
    register_setting('zignites_chat_scheduler_group', 'zignites_chat_followup_ab_enabled', ['sanitize_callback' => 'zignites_chat_sanitize_yes_no']);
    register_setting('zignites_chat_scheduler_group', 'zignites_chat_followup_use_gpt', ['sanitize_callback' => 'zignites_chat_sanitize_yes_no']);
    register_setting('zignites_chat_scheduler_group', 'zignites_chat_gpt_api_endpoint', ['sanitize_callback' => 'zignites_chat_sanitize_url']);
    register_setting('zignites_chat_scheduler_group', 'zignites_chat_gpt_api_key', ['sanitize_callback' => 'zignites_chat_sanitize_text']);
    register_setting('zignites_chat_scheduler_group', 'zignites_chat_gpt_model', ['sanitize_callback' => 'zignites_chat_sanitize_text']);

    // WhatsApp Templates (Pro) — approved-template (HSM) mapping per message type.
    register_setting('zignites_chat_wa_templates_group', 'zignites_chat_wa_templates', [
        'sanitize_callback' => 'zignites_chat_sanitize_wa_templates',
        'default'           => [],
    ]);

    // License.
    register_setting('zignites_chat_license_group', 'zignites_chat_license_key', ['sanitize_callback' => 'zignites_chat_sanitize_text']);
}

/* ---------------------------------------------------------------------------
 * Admin menu — one top-level menu with a submenu page per feature area.
 * ------------------------------------------------------------------------ */
add_action('admin_menu', 'zignites_chat_register_admin_menus');
function zignites_chat_register_admin_menus() {
    add_menu_page(
        __('Zignites Chat', 'zignites-chat'),
        __('Zignites Chat', 'zignites-chat'),
        'manage_options',
        'zignites-chat-dashboard',
        'zignites_chat_render_dashboard_page',
        'dashicons-format-chat',
        66
    );

    $pro_star = zignites_chat_is_pro_active() ? '' : ' ★';

    $submenus = [
        ['zignites-chat-dashboard',     __('Dashboard', 'zignites-chat'),        'zignites_chat_render_dashboard_page'],
        ['zignites-chat-general',       __('General Settings', 'zignites-chat'), 'zignites_chat_render_general_page'],
        ['zignites-chat-messaging',     __('Messaging', 'zignites-chat'),        'zignites_chat_render_messaging_page'],
        ['zignites-chat-wa-templates',  __('WhatsApp Templates', 'zignites-chat'), 'zignites_chat_render_wa_templates_page', true],
        ['zignites-chat-chatbot',       __('Chatbot', 'zignites-chat'),          'zignites_chat_render_chatbot_page'],
        ['zignites-chat-cart-recovery', __('Cart Recovery', 'zignites-chat'),    'zignites_chat_render_cart_recovery_page', true],
        ['zignites-chat-scheduler',     __('Scheduler', 'zignites-chat'),        'zignites_chat_render_scheduler_page',     true],
        ['zignites-chat-campaigns',     __('Campaigns', 'zignites-chat'),        'zignites_chat_render_campaigns_page',     true],
        ['zignites-chat-inbox',         __('Inbox', 'zignites-chat'),            'zignites_chat_render_inbox_page',         true],
        ['zignites-chat-analytics',     __('Analytics', 'zignites-chat'),        'zignites_chat_render_analytics_page',     true],
        ['zignites-chat-logs',          __('Logs', 'zignites-chat'),             'zignites_chat_render_logs_page'],
        ['zignites-chat-webhooks',      __('Webhooks', 'zignites-chat'),         'zignites_chat_render_webhooks_page',      true],
    ];

    // On Pro builds Freemius adds its own Account/Billing/Support submenus,
    // so the legacy License page is removed to avoid two competing license
    // surfaces. Free builds keep it as the only license entry point.
    if ( ! ( defined('ZIGNITES_CHAT_IS_PRO') && ZIGNITES_CHAT_IS_PRO ) ) {
        $submenus[] = ['zignites-chat-license', __('License', 'zignites-chat'), 'zignites_chat_render_license_page'];
    }

    foreach ($submenus as $submenu) {
        list($slug, $title, $callback) = $submenu;
        $is_pro_page = !empty($submenu[3]);
        // Mark Pro-only pages with a star for free users so the upsell is
        // visible straight from the menu.
        $menu_title = ($is_pro_page && !zignites_chat_is_pro_active())
            ? $title . ' ★'
            : $title;

        add_submenu_page(
            'zignites-chat-dashboard',
            $title,
            $menu_title,
            'manage_options',
            $slug,
            $callback
        );
    }
}

/* ---------------------------------------------------------------------------
 * Asset loading — common admin CSS/JS on every Zignites Chat page, plus the
 * page-specific scripts only where they are used.
 * ------------------------------------------------------------------------ */
add_action('admin_enqueue_scripts', 'zignites_chat_enqueue_admin_scripts');
function zignites_chat_enqueue_admin_scripts($hook) {
    if (strpos($hook, 'zignites-chat-') === false) {
        return;
    }

    wp_enqueue_style('zignites-chat-admin-premium-css', ZIGNITES_CHAT_URL . 'assets/css/admin-premium.css', [], ZIGNITES_CHAT_VERSION);
    wp_enqueue_script('zignites-chat-admin-premium-js', ZIGNITES_CHAT_URL . 'assets/js/admin-premium.js', [], ZIGNITES_CHAT_VERSION, true);
    wp_localize_script('zignites-chat-admin-premium-js', 'zignitesChatAdminData', [
        'ajaxUrl'             => admin_url('admin-ajax.php'),
        'resendNonce'         => wp_create_nonce('zignites_chat_resend_cart'),
        'licenseNonce'        => wp_create_nonce('zignites_chat_license_nonce'),
        'testNonce'           => wp_create_nonce('zignites_chat_test_message'),
        'testConnectionNonce' => wp_create_nonce('zignites_chat_test_connection'),
        'licenseLabels' => [
            'keyRequired'        => __('Key required', 'zignites-chat'),
            'activating'         => __('Activating…', 'zignites-chat'),
            'active'             => __('Active', 'zignites-chat'),
            'activationFailed'   => __('Activation failed', 'zignites-chat'),
            'deactivating'       => __('Deactivating…', 'zignites-chat'),
            'inactive'           => __('Inactive', 'zignites-chat'),
            'deactivationFailed' => __('Deactivation failed', 'zignites-chat'),
        ],
        'logClearConfirm'      => __('Clear the log file? This cannot be undone.', 'zignites-chat'),
        'webhookTestNonce'     => wp_create_nonce('zignites_chat_webhook_test'),
        'webhookDeleteConfirm' => __('Delete this webhook? Receivers will stop getting events immediately.', 'zignites-chat'),
        'webhookTesting'       => __('Testing…', 'zignites-chat'),
        'webhookTestLabel'     => __('Test fire', 'zignites-chat'),
    ]);

    // Dashboard — first-run onboarding wizard, shown once per install.
    if (strpos($hook, 'zignites-chat-dashboard') !== false && get_option('zignites_chat_onboarding_completed', 'no') !== 'yes') {
        wp_enqueue_style('zignites-chat-onboarding-css', ZIGNITES_CHAT_URL . 'assets/css/onboarding.css', [], ZIGNITES_CHAT_VERSION);
        wp_enqueue_script('zignites-chat-onboarding-js', ZIGNITES_CHAT_URL . 'assets/js/onboarding.js', [], ZIGNITES_CHAT_VERSION, true);
        wp_localize_script('zignites-chat-onboarding-js', 'zignitesChatOnboarding', [
            'ajaxUrl'      => admin_url('admin-ajax.php'),
            'dismissNonce' => wp_create_nonce('zignites_chat_dismiss_onboarding'),
            'saveNonce'    => wp_create_nonce('zignites_chat_save_onboarding'),
            'i18n'         => [
                'saveError'    => __('Could not save. Please check the highlighted fields.', 'zignites-chat'),
                'networkError' => __('Network error. Please try again.', 'zignites-chat'),
                'saving'       => __('Saving…', 'zignites-chat'),
                'next'         => __('Next', 'zignites-chat'),
            ],
        ]);
    }

    // Campaigns page.
    if (strpos($hook, 'zignites-chat-campaigns') !== false) {
        wp_enqueue_media(); // WP media library frame for the campaign attachment picker.
        wp_enqueue_script('zignites-chat-campaigns-js', ZIGNITES_CHAT_URL . 'assets/js/campaigns.js', [], ZIGNITES_CHAT_VERSION, true);
        wp_localize_script('zignites-chat-campaigns-js', 'zignitesChatCampaigns', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('zignites_chat_campaigns'),
            'i18n'    => [
                'submitting'   => __('Creating campaign…', 'zignites-chat'),
                'create'       => __('Create campaign', 'zignites-chat'),
                'genericError' => __('Could not create campaign. Please try again.', 'zignites-chat'),
                'completed'    => __('Completed', 'zignites-chat'),
                'running'      => __('Running', 'zignites-chat'),
                'queued'       => __('Queued', 'zignites-chat'),
            ],
        ]);
    }

    // Pages that expose the template-library browser.
    if (strpos($hook, 'zignites-chat-messaging') !== false
        || strpos($hook, 'zignites-chat-cart-recovery') !== false
        || strpos($hook, 'zignites-chat-scheduler') !== false) {
        wp_enqueue_script('zignites-chat-template-library-js', ZIGNITES_CHAT_URL . 'assets/js/template-library.js', [], ZIGNITES_CHAT_VERSION, true);
        wp_localize_script('zignites-chat-template-library-js', 'zignitesChatTemplateLibraryI18n', [
            'empty' => __('No templates available for this section yet.', 'zignites-chat'),
        ]);
    }
}

/* ---------------------------------------------------------------------------
 * Shared page chrome.
 * ------------------------------------------------------------------------ */

/**
 * Open the standard Zignites Chat admin page wrapper.
 *
 * @param string $title Page heading, already translated.
 */
function zignites_chat_admin_page_open($title) {
    echo '<div class="wrap zignites-chat-admin-premium-wrap zignites-chat-admin-wrap">';
    echo '<h1>' . esc_html($title) . '</h1>';
}

function zignites_chat_admin_page_close() {
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
function zignites_chat_render_settings_view($title, $group, $view, $with_template_lib = false) {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Unauthorized', 'zignites-chat'));
    }
    zignites_chat_admin_page_open($title);
    echo '<form method="post" action="options.php">';
    settings_fields($group);
    require ZIGNITES_CHAT_PATH . 'admin/views/' . $view;
    submit_button();
    echo '</form>';
    if ($with_template_lib) {
        require ZIGNITES_CHAT_PATH . 'admin/views/template-library-modal.php';
    }
    zignites_chat_admin_page_close();
}

/* ---------------------------------------------------------------------------
 * Page render callbacks.
 * ------------------------------------------------------------------------ */

function zignites_chat_render_dashboard_page() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Unauthorized', 'zignites-chat'));
    }
    zignites_chat_admin_page_open(__('Zignites Chat Dashboard', 'zignites-chat'));
    require ZIGNITES_CHAT_PATH . 'admin/views/dashboard.php';
    zignites_chat_admin_page_close();
}

function zignites_chat_render_general_page() {
    zignites_chat_render_settings_view(__('General Settings', 'zignites-chat'), 'zignites_chat_general_group', 'tab-general.php');
}

function zignites_chat_render_messaging_page() {
    zignites_chat_render_settings_view(__('Messaging', 'zignites-chat'), 'zignites_chat_messaging_group', 'tab-messaging.php', true);
}

function zignites_chat_render_chatbot_page() {
    zignites_chat_render_settings_view(__('Chatbot', 'zignites-chat'), 'zignites_chat_chatbot_group', 'tab-chatbot.php');
}

function zignites_chat_render_wa_templates_page() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Unauthorized', 'zignites-chat'));
    }
    zignites_chat_admin_page_open(__('WhatsApp Templates', 'zignites-chat'));
    if (!zignites_chat_is_pro_active()) {
        zignites_chat_render_pro_upgrade_notice('wa-templates');
        zignites_chat_admin_page_close();
        return;
    }
    echo '<form method="post" action="options.php">';
    settings_fields('zignites_chat_wa_templates_group');
    require ZIGNITES_CHAT_PATH . 'admin/views/tab-wa-templates.php';
    submit_button();
    echo '</form>';
    zignites_chat_admin_page_close();
}

function zignites_chat_render_license_page() {
    zignites_chat_render_settings_view(__('License', 'zignites-chat'), 'zignites_chat_license_group', 'tab-license.php');
}

function zignites_chat_render_cart_recovery_page() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Unauthorized', 'zignites-chat'));
    }
    zignites_chat_admin_page_open(__('Cart Recovery', 'zignites-chat'));
    if (!zignites_chat_is_pro_active()) {
        zignites_chat_render_pro_upgrade_notice('cart-recovery');
        zignites_chat_admin_page_close();
        return;
    }
    echo '<form method="post" action="options.php">';
    settings_fields('zignites_chat_cart_recovery_group');
    require ZIGNITES_CHAT_PATH . 'admin/views/tab-cart-recovery.php';
    submit_button();
    echo '</form>';
    require ZIGNITES_CHAT_PATH . 'admin/views/template-library-modal.php';
    zignites_chat_admin_page_close();
}

function zignites_chat_render_scheduler_page() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Unauthorized', 'zignites-chat'));
    }
    zignites_chat_admin_page_open(__('Scheduler', 'zignites-chat'));
    if (!zignites_chat_is_pro_active()) {
        zignites_chat_render_pro_upgrade_notice('scheduler');
        zignites_chat_admin_page_close();
        return;
    }
    echo '<form method="post" action="options.php">';
    settings_fields('zignites_chat_scheduler_group');
    require ZIGNITES_CHAT_PATH . 'admin/views/tab-scheduler.php';
    submit_button();
    echo '</form>';
    require ZIGNITES_CHAT_PATH . 'admin/views/template-library-modal.php';
    zignites_chat_admin_page_close();
}

function zignites_chat_render_campaigns_page() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Unauthorized', 'zignites-chat'));
    }
    zignites_chat_admin_page_open(__('Campaigns', 'zignites-chat'));
    if (!zignites_chat_is_pro_active()) {
        zignites_chat_render_pro_upgrade_notice('campaigns');
        zignites_chat_admin_page_close();
        return;
    }
    require ZIGNITES_CHAT_PATH . 'admin/views/tab-campaigns.php';
    zignites_chat_admin_page_close();
}

function zignites_chat_render_analytics_page() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Unauthorized', 'zignites-chat'));
    }
    zignites_chat_admin_page_open(__('Analytics', 'zignites-chat'));
    if (!zignites_chat_is_pro_active()) {
        zignites_chat_render_pro_upgrade_notice('analytics');
        zignites_chat_admin_page_close();
        return;
    }
    require ZIGNITES_CHAT_PATH . 'admin/views/tab-analytics.php';
    zignites_chat_admin_page_close();
}

function zignites_chat_render_webhooks_page() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Unauthorized', 'zignites-chat'));
    }
    zignites_chat_admin_page_open(__('Webhooks', 'zignites-chat'));
    if (!zignites_chat_is_pro_active()) {
        zignites_chat_render_pro_upgrade_notice('webhooks');
        zignites_chat_admin_page_close();
        return;
    }
    require ZIGNITES_CHAT_PATH . 'admin/views/tab-webhooks.php';
    zignites_chat_admin_page_close();
}

function zignites_chat_render_logs_page() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Unauthorized', 'zignites-chat'));
    }
    zignites_chat_admin_page_open(__('Logs', 'zignites-chat'));
    require ZIGNITES_CHAT_PATH . 'admin/views/tab-logs.php';
    zignites_chat_admin_page_close();
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
function zignites_chat_render_pro_upgrade_notice($feature = '') {
    $features = [
        'cart-recovery' => [
            'title'       => __('Cart Recovery via WhatsApp', 'zignites-chat'),
            'description' => __('Recover abandoned carts by sending automated WhatsApp reminders. WhatsApp has 90%+ open rates vs 20% for email.', 'zignites-chat'),
            'benefits'    => [
                __('Automated cart abandonment detection', 'zignites-chat'),
                __('Customizable recovery message templates', 'zignites-chat'),
                __('Smart timing with configurable delays', 'zignites-chat'),
                __('Recovery analytics and conversion tracking', 'zignites-chat'),
                __('A/B testing for recovery messages', 'zignites-chat'),
            ],
        ],
        'scheduler' => [
            'title'       => __('Follow-up Scheduler', 'zignites-chat'),
            'description' => __('Automatically send follow-up messages after orders to boost reviews, repeat purchases, and customer loyalty.', 'zignites-chat'),
            'benefits'    => [
                __('Post-purchase follow-up automation', 'zignites-chat'),
                __('Configurable delay timing', 'zignites-chat'),
                __('GPT-powered personalized messages', 'zignites-chat'),
                __('A/B testing for follow-up templates', 'zignites-chat'),
            ],
        ],
        'campaigns' => [
            'title'       => __('Bulk Campaigns', 'zignites-chat'),
            'description' => __('Send targeted WhatsApp campaigns to customer segments. Perfect for promotions, announcements, and re-engagement.', 'zignites-chat'),
            'benefits'    => [
                __('Customer segmentation (all customers, recent orders)', 'zignites-chat'),
                __('Campaign performance tracking', 'zignites-chat'),
                __('Rate-limited sending to protect your number', 'zignites-chat'),
                __('Template-based campaign messages', 'zignites-chat'),
            ],
        ],
        'analytics' => [
            'title'       => __('Analytics Dashboard', 'zignites-chat'),
            'description' => __('Track every WhatsApp message with delivery rates, click tracking, conversion attribution, and A/B test results.', 'zignites-chat'),
            'benefits'    => [
                __('Message delivery and open rate tracking', 'zignites-chat'),
                __('Click-through rate monitoring', 'zignites-chat'),
                __('Revenue attribution from WhatsApp messages', 'zignites-chat'),
                __('A/B test performance comparison', 'zignites-chat'),
                __('Exportable reports', 'zignites-chat'),
            ],
        ],
        'wa-templates' => [
            'title'       => __('WhatsApp Approved Templates', 'zignites-chat'),
            'description' => __('Map your Meta-approved message templates so cart recovery, follow-ups and campaigns send as compliant templates — the only way to reliably reach customers outside the 24-hour window on the Cloud API.', 'zignites-chat'),
            'benefits'    => [
                __('Send pre-approved templates (HSM) on the Cloud API', 'zignites-chat'),
                __('Per message-type template + language mapping', 'zignites-chat'),
                __('Map order/cart/follow-up data to template variables', 'zignites-chat'),
                __('Protects your sender number from quality downgrades', 'zignites-chat'),
            ],
        ],
        'inbox' => [
            'title'       => __('Two-way Team Inbox', 'zignites-chat'),
            'description' => __('Read and reply to customer WhatsApp messages from inside WordPress. Inbound messages are captured automatically and threaded per customer.', 'zignites-chat'),
            'benefits'    => [
                __('Unified inbox for inbound WhatsApp messages', 'zignites-chat'),
                __('Per-customer conversation threads with unread badges', 'zignites-chat'),
                __('Reply within the 24-hour customer-service window', 'zignites-chat'),
                __('Works with both Twilio and Meta Cloud API', 'zignites-chat'),
            ],
        ],
        'webhooks' => [
            'title'       => __('Webhooks & Integrations', 'zignites-chat'),
            'description' => __('Connect Zignites Chat to external services with webhooks. Receive delivery status callbacks and integrate with your stack.', 'zignites-chat'),
            'benefits'    => [
                __('Incoming webhook processing', 'zignites-chat'),
                __('Delivery status callbacks', 'zignites-chat'),
                __('Custom webhook endpoints', 'zignites-chat'),
                __('Opt-out processing via webhook', 'zignites-chat'),
            ],
        ],
    ];

    $f = isset($features[$feature]) ? $features[$feature] : $features['cart-recovery'];
    ?>
    <div class="zignites-chat-pro-upgrade-card">
        <div class="zignites-chat-pro-upgrade-icon">🔒</div>
        <h2><?php echo esc_html($f['title']); ?></h2>
        <p class="zignites-chat-pro-upgrade-desc"><?php echo esc_html($f['description']); ?></p>
        <ul class="zignites-chat-pro-upgrade-benefits">
            <?php foreach ($f['benefits'] as $benefit) : ?>
                <li><?php echo esc_html($benefit); ?></li>
            <?php endforeach; ?>
        </ul>
        <a href="https://zignites.com/zignites-chat" class="button button-primary button-hero" target="_blank" rel="noopener">
            <?php esc_html_e('Upgrade to Zignites Chat Pro', 'zignites-chat'); ?>
        </a>
        <p class="zignites-chat-pro-upgrade-price">
            <?php esc_html_e('Starting at $49/year for a single site', 'zignites-chat'); ?>
        </p>
    </div>
    <?php
}

/**
 * Print the upgrade comparison modal. Hooked into admin_footer so any
 * .zignites-chat-open-upgrade-modal trigger on a Zignites Chat page can open it.
 */
function zignites_chat_render_upgrade_modal() {
    ?>
    <div id="zignites-chat-upgrade-modal">
        <div class="zignites-chat-upgrade-modal-content">
            <button class="zignites-chat-upgrade-modal-close" aria-label="<?php esc_attr_e('Close', 'zignites-chat'); ?>">&times;</button>
            <h2><?php esc_html_e('Upgrade to Zignites Chat Pro', 'zignites-chat'); ?></h2>
            <p><?php esc_html_e("Unlock all premium features and maximize your store's potential!", 'zignites-chat'); ?></p>
            <table class="zignites-chat-comparison-table">
                <tr><th><?php esc_html_e('Feature', 'zignites-chat'); ?></th><th><?php esc_html_e('Free', 'zignites-chat'); ?></th><th class="pro"><?php esc_html_e('Pro', 'zignites-chat'); ?></th></tr>
                <tr><td><?php esc_html_e('Order Notifications via WhatsApp', 'zignites-chat'); ?></td><td>✔️</td><td class="pro">✔️</td></tr>
                <tr><td><?php esc_html_e('Manual Message Button', 'zignites-chat'); ?></td><td>✔️</td><td class="pro">✔️</td></tr>
                <tr><td><?php esc_html_e('WhatsApp Chat Widget', 'zignites-chat'); ?></td><td>✔️</td><td class="pro">✔️</td></tr>
                <tr><td><?php esc_html_e('Gutenberg Blocks', 'zignites-chat'); ?></td><td>✔️</td><td class="pro">✔️</td></tr>
                <tr><td><?php esc_html_e('Twilio &amp; Cloud API', 'zignites-chat'); ?></td><td>✔️</td><td class="pro">✔️</td></tr>
                <tr><td><?php esc_html_e('Message Logs', 'zignites-chat'); ?></td><td><?php esc_html_e('Last 50', 'zignites-chat'); ?></td><td class="pro"><?php esc_html_e('Unlimited + export', 'zignites-chat'); ?></td></tr>
                <tr><td><?php esc_html_e('Cart Recovery', 'zignites-chat'); ?></td><td></td><td class="pro">✔️</td></tr>
                <tr><td><?php esc_html_e('Follow-up Scheduler', 'zignites-chat'); ?></td><td></td><td class="pro">✔️</td></tr>
                <tr><td><?php esc_html_e('Bulk Campaigns', 'zignites-chat'); ?></td><td></td><td class="pro">✔️</td></tr>
                <tr><td><?php esc_html_e('Analytics Dashboard', 'zignites-chat'); ?></td><td></td><td class="pro">✔️</td></tr>
                <tr><td><?php esc_html_e('A/B Testing', 'zignites-chat'); ?></td><td></td><td class="pro">✔️</td></tr>
                <tr><td><?php esc_html_e('GPT/AI Chatbot Replies', 'zignites-chat'); ?></td><td></td><td class="pro">✔️</td></tr>
                <tr><td><?php esc_html_e('Chatbot Customizer &amp; Multi-Agent', 'zignites-chat'); ?></td><td></td><td class="pro">✔️</td></tr>
                <tr><td><?php esc_html_e('Webhooks &amp; Integrations', 'zignites-chat'); ?></td><td></td><td class="pro">✔️</td></tr>
                <tr><td><?php esc_html_e('Premium Support', 'zignites-chat'); ?></td><td></td><td class="pro">✔️</td></tr>
            </table>
            <a href="https://zignites.com/zignites-chat" target="_blank" rel="noopener"><button class="zignites-chat-upgrade-btn"><?php esc_html_e('Upgrade Now', 'zignites-chat'); ?></button></a>
            <p style="margin-top:10px;font-size:0.97rem;color:#888;"><?php esc_html_e('Already have a license? Enter it on the License page.', 'zignites-chat'); ?></p>
        </div>
    </div>
    <?php
}

add_action('admin_footer', 'zignites_chat_render_admin_footer_modals');
function zignites_chat_render_admin_footer_modals() {
    $screen = get_current_screen();
    if (!$screen || strpos($screen->id, 'zignites-chat-') === false) {
        return;
    }
    // Pro builds: license already covers every feature, so the upgrade modal
    // is dead UI — every trigger button is gated by zignites_chat_is_pro_active()
    // and won't render either.
    if (zignites_chat_is_pro_active()) {
        return;
    }
    zignites_chat_render_upgrade_modal();
}

/**
 * One-time, dismissible Pro upsell notice shown after activation.
 */
add_action('admin_notices', 'zignites_chat_pro_upsell_admin_notice');
function zignites_chat_pro_upsell_admin_notice() {
    if (!current_user_can('manage_options') || zignites_chat_is_pro_active()) {
        return;
    }
    if (get_option('zignites_chat_pro_notice_dismissed') === 'yes') {
        return;
    }
    // Keep the upsell on the plugin's own screens instead of following the
    // admin around every unrelated page.
    $screen = get_current_screen();
    if (!$screen || strpos($screen->id, 'zignites-chat') === false) {
        return;
    }

    $dismiss_url = wp_nonce_url(
        add_query_arg('zignites_chat_dismiss_pro_notice', '1'),
        'zignites_chat_dismiss_pro_notice'
    );
    ?>
    <div class="notice notice-info zignites-chat-pro-upsell-notice">
        <p>
            <strong><?php esc_html_e('Zignites Chat is active.', 'zignites-chat'); ?></strong>
            <?php esc_html_e('Upgrade to Zignites Chat Pro for cart recovery, analytics, bulk campaigns and A/B testing.', 'zignites-chat'); ?>
        </p>
        <p>
            <a href="https://zignites.com/zignites-chat" class="button button-primary" target="_blank" rel="noopener"><?php esc_html_e('Upgrade Now', 'zignites-chat'); ?></a>
            <a href="<?php echo esc_url($dismiss_url); ?>" class="button"><?php esc_html_e('Dismiss', 'zignites-chat'); ?></a>
        </p>
    </div>
    <?php
}

/**
 * Persist dismissal of the Pro upsell notice.
 */
add_action('admin_init', 'zignites_chat_handle_pro_notice_dismiss');
function zignites_chat_handle_pro_notice_dismiss() {
    if (!isset($_GET['zignites_chat_dismiss_pro_notice']) || !current_user_can('manage_options')) {
        return;
    }
    $nonce = isset($_GET['_wpnonce']) ? sanitize_text_field(wp_unslash($_GET['_wpnonce'])) : '';
    if (!wp_verify_nonce($nonce, 'zignites_chat_dismiss_pro_notice')) {
        return;
    }
    update_option('zignites_chat_pro_notice_dismissed', 'yes', false);
    wp_safe_redirect(remove_query_arg(['zignites_chat_dismiss_pro_notice', '_wpnonce']));
    exit;
}

/**
 * Surface the most recent GPT failure to the admin.
 *
 * The actual GPT call sites (follow-up scheduler, chatbot fallback) used
 * to return '' on any error and the admin had no idea AI was broken.
 * zignites_chat_record_gpt_error() now stashes a 24h transient on every
 * failure; this notice picks it up on the next Zignites Chat admin page
 * load and lets the admin dismiss once they've fixed the root cause.
 */
add_action('admin_notices', 'zignites_chat_render_gpt_error_notice');
function zignites_chat_render_gpt_error_notice() {
    if (!current_user_can('manage_options')) {
        return;
    }
    $screen = get_current_screen();
    if (!$screen || strpos($screen->id, 'zignites-chat') === false) {
        return;
    }
    $err = zignites_chat_get_last_gpt_error();
    if (!$err) {
        return;
    }

    $dismiss_url = wp_nonce_url(
        add_query_arg('zignites_chat_dismiss_gpt_error', '1'),
        'zignites_chat_dismiss_gpt_error'
    );

    $context_label = ($err['context'] === 'chatbot')
        ? __('chatbot fallback', 'zignites-chat')
        : __('follow-up scheduler', 'zignites-chat');

    $when = isset($err['time']) ? (int) $err['time'] : 0;
    $relative = $when > 0
        ? sprintf(
            /* translators: %s: human-readable "x minutes ago" */
            __('%s ago', 'zignites-chat'),
            human_time_diff($when, time())
        )
        : '';
    ?>
    <div class="notice notice-warning is-dismissible">
        <p>
            <strong><?php esc_html_e('Zignites Chat:', 'zignites-chat'); ?></strong>
            <?php
            printf(
                /* translators: 1: feature (chatbot fallback or follow-up scheduler), 2: relative time, 3: error message */
                esc_html__('The %1$s GPT call failed %2$s: %3$s', 'zignites-chat'),
                esc_html($context_label),
                esc_html($relative),
                esc_html($err['message'])
            );
            ?>
        </p>
        <p>
            <a href="<?php echo esc_url(admin_url('admin.php?page=zignites-chat-scheduler')); ?>" class="button button-small"><?php esc_html_e('Open Scheduler', 'zignites-chat'); ?></a>
            <a href="<?php echo esc_url($dismiss_url); ?>" class="button button-small"><?php esc_html_e('Dismiss', 'zignites-chat'); ?></a>
        </p>
    </div>
    <?php
}

/**
 * Clear the GPT-error transient when the admin dismisses the notice.
 */
add_action('admin_init', 'zignites_chat_handle_gpt_error_dismiss');
function zignites_chat_handle_gpt_error_dismiss() {
    if (!isset($_GET['zignites_chat_dismiss_gpt_error']) || !current_user_can('manage_options')) {
        return;
    }
    $nonce = isset($_GET['_wpnonce']) ? sanitize_text_field(wp_unslash($_GET['_wpnonce'])) : '';
    if (!wp_verify_nonce($nonce, 'zignites_chat_dismiss_gpt_error')) {
        return;
    }
    delete_transient('zignites_chat_last_gpt_error');
    wp_safe_redirect(remove_query_arg(['zignites_chat_dismiss_gpt_error', '_wpnonce']));
    exit;
}

/**
 * Print the first-run onboarding wizard modal. Rendered on the Dashboard
 * until the admin completes or skips it.
 */
function zignites_chat_render_onboarding_modal() {
    if (get_option('zignites_chat_onboarding_completed', 'no') === 'yes') {
        return;
    }

    // Prefill from existing options so a re-run of the wizard (e.g. after a
    // clean reinstall with kept data) doesn't force the admin to retype.
    $ob_provider = get_option('zignites_chat_api_provider', 'twilio');
    if (!in_array($ob_provider, ['twilio', 'cloud'], true)) {
        $ob_provider = 'twilio';
    }
    $ob_twilio_sid   = get_option('zignites_chat_twilio_sid', '');
    $ob_twilio_token = get_option('zignites_chat_twilio_auth_token', '');
    $ob_twilio_from  = get_option('zignites_chat_twilio_from', '');
    $ob_cloud_token  = get_option('zignites_chat_cloud_token', '');
    $ob_cloud_phone  = get_option('zignites_chat_cloud_phone_id', '');
    $ob_cloud_from   = get_option('zignites_chat_cloud_from', '');
    ?>
    <div id="zignites-chat-onboarding-modal">
        <div class="zignites-chat-onboarding-content">
            <div class="zignites-chat-onboarding-progress"><div class="zignites-chat-onboarding-progress-inner"></div></div>

            <div class="zignites-chat-onboarding-step" data-step="welcome">
                <h2><?php esc_html_e('Welcome to Zignites Chat!', 'zignites-chat'); ?></h2>
                <p><?php esc_html_e("Let's connect your WhatsApp account in a couple of steps.", 'zignites-chat'); ?></p>
            </div>

            <div class="zignites-chat-onboarding-step" data-step="provider">
                <h2><?php esc_html_e('Choose your WhatsApp provider', 'zignites-chat'); ?></h2>
                <p><?php esc_html_e('Pick the API you have an account with. You can change this later.', 'zignites-chat'); ?></p>
                <div class="zignites-chat-onboarding-provider-choices">
                    <label class="zignites-chat-onboarding-provider-choice">
                        <input type="radio" name="zignites_chat_ob_provider" value="twilio" <?php checked($ob_provider, 'twilio'); ?> />
                        <span class="zignites-chat-onboarding-provider-title"><?php esc_html_e('Twilio', 'zignites-chat'); ?></span>
                        <span class="zignites-chat-onboarding-provider-desc"><?php esc_html_e('WhatsApp via Twilio Programmable Messaging.', 'zignites-chat'); ?></span>
                    </label>
                    <label class="zignites-chat-onboarding-provider-choice">
                        <input type="radio" name="zignites_chat_ob_provider" value="cloud" <?php checked($ob_provider, 'cloud'); ?> />
                        <span class="zignites-chat-onboarding-provider-title"><?php esc_html_e('Meta Cloud API', 'zignites-chat'); ?></span>
                        <span class="zignites-chat-onboarding-provider-desc"><?php esc_html_e('WhatsApp Business Platform direct from Meta.', 'zignites-chat'); ?></span>
                    </label>
                </div>
            </div>

            <div class="zignites-chat-onboarding-step" data-step="credentials">
                <h2><?php esc_html_e('Enter your credentials', 'zignites-chat'); ?></h2>
                <p class="zignites-chat-onboarding-step-hint" data-provider-hint="twilio"><?php esc_html_e('Find these in your Twilio Console.', 'zignites-chat'); ?></p>
                <p class="zignites-chat-onboarding-step-hint" data-provider-hint="cloud"><?php esc_html_e('Find these in your Meta for Developers app.', 'zignites-chat'); ?></p>

                <div class="zignites-chat-onboarding-fields" data-provider-fields="twilio">
                    <div class="zignites-chat-onboarding-field">
                        <label for="zignites_chat_ob_twilio_sid"><?php esc_html_e('Account SID', 'zignites-chat'); ?></label>
                        <input type="text" id="zignites_chat_ob_twilio_sid" name="twilio_sid" value="<?php echo esc_attr($ob_twilio_sid); ?>" autocomplete="off" />
                        <span class="zignites-chat-onboarding-field-error" data-error-for="twilio_sid"></span>
                    </div>
                    <div class="zignites-chat-onboarding-field">
                        <label for="zignites_chat_ob_twilio_token"><?php esc_html_e('Auth Token', 'zignites-chat'); ?></label>
                        <input type="password" id="zignites_chat_ob_twilio_token" name="twilio_token" value="<?php echo esc_attr($ob_twilio_token); ?>" autocomplete="off" />
                        <span class="zignites-chat-onboarding-field-error" data-error-for="twilio_token"></span>
                    </div>
                    <div class="zignites-chat-onboarding-field">
                        <label for="zignites_chat_ob_twilio_from"><?php esc_html_e('From Number', 'zignites-chat'); ?></label>
                        <input type="text" id="zignites_chat_ob_twilio_from" name="twilio_from" value="<?php echo esc_attr($ob_twilio_from); ?>" placeholder="whatsapp:+14155238886" autocomplete="off" />
                        <span class="zignites-chat-onboarding-field-error" data-error-for="twilio_from"></span>
                    </div>
                </div>

                <div class="zignites-chat-onboarding-fields" data-provider-fields="cloud">
                    <div class="zignites-chat-onboarding-field">
                        <label for="zignites_chat_ob_cloud_token"><?php esc_html_e('Access Token', 'zignites-chat'); ?></label>
                        <input type="password" id="zignites_chat_ob_cloud_token" name="cloud_token" value="<?php echo esc_attr($ob_cloud_token); ?>" autocomplete="off" />
                        <span class="zignites-chat-onboarding-field-error" data-error-for="cloud_token"></span>
                    </div>
                    <div class="zignites-chat-onboarding-field">
                        <label for="zignites_chat_ob_cloud_phone_id"><?php esc_html_e('Phone Number ID', 'zignites-chat'); ?></label>
                        <input type="text" id="zignites_chat_ob_cloud_phone_id" name="cloud_phone_id" value="<?php echo esc_attr($ob_cloud_phone); ?>" autocomplete="off" />
                        <span class="zignites-chat-onboarding-field-error" data-error-for="cloud_phone_id"></span>
                    </div>
                    <div class="zignites-chat-onboarding-field">
                        <label for="zignites_chat_ob_cloud_from"><?php esc_html_e('From Number', 'zignites-chat'); ?></label>
                        <input type="text" id="zignites_chat_ob_cloud_from" name="cloud_from" value="<?php echo esc_attr($ob_cloud_from); ?>" placeholder="+14155238886" autocomplete="off" />
                        <span class="zignites-chat-onboarding-field-error" data-error-for="cloud_from"></span>
                    </div>
                </div>

                <div class="zignites-chat-onboarding-form-error" role="alert"></div>
            </div>

            <div class="zignites-chat-onboarding-step" data-step="done">
                <h2><?php esc_html_e('All set!', 'zignites-chat'); ?></h2>
                <p><?php esc_html_e('Your provider is connected. Use the menu on the left to enable the chatbot and customize your messages.', 'zignites-chat'); ?></p>
            </div>

            <div class="zignites-chat-onboarding-buttons">
                <button type="button" class="zignites-chat-onboarding-prev"><?php esc_html_e('Back', 'zignites-chat'); ?></button>
                <button type="button" class="zignites-chat-onboarding-next"><?php esc_html_e('Next', 'zignites-chat'); ?></button>
                <button type="button" class="zignites-chat-onboarding-finish"><?php esc_html_e('Finish', 'zignites-chat'); ?></button>
                <button type="button" class="zignites-chat-onboarding-skip"><?php esc_html_e('Skip', 'zignites-chat'); ?></button>
            </div>
        </div>
    </div>
    <?php
}

/* ---------------------------------------------------------------------------
 * AJAX handlers.
 * ------------------------------------------------------------------------ */

add_action('wp_ajax_zignites_chat_dismiss_onboarding', 'zignites_chat_ajax_dismiss_onboarding');
function zignites_chat_ajax_dismiss_onboarding() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Unauthorized', 'zignites-chat')], 403);
    }
    if (!check_ajax_referer('zignites_chat_dismiss_onboarding', 'nonce', false)) {
        wp_send_json_error(['message' => __('Bad nonce', 'zignites-chat')], 400);
    }
    update_option('zignites_chat_onboarding_completed', 'yes', false);
    wp_send_json_success();
}

add_action('wp_ajax_zignites_chat_create_campaign', 'zignites_chat_ajax_create_campaign');
function zignites_chat_ajax_create_campaign() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Unauthorized', 'zignites-chat')], 403);
    }
    if (!check_ajax_referer('zignites_chat_campaigns', 'nonce', false)) {
        wp_send_json_error(['message' => __('Bad nonce', 'zignites-chat')], 400);
    }

    $name         = isset($_POST['name']) ? sanitize_text_field(wp_unslash($_POST['name'])) : '';
    $template     = isset($_POST['template']) ? sanitize_textarea_field(wp_unslash($_POST['template'])) : '';
    $segment_type = isset($_POST['segment_type']) ? sanitize_key(wp_unslash($_POST['segment_type'])) : '';
    $scheduled_at = isset($_POST['scheduled_at']) ? sanitize_text_field(wp_unslash($_POST['scheduled_at'])) : '';
    $exclude_days = isset($_POST['exclude_recent_days']) ? max(0, (int) $_POST['exclude_recent_days']) : 0;

    $segment_inputs = [
        'segment_days' => isset($_POST['segment_days']) ? sanitize_text_field(wp_unslash($_POST['segment_days'])) : '',
        'product_ids'  => isset($_POST['product_ids']) ? sanitize_text_field(wp_unslash($_POST['product_ids'])) : '',
        'category_ids' => isset($_POST['category_ids']) ? sanitize_text_field(wp_unslash($_POST['category_ids'])) : '',
        'min_spend'    => isset($_POST['min_spend']) ? sanitize_text_field(wp_unslash($_POST['min_spend'])) : '',
        'countries'    => isset($_POST['countries']) ? sanitize_text_field(wp_unslash($_POST['countries'])) : '',
        'winback_days' => isset($_POST['winback_days']) ? sanitize_text_field(wp_unslash($_POST['winback_days'])) : '',
    ];
    $segment_meta = zignites_chat_build_campaign_segment_meta($segment_type, $segment_inputs);
    if ($exclude_days > 0) {
        $segment_meta['exclude_recent_days'] = $exclude_days;
    }

    $media_url  = isset($_POST['media_url']) ? esc_url_raw(wp_unslash($_POST['media_url'])) : '';
    $media_mime = isset($_POST['media_mime']) ? sanitize_text_field(wp_unslash($_POST['media_mime'])) : '';

    $result = zignites_chat_campaign_create([
        'name'         => $name,
        'template'     => $template,
        'segment_type' => $segment_type,
        'segment_meta' => $segment_meta,
        'scheduled_at' => $scheduled_at,
        'media_url'    => $media_url,
        'media_mime'   => $media_mime,
    ]);

    if (is_wp_error($result)) {
        wp_send_json_error(['message' => $result->get_error_message()], 422);
    }

    wp_send_json_success(['campaign_id' => (int) $result]);
}

add_action('wp_ajax_zignites_chat_campaign_status', 'zignites_chat_ajax_campaign_status');
function zignites_chat_ajax_campaign_status() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Unauthorized', 'zignites-chat')], 403);
    }
    if (!check_ajax_referer('zignites_chat_campaigns', 'nonce', false)) {
        wp_send_json_error(['message' => __('Bad nonce', 'zignites-chat')], 400);
    }

    $id = isset($_GET['campaign_id']) ? (int) $_GET['campaign_id'] : 0;
    $campaign = zignites_chat_campaign_get($id);
    if (!$campaign) {
        wp_send_json_error(['message' => __('Not found', 'zignites-chat')], 404);
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

add_action('wp_ajax_zignites_chat_save_onboarding_credentials', 'zignites_chat_ajax_save_onboarding_credentials');
function zignites_chat_ajax_save_onboarding_credentials() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Unauthorized', 'zignites-chat')], 403);
    }
    if (!check_ajax_referer('zignites_chat_save_onboarding', 'nonce', false)) {
        wp_send_json_error(['message' => __('Bad nonce', 'zignites-chat')], 400);
    }

    $provider_raw = isset($_POST['provider']) ? sanitize_text_field(wp_unslash($_POST['provider'])) : '';
    if (!in_array($provider_raw, ['twilio', 'cloud'], true)) {
        wp_send_json_error(['message' => __('Choose a provider before continuing.', 'zignites-chat')], 422);
    }

    $errors = [];

    if ($provider_raw === 'twilio') {
        $sid   = isset($_POST['twilio_sid'])   ? sanitize_text_field(wp_unslash($_POST['twilio_sid']))   : '';
        $token = isset($_POST['twilio_token']) ? sanitize_text_field(wp_unslash($_POST['twilio_token'])) : '';
        $from  = isset($_POST['twilio_from'])  ? sanitize_text_field(wp_unslash($_POST['twilio_from']))  : '';

        if ($sid === '')   { $errors['twilio_sid']   = __('Twilio Account SID is required.', 'zignites-chat'); }
        if ($token === '') { $errors['twilio_token'] = __('Twilio Auth Token is required.', 'zignites-chat'); }
        if ($from === '')  { $errors['twilio_from']  = __('From Number is required.', 'zignites-chat'); }

        if (!empty($errors)) {
            wp_send_json_error(['fields' => $errors], 422);
        }

        update_option('zignites_chat_api_provider', 'twilio', false);
        update_option('zignites_chat_twilio_sid', $sid, false);
        update_option('zignites_chat_twilio_auth_token', $token, false);
        update_option('zignites_chat_twilio_from', $from, false);
    } else {
        $token = isset($_POST['cloud_token'])    ? sanitize_text_field(wp_unslash($_POST['cloud_token']))    : '';
        $phone = isset($_POST['cloud_phone_id']) ? sanitize_text_field(wp_unslash($_POST['cloud_phone_id'])) : '';
        $from  = isset($_POST['cloud_from'])     ? sanitize_text_field(wp_unslash($_POST['cloud_from']))     : '';

        if ($token === '') { $errors['cloud_token']    = __('Access Token is required.', 'zignites-chat'); }
        if ($phone === '') { $errors['cloud_phone_id'] = __('Phone Number ID is required.', 'zignites-chat'); }
        if ($from === '')  { $errors['cloud_from']     = __('From Number is required.', 'zignites-chat'); }

        if (!empty($errors)) {
            wp_send_json_error(['fields' => $errors], 422);
        }

        update_option('zignites_chat_api_provider', 'cloud', false);
        update_option('zignites_chat_cloud_token', $token, false);
        update_option('zignites_chat_cloud_phone_id', $phone, false);
        update_option('zignites_chat_cloud_from', $from, false);
    }

    wp_send_json_success();
}
