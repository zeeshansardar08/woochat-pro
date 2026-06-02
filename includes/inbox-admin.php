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
 * Customer context (order history beside the thread)
 * ------------------------------------------------------------------------ */

/**
 * Aggregate plain order rows into a customer-context summary. Pure.
 *
 * @param array $rows Each: ['id','number','status','date','total'(float),'edit_url'].
 * @param int   $recent_limit How many recent orders to include in the list.
 * @return array{order_count:int, total_spent:float, orders:array<int,array>}
 */
function zignites_chat_inbox_aggregate_customer_context($rows, $recent_limit = 5) {
    if (!is_array($rows)) {
        $rows = [];
    }
    $count = 0;
    $total = 0.0;
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        $count++;
        $total += isset($row['total']) ? (float) $row['total'] : 0.0;
    }
    $recent_limit = max(0, (int) $recent_limit);
    return [
        'order_count' => $count,
        'total_spent' => $total,
        'orders'      => array_slice(array_values(array_filter($rows, 'is_array')), 0, $recent_limit),
    ];
}

/**
 * Build a customer-context summary for a phone from recent WooCommerce orders.
 *
 * Orders are matched by a last-digits LIKE query (tolerant of formatting /
 * country code), then verified with the suffix matcher, then aggregated.
 *
 * @param string $phone Customer phone (any format).
 * @return array Presented context (formatted strings) or an empty shell.
 */
function zignites_chat_inbox_get_customer_context($phone) {
    $empty = ['order_count' => 0, 'total_spent' => '', 'orders' => []];
    $digits = function_exists('zignites_chat_normalize_phone') ? zignites_chat_normalize_phone($phone) : preg_replace('/[^0-9]/', '', (string) $phone);
    if ($digits === '' || !function_exists('wc_get_orders')) {
        return $empty;
    }

    $suffix = substr($digits, -9);
    $limit  = (int) apply_filters('zignites_chat_inbox_context_order_limit', 50);

    $orders = wc_get_orders([
        'limit'      => max(1, $limit),
        'orderby'    => 'date',
        'order'      => 'DESC',
        'return'     => 'objects',
        'meta_query' => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
            [
                'key'     => '_billing_phone',
                'value'   => $suffix,
                'compare' => 'LIKE',
            ],
        ],
    ]);
    if (empty($orders) || !is_array($orders)) {
        return $empty;
    }

    $rows = [];
    foreach ($orders as $order) {
        if (!is_object($order)) {
            continue;
        }
        // Verify (drops a coincidental last-digits LIKE collision).
        if (function_exists('zignites_chat_cod_phone_matches')
            && !zignites_chat_cod_phone_matches($digits, $order->get_billing_phone())) {
            continue;
        }
        $date = $order->get_date_created();
        $rows[] = [
            'id'       => $order->get_id(),
            'number'   => $order->get_order_number(),
            'status'   => function_exists('wc_get_order_status_name') ? wc_get_order_status_name($order->get_status()) : $order->get_status(),
            'date'     => $date ? $date->date_i18n(get_option('date_format')) : '',
            'total'    => (float) $order->get_total(),
            'edit_url' => method_exists($order, 'get_edit_order_url') ? $order->get_edit_order_url() : admin_url('post.php?post=' . $order->get_id() . '&action=edit'),
        ];
    }

    $agg = zignites_chat_inbox_aggregate_customer_context($rows);

    $format = static function ($amount) {
        if (function_exists('wc_price')) {
            return html_entity_decode(wp_strip_all_tags(wc_price($amount)), ENT_QUOTES, 'UTF-8');
        }
        return number_format((float) $amount, 2);
    };

    $orders_out = [];
    foreach ($agg['orders'] as $row) {
        $orders_out[] = [
            'id'       => (int) $row['id'],
            'number'   => (string) $row['number'],
            'status'   => (string) $row['status'],
            'date'     => (string) $row['date'],
            'total'    => $format($row['total']),
            'edit_url' => esc_url_raw($row['edit_url']),
        ];
    }

    return [
        'order_count' => (int) $agg['order_count'],
        'total_spent' => $format($agg['total_spent']),
        'orders'      => $orders_out,
    ];
}

add_action('wp_ajax_zignites_chat_inbox_context', 'zignites_chat_ajax_inbox_context');
function zignites_chat_ajax_inbox_context() {
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
    $thread = zignites_chat_inbox_get_thread($conversation_id);
    if ($thread === null) {
        wp_send_json_error(['message' => __('Not found', 'zignites-chat')], 404);
    }

    wp_send_json_success(['context' => zignites_chat_inbox_get_customer_context($thread['phone'])]);
}

/* ---------------------------------------------------------------------------
 * Canned / quick replies
 * ------------------------------------------------------------------------ */

/**
 * Parse the canned-replies textarea (one "Title | Body" per line) into a
 * normalized list of [{title, body}]. Pure.
 *
 * A line without a "|" is treated as body-only, with the title derived from
 * the start of the body. Empty lines are skipped; the list is capped.
 *
 * @param string|array $value Raw textarea string (or an already-structured array).
 * @return array<int, array{title:string, body:string}>
 */
function zignites_chat_inbox_parse_canned_replies($value) {
    $rows = [];
    if (is_array($value)) {
        foreach ($value as $entry) {
            if (is_array($entry)) {
                $rows[] = [
                    'title' => isset($entry['title']) ? (string) $entry['title'] : '',
                    'body'  => isset($entry['body']) ? (string) $entry['body'] : '',
                ];
            }
        }
    } else {
        foreach (preg_split('/\r\n|\r|\n/', (string) $value) as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            if (strpos($line, '|') !== false) {
                list($title, $body) = explode('|', $line, 2);
            } else {
                $title = '';
                $body  = $line;
            }
            $rows[] = ['title' => trim($title), 'body' => trim($body)];
        }
    }

    $out = [];
    foreach ($rows as $row) {
        $body = function_exists('sanitize_textarea_field') ? sanitize_textarea_field($row['body']) : trim($row['body']);
        if ($body === '') {
            continue;
        }
        $title = function_exists('sanitize_text_field') ? sanitize_text_field($row['title']) : trim($row['title']);
        if ($title === '') {
            // Derive a short label from the body.
            $title = function_exists('mb_substr') ? mb_substr($body, 0, 40) : substr($body, 0, 40);
        }
        $out[] = ['title' => $title, 'body' => $body];
        if (count($out) >= 50) {
            break;
        }
    }
    return $out;
}

/**
 * Render a canned-replies list back into the "Title | Body" textarea form. Pure.
 *
 * @param array $entries [{title, body}].
 * @return string
 */
function zignites_chat_inbox_canned_replies_to_text($entries) {
    if (!is_array($entries)) {
        return '';
    }
    $lines = [];
    foreach ($entries as $entry) {
        if (!is_array($entry) || empty($entry['body'])) {
            continue;
        }
        $title = isset($entry['title']) ? (string) $entry['title'] : '';
        $lines[] = ($title !== '' ? $title . ' | ' : '') . (string) $entry['body'];
    }
    return implode("\n", $lines);
}

/**
 * Read the saved canned replies.
 *
 * @return array<int, array{title:string, body:string}>
 */
function zignites_chat_inbox_get_canned_replies() {
    return zignites_chat_inbox_parse_canned_replies(get_option('zignites_chat_inbox_canned_replies', []));
}

/* ---------------------------------------------------------------------------
 * Agents (assignment)
 * ------------------------------------------------------------------------ */

/**
 * Users who can be assigned inbox conversations: those who can manage the
 * store (administrators + shop managers). Returns [user_id => display_name].
 *
 * @return array<int, string>
 */
function zignites_chat_inbox_assignable_agents() {
    $agents = [];
    $users  = get_users([
        'role__in' => ['administrator', 'shop_manager'],
        'orderby'  => 'display_name',
        'fields'   => ['ID', 'display_name'],
    ]);
    foreach ($users as $user) {
        $agents[(int) $user->ID] = (string) $user->display_name;
    }
    return apply_filters('zignites_chat_inbox_assignable_agents', $agents);
}

/**
 * Resolve an agent id to a display name from the agents map.
 *
 * @param int   $agent_id
 * @param array $agents   [id => name] map.
 * @return string '' when unassigned, the name when known, else "User #id".
 */
function zignites_chat_inbox_agent_name($agent_id, $agents) {
    $agent_id = (int) $agent_id;
    if ($agent_id <= 0) {
        return '';
    }
    if (isset($agents[$agent_id])) {
        return (string) $agents[$agent_id];
    }
    /* translators: %d: WordPress user id. */
    return sprintf(__('User #%d', 'zignites-chat'), $agent_id);
}

/**
 * Translate a list-scope param into a get_threads() agent_id filter.
 *
 * @param string $scope   'all' | 'mine' | 'unassigned' | numeric agent id.
 * @param int    $user_id Current user id (for 'mine').
 * @return int|null null = no filter, 0 = unassigned, >0 = that agent.
 */
function zignites_chat_inbox_scope_to_agent_filter($scope, $user_id) {
    $scope = (string) $scope;
    if ($scope === 'mine') {
        return (int) $user_id;
    }
    if ($scope === 'unassigned') {
        return 0;
    }
    if ($scope !== '' && ctype_digit($scope)) {
        return (int) $scope;
    }
    return null; // 'all' / empty
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
    $agents = zignites_chat_inbox_assignable_agents();
    $agent_options = [];
    foreach ($agents as $id => $name) {
        $agent_options[] = ['id' => (int) $id, 'name' => (string) $name];
    }
    wp_localize_script('zignites-chat-inbox-js', 'zignitesChatInbox', [
        'ajaxUrl'      => admin_url('admin-ajax.php'),
        'nonce'        => wp_create_nonce('zignites_chat_inbox'),
        'pollInterval' => (int) apply_filters('zignites_chat_inbox_poll_interval', 15000),
        'currentUser'  => get_current_user_id(),
        'agents'       => $agent_options,
        'cannedReplies' => array_values(zignites_chat_inbox_get_canned_replies()),
        'i18n'         => [
            'cannedInsert'  => __('Quick reply…', 'zignites-chat'),
            'ctxOrders'     => __('Orders', 'zignites-chat'),
            'ctxSpent'      => __('Lifetime', 'zignites-chat'),
            'ctxNoOrders'   => __('No matching orders found.', 'zignites-chat'),
            'ctxRecent'     => __('Recent orders', 'zignites-chat'),
            'ctxView'       => __('View', 'zignites-chat'),
            'assignedTo'    => __('Assigned to', 'zignites-chat'),
            'unassigned'    => __('Unassigned', 'zignites-chat'),
            'claim'         => __('Claim', 'zignites-chat'),
            'assign'        => __('Assign…', 'zignites-chat'),
            'filterAll'     => __('All conversations', 'zignites-chat'),
            'filterMine'    => __('Assigned to me', 'zignites-chat'),
            'filterUnassigned' => __('Unassigned', 'zignites-chat'),
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
            'send'          => __('Send', 'zignites-chat'),
            'sending'       => __('Sending…', 'zignites-chat'),
            'replyEmpty'    => __('Type a message before sending.', 'zignites-chat'),
            'replyError'    => __('The message could not be sent. Please try again.', 'zignites-chat'),
            'windowClosedNote' => __('The 24-hour window has closed — a reply now requires an approved template.', 'zignites-chat'),
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
    $scope  = isset($_GET['scope']) ? sanitize_key(wp_unslash($_GET['scope'])) : '';
    $agent_filter = zignites_chat_inbox_scope_to_agent_filter($scope, get_current_user_id());

    $rows = zignites_chat_inbox_get_threads([
        'limit'    => 100,
        'search'   => $search,
        'agent_id' => $agent_filter,
    ]);

    $agents  = zignites_chat_inbox_assignable_agents();
    $threads = [];
    foreach ($rows as $row) {
        $thread = zignites_chat_inbox_present_thread($row);
        $thread['agentName'] = zignites_chat_inbox_agent_name($thread['agent_id'], $agents);
        $threads[] = $thread;
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
    $present['agentName']  = zignites_chat_inbox_agent_name($present['agent_id'], zignites_chat_inbox_assignable_agents());

    wp_send_json_success([
        'thread'   => $present,
        'messages' => $messages,
    ]);
}

/* ---------------------------------------------------------------------------
 * AJAX — assign / claim / unassign a conversation
 * ------------------------------------------------------------------------ */

add_action('wp_ajax_zignites_chat_inbox_assign', 'zignites_chat_ajax_inbox_assign');
function zignites_chat_ajax_inbox_assign() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Unauthorized', 'zignites-chat')], 403);
    }
    if (!check_ajax_referer('zignites_chat_inbox', 'nonce', false)) {
        wp_send_json_error(['message' => __('Bad nonce', 'zignites-chat')], 400);
    }
    if (!zignites_chat_is_pro_active()) {
        wp_send_json_error(['message' => __('Pro required', 'zignites-chat')], 403);
    }

    $conversation_id = isset($_POST['conversation_id']) ? (int) $_POST['conversation_id'] : 0;
    $agent_id        = isset($_POST['agent_id']) ? (int) $_POST['agent_id'] : 0;

    if (zignites_chat_inbox_get_thread($conversation_id) === null) {
        wp_send_json_error(['message' => __('Not found', 'zignites-chat')], 404);
    }
    // A non-zero assignee must be a real, eligible agent.
    if ($agent_id > 0 && !isset(zignites_chat_inbox_assignable_agents()[$agent_id])) {
        wp_send_json_error(['message' => __('Unknown agent', 'zignites-chat')], 422);
    }

    if (!zignites_chat_inbox_assign_thread($conversation_id, $agent_id)) {
        wp_send_json_error(['message' => __('Could not update assignment.', 'zignites-chat')], 500);
    }

    wp_send_json_success([
        'agent_id'  => $agent_id,
        'agentName' => zignites_chat_inbox_agent_name($agent_id, zignites_chat_inbox_assignable_agents()),
    ]);
}

/* ---------------------------------------------------------------------------
 * AJAX — agent reply (free-form, within the 24h service window)
 * ------------------------------------------------------------------------ */

add_action('wp_ajax_zignites_chat_inbox_reply', 'zignites_chat_ajax_inbox_reply');
function zignites_chat_ajax_inbox_reply() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Unauthorized', 'zignites-chat')], 403);
    }
    if (!check_ajax_referer('zignites_chat_inbox', 'nonce', false)) {
        wp_send_json_error(['message' => __('Bad nonce', 'zignites-chat')], 400);
    }
    if (!zignites_chat_is_pro_active()) {
        wp_send_json_error(['message' => __('Pro required', 'zignites-chat')], 403);
    }

    $conversation_id = isset($_POST['conversation_id']) ? (int) $_POST['conversation_id'] : 0;
    $body            = isset($_POST['body']) ? sanitize_textarea_field(wp_unslash($_POST['body'])) : '';

    if ($body === '') {
        wp_send_json_error(['message' => __('Type a message before sending.', 'zignites-chat')], 422);
    }

    $thread = zignites_chat_inbox_get_thread($conversation_id);
    if ($thread === null) {
        wp_send_json_error(['message' => __('Not found', 'zignites-chat')], 404);
    }

    // The 24h customer-service window must still be open for a free-form send.
    if (!zignites_chat_inbox_window_is_open($thread['last_inbound_at'] ?? '')) {
        wp_send_json_error([
            'message'     => __('The 24-hour service window has closed. Use an approved template to reach this customer.', 'zignites-chat'),
            'windowOpen'  => false,
        ], 422);
    }

    $phone = (string) $thread['phone'];
    $sent  = zignites_chat_send_whatsapp_message($phone, $body, true, [
        'type'              => 'inbox',
        'order_id'          => 0,
        // This handler records its own outbound row below; don't let the
        // dispatcher's inbox mirror double-record it.
        'skip_inbox_mirror' => true,
    ]);
    if (!$sent) {
        wp_send_json_error(['message' => __('The message could not be sent. Check the log for details.', 'zignites-chat')], 502);
    }

    // Record the outbound reply into the thread and clear the unread badge.
    $provider = get_option('zignites_chat_api_provider', 'twilio');
    $result = zignites_chat_inbox_record_message([
        'phone'     => $phone,
        'direction' => 'out',
        'body'      => $body,
        'provider'  => $provider,
        'status'    => 'sent',
    ]);
    zignites_chat_inbox_mark_read($conversation_id);

    $message = [];
    if (!is_wp_error($result)) {
        $message = zignites_chat_inbox_present_message([
            'id'         => (int) $result['message_id'],
            'direction'  => 'out',
            'body'       => $body,
            'status'     => 'sent',
            'created_at' => current_time('mysql'),
        ]);
    }

    wp_send_json_success(['message' => $message]);
}
