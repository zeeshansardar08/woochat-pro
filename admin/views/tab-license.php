<?php
if (!defined('ABSPATH')) exit;
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- View partial: these variables are scoped to the render function that includes this file, not the global namespace.
?>
<table class="form-table">
        <tr>
            <th scope="row"><label for="zignites_chat_license_key"><?php esc_html_e('License Key', 'zignites-chat'); ?></label><span class="zignites-chat-help-icon">?<span class="zignites-chat-tooltip"><?php esc_html_e('Enter your Pro license key to unlock premium features.', 'zignites-chat'); ?></span></span></th>
            <td>
                <input type="text" name="zignites_chat_license_key" id="zignites_chat_license_key" value="<?php echo esc_attr(get_option('zignites_chat_license_key')); ?>" class="regular-text" />
                <div style="margin-top:8px; display:flex; align-items:center; gap:12px;">
                    <?php $status = get_option('zignites_chat_license_status', 'inactive'); ?>
                    <span id="zignites-chat-license-status" class="zignites-chat-badge <?php echo esc_attr( $status === 'valid' ? 'zignites-chat-badge-success' : 'zignites-chat-badge-muted' ); ?>">
                        <?php echo esc_html(zignites_chat_license_status_label($status)); ?>
                    </span>
                    <button type="button" class="button button-primary" id="zignites-chat-activate-license"><?php esc_html_e('Activate', 'zignites-chat'); ?></button>
                    <button type="button" class="button" id="zignites-chat-deactivate-license"><?php esc_html_e('Deactivate', 'zignites-chat'); ?></button>
                </div>
                <?php
                $expires = get_option('zignites_chat_license_expires');
                $message = get_option('zignites_chat_license_message');
                if ($expires) {
                    printf(
                        '<p class="description">%s</p>',
                        esc_html( sprintf( /* translators: %s: license expiry date */ __( 'Expires: %s', 'zignites-chat' ), $expires ) )
                    );
                }
                if ($message) {
                    echo '<p class="description">' . esc_html($message) . '</p>';
                }
                ?>
            </td>
        </tr>
    </table>
