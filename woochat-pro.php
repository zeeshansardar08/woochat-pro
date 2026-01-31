<?php
/**
 * Plugin Name: WooChat Pro – WhatsApp for WooCommerce
 * Description: Sends WhatsApp messages when a WooCommerce order is placed.
 * Version: 1.0
 * Author: ZeeCreatives
 * License: GPL2
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'WCWP_PATH', plugin_dir_path( __FILE__ ) );
define( 'WCWP_URL', plugin_dir_url( __FILE__ ) );

// Load core modules
require_once WCWP_PATH . 'includes/helpers.php';
require_once WCWP_PATH . 'includes/analytics.php';
require_once WCWP_PATH . 'includes/order-hooks.php';
require_once WCWP_PATH . 'admin/settings-page.php';
require_once WCWP_PATH . 'includes/cart-recovery.php';
require_once WCWP_PATH . 'includes/chatbot-engine.php';
require_once WCWP_PATH . 'includes/license-manager.php';
require_once WCWP_PATH . 'includes/scheduler.php';
require_once WCWP_PATH . 'includes/optout.php';

register_activation_hook(__FILE__, 'wcwp_activate_plugin');
register_deactivation_hook(__FILE__, 'wcwp_deactivate_plugin');

function wcwp_activate_plugin() {
	if (function_exists('wcwp_create_cart_recovery_table')) {
		wcwp_create_cart_recovery_table();
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
