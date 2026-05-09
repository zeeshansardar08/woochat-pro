<?php
if (!defined('ABSPATH')) exit;

$wcwp_wh_list      = wcwp_get_webhooks();
$wcwp_wh_events    = wcwp_webhook_event_keys();
$wcwp_wh_recent    = wcwp_webhook_log_recent('', 25);
$wcwp_wh_msg       = isset($_GET['wcwp_webhook_msg']) ? sanitize_text_field(wp_unslash($_GET['wcwp_webhook_msg'])) : '';
$wcwp_wh_post_url  = admin_url('admin-post.php');
?>
<div id="wcwp-tab-content-webhooks" class="wcwp-tab-content" style="display:none;">
    <h2><?php esc_html_e('Webhooks', 'woochat-pro'); ?></h2>
    <p class="description">
        <?php esc_html_e('Pipe plugin events to Zapier, Make, n8n, or your own backend. Each request is signed with HMAC-SHA256 — receivers verify by recomputing the signature with the per-webhook secret.', 'woochat-pro'); ?>
    </p>

    <?php if ($wcwp_wh_msg === 'added') : ?>
        <div class="notice notice-success is-dismissible" style="margin:10px 0;"><p><?php esc_html_e('Webhook saved.', 'woochat-pro'); ?></p></div>
    <?php elseif ($wcwp_wh_msg === 'deleted') : ?>
        <div class="notice notice-success is-dismissible" style="margin:10px 0;"><p><?php esc_html_e('Webhook deleted.', 'woochat-pro'); ?></p></div>
    <?php elseif ($wcwp_wh_msg === 'invalid') : ?>
        <div class="notice notice-error is-dismissible" style="margin:10px 0;"><p><?php esc_html_e('Could not save the webhook. Check that the URL is valid and at least one event is selected.', 'woochat-pro'); ?></p></div>
    <?php endif; ?>

    <h3 style="margin-top:18px;"><?php esc_html_e('Add a webhook', 'woochat-pro'); ?></h3>
    <form method="post" action="<?php echo esc_url($wcwp_wh_post_url); ?>" style="margin-top:8px;max-width:780px;">
        <?php wp_nonce_field('wcwp_webhook_add', 'wcwp_webhook_add_nonce'); ?>
        <input type="hidden" name="action" value="wcwp_webhook_add" />
        <table class="form-table">
            <tr>
                <th scope="row"><label for="wcwp_webhook_url"><?php esc_html_e('Endpoint URL', 'woochat-pro'); ?></label></th>
                <td>
                    <input type="url" name="wcwp_webhook_url" id="wcwp_webhook_url" class="regular-text" placeholder="https://hooks.example.com/wcwp" required />
                    <p class="description"><?php esc_html_e('Must be HTTP or HTTPS. The receiver will get a JSON POST with X-WCWP-Event and X-WCWP-Signature headers.', 'woochat-pro'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Events', 'woochat-pro'); ?></th>
                <td>
                    <fieldset>
                        <?php foreach ($wcwp_wh_events as $wcwp_wh_event_key => $wcwp_wh_event_label) : ?>
                            <label style="display:block;margin-bottom:4px;">
                                <input type="checkbox" name="wcwp_webhook_events[]" value="<?php echo esc_attr($wcwp_wh_event_key); ?>" />
                                <code style="font-size:0.92em;"><?php echo esc_html($wcwp_wh_event_key); ?></code>
                                — <?php echo esc_html($wcwp_wh_event_label); ?>
                            </label>
                        <?php endforeach; ?>
                    </fieldset>
                    <p class="description"><?php esc_html_e('Select at least one event. A secret is generated automatically when the webhook is saved.', 'woochat-pro'); ?></p>
                </td>
            </tr>
        </table>
        <?php submit_button(__('Save webhook', 'woochat-pro'), 'primary', 'wcwp_webhook_submit', false); ?>
    </form>

    <h3 style="margin-top:24px;"><?php esc_html_e('Active webhooks', 'woochat-pro'); ?></h3>
    <?php if (empty($wcwp_wh_list)) : ?>
        <p><em><?php esc_html_e('No webhooks configured yet.', 'woochat-pro'); ?></em></p>
    <?php else : ?>
        <table class="widefat striped" style="margin-top:8px;">
            <thead>
                <tr>
                    <th><?php esc_html_e('Endpoint', 'woochat-pro'); ?></th>
                    <th><?php esc_html_e('Events', 'woochat-pro'); ?></th>
                    <th><?php esc_html_e('Secret', 'woochat-pro'); ?></th>
                    <th><?php esc_html_e('Created', 'woochat-pro'); ?></th>
                    <th><?php esc_html_e('Actions', 'woochat-pro'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($wcwp_wh_list as $wcwp_wh) :
                    $wcwp_wh_id     = isset($wcwp_wh['id']) ? (string) $wcwp_wh['id'] : '';
                    $wcwp_wh_url    = isset($wcwp_wh['url']) ? (string) $wcwp_wh['url'] : '';
                    $wcwp_wh_events_list = isset($wcwp_wh['events']) && is_array($wcwp_wh['events']) ? $wcwp_wh['events'] : [];
                    $wcwp_wh_secret = isset($wcwp_wh['secret']) ? (string) $wcwp_wh['secret'] : '';
                    $wcwp_wh_created = isset($wcwp_wh['created_at']) ? (string) $wcwp_wh['created_at'] : '';

                    $wcwp_wh_delete_url = wp_nonce_url(
                        add_query_arg([
                            'action'     => 'wcwp_webhook_delete',
                            'webhook_id' => $wcwp_wh_id,
                        ], admin_url('admin-post.php')),
                        'wcwp_webhook_delete',
                        'wcwp_webhook_delete_nonce'
                    );
                ?>
                    <tr data-webhook-id="<?php echo esc_attr($wcwp_wh_id); ?>">
                        <td><code style="word-break:break-all;font-size:0.92em;"><?php echo esc_html($wcwp_wh_url); ?></code></td>
                        <td>
                            <?php foreach ($wcwp_wh_events_list as $wcwp_wh_e) : ?>
                                <code style="font-size:0.85em;display:inline-block;margin:1px 2px;padding:1px 6px;background:#f0f3f1;border-radius:3px;"><?php echo esc_html($wcwp_wh_e); ?></code>
                            <?php endforeach; ?>
                        </td>
                        <td><code style="font-size:0.82em;word-break:break-all;"><?php echo esc_html($wcwp_wh_secret); ?></code></td>
                        <td><?php echo esc_html($wcwp_wh_created); ?></td>
                        <td>
                            <button type="button" class="button wcwp-webhook-test" data-webhook-id="<?php echo esc_attr($wcwp_wh_id); ?>"><?php esc_html_e('Test fire', 'woochat-pro'); ?></button>
                            <a class="button wcwp-webhook-delete" href="<?php echo esc_url($wcwp_wh_delete_url); ?>" style="color:#b32d2e;"><?php esc_html_e('Delete', 'woochat-pro'); ?></a>
                            <span class="wcwp-webhook-test-result" style="display:block;margin-top:4px;font-size:0.92em;"></span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <h3 style="margin-top:24px;"><?php esc_html_e('Recent dispatches', 'woochat-pro'); ?></h3>
    <?php if (empty($wcwp_wh_recent)) : ?>
        <p><em><?php esc_html_e('Nothing dispatched yet.', 'woochat-pro'); ?></em></p>
    <?php else : ?>
        <table class="widefat striped" style="margin-top:8px;">
            <thead>
                <tr>
                    <th><?php esc_html_e('Time', 'woochat-pro'); ?></th>
                    <th><?php esc_html_e('Event', 'woochat-pro'); ?></th>
                    <th><?php esc_html_e('Webhook', 'woochat-pro'); ?></th>
                    <th><?php esc_html_e('Status', 'woochat-pro'); ?></th>
                    <th><?php esc_html_e('HTTP', 'woochat-pro'); ?></th>
                    <th><?php esc_html_e('Attempt', 'woochat-pro'); ?></th>
                    <th><?php esc_html_e('Error', 'woochat-pro'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($wcwp_wh_recent as $wcwp_wh_row) : ?>
                    <tr>
                        <td><?php echo esc_html($wcwp_wh_row['ts'] ?? ''); ?></td>
                        <td><code style="font-size:0.9em;"><?php echo esc_html($wcwp_wh_row['event'] ?? ''); ?></code></td>
                        <td><code style="font-size:0.85em;"><?php echo esc_html($wcwp_wh_row['webhook_id'] ?? ''); ?></code></td>
                        <td>
                            <?php
                            $wcwp_wh_status = (string) ($wcwp_wh_row['status'] ?? '');
                            $wcwp_wh_color  = $wcwp_wh_status === 'sent' ? '#1c7c54' : '#b32d2e';
                            ?>
                            <span style="color:<?php echo esc_attr($wcwp_wh_color); ?>;font-weight:600;"><?php echo esc_html($wcwp_wh_status); ?></span>
                        </td>
                        <td><?php echo esc_html((string) (int) ($wcwp_wh_row['code'] ?? 0)); ?></td>
                        <td><?php echo esc_html((string) (int) ($wcwp_wh_row['attempt'] ?? 1)); ?></td>
                        <td><?php echo esc_html((string) ($wcwp_wh_row['error'] ?? '')); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
