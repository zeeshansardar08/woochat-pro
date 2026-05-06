<?php
/**
 * Plugin Name: WooChat Pro – WhatsApp for WooCommerce
 * Plugin URI:  https://zignites.com/woochat-pro
 * Description: Sends WhatsApp messages when a WooCommerce order is placed.
 * Version:     1.0.1
 * Author:      Zignite
 * Author URI:  https://zignites.com
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: woochat-pro
 * Domain Path: /languages
 * Requires Plugins: woocommerce
 * Requires at least: 6.0
 * Tested up to: 6.9
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'WCWP_VERSION', '1.0.1' );
define( 'WCWP_PATH', plugin_dir_path( __FILE__ ) );
define( 'WCWP_URL', plugin_dir_url( __FILE__ ) );
define( 'WCWP_PLUGIN_FILE', __FILE__ );

// Helpers must load before the plugin class so its dependency checks
// (wcwp_is_woocommerce_active) can resolve.
require_once WCWP_PATH . 'includes/helpers.php';
require_once WCWP_PATH . 'includes/class-wcwp-plugin.php';

\WooChatPro\Plugin::instance()->init();

register_activation_hook( __FILE__, 'wcwp_activate_plugin' );
register_deactivation_hook( __FILE__, 'wcwp_deactivate_plugin' );

/**
 * Global activation/deactivation shims. WordPress invokes these by name
 * via register_activation_hook / register_deactivation_hook, so they
 * cannot be class methods (the callback is resolved by string at the
 * time WP fires the hook, before class autoloading). Both delegate to
 * the Plugin singleton.
 */
function wcwp_activate_plugin( $network_wide ) {
	\WooChatPro\Plugin::instance()->activate( $network_wide );
}

function wcwp_deactivate_plugin() {
	\WooChatPro\Plugin::instance()->deactivate();
}
