<?php
if (!defined('ABSPATH')) exit;
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals, WordPress.Security.NonceVerification.Recommended -- View partial: variables are render-function-scoped; the GET query string is read only to pre-fill display filters.
?>
<?php $is_pro = zignites_chat_is_pro_active(); ?>
    <?php
    $filters = [
        'type' => isset($_GET['zignites_chat_type']) ? sanitize_text_field(wp_unslash($_GET['zignites_chat_type'])) : '',
        'status' => isset($_GET['zignites_chat_status']) ? sanitize_text_field(wp_unslash($_GET['zignites_chat_status'])) : '',
        'phone' => isset($_GET['zignites_chat_phone']) ? sanitize_text_field(wp_unslash($_GET['zignites_chat_phone'])) : '',
        'date_from' => isset($_GET['zignites_chat_date_from']) ? sanitize_text_field(wp_unslash($_GET['zignites_chat_date_from'])) : '',
        'date_to' => isset($_GET['zignites_chat_date_to']) ? sanitize_text_field(wp_unslash($_GET['zignites_chat_date_to'])) : '',
    ];
    $totals = zignites_chat_analytics_get_totals();
    $breakdown = zignites_chat_analytics_get_per_type_breakdown($filters);
    $conversions = zignites_chat_analytics_get_conversions($filters);
    $events = zignites_chat_analytics_get_events(25, $filters);
    $export_url = wp_nonce_url(
        add_query_arg(
            array_merge(
                ['action' => 'zignites_chat_analytics_export_csv'],
                array_filter($filters, static function ($v) { return $v !== ''; })
            ),
            admin_url('admin-post.php')
        ),
        'zignites_chat_analytics_export',
        'zignites_chat_analytics_export_nonce'
    );
    ?>
    <?php if (!$is_pro) : ?>
        <div class="zignites-chat-pro-banner"><span class="dashicons dashicons-chart-bar"></span> <strong><?php esc_html_e('Analytics Dashboard', 'zignites-chat'); ?></strong> <?php esc_html_e('is a Pro feature.', 'zignites-chat'); ?> <button type="button" class="zignites-chat-open-upgrade-modal" style="margin-left:12px;"><?php esc_html_e('Upgrade', 'zignites-chat'); ?></button></div>
    <?php endif; ?>
    <div class="zignites-chat-analytics-presets" style="margin:16px 0 4px;display:flex;gap:6px;flex-wrap:wrap;align-items:center;">
        <span style="color:#666;font-size:0.95em;"><?php esc_html_e('Quick range:', 'zignites-chat'); ?></span>
        <button type="button" class="button button-small zignites-chat-analytics-preset" data-range="today"><?php esc_html_e('Today', 'zignites-chat'); ?></button>
        <button type="button" class="button button-small zignites-chat-analytics-preset" data-range="7d"><?php esc_html_e('Last 7 days', 'zignites-chat'); ?></button>
        <button type="button" class="button button-small zignites-chat-analytics-preset" data-range="30d"><?php esc_html_e('Last 30 days', 'zignites-chat'); ?></button>
        <button type="button" class="button button-small zignites-chat-analytics-preset" data-range="month"><?php esc_html_e('This month', 'zignites-chat'); ?></button>
        <button type="button" class="button button-small zignites-chat-analytics-preset" data-range="all"><?php esc_html_e('All time', 'zignites-chat'); ?></button>
    </div>
    <div class="zignites-chat-analytics-filters" style="margin:8px 0;display:flex;gap:8px;flex-wrap:wrap;align-items:end;">
        <div>
            <label for="zignites_chat_type"><?php esc_html_e('Type', 'zignites-chat'); ?></label><br>
            <input type="text" id="zignites_chat_type" name="zignites_chat_type" value="<?php echo esc_attr($filters['type']); ?>" placeholder="order, cart_recovery" />
        </div>
        <div>
            <label for="zignites_chat_status"><?php esc_html_e('Status', 'zignites-chat'); ?></label><br>
            <input type="text" id="zignites_chat_status" name="zignites_chat_status" value="<?php echo esc_attr($filters['status']); ?>" placeholder="sent, failed" />
        </div>
        <div>
            <label for="zignites_chat_phone"><?php esc_html_e('Phone', 'zignites-chat'); ?></label><br>
            <input type="text" id="zignites_chat_phone" name="zignites_chat_phone" value="<?php echo esc_attr($filters['phone']); ?>" placeholder="last 4 digits" />
        </div>
        <div>
            <label for="zignites_chat_date_from"><?php esc_html_e('From', 'zignites-chat'); ?></label><br>
            <input type="date" id="zignites_chat_date_from" name="zignites_chat_date_from" value="<?php echo esc_attr($filters['date_from']); ?>" />
        </div>
        <div>
            <label for="zignites_chat_date_to"><?php esc_html_e('To', 'zignites-chat'); ?></label><br>
            <input type="date" id="zignites_chat_date_to" name="zignites_chat_date_to" value="<?php echo esc_attr($filters['date_to']); ?>" />
        </div>
        <div>
            <button type="button" class="button button-primary" id="zignites-chat-analytics-filter-button"><?php esc_html_e('Filter', 'zignites-chat'); ?></button>
        </div>
        <div>
            <a class="button" href="<?php echo esc_url($export_url); ?>" id="zignites-chat-analytics-export-csv"><span class="dashicons dashicons-download" style="vertical-align:middle;line-height:28px;"></span> <?php esc_html_e('Export CSV', 'zignites-chat'); ?></a>
        </div>
    </div>
    <div class="zignites-chat-analytics-cards" style="display:flex;gap:12px;flex-wrap:wrap;">
        <div class="zignites-chat-analytics-card" style="background:#fff;border:1px solid #e5e5e5;border-radius:10px;padding:14px 16px;min-width:140px;">
            <div class="zignites-chat-analytics-label"><?php esc_html_e('Sent', 'zignites-chat'); ?></div>
            <div class="zignites-chat-analytics-value"><?php echo esc_html($totals['sent']); ?></div>
        </div>
        <div class="zignites-chat-analytics-card" style="background:#fff;border:1px solid #e5e5e5;border-radius:10px;padding:14px 16px;min-width:140px;">
            <div class="zignites-chat-analytics-label"><?php esc_html_e('Delivered', 'zignites-chat'); ?></div>
            <div class="zignites-chat-analytics-value"><?php echo esc_html($totals['delivered']); ?></div>
        </div>
        <div class="zignites-chat-analytics-card" style="background:#fff;border:1px solid #e5e5e5;border-radius:10px;padding:14px 16px;min-width:140px;">
            <div class="zignites-chat-analytics-label"><?php esc_html_e('Clicked', 'zignites-chat'); ?></div>
            <div class="zignites-chat-analytics-value"><?php echo esc_html($totals['clicked']); ?></div>
        </div>
        <div class="zignites-chat-analytics-card" style="background:#fff;border:1px solid #e5e5e5;border-radius:10px;padding:14px 16px;min-width:200px;">
            <div class="zignites-chat-analytics-label">
                <?php
                printf(
                    /* translators: %d is the attribution window in days */
                    esc_html__('Attributed orders (%d-day window)', 'zignites-chat'),
                    (int) $conversions['window_days']
                );
                ?>
            </div>
            <div class="zignites-chat-analytics-value"><?php echo esc_html($conversions['conversions']); ?></div>
            <?php if ($conversions['revenue'] > 0) : ?>
                <div style="color:#666;font-size:0.92em;margin-top:4px;">
                    <?php
                    if (function_exists('wc_price')) {
                        echo wp_kses_post(wc_price($conversions['revenue']));
                    } else {
                        echo esc_html(number_format_i18n($conversions['revenue'], 2));
                    }
                    ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <h3 style="margin-top:20px;"><?php esc_html_e('Performance by template', 'zignites-chat'); ?></h3>
    <table class="widefat striped" style="margin-top:10px;max-width:780px;">
        <thead>
            <tr>
                <th><?php esc_html_e('Template / source', 'zignites-chat'); ?></th>
                <th><?php esc_html_e('Sent', 'zignites-chat'); ?></th>
                <th><?php esc_html_e('Delivered', 'zignites-chat'); ?></th>
                <th><?php esc_html_e('Clicked', 'zignites-chat'); ?></th>
                <th><?php esc_html_e('Failed', 'zignites-chat'); ?></th>
                <th><?php esc_html_e('Total', 'zignites-chat'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($breakdown)) : ?>
                <?php foreach ($breakdown as $row) : ?>
                    <tr>
                        <td><code><?php echo esc_html($row['type']); ?></code></td>
                        <td><?php echo esc_html((string) $row['sent']); ?></td>
                        <td><?php echo esc_html((string) $row['delivered']); ?></td>
                        <td><?php echo esc_html((string) $row['clicked']); ?></td>
                        <td><?php echo esc_html((string) $row['failed']); ?></td>
                        <td><strong><?php echo esc_html((string) $row['total']); ?></strong></td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr><td colspan="6"><?php esc_html_e('No events match the current filters.', 'zignites-chat'); ?></td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    <h3 style="margin-top:24px;"><?php esc_html_e('A/B test results', 'zignites-chat'); ?></h3>
    <?php
    $ab_kinds_cfg = zignites_chat_ab_kinds();
    $ab_any_enabled = false;
    foreach ($ab_kinds_cfg as $ab_kind => $ab_cfg) {
        if (get_option($ab_cfg['option_enabled'], 'no') === 'yes') {
            $ab_any_enabled = true;
            break;
        }
    }
    ?>
    <?php if (!$ab_any_enabled) : ?>
        <p class="description" style="margin-top:8px;"><?php esc_html_e('No A/B tests are running. Turn on "A/B test this message" on the Messaging, Cart Recovery, or Scheduler tabs to start measuring.', 'zignites-chat'); ?></p>
    <?php else : ?>
        <table class="widefat striped" style="margin-top:10px;">
            <thead>
                <tr>
                    <th><?php esc_html_e('Test', 'zignites-chat'); ?></th>
                    <th><?php esc_html_e('Variant', 'zignites-chat'); ?></th>
                    <th><?php esc_html_e('Sent', 'zignites-chat'); ?></th>
                    <th><?php esc_html_e('Conversions', 'zignites-chat'); ?></th>
                    <th><?php esc_html_e('Conv. rate', 'zignites-chat'); ?></th>
                    <th><?php esc_html_e('Revenue', 'zignites-chat'); ?></th>
                    <th><?php esc_html_e('Outcome', 'zignites-chat'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($ab_kinds_cfg as $ab_kind => $ab_cfg) :
                    if (get_option($ab_cfg['option_enabled'], 'no') !== 'yes') continue;
                    $ab_results = zignites_chat_ab_get_results($ab_kind, $filters);
                    foreach (['a', 'b'] as $variant) :
                        $row = $ab_results[$variant];
                        $is_winner = $ab_results['winner'] === $variant;
                ?>
                    <tr>
                        <?php if ($variant === 'a') : ?>
                            <td rowspan="2"><strong><?php echo esc_html($ab_cfg['label']); ?></strong></td>
                        <?php endif; ?>
                        <td><?php echo esc_html(strtoupper($variant)); ?><?php if ($is_winner) : ?> <span style="color:#1c7c54;font-weight:600;">★</span><?php endif; ?></td>
                        <td><?php echo esc_html((string) $row['sent']); ?></td>
                        <td><?php echo esc_html((string) $row['conversions']); ?></td>
                        <td><?php echo esc_html(number_format_i18n($row['cvr'] * 100, 1) . '%'); ?></td>
                        <td><?php
                            if ($row['revenue'] > 0 && function_exists('wc_price')) {
                                echo wp_kses_post(wc_price($row['revenue']));
                            } else {
                                echo esc_html(number_format_i18n($row['revenue'], 2));
                            }
                        ?></td>
                        <?php if ($variant === 'a') :
                            if ($ab_results['winner'] === null) {
                                $outcome = sprintf(
                                    /* translators: %d is the minimum sample size per variant before declaring a winner */
                                    esc_html__('Insufficient data (need %d sends per variant)', 'zignites-chat'),
                                    (int) apply_filters('zignites_chat_ab_min_sample_size', 30)
                                );
                            } else {
                                $outcome = sprintf(
                                    /* translators: %s is the winning variant letter (A or B) */
                                    esc_html__('Variant %s leads', 'zignites-chat'),
                                    strtoupper($ab_results['winner'])
                                );
                            }
                        ?>
                            <td rowspan="2"><?php echo $outcome; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- already escaped via esc_html__ inside sprintf above. ?></td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; endforeach; ?>
            </tbody>
        </table>
        <p class="description" style="margin-top:6px;">
            <?php
            printf(
                /* translators: %d is the attribution window in days */
                esc_html__('Conversions count orders placed within %d days of the message by the same phone number — the same attribution window the totals card uses.', 'zignites-chat'),
                (int) apply_filters('zignites_chat_analytics_attribution_window_days', 7)
            );
            ?>
        </p>
    <?php endif; ?>

    <h3 style="margin-top:20px;"><?php esc_html_e('Recent Events', 'zignites-chat'); ?></h3>
    <table class="widefat striped" style="margin-top:10px;">
        <thead>
            <tr><th><?php esc_html_e('Time', 'zignites-chat'); ?></th><th><?php esc_html_e('Type', 'zignites-chat'); ?></th><th><?php esc_html_e('Status', 'zignites-chat'); ?></th><th><?php esc_html_e('Phone', 'zignites-chat'); ?></th><th><?php esc_html_e('Provider', 'zignites-chat'); ?></th><th><?php esc_html_e('Message ID', 'zignites-chat'); ?></th><th><?php esc_html_e('Preview', 'zignites-chat'); ?></th></tr>
        </thead>
        <tbody>
            <?php if (!empty($events)) : ?>
                <?php foreach ($events as $evt) : ?>
                    <tr>
                        <td><?php echo esc_html($evt['time'] ?? ''); ?></td>
                        <td><?php echo esc_html($evt['type'] ?? ''); ?></td>
                        <td><?php echo esc_html($evt['status'] ?? ''); ?></td>
                        <td><?php echo esc_html($evt['phone'] ?? ''); ?></td>
                        <td><?php echo esc_html($evt['provider'] ?? ''); ?></td>
                        <td><?php echo esc_html($evt['message_id'] ?? ''); ?></td>
                        <td><pre style="white-space:pre-line;font-size:0.95em;max-width:320px;overflow-x:auto;"><?php echo esc_html($evt['message_preview'] ?? ''); ?></pre></td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr><td colspan="7"><?php esc_html_e('No analytics events logged yet.', 'zignites-chat'); ?></td></tr>
            <?php endif; ?>
        </tbody>
    </table>
