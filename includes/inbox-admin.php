<?php
/**
 * Two-way team inbox — admin surface (increment I3).
 *
 * Renders the Pro "Inbox" submenu (two-pane conversation list + thread panel)
 * and serves the AJAX endpoints the page uses to list threads and load a
 * thread's messages (with polling for new ones). Agent replies are added in
 * I4; this increment is read-only apart from clearing a thread's unread badge
 * when it is opened.
 *
 * @package Zignites_Chat
 */

if (!defined('ABSPATH')) exit;

/* ---------------------------------------------------------------------------
 * Page render
 * ------------------------------------------------------------------------ */

/**
 * Render the Inbox admin page (Pro-gated).
 */
function zignites_chat_render_inbox_page() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Unauthorized', 'zignites-chat'));
    }
    zignites_chat_admin_page_open(__('Inbox', 'zignites-chat'));
    if (!zignites_chat_is_pro_active()) {
        zignites_chat_render_pro_upgrade_notice('inbox');
        zignites_chat_admin_page_close();
        return;
    }
    require ZIGNITES_CHAT_PATH . 'admin/views/tab-inbox.php';
    zignites_chat_admin_page_close();
}

/* ---------------------------------------------------------------------------
 * Assets
 * ------------------------------------------------------------------------ */

add_action('admin_enqueue_scripts', 'zignites_chat_inbox_enqueue_assets');
function zignites_chat_inbox_enqueue_assets($hook) {
    if (strpos($hook, 'zignites-chat-inbox') === false) {
        return;
    }
    wp_enqueue_style('zignites-chat-inbox-css', ZIGNITES_CHAT_URL . 'assets/css/inbox.css', [], ZIGNITES_CHAT_VERSION);
    wp_enqueue_script('zignites-chat-inbox-js', ZIGNITES_CHAT_URL . 'assets/js/inbox.js', [], ZIGNITES_CHAT_VERSION, true);
    wp_localize_script('zignites-chat-inbox-js', 'zignitesChatInbox', [
        'ajaxUrl'      => admin_url('admin-ajax.php'),
        'nonce'        => wp_create_nonce('zignites_chat_inbox'),
        'pollInterval' => (int) apply_filters('zignites_chat_inbox_poll_interval', 15000),
        'i18n'         => [
            'loading'       => __('Loading…', 'zignites-chat'),
            'noThreads'     => __('No conversations yet. Inbound WhatsApp messages will appear here.', 'zignites-chat'),
            'noMessages'    => __('No messages in this conversation.', 'zignites-chat'),
            'selectThread'  => __('Select a conversation to read it.', 'zignites-chat'),
            'loadError'     => __('Could not load. Please try again.', 'zignites-chat'),
            'unknown'       => __('Unknown', 'zignites-chat'),
            'windowOpen'    => __('Customer-service window open — you can reply freely.', 'zignites-chat'),
            'windowClosed'  => __('The 24-hour service window has closed. A reply now requires an approved template.', 'zignites-chat'),
            'you'           => __('You', 'zignites-chat'),
            'customer'      => __('Customer', 'zignites-chat'),
        ],
    ]);
}

/* ---------------------------------------------------------------------------
 * AJAX — list threads
 * ------------------------------------------------------------------------ */

add_action('wp_ajax_zignites_chat_inbox_threads', 'zignites_chat_ajax_inbox_threads');
function zignites_chat_ajax_inbox_threads() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Unauthorized', 'zignites-chat')], 403);
    }
    if (!check_ajax_referer('zignites_chat_inbox', 'nonce', false)) {
        wp_send_json_error(['message' => __('Bad nonce', 'zignites-chat')], 400);
    }
    if (!zignites_chat_is_pro_active()) {
        wp_send_json_error(['message' => __('Pro required', 'zignites-chat')], 403);
    }

    $search = isset($_GET['search']) ? sanitize_text_field(wp_unslash($_GET['search'])) : '';
    $rows   = zignites_chat_inbox_get_threads(['limit' => 100, 'search' => $search]);

    $threads = [];
    foreach ($rows as $row) {
        $threads[] = zignites_chat_inbox_present_thread($row);
    }

    wp_send_json_success([
        'threads'     => $threads,
        'totalUnread' => zignites_chat_inbox_total_unread(),
    ]);
}

/* ---------------------------------------------------------------------------
 * AJAX — fetch one thread's messages (and clear its unread badge)
 * ------------------------------------------------------------------------ */

add_action('wp_ajax_zignites_chat_inbox_thread', 'zignites_chat_ajax_inbox_thread');
function zignites_chat_ajax_inbox_thread() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Unauthorized', 'zignites-chat')], 403);
    }
    if (!check_ajax_referer('zignites_chat_inbox', 'nonce', false)) {
        wp_send_json_error(['message' => __('Bad nonce', 'zignites-chat')], 400);
    }
    if (!zignites_chat_is_pro_active()) {
        wp_send_json_error(['message' => __('Pro required', 'zignites-chat')], 403);
    }

    $conversation_id = isset($_GET['conversation_id']) ? (int) $_GET['conversation_id'] : 0;
    $after_id        = isset($_GET['after_id']) ? (int) $_GET['after_id'] : 0;

    $thread = zignites_chat_inbox_get_thread($conversation_id);
    if ($thread === null) {
        wp_send_json_error(['message' => __('Not found', 'zignites-chat')], 404);
    }

    $rows = zignites_chat_inbox_get_messages($conversation_id, ['after_id' => $after_id]);
    $messages = [];
    foreach ($rows as $row) {
        $messages[] = zignites_chat_inbox_present_message($row);
    }

    // Opening (or polling) a thread clears its unread badge. Only do so on the
    // initial open (after_id === 0) so a background poll doesn't suppress a
    // badge the agent has not actually looked at.
    if ($after_id === 0) {
        zignites_chat_inbox_mark_read($conversation_id);
    }

    $present = zignites_chat_inbox_present_thread($thread);
    $present['windowOpen'] = zignites_chat_inbox_window_is_open($thread['last_inbound_at'] ?? '');

    wp_send_json_success([
        'thread'   => $present,
        'messages' => $messages,
    ]);
}
