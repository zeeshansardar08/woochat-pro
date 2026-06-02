<?php
if (!defined('ABSPATH')) exit;
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- View partial: these variables are scoped to the render function that includes this file, not the global namespace.

$zignites_chat_threads = zignites_chat_inbox_get_threads(['limit' => 100]);
$zignites_chat_canned  = zignites_chat_inbox_get_canned_replies();
?>
<p class="description">
    <?php esc_html_e('Read and reply to WhatsApp conversations with your customers. Inbound messages are captured automatically from your connected provider.', 'zignites-chat'); ?>
</p>

<details class="zignites-chat-inbox-canned-manage" style="margin:8px 0 16px;">
    <summary style="cursor:pointer; font-weight:600;"><?php esc_html_e('Manage quick replies', 'zignites-chat'); ?></summary>
    <form method="post" action="options.php" style="margin-top:10px;">
        <?php settings_fields('zignites_chat_inbox_group'); ?>
        <p class="description"><?php esc_html_e('One reply per line as "Title | Message". The title is what agents pick from the composer; the message is inserted. Lines without a "|" use the message as both.', 'zignites-chat'); ?></p>
        <textarea name="zignites_chat_inbox_canned_replies" rows="5" class="large-text" placeholder="<?php esc_attr_e('Shipping delay | Sorry for the delay — your order ships within 24 hours.', 'zignites-chat'); ?>"><?php echo esc_textarea(zignites_chat_inbox_canned_replies_to_text($zignites_chat_canned)); ?></textarea>
        <?php submit_button(__('Save quick replies', 'zignites-chat'), 'secondary', 'submit', true); ?>
    </form>
</details>

<details class="zignites-chat-inbox-notify-manage" style="margin:8px 0 16px;">
    <summary style="cursor:pointer; font-weight:600;"><?php esc_html_e('Email notifications', 'zignites-chat'); ?></summary>
    <form method="post" action="options.php" style="margin-top:10px;">
        <?php settings_fields('zignites_chat_inbox_notify_group'); ?>
        <?php
        $zignites_chat_notify_on    = get_option('zignites_chat_inbox_notify_enabled', 'no');
        $zignites_chat_notify_email = get_option('zignites_chat_inbox_notify_email', '');
        ?>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="zignites_chat_inbox_notify_enabled"><?php esc_html_e('Notify on new message', 'zignites-chat'); ?></label></th>
                <td>
                    <select name="zignites_chat_inbox_notify_enabled" id="zignites_chat_inbox_notify_enabled">
                        <option value="no" <?php selected($zignites_chat_notify_on, 'no'); ?>><?php esc_html_e('No', 'zignites-chat'); ?></option>
                        <option value="yes" <?php selected($zignites_chat_notify_on, 'yes'); ?>><?php esc_html_e('Yes', 'zignites-chat'); ?></option>
                    </select>
                    <p class="description"><?php esc_html_e('Emails the assigned agent (or all managers when unassigned) when a customer replies. Throttled to one email per conversation every 15 minutes.', 'zignites-chat'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="zignites_chat_inbox_notify_email"><?php esc_html_e('Override address', 'zignites-chat'); ?></label></th>
                <td>
                    <input type="email" name="zignites_chat_inbox_notify_email" id="zignites_chat_inbox_notify_email" value="<?php echo esc_attr($zignites_chat_notify_email); ?>" class="regular-text" placeholder="<?php esc_attr_e('team@example.com', 'zignites-chat'); ?>" />
                    <p class="description"><?php esc_html_e('Optional. When set, all notifications go here instead of to individual agents.', 'zignites-chat'); ?></p>
                </td>
            </tr>
        </table>
        <?php submit_button(__('Save notification settings', 'zignites-chat'), 'secondary', 'submit', true); ?>
    </form>
</details>

<div class="zignites-chat-inbox" id="zignites-chat-inbox">
    <div class="zignites-chat-inbox-list" id="zignites-chat-inbox-list">
        <div class="zignites-chat-inbox-search">
            <input type="search" id="zignites-chat-inbox-search" class="regular-text"
                   placeholder="<?php esc_attr_e('Search name or number…', 'zignites-chat'); ?>" />
            <select id="zignites-chat-inbox-filter" style="margin-top:8px; width:100%;">
                <option value="all"><?php esc_html_e('All conversations', 'zignites-chat'); ?></option>
                <option value="mine"><?php esc_html_e('Assigned to me', 'zignites-chat'); ?></option>
                <option value="unassigned"><?php esc_html_e('Unassigned', 'zignites-chat'); ?></option>
            </select>
        </div>
        <ul class="zignites-chat-inbox-threads" id="zignites-chat-inbox-threads">
            <?php if (empty($zignites_chat_threads)) : ?>
                <li class="zignites-chat-inbox-empty">
                    <?php esc_html_e('No conversations yet. Inbound WhatsApp messages will appear here.', 'zignites-chat'); ?>
                </li>
            <?php else : ?>
                <?php foreach ($zignites_chat_threads as $zignites_chat_thread) :
                    $zignites_chat_t = zignites_chat_inbox_present_thread($zignites_chat_thread);
                    $zignites_chat_label = $zignites_chat_t['name'] !== '' ? $zignites_chat_t['name'] : $zignites_chat_t['phone'];
                    ?>
                    <li class="zignites-chat-inbox-thread<?php echo $zignites_chat_t['unread'] > 0 ? ' is-unread' : ''; ?>"
                        data-id="<?php echo esc_attr((string) $zignites_chat_t['id']); ?>">
                        <span class="zignites-chat-inbox-thread-name"><?php echo esc_html($zignites_chat_label); ?></span>
                        <?php if ($zignites_chat_t['unread'] > 0) : ?>
                            <span class="zignites-chat-inbox-badge"><?php echo esc_html((string) $zignites_chat_t['unread']); ?></span>
                        <?php endif; ?>
                        <span class="zignites-chat-inbox-thread-excerpt"><?php echo esc_html($zignites_chat_t['excerpt']); ?></span>
                        <span class="zignites-chat-inbox-thread-time"><?php echo esc_html($zignites_chat_t['last_message_at']); ?></span>
                    </li>
                <?php endforeach; ?>
            <?php endif; ?>
        </ul>
    </div>

    <div class="zignites-chat-inbox-panel" id="zignites-chat-inbox-panel">
        <div class="zignites-chat-inbox-panel-empty" id="zignites-chat-inbox-panel-empty">
            <?php esc_html_e('Select a conversation to read it.', 'zignites-chat'); ?>
        </div>
        <div class="zignites-chat-inbox-thread-view" id="zignites-chat-inbox-thread-view" style="display:none;">
            <div class="zignites-chat-inbox-thread-header">
                <span class="zignites-chat-inbox-thread-title" id="zignites-chat-inbox-thread-title"></span>
                <span class="zignites-chat-inbox-thread-phone" id="zignites-chat-inbox-thread-phone"></span>
                <span class="zignites-chat-inbox-assign" id="zignites-chat-inbox-assign">
                    <span class="zignites-chat-inbox-assignee" id="zignites-chat-inbox-assignee"></span>
                    <button type="button" class="button button-small" id="zignites-chat-inbox-claim"></button>
                    <select id="zignites-chat-inbox-assign-select"></select>
                </span>
            </div>
            <div class="zignites-chat-inbox-context" id="zignites-chat-inbox-context" style="display:none;"></div>
            <div class="zignites-chat-inbox-window" id="zignites-chat-inbox-window"></div>
            <div class="zignites-chat-inbox-messages" id="zignites-chat-inbox-messages"></div>
            <div class="zignites-chat-inbox-composer" id="zignites-chat-inbox-composer">
                <?php if (!empty($zignites_chat_canned)) : ?>
                    <select id="zignites-chat-inbox-canned" class="zignites-chat-inbox-canned-select">
                        <option value=""><?php esc_html_e('Quick reply…', 'zignites-chat'); ?></option>
                        <?php foreach ($zignites_chat_canned as $zignites_chat_cr) : ?>
                            <option value="<?php echo esc_attr($zignites_chat_cr['body']); ?>"><?php echo esc_html($zignites_chat_cr['title']); ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
                <textarea id="zignites-chat-inbox-reply" rows="2"
                          placeholder="<?php esc_attr_e('Type a reply…', 'zignites-chat'); ?>"></textarea>
                <button type="button" class="button button-primary" id="zignites-chat-inbox-send">
                    <?php esc_html_e('Send', 'zignites-chat'); ?>
                </button>
                <label class="zignites-chat-inbox-note-toggle">
                    <input type="checkbox" id="zignites-chat-inbox-note-mode" />
                    <?php esc_html_e('Internal note (not sent to customer)', 'zignites-chat'); ?>
                </label>
                <p class="zignites-chat-inbox-composer-note" id="zignites-chat-inbox-composer-note"></p>
            </div>
        </div>
    </div>
</div>
