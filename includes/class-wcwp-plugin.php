<?php
/**
 * WooChat Pro plugin entry-point class.
 *
 * Owns bootstrap orchestration: HPOS compat declaration, text domain
 * loading, WooCommerce-dependency gate, module require chain, activation
 * / deactivation logic, migration runner wiring, and the front-end UUID
 * polyfill enqueue. The procedural helpers in includes/*.php are still
 * the implementation surface for now — this class is a thin orchestrator
 * that loads them and registers the plugin's top-level hooks.
 */

namespace WooChatPro;

if (!defined('ABSPATH')) exit;

final class Plugin {
    /** @var Plugin|null */
    private static $instance = null;

    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {}

    /**
     * Wire up the plugin: HPOS compat, i18n, dependency gate, modules,
     * migrations, polyfill, and the plugins-page row notice.
     */
    public function init() {
        add_action('before_woocommerce_init', [$this, 'declare_hpos_compat']);
        add_action('init', [$this, 'load_textdomain']);

        if (wcwp_is_woocommerce_active()) {
            $this->boot_modules();
        } else {
            add_action('admin_notices', [$this, 'render_wc_admin_notice']);
            add_action('network_admin_notices', [$this, 'render_wc_network_admin_notice']);
        }

        // Migrations also run on admin requests — covers WP auto-updates
        // where the activation hook never fires. Short-circuits on the
        // wcwp_db_version flag, so this is effectively free post-migration.
        add_action('admin_init', [$this, 'run_migrations'], 5);

        add_action('admin_init', [$this, 'enforce_woocommerce_dependency']);

        add_action(
            'after_plugin_row_' . plugin_basename(WCWP_PLUGIN_FILE),
            [$this, 'render_plugin_row_notice'],
            10,
            3
        );

        add_action('wp_enqueue_scripts', [$this, 'enqueue_uuid_polyfill'], 1);
    }

    public function declare_hpos_compat() {
        if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', WCWP_PLUGIN_FILE, true);
        }
    }

    public function load_textdomain() {
        load_plugin_textdomain('woochat-pro', false, dirname(plugin_basename(WCWP_PLUGIN_FILE)) . '/languages');
    }

    /**
     * Load every module file. Order matters — providers come before the
     * messaging dispatcher that resolves them via wcwp_get_provider().
     */
    public function boot_modules() {
        require_once WCWP_PATH . 'includes/providers/abstract-class-wcwp-provider.php';
        require_once WCWP_PATH . 'includes/providers/class-wcwp-provider-twilio.php';
        require_once WCWP_PATH . 'includes/providers/class-wcwp-provider-cloud.php';
        require_once WCWP_PATH . 'includes/messaging.php';

        require_once WCWP_PATH . 'includes/analytics.php';
        require_once WCWP_PATH . 'admin/settings-page.php';
        require_once WCWP_PATH . 'includes/chatbot-engine.php';
        require_once WCWP_PATH . 'includes/license-manager.php';
        require_once WCWP_PATH . 'includes/optout.php';
        require_once WCWP_PATH . 'includes/update-checker.php';

        require_once WCWP_PATH . 'includes/order-hooks.php';
        require_once WCWP_PATH . 'includes/cart-recovery.php';
        require_once WCWP_PATH . 'includes/scheduler.php';
        require_once WCWP_PATH . 'includes/campaigns.php';
    }

    public function run_migrations() {
        wcwp_run_migrations();
    }

    public function activate($network_wide) {
        if (!wcwp_is_woocommerce_active()) {
            $this->die_missing_woocommerce($network_wide);
        }
        wcwp_create_cart_recovery_table();
        wcwp_create_analytics_table();
        require_once WCWP_PATH . 'includes/campaigns.php';
        wcwp_create_campaign_tables();
        wcwp_schedule_cart_recovery_cron();
        wcwp_run_migrations();
    }

    public function deactivate() {
        // Guard kept: when WC has been deactivated, Plugin::init() skips
        // boot_modules(), so cart-recovery.php is never loaded. The
        // self-deactivate cascade in enforce_woocommerce_dependency()
        // then fires the deactivation hook on a request where this
        // function does not exist.
        if (function_exists('wcwp_unschedule_cart_recovery_cron')) {
            wcwp_unschedule_cart_recovery_cron();
        }
    }

    /**
     * Self-deactivate when WooCommerce is not active. Catches the case
     * where WC was deactivated without our activation hook firing.
     */
    public function enforce_woocommerce_dependency() {
        if (!function_exists('is_plugin_active')) {
            include_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        if (!wcwp_is_woocommerce_active() && is_plugin_active(plugin_basename(WCWP_PLUGIN_FILE))) {
            deactivate_plugins(plugin_basename(WCWP_PLUGIN_FILE));
            add_action('admin_notices', function() {
                if (!current_user_can('activate_plugins')) return;
                echo '<div class="notice notice-error"><p><strong>'
                    . esc_html__('WooChat Pro', 'woochat-pro') . '</strong> '
                    . esc_html__('was deactivated because WooCommerce is not active.', 'woochat-pro')
                    . '</p></div>';
            });
        }
    }

    public function render_wc_admin_notice() {
        if (!current_user_can('activate_plugins')) return;
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if ($screen && $screen->id !== 'plugins') return;
        echo '<div class="notice notice-error"><p>' . $this->dependency_notice_message() . '</p></div>';
    }

    public function render_wc_network_admin_notice() {
        if (!current_user_can('activate_plugins')) return;
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if ($screen && $screen->id !== 'plugins-network') return;
        echo '<div class="notice notice-error"><p>' . $this->dependency_notice_message() . '</p></div>';
    }

    public function render_plugin_row_notice($plugin_file, $plugin_data, $status) {
        if (wcwp_is_woocommerce_active()) return;
        if (!current_user_can('activate_plugins')) return;
        echo '<tr class="plugin-update-tr"><td colspan="3" class="plugin-update colspanchange"><div class="update-message notice inline notice-error notice-alt"><p>'
            . $this->dependency_notice_message()
            . '</p></div></td></tr>';
    }

    /**
     * Browser polyfill — only loaded when chatbot or cart recovery is on.
     * Enqueued at priority 1 with in_footer=false so it lands in <head>
     * before any consumer scripts. Loading as a real asset (not inline)
     * keeps the page CSP-friendly and avoids shipping bytes when both
     * features are off.
     */
    public function enqueue_uuid_polyfill() {
        $chatbot_on = get_option('wcwp_chatbot_enabled', 'no') === 'yes';
        $cart_on    = get_option('wcwp_cart_recovery_enabled', 'no') === 'yes';
        if (!$chatbot_on && !$cart_on) {
            return;
        }
        wp_enqueue_script(
            'wcwp-uuid-polyfill',
            WCWP_URL . 'assets/js/uuid-polyfill.js',
            [],
            WCWP_VERSION,
            false
        );
    }

    private function die_missing_woocommerce($network_wide) {
        if ($network_wide && is_multisite()) {
            if (!function_exists('is_plugin_active_for_network')) {
                include_once ABSPATH . 'wp-admin/includes/plugin.php';
            }
            if (function_exists('is_plugin_active_for_network') && !is_plugin_active_for_network('woocommerce/woocommerce.php')) {
                deactivate_plugins(plugin_basename(WCWP_PLUGIN_FILE));
                $plugins_url = network_admin_url('plugins.php');
                wp_die(
                    '<p><strong>' . esc_html__('WooChat Pro', 'woochat-pro') . '</strong> '
                    . esc_html__('requires WooCommerce to be network-activated.', 'woochat-pro')
                    . '</p><p><a href="' . esc_url($plugins_url) . '">'
                    . esc_html__('Return to Plugins', 'woochat-pro') . '</a></p>',
                    esc_html__('WooChat Pro', 'woochat-pro'),
                    ['response' => 200]
                );
            }
        }
        deactivate_plugins(plugin_basename(WCWP_PLUGIN_FILE));
        $plugins_url = is_network_admin() ? network_admin_url('plugins.php') : admin_url('plugins.php');
        wp_die(
            '<p><strong>' . esc_html__('WooChat Pro', 'woochat-pro') . '</strong> '
            . esc_html__('requires WooCommerce to be installed and active.', 'woochat-pro')
            . '</p><p><a href="' . esc_url($plugins_url) . '">'
            . esc_html__('Return to Plugins', 'woochat-pro') . '</a></p>',
            esc_html__('WooChat Pro', 'woochat-pro'),
            ['response' => 200]
        );
    }

    private function dependency_link() {
        $plugin_file = 'woocommerce/woocommerce.php';
        $is_installed = file_exists(WP_PLUGIN_DIR . '/' . $plugin_file);

        if ($is_installed) {
            if (current_user_can('activate_plugins')) {
                $url = wp_nonce_url(self_admin_url('plugins.php?action=activate&plugin=' . $plugin_file), 'activate-plugin_' . $plugin_file);
                return '<a href="' . esc_url($url) . '">' . esc_html__('Activate WooCommerce', 'woochat-pro') . '</a>';
            }
            return '';
        }

        if (current_user_can('install_plugins')) {
            $url = wp_nonce_url(self_admin_url('update.php?action=install-plugin&plugin=woocommerce'), 'install-plugin_woocommerce');
            return '<a href="' . esc_url($url) . '">' . esc_html__('Install WooCommerce', 'woochat-pro') . '</a>';
        }

        return '';
    }

    private function dependency_notice_message() {
        $link = $this->dependency_link();
        $tail = $link ? ' ' . $link . '.' : '';
        return '<strong>' . esc_html__('WooChat Pro', 'woochat-pro') . '</strong> '
            . esc_html__('requires WooCommerce. Please install and activate WooCommerce.', 'woochat-pro')
            . $tail;
    }
}
