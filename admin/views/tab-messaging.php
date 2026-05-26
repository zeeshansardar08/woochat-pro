<?php
if ( ! defined( 'ABSPATH' ) ) exit;
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- View partial scoped to the render function.
?>
<table class="form-table">
	<tr>
		<th scope="row">
			<label for="zignites_chat_test_phone"><?php esc_html_e( 'Send Test Message', 'zignites-chat' ); ?></label>
			<span class="zignites-chat-help-icon">?<span class="zignites-chat-tooltip"><?php esc_html_e( 'Send a one-off message to verify your setup. Test mode logs instead of sending.', 'zignites-chat' ); ?></span></span>
		</th>
		<td>
			<input type="text" id="zignites_chat_test_phone" class="regular-text" placeholder="e.g. +14155238886" value="<?php echo esc_attr( get_option( 'zignites_chat_test_phone', '' ) ); ?>" />
			<p class="description"><?php esc_html_e( 'Phone number to receive the test message.', 'zignites-chat' ); ?></p>
			<textarea id="zignites_chat_test_message" rows="4" class="large-text" placeholder="<?php esc_attr_e( 'Type your test message here...', 'zignites-chat' ); ?>"><?php echo esc_textarea( get_option( 'zignites_chat_test_message', 'Hello! This is a test message from Zignites Chat.' ) ); ?></textarea>
			<div style="margin-top:8px;display:flex;align-items:center;gap:10px;">
				<button type="button" class="button button-primary" id="zignites-chat-send-test-message"><?php esc_html_e( 'Send Test Message', 'zignites-chat' ); ?></button>
				<span id="zignites-chat-test-mode-badge" style="display:none;background:#fff3cd;color:#856404;border:1px solid #ffe066;border-radius:12px;padding:2px 8px;font-size:12px;font-weight:600;"><?php esc_html_e( 'Test Mode ON', 'zignites-chat' ); ?></span>
				<span id="zignites-chat-test-status" style="font-weight:600;"></span>
			</div>
			<p id="zignites-chat-test-log-hint" class="description" style="<?php echo esc_attr( 'margin-top:6px;' . ( get_option( 'zignites_chat_test_mode_enabled', 'no' ) === 'yes' ? '' : 'display:none;' ) ); ?>"><?php esc_html_e( 'Test Mode is enabled. Messages are logged to wp-content/uploads/zignites-chat/zignites-chat.log.', 'zignites-chat' ); ?></p>
		</td>
	</tr>
	<tr>
		<th scope="row">
			<label for="zignites_chat_order_message_template"><?php esc_html_e( 'Order Message Template', 'zignites-chat' ); ?></label>
			<span class="zignites-chat-help-icon">?<span class="zignites-chat-tooltip"><?php esc_html_e( 'Customize the WhatsApp message sent for new orders. Use placeholders: {name}, {order_id}, {total}, {currency_symbol}', 'zignites-chat' ); ?></span></span>
		</th>
		<td>
			<textarea id="zignites_chat_order_message_template" name="zignites_chat_order_message_template" rows="5" class="large-text"><?php echo esc_textarea( get_option( 'zignites_chat_order_message_template', 'Hi {name}, thanks for your order #{order_id}! Total: {total} {currency_symbol}.' ) ); ?></textarea>
			<p class="description"><?php
				/* translators: do not translate placeholders inside curly braces */
				esc_html_e( 'Use placeholders: {name}, {order_id}, {total}, {currency_symbol}', 'zignites-chat' );
			?></p>
			<p style="margin-top:6px;">
				<button type="button" class="button zignites-chat-browse-templates" data-target="zignites_chat_order_message_template" data-kind="order">
					<span class="dashicons dashicons-book" style="vertical-align:middle;line-height:28px;"></span>
					<?php esc_html_e( 'Browse template library', 'zignites-chat' ); ?>
				</button>
			</p>
		</td>
	</tr>
</table>
