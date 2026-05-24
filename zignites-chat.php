<?php
/**
 * Plugin Name: Zignites Chat Pro – WhatsApp Marketing for WooCommerce
 * Plugin URI:  https://zignites.com/plugins/zignites-chat-pro
 * Description: WhatsApp order notifications, abandoned cart recovery, scheduled follow-ups, bulk campaigns, analytics, multi-agent chat with GPT fallback, A/B testing, and outbound webhooks — all in one premium plugin. Supports Twilio and Meta WhatsApp Cloud API.
 * Version:     1.1.0
 * Author:      Zignites
 * Author URI:  https://zignites.com
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: zignites-chat
 * Domain Path: /languages
 * Requires Plugins: woocommerce
 * Requires at least: 6.0
 * Tested up to: 7.0
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'ZIGNITES_CHAT_VERSION', '1.1.0' );
define( 'ZIGNITES_CHAT_IS_PRO', true );
define( 'ZIGNITES_CHAT_PATH', plugin_dir_path( __FILE__ ) );
define( 'ZIGNITES_CHAT_URL', plugin_dir_url( __FILE__ ) );
define( 'ZIGNITES_CHAT_PLUGIN_FILE', __FILE__ );

// Freemius must boot first so its hooks register before any plugin
// module fires its own listeners. The SDK lives in /freemius and is
// initialised by includes/freemius.php.
require_once ZIGNITES_CHAT_PATH . 'includes/freemius.php';

// Helpers must load before the plugin class so its dependency checks
// (zignites_chat_is_woocommerce_active) can resolve.
require_once ZIGNITES_CHAT_PATH . 'includes/helpers.php';
require_once ZIGNITES_CHAT_PATH . 'includes/class-zignites-chat-plugin.php';

\ZignitesChat\Plugin::instance()->init();

register_activation_hook( __FILE__, 'zignites_chat_activate_plugin' );
register_deactivation_hook( __FILE__, 'zignites_chat_deactivate_plugin' );

/**
 * Global activation/deactivation shims. WordPress invokes these by name
 * via register_activation_hook / register_deactivation_hook, so they
 * cannot be class methods (the callback is resolved by string at the
 * time WP fires the hook, before class autoloading). Both delegate to
 * the Plugin singleton.
 */
function zignites_chat_activate_plugin( $network_wide ) {
	\ZignitesChat\Plugin::instance()->activate( $network_wide );
}

function zignites_chat_deactivate_plugin() {
	\ZignitesChat\Plugin::instance()->deactivate();
}
