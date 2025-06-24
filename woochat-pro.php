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

// Load Order Hooks
require_once WCWP_PATH . 'includes/order-hooks.php';
require_once WCWP_PATH . 'admin/settings-page.php';
require_once WCWP_PATH . 'includes/cart-recovery.php';
require_once WCWP_PATH . 'includes/chatbot-engine.php';
require_once WCWP_PATH . 'includes/license-manager.php';
