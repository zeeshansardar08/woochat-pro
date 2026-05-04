<?php
if (!defined('ABSPATH')) exit;
?>
<div id="wcwp-tab-content-license" class="wcwp-tab-content" style="display:none;">
    <table class="form-table">
        <tr>
            <th scope="row"><label for="wcwp_license_key"><?php esc_html_e('License Key', 'woochat-pro'); ?></label><span class="wcwp-help-icon">?<span class="wcwp-tooltip"><?php esc_html_e('Enter your Pro license key to unlock premium features.', 'woochat-pro'); ?></span></span></th>
            <td>
                <input type="text" name="wcwp_license_key" id="wcwp_license_key" value="<?php echo esc_attr(get_option('wcwp_license_key')); ?>" class="regular-text" />
                <div style="margin-top:8px; display:flex; align-items:center; gap:12px;">
                    <?php $status = get_option('wcwp_license_status', 'inactive'); ?>
                    <span id="wcwp-license-status" class="wcwp-badge <?php echo $status === 'valid' ? 'wcwp-badge-success' : 'wcwp-badge-muted'; ?>">
                        <?php echo $status === 'valid' ? 'Active' : ucfirst($status); ?>
                    </span>
                    <button type="button" class="button button-primary" id="wcwp-activate-license"><?php esc_html_e('Activate', 'woochat-pro'); ?></button>
                    <button type="button" class="button" id="wcwp-deactivate-license"><?php esc_html_e('Deactivate', 'woochat-pro'); ?></button>
                </div>
                <?php
                $expires = get_option('wcwp_license_expires');
                $message = get_option('wcwp_license_message');
                if ($expires) {
                    echo '<p class="description">Expires: ' . esc_html($expires) . '</p>';
                }
                if ($message) {
                    echo '<p class="description">' . esc_html($message) . '</p>';
                }
                ?>
            </td>
        </tr>
    </table>
</div>
