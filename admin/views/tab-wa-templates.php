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
?>
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
					<input type="text" id="<?php echo esc_attr( $zignites_chat_id_prefix . '_name' ); ?>" class="regular-text" name="<?php echo esc_attr( $zignites_chat_field . '[name]' ); ?>" value="<?php echo esc_attr( $zignites_chat_entry['name'] ?? '' ); ?>" placeholder="order_confirmation" />
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
