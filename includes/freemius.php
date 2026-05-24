<?php
/**
 * Freemius SDK bootstrap for Zignites Chat Pro.
 *
 * Loaded by zignites-chat.php BEFORE the rest of the plugin so Freemius
 * hooks (`my_freemius_loaded`) can register before the plugin's modules
 * fire their own `plugins_loaded` listeners. License + update delivery
 * runs through Freemius; the legacy zignites_chat_license_* AJAX handlers
 * in license-manager.php are kept for backwards compatibility with sites
 * that activated a key before the Freemius migration and will be removed
 * once everyone has rolled to 1.1.x.
 *
 * @package ZignitesChatPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'zignites_chat_pro_freemius' ) ) {
	/**
	 * Lazily instantiate the Freemius singleton for this plugin.
	 *
	 * Mirrors the canonical `my_freemius()` factory Freemius generates in
	 * the dashboard "Get SDK init" wizard. The singleton is cached on a
	 * file-scoped global so subsequent calls (gate checks, admin pages)
	 * reuse the same instance.
	 *
	 * @return Freemius
	 */
	function zignites_chat_pro_freemius() {
		global $zignites_chat_pro_fs;

		if ( ! isset( $zignites_chat_pro_fs ) ) {
			// Bundled Freemius SDK lives in /freemius. start.php registers
			// the SDK with WordPress and exposes fs_dynamic_init().
			require_once ZIGNITES_CHAT_PATH . 'freemius/start.php';

			$zignites_chat_pro_fs = fs_dynamic_init( array(
				// TODO: replace the four placeholders below with the values
				// from your Freemius dashboard → Plugin → Settings:
				//   * id           — numeric plugin ID
				//   * public_key   — starts with "pk_"
				//   * slug         — should match the free plugin slug on
				//                    WordPress.org so cross-plugin handoff
				//                    works (zignites-chat).
				//   * premium_slug — slug for this Pro-only distribution
				//                    (zignites-chat-pro).
				'id'                  => 'FREEMIUS_PLUGIN_ID',
				'slug'                => 'zignites-chat',
				'premium_slug'        => 'zignites-chat-pro',
				'type'                => 'plugin',
				'public_key'          => 'pk_FREEMIUS_PUBLIC_KEY',
				'is_premium'          => true,
				'is_premium_only'     => true,
				'has_addons'          => false,
				'has_paid_plans'      => true,
				'menu'                => array(
					'slug'    => 'zignites-chat',
					'account' => true,
					'contact' => true,
					'support' => true,
				),
				'is_live'             => true,
			) );
		}

		return $zignites_chat_pro_fs;
	}

	// Boot Freemius immediately so its actions/filters register before any
	// of the plugin's modules wire their own hooks.
	zignites_chat_pro_freemius();

	// Signal that Freemius is ready. Any module that wants to add filters
	// to Freemius behaviour (custom upgrade URL, opt-in fields, etc.)
	// should hook this action rather than running at file scope.
	do_action( 'zignites_chat_pro_freemius_loaded' );
}
