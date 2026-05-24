<?php
if (!defined('ABSPATH')) exit;

$zignites_chat_dash_totals  = zignites_chat_analytics_get_totals();
$zignites_chat_dash_is_pro  = zignites_chat_is_pro_active();
$zignites_chat_dash_license = $zignites_chat_dash_is_pro
    ? 'valid'
    : get_option('zignites_chat_license_status', 'inactive');
$zignites_chat_dash_open_rate = $zignites_chat_dash_totals['sent'] > 0
    ? round(($zignites_chat_dash_totals['delivered'] / $zignites_chat_dash_totals['sent']) * 100) . '%'
    : '—';
?>
<div class="zignites-chat-plugin-splash">
    <span class="zignites-chat-plugin-logo">💬</span>
    <span class="zignites-chat-plugin-title">Zignites Chat</span>
</div>

<div class="zignites-chat-dashboard-widget">
    <div class="zignites-chat-dashboard-widget-stats">
        <div class="zignites-chat-dashboard-widget-stat">
            <span class="dashicons dashicons-format-chat"></span>
            <div class="zignites-chat-stat-value"><?php echo esc_html(number_format_i18n($zignites_chat_dash_totals['sent'])); ?></div>
            <div class="zignites-chat-stat-label"><?php esc_html_e('Messages Sent', 'zignites-chat'); ?></div>
        </div>
        <div class="zignites-chat-dashboard-widget-stat">
            <span class="dashicons dashicons-yes"></span>
            <div class="zignites-chat-stat-value"><?php echo esc_html($zignites_chat_dash_open_rate); ?></div>
            <div class="zignites-chat-stat-label"><?php esc_html_e('Delivery Rate', 'zignites-chat'); ?></div>
        </div>
        <div class="zignites-chat-dashboard-widget-stat">
            <span class="dashicons dashicons-admin-network"></span>
            <div class="zignites-chat-stat-value"><?php echo esc_html(zignites_chat_license_status_label($zignites_chat_dash_license)); ?></div>
            <div class="zignites-chat-stat-label"><?php esc_html_e('License Status', 'zignites-chat'); ?></div>
        </div>
    </div>
    <div class="zignites-chat-dashboard-widget-actions">
        <a href="<?php echo esc_url(admin_url('admin.php?page=zignites-chat-general')); ?>" class="button"><?php esc_html_e('General Settings', 'zignites-chat'); ?></a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=zignites-chat-messaging')); ?>" class="button"><?php esc_html_e('Send Test Message', 'zignites-chat'); ?></a>
        <?php
        // Pro builds link Freemius's Account page (added by the SDK), free
        // builds link the legacy License submenu.
        if ($zignites_chat_dash_is_pro && function_exists('zignites_chat_pro_freemius')) :
            $zignites_chat_dash_account_url = admin_url('admin.php?page=zignites-chat-account');
        else :
            $zignites_chat_dash_account_url = admin_url('admin.php?page=zignites-chat-license');
        endif;
        ?>
        <a href="<?php echo esc_url($zignites_chat_dash_account_url); ?>" class="button"><?php esc_html_e('Manage License', 'zignites-chat'); ?></a>
    </div>
</div>

<?php if (!$zignites_chat_dash_is_pro) : ?>
    <h2 style="margin-top:28px;"><?php esc_html_e('Unlock more with Zignites Chat Pro', 'zignites-chat'); ?></h2>
    <div class="zignites-chat-dashboard-teasers">
        <?php
        $zignites_chat_dash_teasers = [
            'cart-recovery' => [
                'icon'  => '🛒',
                'title' => __('Cart Recovery', 'zignites-chat'),
                'desc'  => __('Win back abandoned carts with automated WhatsApp reminders.', 'zignites-chat'),
            ],
            'analytics' => [
                'icon'  => '📊',
                'title' => __('Analytics', 'zignites-chat'),
                'desc'  => __('Track delivery, clicks, and revenue attributed to WhatsApp.', 'zignites-chat'),
            ],
            'campaigns' => [
                'icon'  => '📣',
                'title' => __('Bulk Campaigns', 'zignites-chat'),
                'desc'  => __('Send targeted promotions to your customer segments.', 'zignites-chat'),
            ],
            'scheduler' => [
                'icon'  => '⏰',
                'title' => __('Follow-up Scheduler', 'zignites-chat'),
                'desc'  => __('Automate post-purchase messages for reviews and loyalty.', 'zignites-chat'),
            ],
        ];
        foreach ($zignites_chat_dash_teasers as $zignites_chat_dash_slug => $zignites_chat_dash_teaser) :
            ?>
            <div class="zignites-chat-dashboard-teaser">
                <div class="zignites-chat-dashboard-teaser-icon"><?php echo esc_html($zignites_chat_dash_teaser['icon']); ?></div>
                <h3><?php echo esc_html($zignites_chat_dash_teaser['title']); ?></h3>
                <p><?php echo esc_html($zignites_chat_dash_teaser['desc']); ?></p>
                <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=zignites-chat-' . $zignites_chat_dash_slug)); ?>">
                    <?php esc_html_e('Learn more', 'zignites-chat'); ?>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
    <p style="margin-top:16px;">
        <button type="button" class="button button-primary button-hero zignites-chat-open-upgrade-modal">
            <?php esc_html_e('Compare Free vs Pro', 'zignites-chat'); ?>
        </button>
    </p>
<?php endif; ?>

<?php zignites_chat_render_onboarding_modal(); ?>
