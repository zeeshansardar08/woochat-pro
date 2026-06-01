<?php
if (!defined('ABSPATH')) exit;
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- View partial: these variables are scoped to the render function that includes this file, not the global namespace.

$zignites_chat_segment_types = zignites_chat_campaign_segment_types();
$zignites_chat_recent_campaigns = zignites_chat_campaign_list(20);

// Option lists for the richer-segment pickers.
$zignites_chat_product_cats = function_exists('get_terms') ? get_terms([
    'taxonomy'   => 'product_cat',
    'hide_empty' => false,
]) : [];
if (is_wp_error($zignites_chat_product_cats) || !is_array($zignites_chat_product_cats)) {
    $zignites_chat_product_cats = [];
}
$zignites_chat_wc_countries = (function_exists('WC') && WC()->countries) ? WC()->countries->get_countries() : [];
?>
<h2><?php esc_html_e('Bulk Campaigns', 'zignites-chat'); ?></h2>
    <p class="description">
        <?php esc_html_e('Send a one-shot WhatsApp message to a customer segment. Sends are throttled to 10 per minute by default and skip anyone on the suppression list.', 'zignites-chat'); ?>
    </p>

<div id="zignites-chat-tab-content-campaigns">
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

                    <div class="zignites-chat-campaign-meta" data-segment="recent_orders" style="display:none; margin-top:8px;">
                        <?php esc_html_e('Last', 'zignites-chat'); ?>
                        <input type="number" id="zignites-chat-campaign-days" min="1" max="3650" value="30" class="small-text" />
                        <?php esc_html_e('days', 'zignites-chat'); ?>
                    </div>

                    <div class="zignites-chat-campaign-meta" data-segment="product_purchased" style="display:none; margin-top:8px;">
                        <label for="zignites-chat-campaign-product-ids"><?php esc_html_e('Product IDs (comma-separated)', 'zignites-chat'); ?></label><br />
                        <input type="text" id="zignites-chat-campaign-product-ids" class="regular-text" placeholder="e.g. 42, 108, 256" />
                        <p class="description"><?php esc_html_e('Find a product ID by hovering its row in Products.', 'zignites-chat'); ?></p>
                    </div>

                    <div class="zignites-chat-campaign-meta" data-segment="category_purchased" style="display:none; margin-top:8px;">
                        <label for="zignites-chat-campaign-category-ids"><?php esc_html_e('Categories', 'zignites-chat'); ?></label><br />
                        <select id="zignites-chat-campaign-category-ids" multiple size="6" style="min-width:240px;">
                            <?php foreach ($zignites_chat_product_cats as $zignites_chat_cat) : ?>
                                <option value="<?php echo esc_attr($zignites_chat_cat->term_id); ?>"><?php echo esc_html($zignites_chat_cat->name); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description"><?php esc_html_e('Hold Ctrl/Cmd to select multiple.', 'zignites-chat'); ?></p>
                    </div>

                    <div class="zignites-chat-campaign-meta" data-segment="min_spend" style="display:none; margin-top:8px;">
                        <label for="zignites-chat-campaign-min-spend"><?php esc_html_e('Minimum lifetime spend', 'zignites-chat'); ?></label><br />
                        <input type="number" id="zignites-chat-campaign-min-spend" min="0" step="0.01" value="100" class="small-text" />
                    </div>

                    <div class="zignites-chat-campaign-meta" data-segment="location" style="display:none; margin-top:8px;">
                        <label for="zignites-chat-campaign-countries"><?php esc_html_e('Countries', 'zignites-chat'); ?></label><br />
                        <?php if (!empty($zignites_chat_wc_countries)) : ?>
                            <select id="zignites-chat-campaign-countries" multiple size="6" style="min-width:240px;">
                                <?php foreach ($zignites_chat_wc_countries as $zignites_chat_code => $zignites_chat_country) : ?>
                                    <option value="<?php echo esc_attr($zignites_chat_code); ?>"><?php echo esc_html($zignites_chat_country); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description"><?php esc_html_e('Hold Ctrl/Cmd to select multiple.', 'zignites-chat'); ?></p>
                        <?php else : ?>
                            <input type="text" id="zignites-chat-campaign-countries-text" class="regular-text" placeholder="US, GB, AE" />
                            <p class="description"><?php esc_html_e('Two-letter country codes, comma-separated.', 'zignites-chat'); ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="zignites-chat-campaign-meta" data-segment="win_back" style="display:none; margin-top:8px;">
                        <?php esc_html_e('No order in the last', 'zignites-chat'); ?>
                        <input type="number" id="zignites-chat-campaign-winback-days" min="1" max="3650" value="60" class="small-text" />
                        <?php esc_html_e('days', 'zignites-chat'); ?>
                    </div>
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
                <th scope="row"><?php esc_html_e('Attachment', 'zignites-chat'); ?></th>
                <td>
                    <input type="hidden" id="zignites-chat-campaign-media-url" value="" />
                    <input type="hidden" id="zignites-chat-campaign-media-mime" value="" />
                    <button type="button" class="button" id="zignites-chat-campaign-media-select"><?php esc_html_e('Select image or document', 'zignites-chat'); ?></button>
                    <button type="button" class="button-link" id="zignites-chat-campaign-media-remove" style="display:none; margin-left:8px;"><?php esc_html_e('Remove', 'zignites-chat'); ?></button>
                    <span id="zignites-chat-campaign-media-name" style="margin-left:8px; color:#555;"></span>
                    <p class="description"><?php esc_html_e('Optional. Sends the message as an image/document with your text as the caption. The file must be in this site\'s Media Library.', 'zignites-chat'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="zignites-chat-campaign-schedule"><?php esc_html_e('Schedule send', 'zignites-chat'); ?></label></th>
                <td>
                    <input type="datetime-local" id="zignites-chat-campaign-schedule" />
                    <p class="description"><?php esc_html_e('Leave blank to send now. A future time queues the campaign until then (checked every 5 minutes).', 'zignites-chat'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="zignites-chat-campaign-exclude-days"><?php esc_html_e('Skip recently messaged', 'zignites-chat'); ?></label></th>
                <td>
                    <?php esc_html_e('Skip customers who got a campaign in the last', 'zignites-chat'); ?>
                    <input type="number" id="zignites-chat-campaign-exclude-days" min="0" max="3650" value="0" class="small-text" />
                    <?php esc_html_e('days', 'zignites-chat'); ?>
                    <p class="description"><?php esc_html_e('Set to 0 to message everyone in the segment.', 'zignites-chat'); ?></p>
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
                    <th><?php esc_html_e('Scheduled', 'zignites-chat'); ?></th>
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
                        <td><?php echo !empty($c['scheduled_at']) ? esc_html(mysql2date(get_option('date_format') . ' ' . get_option('time_format'), $c['scheduled_at'])) : '—'; ?></td>
                        <td><?php echo esc_html(mysql2date(get_option('date_format') . ' ' . get_option('time_format'), $c['created_at'])); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
<?php // end #zignites-chat-tab-content-campaigns ?>
