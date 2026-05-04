<?php
if (!defined('ABSPATH')) exit;
?>
<div id="wcwp-tab-content-analytics" class="wcwp-tab-content" style="display:none;">
    <?php $is_pro = function_exists('wcwp_is_pro_active') && wcwp_is_pro_active(); ?>
    <?php $totals = function_exists('wcwp_analytics_get_totals') ? wcwp_analytics_get_totals() : ['sent' => 0, 'delivered' => 0, 'clicked' => 0]; ?>
    <?php
    $filters = [
        'type' => isset($_GET['wcwp_type']) ? sanitize_text_field(wp_unslash($_GET['wcwp_type'])) : '',
        'status' => isset($_GET['wcwp_status']) ? sanitize_text_field(wp_unslash($_GET['wcwp_status'])) : '',
        'phone' => isset($_GET['wcwp_phone']) ? sanitize_text_field(wp_unslash($_GET['wcwp_phone'])) : '',
        'date_from' => isset($_GET['wcwp_date_from']) ? sanitize_text_field(wp_unslash($_GET['wcwp_date_from'])) : '',
        'date_to' => isset($_GET['wcwp_date_to']) ? sanitize_text_field(wp_unslash($_GET['wcwp_date_to'])) : '',
    ];
    $events = function_exists('wcwp_analytics_get_events') ? wcwp_analytics_get_events(25, $filters) : [];
    ?>
    <?php if (!$is_pro) : ?>
        <div class="wcwp-pro-banner"><span class="dashicons dashicons-chart-bar"></span> <strong><?php esc_html_e('Analytics Dashboard', 'woochat-pro'); ?></strong> <?php esc_html_e('is a Pro feature.', 'woochat-pro'); ?> <button type="button" class="wcwp-open-upgrade-modal" style="margin-left:12px;"><?php esc_html_e('Upgrade', 'woochat-pro'); ?></button></div>
    <?php endif; ?>
    <div class="wcwp-analytics-filters" style="margin:16px 0 8px;display:flex;gap:8px;flex-wrap:wrap;align-items:end;">
        <div>
            <label for="wcwp_type"><?php esc_html_e('Type', 'woochat-pro'); ?></label><br>
            <input type="text" id="wcwp_type" name="wcwp_type" value="<?php echo esc_attr($filters['type']); ?>" placeholder="order, cart_recovery" />
        </div>
        <div>
            <label for="wcwp_status"><?php esc_html_e('Status', 'woochat-pro'); ?></label><br>
            <input type="text" id="wcwp_status" name="wcwp_status" value="<?php echo esc_attr($filters['status']); ?>" placeholder="sent, failed" />
        </div>
        <div>
            <label for="wcwp_phone"><?php esc_html_e('Phone', 'woochat-pro'); ?></label><br>
            <input type="text" id="wcwp_phone" name="wcwp_phone" value="<?php echo esc_attr($filters['phone']); ?>" placeholder="last 4 digits" />
        </div>
        <div>
            <label for="wcwp_date_from"><?php esc_html_e('From', 'woochat-pro'); ?></label><br>
            <input type="date" id="wcwp_date_from" name="wcwp_date_from" value="<?php echo esc_attr($filters['date_from']); ?>" />
        </div>
        <div>
            <label for="wcwp_date_to"><?php esc_html_e('To', 'woochat-pro'); ?></label><br>
            <input type="date" id="wcwp_date_to" name="wcwp_date_to" value="<?php echo esc_attr($filters['date_to']); ?>" />
        </div>
        <div>
            <button type="button" class="button" id="wcwp-analytics-filter-button"><?php esc_html_e('Filter', 'woochat-pro'); ?></button>
        </div>
    </div>
    <div class="wcwp-analytics-cards" style="display:flex;gap:12px;flex-wrap:wrap;">
        <div class="wcwp-analytics-card" style="background:#fff;border:1px solid #e5e5e5;border-radius:10px;padding:14px 16px;min-width:140px;">
            <div class="wcwp-analytics-label"><?php esc_html_e('Sent', 'woochat-pro'); ?></div>
            <div class="wcwp-analytics-value"><?php echo esc_html($totals['sent']); ?></div>
        </div>
        <div class="wcwp-analytics-card" style="background:#fff;border:1px solid #e5e5e5;border-radius:10px;padding:14px 16px;min-width:140px;">
            <div class="wcwp-analytics-label"><?php esc_html_e('Delivered', 'woochat-pro'); ?></div>
            <div class="wcwp-analytics-value"><?php echo esc_html($totals['delivered']); ?></div>
        </div>
        <div class="wcwp-analytics-card" style="background:#fff;border:1px solid #e5e5e5;border-radius:10px;padding:14px 16px;min-width:140px;">
            <div class="wcwp-analytics-label"><?php esc_html_e('Clicked', 'woochat-pro'); ?></div>
            <div class="wcwp-analytics-value"><?php echo esc_html($totals['clicked']); ?></div>
        </div>
    </div>
    <h3 style="margin-top:20px;"><?php esc_html_e('Recent Events', 'woochat-pro'); ?></h3>
    <table class="widefat striped" style="margin-top:10px;">
        <thead>
            <tr><th><?php esc_html_e('Time', 'woochat-pro'); ?></th><th><?php esc_html_e('Type', 'woochat-pro'); ?></th><th><?php esc_html_e('Status', 'woochat-pro'); ?></th><th><?php esc_html_e('Phone', 'woochat-pro'); ?></th><th><?php esc_html_e('Provider', 'woochat-pro'); ?></th><th><?php esc_html_e('Message ID', 'woochat-pro'); ?></th><th><?php esc_html_e('Preview', 'woochat-pro'); ?></th></tr>
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
                <tr><td colspan="7"><?php esc_html_e('No analytics events logged yet.', 'woochat-pro'); ?></td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
