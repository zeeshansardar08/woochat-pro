<?php
if (!defined('ABSPATH')) exit;
// phpcs:disable WordPress.Security.NonceVerification.Recommended -- The GET query string is read only to pre-fill display filters; no state-changing action is taken here.

$zignites_chat_wh_list      = zignites_chat_get_webhooks();
$zignites_chat_wh_events    = zignites_chat_webhook_event_keys();
$zignites_chat_wh_recent    = zignites_chat_webhook_log_recent('', 25);
$zignites_chat_wh_msg       = isset($_GET['zignites_chat_webhook_msg']) ? sanitize_text_field(wp_unslash($_GET['zignites_chat_webhook_msg'])) : '';
$zignites_chat_wh_post_url  = admin_url('admin-post.php');
?>
<h2><?php esc_html_e('Webhooks', 'zignites-chat'); ?></h2>
    <p class="description">
        <?php esc_html_e('Pipe plugin events to Zapier, Make, n8n, or your own backend. Each request is signed with HMAC-SHA256 — receivers verify by recomputing the signature with the per-webhook secret.', 'zignites-chat'); ?>
    </p>

    <?php if ($zignites_chat_wh_msg === 'added') : ?>
        <div class="notice notice-success is-dismissible" style="margin:10px 0;"><p><?php esc_html_e('Webhook saved.', 'zignites-chat'); ?></p></div>
    <?php elseif ($zignites_chat_wh_msg === 'deleted') : ?>
        <div class="notice notice-success is-dismissible" style="margin:10px 0;"><p><?php esc_html_e('Webhook deleted.', 'zignites-chat'); ?></p></div>
    <?php elseif ($zignites_chat_wh_msg === 'invalid') : ?>
        <div class="notice notice-error is-dismissible" style="margin:10px 0;"><p><?php esc_html_e('Could not save the webhook. Check that the URL is valid and at least one event is selected.', 'zignites-chat'); ?></p></div>
    <?php endif; ?>

    <h3 style="margin-top:18px;"><?php esc_html_e('Add a webhook', 'zignites-chat'); ?></h3>
    <form method="post" action="<?php echo esc_url($zignites_chat_wh_post_url); ?>" style="margin-top:8px;max-width:780px;">
        <?php wp_nonce_field('zignites_chat_webhook_add', 'zignites_chat_webhook_add_nonce'); ?>
        <input type="hidden" name="action" value="zignites_chat_webhook_add" />
        <table class="form-table">
            <tr>
                <th scope="row"><label for="zignites_chat_webhook_url"><?php esc_html_e('Endpoint URL', 'zignites-chat'); ?></label></th>
                <td>
                    <input type="url" name="zignites_chat_webhook_url" id="zignites_chat_webhook_url" class="regular-text" placeholder="https://hooks.example.com/zignitesChat" required />
                    <p class="description"><?php esc_html_e('Must be HTTP or HTTPS. The receiver will get a JSON POST with X-Zignites-Chat-Event and X-Zignites-Chat-Signature headers.', 'zignites-chat'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Events', 'zignites-chat'); ?></th>
                <td>
                    <fieldset>
                        <?php foreach ($zignites_chat_wh_events as $zignites_chat_wh_event_key => $zignites_chat_wh_event_label) : ?>
                            <label style="display:block;margin-bottom:4px;">
                                <input type="checkbox" name="zignites_chat_webhook_events[]" value="<?php echo esc_attr($zignites_chat_wh_event_key); ?>" />
                                <code style="font-size:0.92em;"><?php echo esc_html($zignites_chat_wh_event_key); ?></code>
                                — <?php echo esc_html($zignites_chat_wh_event_label); ?>
                            </label>
                        <?php endforeach; ?>
                    </fieldset>
                    <p class="description"><?php esc_html_e('Select at least one event. A secret is generated automatically when the webhook is saved.', 'zignites-chat'); ?></p>
                </td>
            </tr>
        </table>
        <?php submit_button(__('Save webhook', 'zignites-chat'), 'primary', 'zignites_chat_webhook_submit', false); ?>
    </form>

    <h3 style="margin-top:24px;"><?php esc_html_e('Active webhooks', 'zignites-chat'); ?></h3>
    <?php if (empty($zignites_chat_wh_list)) : ?>
        <p><em><?php esc_html_e('No webhooks configured yet.', 'zignites-chat'); ?></em></p>
    <?php else : ?>
        <table class="widefat striped" style="margin-top:8px;">
            <thead>
                <tr>
                    <th><?php esc_html_e('Endpoint', 'zignites-chat'); ?></th>
                    <th><?php esc_html_e('Events', 'zignites-chat'); ?></th>
                    <th><?php esc_html_e('Secret', 'zignites-chat'); ?></th>
                    <th><?php esc_html_e('Created', 'zignites-chat'); ?></th>
                    <th><?php esc_html_e('Actions', 'zignites-chat'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($zignites_chat_wh_list as $zignites_chat_wh) :
                    $zignites_chat_wh_id     = isset($zignites_chat_wh['id']) ? (string) $zignites_chat_wh['id'] : '';
                    $zignites_chat_wh_url    = isset($zignites_chat_wh['url']) ? (string) $zignites_chat_wh['url'] : '';
                    $zignites_chat_wh_events_list = isset($zignites_chat_wh['events']) && is_array($zignites_chat_wh['events']) ? $zignites_chat_wh['events'] : [];
                    $zignites_chat_wh_secret = isset($zignites_chat_wh['secret']) ? (string) $zignites_chat_wh['secret'] : '';
                    $zignites_chat_wh_created = isset($zignites_chat_wh['created_at']) ? (string) $zignites_chat_wh['created_at'] : '';

                    $zignites_chat_wh_delete_url = wp_nonce_url(
                        add_query_arg([
                            'action'     => 'zignites_chat_webhook_delete',
                            'webhook_id' => $zignites_chat_wh_id,
                        ], admin_url('admin-post.php')),
                        'zignites_chat_webhook_delete',
                        'zignites_chat_webhook_delete_nonce'
                    );
                ?>
                    <tr data-webhook-id="<?php echo esc_attr($zignites_chat_wh_id); ?>">
                        <td><code style="word-break:break-all;font-size:0.92em;"><?php echo esc_html($zignites_chat_wh_url); ?></code></td>
                        <td>
                            <?php foreach ($zignites_chat_wh_events_list as $zignites_chat_wh_e) : ?>
                                <code style="font-size:0.85em;display:inline-block;margin:1px 2px;padding:1px 6px;background:#f0f3f1;border-radius:3px;"><?php echo esc_html($zignites_chat_wh_e); ?></code>
                            <?php endforeach; ?>
                        </td>
                        <td><code style="font-size:0.82em;word-break:break-all;"><?php echo esc_html($zignites_chat_wh_secret); ?></code></td>
                        <td><?php echo esc_html($zignites_chat_wh_created); ?></td>
                        <td>
                            <button type="button" class="button zignites-chat-webhook-test" data-webhook-id="<?php echo esc_attr($zignites_chat_wh_id); ?>"><?php esc_html_e('Test fire', 'zignites-chat'); ?></button>
                            <a class="button zignites-chat-webhook-delete" href="<?php echo esc_url($zignites_chat_wh_delete_url); ?>" style="color:#b32d2e;"><?php esc_html_e('Delete', 'zignites-chat'); ?></a>
                            <span class="zignites-chat-webhook-test-result" style="display:block;margin-top:4px;font-size:0.92em;"></span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <h3 style="margin-top:24px;"><?php esc_html_e('Recent dispatches', 'zignites-chat'); ?></h3>
    <?php if (empty($zignites_chat_wh_recent)) : ?>
        <p><em><?php esc_html_e('Nothing dispatched yet.', 'zignites-chat'); ?></em></p>
    <?php else : ?>
        <table class="widefat striped" style="margin-top:8px;">
            <thead>
                <tr>
                    <th><?php esc_html_e('Time', 'zignites-chat'); ?></th>
                    <th><?php esc_html_e('Event', 'zignites-chat'); ?></th>
                    <th><?php esc_html_e('Webhook', 'zignites-chat'); ?></th>
                    <th><?php esc_html_e('Status', 'zignites-chat'); ?></th>
                    <th><?php esc_html_e('HTTP', 'zignites-chat'); ?></th>
                    <th><?php esc_html_e('Attempt', 'zignites-chat'); ?></th>
                    <th><?php esc_html_e('Error', 'zignites-chat'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($zignites_chat_wh_recent as $zignites_chat_wh_row) : ?>
                    <tr>
                        <td><?php echo esc_html($zignites_chat_wh_row['ts'] ?? ''); ?></td>
                        <td><code style="font-size:0.9em;"><?php echo esc_html($zignites_chat_wh_row['event'] ?? ''); ?></code></td>
                        <td><code style="font-size:0.85em;"><?php echo esc_html($zignites_chat_wh_row['webhook_id'] ?? ''); ?></code></td>
                        <td>
                            <?php
                            $zignites_chat_wh_status = (string) ($zignites_chat_wh_row['status'] ?? '');
                            $zignites_chat_wh_color  = $zignites_chat_wh_status === 'sent' ? '#1c7c54' : '#b32d2e';
                            ?>
                            <span style="color:<?php echo esc_attr($zignites_chat_wh_color); ?>;font-weight:600;"><?php echo esc_html($zignites_chat_wh_status); ?></span>
                        </td>
                        <td><?php echo esc_html((string) (int) ($zignites_chat_wh_row['code'] ?? 0)); ?></td>
                        <td><?php echo esc_html((string) (int) ($zignites_chat_wh_row['attempt'] ?? 1)); ?></td>
                        <td><?php echo esc_html((string) ($zignites_chat_wh_row['error'] ?? '')); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
