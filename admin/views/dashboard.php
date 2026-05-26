<?php
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="zignites-chat-plugin-splash">
	<span class="zignites-chat-plugin-logo">💬</span>
	<span class="zignites-chat-plugin-title">Zignites Chat</span>
</div>

<div class="zignites-chat-dashboard-widget">
	<div class="zignites-chat-dashboard-widget-actions">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=zignites-chat-general' ) ); ?>" class="button"><?php esc_html_e( 'General Settings', 'zignites-chat' ); ?></a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=zignites-chat-messaging' ) ); ?>" class="button"><?php esc_html_e( 'Send Test Message', 'zignites-chat' ); ?></a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=zignites-chat-logs' ) ); ?>" class="button"><?php esc_html_e( 'View Logs', 'zignites-chat' ); ?></a>
	</div>
</div>

<p style="margin-top:20px;">
	<?php esc_html_e( 'Want cart recovery, analytics, bulk campaigns and more?', 'zignites-chat' ); ?>
	<a href="https://zignites.com/zignites-chat" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Upgrade to Zignites Chat Pro →', 'zignites-chat' ); ?></a>
</p>

<?php zignites_chat_render_onboarding_modal(); ?>
