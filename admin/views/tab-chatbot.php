<?php
if ( ! defined( 'ABSPATH' ) ) exit;
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- View partial scoped to the render function.
?>
<table class="form-table">
	<tr>
		<th scope="row">
			<label for="zignites_chat_chatbot_enabled"><?php esc_html_e( 'Enable Chatbot', 'zignites-chat' ); ?></label>
			<span class="zignites-chat-help-icon">?<span class="zignites-chat-tooltip"><?php esc_html_e( 'Toggle the floating WhatsApp chatbot widget on your site.', 'zignites-chat' ); ?></span></span>
		</th>
		<td>
			<select name="zignites_chat_chatbot_enabled" id="zignites_chat_chatbot_enabled">
				<option value="yes" <?php selected( get_option( 'zignites_chat_chatbot_enabled' ), 'yes' ); ?>><?php esc_html_e( 'Yes', 'zignites-chat' ); ?></option>
				<option value="no"  <?php selected( get_option( 'zignites_chat_chatbot_enabled' ), 'no' ); ?>><?php esc_html_e( 'No', 'zignites-chat' ); ?></option>
			</select>
			<p class="description"><?php esc_html_e( 'Show or hide the floating WhatsApp widget on the front end.', 'zignites-chat' ); ?></p>
		</td>
	</tr>
	<tr>
		<th scope="row">
			<label for="zignites-chat-chatbot-welcome"><?php esc_html_e( 'Welcome Message', 'zignites-chat' ); ?></label>
			<span class="zignites-chat-help-icon">?<span class="zignites-chat-tooltip"><?php esc_html_e( 'The greeting displayed in the chat bubble before the customer taps to start a conversation.', 'zignites-chat' ); ?></span></span>
		</th>
		<td>
			<input type="text" id="zignites-chat-chatbot-welcome" name="zignites_chat_chatbot_welcome" class="regular-text" value="<?php echo esc_attr( get_option( 'zignites_chat_chatbot_welcome', 'Hi! How can I help you?' ) ); ?>" />
		</td>
	</tr>
	<tr>
		<th scope="row">
			<label for="zignites_chat_faq_pairs"><?php esc_html_e( 'FAQ Rules (JSON)', 'zignites-chat' ); ?></label>
			<span class="zignites-chat-help-icon">?<span class="zignites-chat-tooltip"><?php esc_html_e( 'Add question/answer pairs for the chatbot. The widget matches keywords and replies automatically.', 'zignites-chat' ); ?></span></span>
		</th>
		<td>
			<textarea name="zignites_chat_faq_pairs" id="zignites_chat_faq_pairs" rows="6" class="large-text"><?php echo esc_textarea( get_option( 'zignites_chat_faq_pairs', '[{"question":"order","answer":"You can track your order here: yourdomain.com/track"},{"question":"return","answer":"We offer 7-day easy returns."}]' ) ); ?></textarea>
			<p class="description"><?php esc_html_e( 'JSON array of {"question":"…","answer":"…"} pairs. Keyword matching is case-insensitive.', 'zignites-chat' ); ?></p>
		</td>
	</tr>
</table>

<h3><?php esc_html_e( 'Agent', 'zignites-chat' ); ?></h3>
<p class="description"><?php esc_html_e( 'The team member who receives customer chats. Upgrade to Pro to add multiple agents and load-balance between them.', 'zignites-chat' ); ?></p>

<?php $zignites_chat_agents = array_slice( zignites_chat_get_agents(), 0, 1 ); ?>
<table class="widefat striped" id="zignites-chat-agents-table">
	<thead>
		<tr>
			<th><?php esc_html_e( 'Agent name', 'zignites-chat' ); ?></th>
			<th><?php esc_html_e( 'WhatsApp number', 'zignites-chat' ); ?></th>
		</tr>
	</thead>
	<tbody>
		<?php if ( empty( $zignites_chat_agents ) ) : ?>
			<tr class="zignites-chat-agent-row">
				<td><input type="text" class="zignites-chat-agent-name regular-text" placeholder="<?php esc_attr_e( 'e.g. Sales', 'zignites-chat' ); ?>" value="" /></td>
				<td><input type="text" class="zignites-chat-agent-phone regular-text" placeholder="<?php esc_attr_e( 'e.g. +14155550100', 'zignites-chat' ); ?>" value="" /></td>
			</tr>
		<?php else : foreach ( $zignites_chat_agents as $zignites_chat_agent ) : ?>
			<tr class="zignites-chat-agent-row">
				<td><input type="text" class="zignites-chat-agent-name regular-text" placeholder="<?php esc_attr_e( 'e.g. Sales', 'zignites-chat' ); ?>" value="<?php echo esc_attr( $zignites_chat_agent['name'] ); ?>" /></td>
				<td><input type="text" class="zignites-chat-agent-phone regular-text" placeholder="<?php esc_attr_e( 'e.g. +14155550100', 'zignites-chat' ); ?>" value="<?php echo esc_attr( $zignites_chat_agent['phone'] ); ?>" /></td>
			</tr>
		<?php endforeach; endif; ?>
	</tbody>
</table>
<input type="hidden" name="zignites_chat_agents" id="zignites_chat_agents_input" value="<?php echo esc_attr( get_option( 'zignites_chat_agents', '[]' ) ); ?>" />
