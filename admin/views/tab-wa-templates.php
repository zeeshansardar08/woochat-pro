<?php
/**
 * WhatsApp approved templates (HSM) settings view.
 *
 * Lets the admin map each automated message type to a Meta-approved
 * template name + language + ordered body variables. Rendered inside the
 * options.php form opened by zignites_chat_render_wa_templates_page().
 */
if ( ! defined( 'ABSPATH' ) ) exit;
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- View partial: locals are scoped to the including render function.

$zignites_chat_wa_types     = zignites_chat_wa_template_types();
$zignites_chat_wa_templates = zignites_chat_get_wa_templates();
$zignites_chat_wa_provider  = get_option( 'zignites_chat_api_provider', 'twilio' );
$zignites_chat_wa_spares    = 3; // blank rows offered for adding new variables.

$zignites_chat_wa_waba_id   = get_option( 'zignites_chat_cloud_waba_id', '' );
$zignites_chat_wa_synced    = zignites_chat_wa_get_synced_templates();
$zignites_chat_wa_synced_at = get_option( 'zignites_chat_wa_synced_at', '' );

// Approved templates only, for the name autocomplete (the dropdown of valid
// choices); the reference list below shows everything so the admin can see
// why a pending/rejected one isn't selectable.
$zignites_chat_wa_approved_names = array();
foreach ( $zignites_chat_wa_synced as $zignites_chat_wa_tpl ) {
	if ( ( $zignites_chat_wa_tpl['status'] ?? '' ) === 'APPROVED' && ! in_array( $zignites_chat_wa_tpl['name'], $zignites_chat_wa_approved_names, true ) ) {
		$zignites_chat_wa_approved_names[] = $zignites_chat_wa_tpl['name'];
	}
}
?>

<div class="zignites-chat-card" style="margin:16px 0;padding:4px 16px 12px;border:1px solid #e2e4e7;border-radius:6px;background:#fff;">
	<h2 style="margin:12px 0 4px;"><?php esc_html_e( 'Sync from Meta', 'zignites-chat' ); ?></h2>
	<p class="description" style="max-width:760px;margin-top:0;">
		<?php esc_html_e( 'Pull your approved templates straight from WhatsApp Manager so you can pick the exact name (and see how many variables each one expects) instead of typing it by hand.', 'zignites-chat' ); ?>
	</p>
	<table class="form-table" role="presentation">
		<tr>
			<th scope="row"><label for="zignites_chat_cloud_waba_id"><?php esc_html_e( 'WhatsApp Business Account ID', 'zignites-chat' ); ?></label></th>
			<td>
				<input type="text" id="zignites_chat_cloud_waba_id" class="regular-text" name="zignites_chat_cloud_waba_id" value="<?php echo esc_attr( $zignites_chat_wa_waba_id ); ?>" placeholder="123456789012345" />
				<p class="description"><?php esc_html_e( 'Found in WhatsApp Manager → Account tools → WhatsApp Business Account ID. Uses your saved Cloud API token.', 'zignites-chat' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'Approved templates', 'zignites-chat' ); ?></th>
			<td>
				<button type="button" class="button" id="zignites-chat-sync-templates"><?php esc_html_e( 'Sync approved templates', 'zignites-chat' ); ?></button>
				<span id="zignites-chat-sync-status" class="description" style="margin-left:8px;"></span>
				<?php if ( $zignites_chat_wa_synced_at ) : ?>
					<p class="description" style="margin-top:8px;">
						<?php
						printf(
							/* translators: 1: number of templates, 2: human-readable time since last sync */
							esc_html__( 'Last synced %1$d template(s) %2$s ago.', 'zignites-chat' ),
							count( $zignites_chat_wa_synced ),
							esc_html( human_time_diff( strtotime( $zignites_chat_wa_synced_at ), current_time( 'timestamp' ) ) )
						);
						?>
					</p>
				<?php endif; ?>

				<?php if ( ! empty( $zignites_chat_wa_synced ) ) : ?>
					<table class="widefat striped" style="margin-top:10px;max-width:760px;">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Name', 'zignites-chat' ); ?></th>
								<th><?php esc_html_e( 'Language', 'zignites-chat' ); ?></th>
								<th><?php esc_html_e( 'Status', 'zignites-chat' ); ?></th>
								<th><?php esc_html_e( 'Category', 'zignites-chat' ); ?></th>
								<th><?php esc_html_e( 'Variables', 'zignites-chat' ); ?></th>
							</tr>
						</thead>
						<tbody>
						<?php foreach ( $zignites_chat_wa_synced as $zignites_chat_wa_tpl ) : ?>
							<tr>
								<td><code><?php echo esc_html( $zignites_chat_wa_tpl['name'] ); ?></code></td>
								<td><?php echo esc_html( $zignites_chat_wa_tpl['language'] ); ?></td>
								<td><?php echo esc_html( $zignites_chat_wa_tpl['status'] ); ?></td>
								<td><?php echo esc_html( $zignites_chat_wa_tpl['category'] ); ?></td>
								<td><?php echo (int) $zignites_chat_wa_tpl['body_params']; ?></td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			</td>
		</tr>
	</table>
</div>

<?php if ( ! empty( $zignites_chat_wa_approved_names ) ) : ?>
	<datalist id="zignites-chat-wa-template-names">
		<?php foreach ( $zignites_chat_wa_approved_names as $zignites_chat_wa_name ) : ?>
			<option value="<?php echo esc_attr( $zignites_chat_wa_name ); ?>"></option>
		<?php endforeach; ?>
	</datalist>
<?php endif; ?>

<p class="description" style="max-width:760px;margin:8px 0 4px;">
	<?php esc_html_e( 'Meta\'s WhatsApp Cloud API rejects free-form, business-initiated messages sent outside the 24-hour customer-service window. Map each message type to one of your approved templates so cart recovery, follow-ups and campaigns stay deliverable. Template names and variables must match exactly what you submitted in WhatsApp Manager.', 'zignites-chat' ); ?>
</p>

<?php if ( $zignites_chat_wa_provider !== 'cloud' ) : ?>
	<div class="notice notice-warning inline" style="margin:12px 0;">
		<p>
			<?php esc_html_e( 'Your active provider is not the WhatsApp Cloud API, so these templates will not be used yet. You can still configure them now — they take effect once you switch the provider to Cloud on General Settings.', 'zignites-chat' ); ?>
		</p>
	</div>
<?php endif; ?>

<?php foreach ( $zignites_chat_wa_types as $zignites_chat_type => $zignites_chat_meta ) :
	$zignites_chat_entry     = $zignites_chat_wa_templates[ $zignites_chat_type ];
	$zignites_chat_vars      = isset( $zignites_chat_entry['variables'] ) && is_array( $zignites_chat_entry['variables'] ) ? $zignites_chat_entry['variables'] : array();
	$zignites_chat_field     = 'zignites_chat_wa_templates[' . $zignites_chat_type . ']';
	$zignites_chat_id_prefix = 'zignites_chat_wa_' . $zignites_chat_type;
	$zignites_chat_row_count = count( $zignites_chat_vars ) + $zignites_chat_spares;
	$zignites_chat_placeholders = implode( ' ', (array) $zignites_chat_meta['placeholders'] );
	?>
	<div class="zignites-chat-card" style="margin:16px 0;padding:4px 16px 8px;border:1px solid #e2e4e7;border-radius:6px;background:#fff;">
		<h2 style="margin:12px 0 0;"><?php echo esc_html( $zignites_chat_meta['label'] ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Use a template', 'zignites-chat' ); ?></th>
				<td>
					<label>
						<input type="hidden" name="<?php echo esc_attr( $zignites_chat_field . '[enabled]' ); ?>" value="no" />
						<input type="checkbox" name="<?php echo esc_attr( $zignites_chat_field . '[enabled]' ); ?>" value="yes" <?php checked( $zignites_chat_entry['enabled'] ?? 'no', 'yes' ); ?> />
						<?php esc_html_e( 'Send this message type as an approved template', 'zignites-chat' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="<?php echo esc_attr( $zignites_chat_id_prefix . '_name' ); ?>"><?php esc_html_e( 'Template name', 'zignites-chat' ); ?></label></th>
				<td>
					<input type="text" id="<?php echo esc_attr( $zignites_chat_id_prefix . '_name' ); ?>" class="regular-text" name="<?php echo esc_attr( $zignites_chat_field . '[name]' ); ?>" value="<?php echo esc_attr( $zignites_chat_entry['name'] ?? '' ); ?>" placeholder="order_confirmation"<?php echo ! empty( $zignites_chat_wa_approved_names ) ? ' list="zignites-chat-wa-template-names"' : ''; ?> />
					<p class="description"><?php esc_html_e( 'Exact name as approved in WhatsApp Manager (lowercase, underscores).', 'zignites-chat' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="<?php echo esc_attr( $zignites_chat_id_prefix . '_language' ); ?>"><?php esc_html_e( 'Language code', 'zignites-chat' ); ?></label></th>
				<td>
					<input type="text" id="<?php echo esc_attr( $zignites_chat_id_prefix . '_language' ); ?>" class="small-text" name="<?php echo esc_attr( $zignites_chat_field . '[language]' ); ?>" value="<?php echo esc_attr( $zignites_chat_entry['language'] ?? 'en_US' ); ?>" placeholder="en_US" />
					<p class="description"><?php esc_html_e( 'The template language, e.g. en_US, en, es, pt_BR.', 'zignites-chat' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Body variables', 'zignites-chat' ); ?></th>
				<td>
					<p class="description" style="margin-top:0;">
						<?php
						printf(
							/* translators: %s: list of available placeholder tokens */
							esc_html__( 'Each row maps to a template variable in order: row 1 = {{1}}, row 2 = {{2}}, and so on. Use any of these placeholders: %s. Blank rows are skipped.', 'zignites-chat' ),
							'<code>' . esc_html( $zignites_chat_placeholders ) . '</code>'
						);
						?>
					</p>
					<?php for ( $zignites_chat_i = 0; $zignites_chat_i < $zignites_chat_row_count; $zignites_chat_i++ ) :
						$zignites_chat_val = isset( $zignites_chat_vars[ $zignites_chat_i ] ) ? $zignites_chat_vars[ $zignites_chat_i ] : '';
						?>
						<div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;">
							<span style="display:inline-block;min-width:46px;color:#666;font-family:monospace;">{{<?php echo (int) ( $zignites_chat_i + 1 ); ?>}}</span>
							<input type="text" class="regular-text" name="<?php echo esc_attr( $zignites_chat_field . '[variables][]' ); ?>" value="<?php echo esc_attr( $zignites_chat_val ); ?>" placeholder="<?php echo esc_attr( $zignites_chat_meta['placeholders'][ $zignites_chat_i ] ?? '{name}' ); ?>" />
						</div>
					<?php endfor; ?>
					<p class="description"><?php esc_html_e( 'Save to add more rows — three blank rows are added each time.', 'zignites-chat' ); ?></p>
				</td>
			</tr>
		</table>
	</div>
<?php endforeach; ?>
