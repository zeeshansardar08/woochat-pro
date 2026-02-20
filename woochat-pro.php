<?php
/**
 * Plugin Name: WooChat Pro – WhatsApp for WooCommerce
 * Description: Sends WhatsApp messages when a WooCommerce order is placed.
 * Version: 1.0.1
 * Author: ZeeCreatives
 * License: GPL2
 * Requires Plugins: woocommerce
 * Requires at least: 6.0
 * Tested up to: 6.7
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'WCWP_VERSION', '1.0.1' );
define( 'WCWP_PATH', plugin_dir_path( __FILE__ ) );
define( 'WCWP_URL', plugin_dir_url( __FILE__ ) );
define( 'WCWP_PLUGIN_FILE', __FILE__ );

// Load helpers early for dependency checks.
require_once WCWP_PATH . 'includes/helpers.php';

function wcwp_wc_dependency_link() {
	$plugin_file = 'woocommerce/woocommerce.php';
	$is_installed = file_exists(WP_PLUGIN_DIR . '/' . $plugin_file);

	if ($is_installed) {
		if (current_user_can('activate_plugins')) {
			$url = wp_nonce_url(self_admin_url('plugins.php?action=activate&plugin=' . $plugin_file), 'activate-plugin_' . $plugin_file);
			return '<a href="' . esc_url($url) . '">Activate WooCommerce</a>';
		}
		return '';
	}

	if (current_user_can('install_plugins')) {
		$url = wp_nonce_url(self_admin_url('update.php?action=install-plugin&plugin=woocommerce'), 'install-plugin_woocommerce');
		return '<a href="' . esc_url($url) . '">Install WooCommerce</a>';
	}

	return '';
}

function wcwp_wc_dependency_notice_message() {
	$link = wcwp_wc_dependency_link();
	$tail = $link ? ' ' . $link . '.' : '';
	return '<strong>WooChat Pro</strong> requires WooCommerce. Please install and activate WooCommerce.' . $tail;
}

function wcwp_bootstrap() {
	require_once WCWP_PATH . 'includes/analytics.php';
	require_once WCWP_PATH . 'admin/settings-page.php';
	require_once WCWP_PATH . 'includes/chatbot-engine.php';
	require_once WCWP_PATH . 'includes/license-manager.php';
	require_once WCWP_PATH . 'includes/optout.php';
	require_once WCWP_PATH . 'includes/update-checker.php';

	require_once WCWP_PATH . 'includes/order-hooks.php';
	require_once WCWP_PATH . 'includes/cart-recovery.php';
	require_once WCWP_PATH . 'includes/scheduler.php';
}

if (function_exists('wcwp_is_woocommerce_active') && wcwp_is_woocommerce_active()) {
	wcwp_bootstrap();
} else {
	add_action('admin_notices', function() {
		if (!current_user_can('activate_plugins')) return;
		$screen = function_exists('get_current_screen') ? get_current_screen() : null;
		if ($screen && $screen->id !== 'plugins') return;
		echo '<div class="notice notice-error"><p>' . wcwp_wc_dependency_notice_message() . '</p></div>';
	});
	add_action('network_admin_notices', function() {
		if (!current_user_can('activate_plugins')) return;
		$screen = function_exists('get_current_screen') ? get_current_screen() : null;
		if ($screen && $screen->id !== 'plugins-network') return;
		echo '<div class="notice notice-error"><p>' . wcwp_wc_dependency_notice_message() . '</p></div>';
	});
}

register_activation_hook(__FILE__, 'wcwp_activate_plugin');
register_deactivation_hook(__FILE__, 'wcwp_deactivate_plugin');

function wcwp_activate_plugin($network_wide) {
	if (!function_exists('wcwp_is_woocommerce_active') || !wcwp_is_woocommerce_active()) {
		if ($network_wide && is_multisite()) {
			if (!function_exists('is_plugin_active_for_network')) {
				include_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			if (function_exists('is_plugin_active_for_network') && !is_plugin_active_for_network('woocommerce/woocommerce.php')) {
				deactivate_plugins(plugin_basename(__FILE__));
				$plugins_url = network_admin_url('plugins.php');
				wp_die(
					'<p><strong>WooChat Pro</strong> requires WooCommerce to be network-activated.</p><p><a href="' . esc_url($plugins_url) . '">Return to Plugins</a></p>',
					'WooChat Pro',
					['response' => 200]
				);
			}
		}
		deactivate_plugins(plugin_basename(__FILE__));
		$plugins_url = is_network_admin() ? network_admin_url('plugins.php') : admin_url('plugins.php');
		wp_die(
			'<p><strong>WooChat Pro</strong> requires WooCommerce to be installed and active.</p><p><a href="' . esc_url($plugins_url) . '">Return to Plugins</a></p>',
			'WooChat Pro',
			['response' => 200]
		);
	}
	if (function_exists('wcwp_create_cart_recovery_table')) {
		wcwp_create_cart_recovery_table();
	}
	if (function_exists('wcwp_create_analytics_table')) {
		wcwp_create_analytics_table();
	}
	if (function_exists('wcwp_schedule_cart_recovery_cron')) {
		wcwp_schedule_cart_recovery_cron();
	}
}

function wcwp_deactivate_plugin() {
	if (function_exists('wcwp_unschedule_cart_recovery_cron')) {
		wcwp_unschedule_cart_recovery_cron();
	}
}

add_action('admin_init', function() {
	if (!function_exists('is_plugin_active')) {
		include_once ABSPATH . 'wp-admin/includes/plugin.php';
	}
	if (function_exists('wcwp_is_woocommerce_active') && !wcwp_is_woocommerce_active() && is_plugin_active(plugin_basename(__FILE__))) {
		deactivate_plugins(plugin_basename(__FILE__));
		add_action('admin_notices', function() {
			if (!current_user_can('activate_plugins')) return;
			echo '<div class="notice notice-error"><p><strong>WooChat Pro</strong> was deactivated because WooCommerce is not active.</p></div>';
		});
	}
});

add_action('after_plugin_row_' . plugin_basename(__FILE__), function($plugin_file, $plugin_data, $status) {
	if (function_exists('wcwp_is_woocommerce_active') && wcwp_is_woocommerce_active()) return;
	if (!current_user_can('activate_plugins')) return;
	echo '<tr class="plugin-update-tr"><td colspan="3" class="plugin-update colspanchange"><div class="update-message notice inline notice-error notice-alt"><p>' . wcwp_wc_dependency_notice_message() . '</p></div></td></tr>';
}, 10, 3);

// Browser polyfills used across frontend hooks
add_action('wp_head', function() {
	?>
	<script>
	if (typeof window !== 'undefined') {
		if (typeof window.crypto === 'undefined') {
			window.crypto = {};
		}
		if (typeof window.crypto.randomUUID !== 'function') {
			window.crypto.randomUUID = function () {
				const bytes = new Uint8Array(16);
				const getRandomValues = (window.crypto && window.crypto.getRandomValues) ? window.crypto.getRandomValues.bind(window.crypto) : null;
				if (getRandomValues) {
					getRandomValues(bytes);
					// Set version (4) and variant bits per RFC4122
					bytes[6] = (bytes[6] & 0x0f) | 0x40;
					bytes[8] = (bytes[8] & 0x3f) | 0x80;
					const toHex = Array.from(bytes, b => b.toString(16).padStart(2, '0'));
					return `${toHex[0]}${toHex[1]}${toHex[2]}${toHex[3]}-${toHex[4]}${toHex[5]}-${toHex[6]}${toHex[7]}-${toHex[8]}${toHex[9]}-${toHex[10]}${toHex[11]}${toHex[12]}${toHex[13]}${toHex[14]}${toHex[15]}`;
				}
				// Fallback: non-cryptographic
				let template = 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx';
				return template.replace(/[xy]/g, function(c) {
					const r = Math.random() * 16 | 0;
					const v = c === 'x' ? r : (r & 0x3 | 0x8);
					return v.toString(16);
				});
			};
		}
	}
	</script>
	<?php
}, 1);

