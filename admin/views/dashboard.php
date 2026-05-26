<?php
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="zignites-chat-plugin-splash">
	<span class="zignites-chat-plugin-logo">💬</span>
	<span class="zignites-chat-plugin-title">Zignites Chat</span>
</div>

<div class="zignites-chat-dashboard-widget">
	<div class="zignites-chat-dashboard-desc">
		<p><?php esc_html_e( 'Send WhatsApp order notifications to your customers automatically — powered by Twilio or the WhatsApp Cloud API.', 'zignites-chat' ); ?></p>
		<ul>
			<li>✓ <?php esc_html_e( 'Automatic order messages with custom templates', 'zignites-chat' ); ?></li>
			<li>✓ <?php esc_html_e( 'Built-in opt-out management', 'zignites-chat' ); ?></li>
			<li>✓ <?php esc_html_e( 'FAQ chatbot widget for your storefront', 'zignites-chat' ); ?></li>
			<li>✓ <?php esc_html_e( 'Test mode with log viewer for easy debugging', 'zignites-chat' ); ?></li>
		</ul>
	</div>
	<div class="zignites-chat-dashboard-widget-actions">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=zignites-chat-general' ) ); ?>" class="button"><?php esc_html_e( 'General Settings', 'zignites-chat' ); ?></a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=zignites-chat-messaging' ) ); ?>" class="button"><?php esc_html_e( 'Send Test Message', 'zignites-chat' ); ?></a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=zignites-chat-logs' ) ); ?>" class="button"><?php esc_html_e( 'View Logs', 'zignites-chat' ); ?></a>
	</div>
</div>

<p style="margin-top:16px;color:#555;">
	<?php esc_html_e( 'Want cart recovery, analytics, bulk campaigns and more?', 'zignites-chat' ); ?>
	<a href="https://zignites.com/plugins/zignites-chat-pro" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Upgrade to Zignites Chat Pro →', 'zignites-chat' ); ?></a>
</p>

<?php zignites_chat_render_onboarding_modal(); ?>
