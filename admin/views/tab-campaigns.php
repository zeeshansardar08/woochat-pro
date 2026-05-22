<?php
if (!defined('ABSPATH')) exit;
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- View partial: these variables are scoped to the render function that includes this file, not the global namespace.

$zignites_chat_segment_types = zignites_chat_campaign_segment_types();
$zignites_chat_recent_campaigns = zignites_chat_campaign_list(20);
?>
<h2><?php esc_html_e('Bulk Campaigns', 'zignites-chat'); ?></h2>
    <p class="description">
        <?php esc_html_e('Send a one-shot WhatsApp message to a customer segment. Sends are throttled to 10 per minute by default and skip anyone on the suppression list.', 'zignites-chat'); ?>
    </p>

    <div class="zignites-chat-campaign-create" id="zignites-chat-campaign-create">
        <h3><?php esc_html_e('New campaign', 'zignites-chat'); ?></h3>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="zignites-chat-campaign-name"><?php esc_html_e('Campaign name', 'zignites-chat'); ?></label></th>
                <td><input type="text" id="zignites-chat-campaign-name" class="regular-text" placeholder="<?php esc_attr_e('e.g. April promo blast', 'zignites-chat'); ?>" /></td>
            </tr>
            <tr>
                <th scope="row"><label for="zignites-chat-campaign-segment"><?php esc_html_e('Segment', 'zignites-chat'); ?></label></th>
                <td>
                    <select id="zignites-chat-campaign-segment">
                        <?php foreach ($zignites_chat_segment_types as $value => $label) : ?>
                            <option value="<?php echo esc_attr($value); ?>"><?php echo esc_html($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <span id="zignites-chat-campaign-days-wrap" style="display:none; margin-left:8px;">
                        <?php esc_html_e('Last', 'zignites-chat'); ?>
                        <input type="number" id="zignites-chat-campaign-days" min="1" max="3650" value="30" class="small-text" />
                        <?php esc_html_e('days', 'zignites-chat'); ?>
                    </span>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="zignites-chat-campaign-template"><?php esc_html_e('Message template', 'zignites-chat'); ?></label></th>
                <td>
                    <textarea id="zignites-chat-campaign-template" rows="4" class="large-text" placeholder="<?php esc_attr_e('Hi {name}, big news from {site}…', 'zignites-chat'); ?>"></textarea>
                    <p class="description">
                        <?php esc_html_e('Placeholders:', 'zignites-chat'); ?>
                        <code>{name}</code>, <code>{site}</code>, <code>{currency_symbol}</code>
                    </p>
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    <button type="button" class="button button-primary" id="zignites-chat-campaign-submit"><?php esc_html_e('Create campaign', 'zignites-chat'); ?></button>
                    <span class="zignites-chat-campaign-feedback" id="zignites-chat-campaign-feedback" role="status"></span>
                </td>
            </tr>
        </table>
    </div>

    <h3><?php esc_html_e('Recent campaigns', 'zignites-chat'); ?></h3>
    <?php if (empty($zignites_chat_recent_campaigns)) : ?>
        <p><em><?php esc_html_e('No campaigns yet.', 'zignites-chat'); ?></em></p>
    <?php else : ?>
        <table class="widefat striped zignites-chat-campaigns-list" id="zignites-chat-campaigns-list">
            <thead>
                <tr>
                    <th><?php esc_html_e('Name', 'zignites-chat'); ?></th>
                    <th><?php esc_html_e('Status', 'zignites-chat'); ?></th>
                    <th><?php esc_html_e('Sent', 'zignites-chat'); ?></th>
                    <th><?php esc_html_e('Failed', 'zignites-chat'); ?></th>
                    <th><?php esc_html_e('Skipped', 'zignites-chat'); ?></th>
                    <th><?php esc_html_e('Total', 'zignites-chat'); ?></th>
                    <th><?php esc_html_e('Created', 'zignites-chat'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($zignites_chat_recent_campaigns as $c) : ?>
                    <tr data-campaign-id="<?php echo esc_attr($c['id']); ?>">
                        <td><?php echo esc_html($c['name']); ?></td>
                        <td class="zignites-chat-campaign-status"><?php echo esc_html(ucfirst($c['status'])); ?></td>
                        <td class="zignites-chat-campaign-sent"><?php echo esc_html(number_format_i18n((int) $c['sent_count'])); ?></td>
                        <td class="zignites-chat-campaign-failed"><?php echo esc_html(number_format_i18n((int) $c['failed_count'])); ?></td>
                        <td class="zignites-chat-campaign-skipped"><?php echo esc_html(number_format_i18n((int) $c['skipped_count'])); ?></td>
                        <td class="zignites-chat-campaign-total"><?php echo esc_html(number_format_i18n((int) $c['total_count'])); ?></td>
                        <td><?php echo esc_html(mysql2date(get_option('date_format') . ' ' . get_option('time_format'), $c['created_at'])); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
