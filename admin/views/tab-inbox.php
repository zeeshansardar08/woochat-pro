<?php
if (!defined('ABSPATH')) exit;
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- View partial: these variables are scoped to the render function that includes this file, not the global namespace.

$zignites_chat_threads = zignites_chat_inbox_get_threads(['limit' => 100]);
?>
<p class="description">
    <?php esc_html_e('Read and reply to WhatsApp conversations with your customers. Inbound messages are captured automatically from your connected provider.', 'zignites-chat'); ?>
</p>

<div class="zignites-chat-inbox" id="zignites-chat-inbox">
    <div class="zignites-chat-inbox-list" id="zignites-chat-inbox-list">
        <div class="zignites-chat-inbox-search">
            <input type="search" id="zignites-chat-inbox-search" class="regular-text"
                   placeholder="<?php esc_attr_e('Search name or number…', 'zignites-chat'); ?>" />
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
            </div>
            <div class="zignites-chat-inbox-window" id="zignites-chat-inbox-window"></div>
            <div class="zignites-chat-inbox-messages" id="zignites-chat-inbox-messages"></div>
            <div class="zignites-chat-inbox-composer" id="zignites-chat-inbox-composer">
                <textarea id="zignites-chat-inbox-reply" rows="2"
                          placeholder="<?php esc_attr_e('Type a reply…', 'zignites-chat'); ?>"></textarea>
                <button type="button" class="button button-primary" id="zignites-chat-inbox-send">
                    <?php esc_html_e('Send', 'zignites-chat'); ?>
                </button>
                <p class="zignites-chat-inbox-composer-note" id="zignites-chat-inbox-composer-note"></p>
            </div>
        </div>
    </div>
</div>
