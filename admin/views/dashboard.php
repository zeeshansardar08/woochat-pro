<?php
if (!defined('ABSPATH')) exit;

$wcwp_dash_totals  = wcwp_analytics_get_totals();
$wcwp_dash_license = get_option('wcwp_license_status', 'inactive');
$wcwp_dash_open_rate = $wcwp_dash_totals['sent'] > 0
    ? round(($wcwp_dash_totals['delivered'] / $wcwp_dash_totals['sent']) * 100) . '%'
    : '—';
$wcwp_dash_is_pro = wcwp_is_pro_active();
?>
<div class="wcwp-plugin-splash">
    <span class="wcwp-plugin-logo">💬</span>
    <span class="wcwp-plugin-title">WooChat</span>
</div>

<div class="wcwp-dashboard-widget">
    <div class="wcwp-dashboard-widget-stats">
        <div class="wcwp-dashboard-widget-stat">
            <span class="dashicons dashicons-format-chat"></span>
            <div class="wcwp-stat-value"><?php echo esc_html(number_format_i18n($wcwp_dash_totals['sent'])); ?></div>
            <div class="wcwp-stat-label"><?php esc_html_e('Messages Sent', 'woochat'); ?></div>
        </div>
        <div class="wcwp-dashboard-widget-stat">
            <span class="dashicons dashicons-yes"></span>
            <div class="wcwp-stat-value"><?php echo esc_html($wcwp_dash_open_rate); ?></div>
            <div class="wcwp-stat-label"><?php esc_html_e('Delivery Rate', 'woochat'); ?></div>
        </div>
        <div class="wcwp-dashboard-widget-stat">
            <span class="dashicons dashicons-admin-network"></span>
            <div class="wcwp-stat-value"><?php echo esc_html(wcwp_license_status_label($wcwp_dash_license)); ?></div>
            <div class="wcwp-stat-label"><?php esc_html_e('License Status', 'woochat'); ?></div>
        </div>
    </div>
    <div class="wcwp-dashboard-widget-actions">
        <a href="<?php echo esc_url(admin_url('admin.php?page=wcwp-general')); ?>" class="button"><?php esc_html_e('General Settings', 'woochat'); ?></a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=wcwp-messaging')); ?>" class="button"><?php esc_html_e('Send Test Message', 'woochat'); ?></a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=wcwp-license')); ?>" class="button"><?php esc_html_e('Manage License', 'woochat'); ?></a>
    </div>
</div>

<?php if (!$wcwp_dash_is_pro) : ?>
    <h2 style="margin-top:28px;"><?php esc_html_e('Unlock more with WooChat Pro', 'woochat'); ?></h2>
    <div class="wcwp-dashboard-teasers">
        <?php
        $wcwp_dash_teasers = [
            'cart-recovery' => [
                'icon'  => '🛒',
                'title' => __('Cart Recovery', 'woochat'),
                'desc'  => __('Win back abandoned carts with automated WhatsApp reminders.', 'woochat'),
            ],
            'analytics' => [
                'icon'  => '📊',
                'title' => __('Analytics', 'woochat'),
                'desc'  => __('Track delivery, clicks, and revenue attributed to WhatsApp.', 'woochat'),
            ],
            'campaigns' => [
                'icon'  => '📣',
                'title' => __('Bulk Campaigns', 'woochat'),
                'desc'  => __('Send targeted promotions to your customer segments.', 'woochat'),
            ],
            'scheduler' => [
                'icon'  => '⏰',
                'title' => __('Follow-up Scheduler', 'woochat'),
                'desc'  => __('Automate post-purchase messages for reviews and loyalty.', 'woochat'),
            ],
        ];
        foreach ($wcwp_dash_teasers as $wcwp_dash_slug => $wcwp_dash_teaser) :
            ?>
            <div class="wcwp-dashboard-teaser">
                <div class="wcwp-dashboard-teaser-icon"><?php echo esc_html($wcwp_dash_teaser['icon']); ?></div>
                <h3><?php echo esc_html($wcwp_dash_teaser['title']); ?></h3>
                <p><?php echo esc_html($wcwp_dash_teaser['desc']); ?></p>
                <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=wcwp-' . $wcwp_dash_slug)); ?>">
                    <?php esc_html_e('Learn more', 'woochat'); ?>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
    <p style="margin-top:16px;">
        <button type="button" class="button button-primary button-hero wcwp-open-upgrade-modal">
            <?php esc_html_e('Compare Free vs Pro', 'woochat'); ?>
        </button>
    </p>
<?php endif; ?>

<?php wcwp_render_onboarding_modal(); ?>
