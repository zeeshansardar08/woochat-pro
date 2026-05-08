<?php
if (!defined('ABSPATH')) exit;

$wcwp_segment_types = wcwp_campaign_segment_types();
$wcwp_recent_campaigns = wcwp_campaign_list(20);
?>
<div id="wcwp-tab-content-campaigns" class="wcwp-tab-content" style="display:none;">
    <h2><?php esc_html_e('Bulk Campaigns', 'woochat-pro'); ?></h2>
    <p class="description">
        <?php esc_html_e('Send a one-shot WhatsApp message to a customer segment. Sends are throttled to 10 per minute by default and skip anyone on the suppression list.', 'woochat-pro'); ?>
    </p>

    <div class="wcwp-campaign-create" id="wcwp-campaign-create">
        <h3><?php esc_html_e('New campaign', 'woochat-pro'); ?></h3>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="wcwp-campaign-name"><?php esc_html_e('Campaign name', 'woochat-pro'); ?></label></th>
                <td><input type="text" id="wcwp-campaign-name" class="regular-text" placeholder="<?php esc_attr_e('e.g. April promo blast', 'woochat-pro'); ?>" /></td>
            </tr>
            <tr>
                <th scope="row"><label for="wcwp-campaign-segment"><?php esc_html_e('Segment', 'woochat-pro'); ?></label></th>
                <td>
                    <select id="wcwp-campaign-segment">
                        <?php foreach ($wcwp_segment_types as $value => $label) : ?>
                            <option value="<?php echo esc_attr($value); ?>"><?php echo esc_html($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <span id="wcwp-campaign-days-wrap" style="display:none; margin-left:8px;">
                        <?php esc_html_e('Last', 'woochat-pro'); ?>
                        <input type="number" id="wcwp-campaign-days" min="1" max="3650" value="30" class="small-text" />
                        <?php esc_html_e('days', 'woochat-pro'); ?>
                    </span>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="wcwp-campaign-template"><?php esc_html_e('Message template', 'woochat-pro'); ?></label></th>
                <td>
                    <textarea id="wcwp-campaign-template" rows="4" class="large-text" placeholder="<?php esc_attr_e('Hi {name}, big news from {site}…', 'woochat-pro'); ?>"></textarea>
                    <p class="description">
                        <?php esc_html_e('Placeholders:', 'woochat-pro'); ?>
                        <code>{name}</code>, <code>{site}</code>, <code>{currency_symbol}</code>
                    </p>
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    <button type="button" class="button button-primary" id="wcwp-campaign-submit"><?php esc_html_e('Create campaign', 'woochat-pro'); ?></button>
                    <span class="wcwp-campaign-feedback" id="wcwp-campaign-feedback" role="status"></span>
                </td>
            </tr>
        </table>
    </div>

    <h3><?php esc_html_e('Recent campaigns', 'woochat-pro'); ?></h3>
    <?php if (empty($wcwp_recent_campaigns)) : ?>
        <p><em><?php esc_html_e('No campaigns yet.', 'woochat-pro'); ?></em></p>
    <?php else : ?>
        <table class="widefat striped wcwp-campaigns-list" id="wcwp-campaigns-list">
            <thead>
                <tr>
                    <th><?php esc_html_e('Name', 'woochat-pro'); ?></th>
                    <th><?php esc_html_e('Status', 'woochat-pro'); ?></th>
                    <th><?php esc_html_e('Sent', 'woochat-pro'); ?></th>
                    <th><?php esc_html_e('Failed', 'woochat-pro'); ?></th>
                    <th><?php esc_html_e('Skipped', 'woochat-pro'); ?></th>
                    <th><?php esc_html_e('Total', 'woochat-pro'); ?></th>
                    <th><?php esc_html_e('Created', 'woochat-pro'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($wcwp_recent_campaigns as $c) : ?>
                    <tr data-campaign-id="<?php echo esc_attr($c['id']); ?>">
                        <td><?php echo esc_html($c['name']); ?></td>
                        <td class="wcwp-campaign-status"><?php echo esc_html(ucfirst($c['status'])); ?></td>
                        <td class="wcwp-campaign-sent"><?php echo esc_html(number_format_i18n((int) $c['sent_count'])); ?></td>
                        <td class="wcwp-campaign-failed"><?php echo esc_html(number_format_i18n((int) $c['failed_count'])); ?></td>
                        <td class="wcwp-campaign-skipped"><?php echo esc_html(number_format_i18n((int) $c['skipped_count'])); ?></td>
                        <td class="wcwp-campaign-total"><?php echo esc_html(number_format_i18n((int) $c['total_count'])); ?></td>
                        <td><?php echo esc_html(mysql2date(get_option('date_format') . ' ' . get_option('time_format'), $c['created_at'])); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
