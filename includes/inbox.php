<?php
/**
 * Two-way team inbox — storage layer (increment I1).
 *
 * Persists WhatsApp conversations as threads (one row per phone number) and
 * the individual inbound/outbound messages that make up each thread. This
 * file is the pure data layer: schema creation (idempotent dbDelta), small
 * pure helpers for shaping rows, and thin $wpdb wrappers for upsert/insert.
 *
 * Inbound capture (I2), the admin Inbox view (I3) and agent replies (I4) are
 * wired in later increments on top of these primitives.
 *
 * @package Zignites_Chat
 */

if (!defined('ABSPATH')) exit;

/* -------------------------------------------------------------------------
 * Table names
 * ----------------------------------------------------------------------- */

/**
 * Conversations table — one row per phone number.
 *
 * @return string Fully-prefixed table name.
 */
function zignites_chat_conversations_table_name() {
    global $wpdb;
    return $wpdb->prefix . 'zignites_chat_conversations';
}

/**
 * Messages table — one row per inbound/outbound message.
 *
 * @return string Fully-prefixed table name.
 */
function zignites_chat_messages_table_name() {
    global $wpdb;
    return $wpdb->prefix . 'zignites_chat_messages';
}

/* -------------------------------------------------------------------------
 * Schema
 * ----------------------------------------------------------------------- */

/**
 * Create the inbox tables. dbDelta-based and idempotent: called from
 * migration v6 and from the activation hook so a fresh install lands the
 * tables before the first admin_init migration tick.
 *
 * `last_inbound_at` is tracked separately from `last_message_at` so the 24h
 * WhatsApp service-window check (I3/I4) is a cheap column read rather than a
 * messages-table scan.
 */
function zignites_chat_create_inbox_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    $conversations   = zignites_chat_conversations_table_name();
    $messages        = zignites_chat_messages_table_name();

    $sql_conversations = "CREATE TABLE {$conversations} (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        phone VARCHAR(40) NOT NULL,
        customer_name VARCHAR(190) NOT NULL DEFAULT '',
        last_message_at DATETIME NULL,
        last_inbound_at DATETIME NULL,
        last_excerpt VARCHAR(190) NOT NULL DEFAULT '',
        last_direction VARCHAR(3) NOT NULL DEFAULT '',
        unread_count INT UNSIGNED NOT NULL DEFAULT 0,
        agent_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY phone (phone),
        KEY last_message_at (last_message_at)
    ) {$charset_collate};";

    $sql_messages = "CREATE TABLE {$messages} (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        conversation_id BIGINT UNSIGNED NOT NULL,
        phone VARCHAR(40) NOT NULL,
        direction VARCHAR(8) NOT NULL,
        body TEXT NULL,
        provider VARCHAR(20) NOT NULL DEFAULT '',
        message_id VARCHAR(190) NOT NULL DEFAULT '',
        status VARCHAR(20) NOT NULL DEFAULT '',
        author_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL,
        PRIMARY KEY  (id),
        KEY conversation_created (conversation_id, created_at),
        KEY message_id (message_id)
    ) {$charset_collate};";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql_conversations);
    dbDelta($sql_messages);
}

/* -------------------------------------------------------------------------
 * Pure helpers (no DB, no globals) — unit-tested
 * ----------------------------------------------------------------------- */

/**
 * Normalize a free-form direction string to the stored 'in'/'out' enum.
 *
 * Inbound (customer → store) maps to 'in'; internal agent notes map to 'note';
 * everything else (the store's outbound sends) maps to 'out'. The catch-all
 * 'out' default keeps a typo from being recorded as a customer message and
 * inflating the unread badge.
 *
 * @param string $direction Raw direction (in/inbound/incoming, note, or out/...).
 * @return string 'in' | 'out' | 'note'.
 */
function zignites_chat_inbox_normalize_direction($direction) {
    $direction = strtolower(trim((string) $direction));
    if (in_array($direction, ['in', 'inbound', 'incoming', 'received'], true)) {
        return 'in';
    }
    if ($direction === 'note') {
        return 'note';
    }
    return 'out';
}

/**
 * Build a single-line excerpt of a message body for the thread list.
 *
 * Strips tags, collapses whitespace, trims, and truncates to $length with a
 * trailing ellipsis. Kept under the conversations.last_excerpt column width
 * (VARCHAR(190)) by default.
 *
 * @param string $body   Raw message body.
 * @param int    $length Max characters before truncation.
 * @return string Clean, single-line excerpt.
 */
function zignites_chat_inbox_make_excerpt($body, $length = 160) {
    $text = trim(wp_strip_all_tags((string) $body));
    $text = preg_replace('/\s+/u', ' ', $text);
    if ($text === null) {
        $text = '';
    }
    $length = max(1, (int) $length);
    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
        if (mb_strlen($text) > $length) {
            $text = rtrim(mb_substr($text, 0, $length - 1)) . '…';
        }
    } elseif (strlen($text) > $length) {
        $text = rtrim(substr($text, 0, $length - 1)) . '…';
    }
    return $text;
}

/**
 * Whether the WhatsApp 24h customer-service window is still open.
 *
 * Free-form (non-template) business messages are only deliverable within 24h
 * of the customer's last inbound message. The Inbox uses this to enable or
 * disable the reply box (resolved decision: banner + disabled reply).
 *
 * @param string|null $last_inbound_at Mysql datetime of the last inbound
 *                                     message, or empty/null if none.
 * @param int|null    $now             Unix timestamp to compare against
 *                                     (defaults to current_time('timestamp')).
 * @return bool True when a free-form reply is still allowed.
 */
function zignites_chat_inbox_window_is_open($last_inbound_at, $now = null) {
    if (empty($last_inbound_at)) {
        return false;
    }
    $last = strtotime((string) $last_inbound_at);
    if ($last === false) {
        return false;
    }
    if ($now === null) {
        $now = current_time('timestamp');
    }
    return ((int) $now - $last) < DAY_IN_SECONDS;
}

/**
 * Shape a validated, insert-ready message row for $wpdb->insert.
 *
 * Pure: takes raw args + a resolved conversation id and timestamp, returns
 * the data array and its column-format list. The DB wrapper
 * (zignites_chat_inbox_insert_message) calls this so the normalization is
 * testable without a database.
 *
 * @param array  $args            Raw message args (phone, direction, body,
 *                                provider, message_id, status).
 * @param int    $conversation_id Resolved thread id.
 * @param string $now             Mysql datetime for created_at.
 * @return array{data: array<string,mixed>, format: array<int,string>}
 */
function zignites_chat_inbox_build_message_row($args, $conversation_id, $now) {
    $data = [
        'conversation_id' => (int) $conversation_id,
        'phone'           => isset($args['phone']) ? zignites_chat_normalize_phone($args['phone']) : '',
        'direction'       => zignites_chat_inbox_normalize_direction($args['direction'] ?? ''),
        'body'            => isset($args['body']) ? zignites_chat_sanitize_textarea($args['body']) : '',
        'provider'        => isset($args['provider']) ? sanitize_text_field($args['provider']) : '',
        'message_id'      => isset($args['message_id']) ? sanitize_text_field($args['message_id']) : '',
        'status'          => isset($args['status']) ? sanitize_text_field($args['status']) : '',
        'created_at'      => $now,
    ];
    $format = ['%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s'];
    return ['data' => $data, 'format' => $format];
}

/**
 * Compute the conversation-row updates produced by recording a message.
 *
 * Pure: given the existing thread row (or null for a brand-new thread) and a
 * normalized message, returns the field=>value map to write back to the
 * conversations table. Inbound messages bump unread_count and last_inbound_at;
 * a customer name is only filled in when the thread does not already have one.
 *
 * @param array|null $existing Existing thread row (assoc) or null.
 * @param array      $message  Normalized message (direction, body, phone,
 *                            optional customer_name).
 * @param string     $now      Mysql datetime for the message.
 * @return array<string,mixed> Columns to update/insert on the thread.
 */
function zignites_chat_inbox_build_thread_update($existing, $message, $now) {
    $direction = zignites_chat_inbox_normalize_direction($message['direction'] ?? '');
    $is_inbound = ($direction === 'in');

    $current_unread = isset($existing['unread_count']) ? (int) $existing['unread_count'] : 0;
    $update = [
        'last_message_at' => $now,
        'last_excerpt'    => zignites_chat_inbox_make_excerpt($message['body'] ?? ''),
        'last_direction'  => $direction,
        'unread_count'    => $is_inbound ? $current_unread + 1 : $current_unread,
        'updated_at'      => $now,
    ];
    if ($is_inbound) {
        $update['last_inbound_at'] = $now;
    }

    $incoming_name = isset($message['customer_name']) ? sanitize_text_field($message['customer_name']) : '';
    $existing_name = isset($existing['customer_name']) ? (string) $existing['customer_name'] : '';
    if ($incoming_name !== '' && $existing_name === '') {
        $update['customer_name'] = $incoming_name;
    }

    return $update;
}

/* -------------------------------------------------------------------------
 * Storage (DB wrappers)
 * ----------------------------------------------------------------------- */

/**
 * Fetch a conversation thread by phone number.
 *
 * @param string $phone Phone (any format; normalized internally).
 * @return array|null Assoc thread row, or null when not found.
 */
function zignites_chat_inbox_get_thread_by_phone($phone) {
    global $wpdb;
    $phone = zignites_chat_normalize_phone($phone);
    if ($phone === '') {
        return null;
    }
    $table = zignites_chat_conversations_table_name();
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $row = $wpdb->get_row(
        $wpdb->prepare("SELECT * FROM {$table} WHERE phone = %s", $phone), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        ARRAY_A
    );
    return $row ?: null;
}

/**
 * Fetch a conversation thread by id.
 *
 * @param int $conversation_id Thread id.
 * @return array|null Assoc thread row, or null when not found.
 */
function zignites_chat_inbox_get_thread($conversation_id) {
    global $wpdb;
    $conversation_id = (int) $conversation_id;
    if ($conversation_id <= 0) {
        return null;
    }
    $table = zignites_chat_conversations_table_name();
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $row = $wpdb->get_row(
        $wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $conversation_id), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        ARRAY_A
    );
    return $row ?: null;
}

/**
 * List conversation threads, unread first then most-recently active.
 *
 * @param array $args {
 *   @type int      $limit    Max rows (1–200). Default 50.
 *   @type int      $offset   Pagination offset. Default 0.
 *   @type string   $search   Optional phone/name LIKE filter.
 *   @type int|null $agent_id Optional assignee filter: null = no filter,
 *                           0 = unassigned only, >0 = that agent only.
 * }
 * @return array<int, array> Thread rows (ARRAY_A).
 */
function zignites_chat_inbox_get_threads($args = []) {
    global $wpdb;
    $limit  = isset($args['limit']) ? max(1, min(200, (int) $args['limit'])) : 50;
    $offset = isset($args['offset']) ? max(0, (int) $args['offset']) : 0;
    $search = isset($args['search']) ? trim((string) $args['search']) : '';
    $agent  = array_key_exists('agent_id', $args) && $args['agent_id'] !== null ? (int) $args['agent_id'] : null;
    $table  = zignites_chat_conversations_table_name();

    $where  = [];
    $params = [];
    if ($search !== '') {
        $like = '%' . $wpdb->esc_like($search) . '%';
        $where[] = '(phone LIKE %s OR customer_name LIKE %s)';
        $params[] = $like;
        $params[] = $like;
    }
    if ($agent !== null) {
        $where[] = 'agent_id = %d';
        $params[] = max(0, $agent);
    }

    $where_sql = $where ? ' WHERE ' . implode(' AND ', $where) : '';
    $params[]  = $limit;
    $params[]  = $offset;

    $sql = "SELECT * FROM {$table}{$where_sql} ORDER BY unread_count DESC, last_message_at DESC LIMIT %d OFFSET %d";
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
    $rows = $wpdb->get_results($wpdb->prepare($sql, $params), ARRAY_A);
    return is_array($rows) ? $rows : [];
}

/**
 * Assign (or clear) a thread's agent.
 *
 * @param int $conversation_id Thread id.
 * @param int $agent_id        WP user id, or 0 to unassign.
 * @return bool
 */
function zignites_chat_inbox_assign_thread($conversation_id, $agent_id) {
    global $wpdb;
    $conversation_id = (int) $conversation_id;
    if ($conversation_id <= 0) {
        return false;
    }
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $updated = $wpdb->update(
        zignites_chat_conversations_table_name(),
        ['agent_id' => max(0, (int) $agent_id)],
        ['id' => $conversation_id],
        ['%d'],
        ['%d']
    );
    return $updated !== false;
}

/**
 * Total number of conversation threads (for pagination / the empty state).
 *
 * @return int
 */
function zignites_chat_inbox_count_threads() {
    global $wpdb;
    $table = zignites_chat_conversations_table_name();
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
}

/**
 * Sum of unread counts across all threads (menu badge / inbox heading).
 *
 * @return int
 */
function zignites_chat_inbox_total_unread() {
    global $wpdb;
    $table = zignites_chat_conversations_table_name();
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    return (int) $wpdb->get_var("SELECT COALESCE(SUM(unread_count), 0) FROM {$table}");
}

/**
 * Fetch messages for a thread in chronological order.
 *
 * With $after_id > 0 returns only newer messages (the polling path). Otherwise
 * returns the most recent $limit messages, still oldest-first for display.
 *
 * @param int   $conversation_id Thread id.
 * @param array $args {
 *   @type int $limit    Max rows (1–500). Default 200.
 *   @type int $after_id Return only messages with id greater than this. Default 0.
 * }
 * @return array<int, array> Message rows (ARRAY_A), ascending by id.
 */
function zignites_chat_inbox_get_messages($conversation_id, $args = []) {
    global $wpdb;
    $conversation_id = (int) $conversation_id;
    if ($conversation_id <= 0) {
        return [];
    }
    $limit    = isset($args['limit']) ? max(1, min(500, (int) $args['limit'])) : 200;
    $after_id = isset($args['after_id']) ? max(0, (int) $args['after_id']) : 0;
    $table    = zignites_chat_messages_table_name();

    if ($after_id > 0) {
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table} WHERE conversation_id = %d AND id > %d ORDER BY id ASC LIMIT %d",
            $conversation_id, $after_id, $limit
        ), ARRAY_A);
        return is_array($rows) ? $rows : [];
    }

    // Most recent $limit, re-sorted ascending so the thread reads top-to-bottom.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    $rows = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM (SELECT * FROM {$table} WHERE conversation_id = %d ORDER BY id DESC LIMIT %d) sub ORDER BY id ASC",
        $conversation_id, $limit
    ), ARRAY_A);
    return is_array($rows) ? $rows : [];
}

/**
 * Clear a thread's unread count (agent opened/read it).
 *
 * @param int $conversation_id Thread id.
 * @return void
 */
function zignites_chat_inbox_mark_read($conversation_id) {
    global $wpdb;
    $conversation_id = (int) $conversation_id;
    if ($conversation_id <= 0) {
        return;
    }
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $wpdb->update(
        zignites_chat_conversations_table_name(),
        ['unread_count' => 0],
        ['id' => $conversation_id],
        ['%d'],
        ['%d']
    );
}

/**
 * Add an internal agent note to a thread.
 *
 * Stored as a message row with direction 'note' and the author's user id. Notes
 * interleave with messages chronologically but never touch the conversation's
 * customer-facing aggregates (last_excerpt / unread / last_message_at) and are
 * never sent to the customer.
 *
 * @param int    $conversation_id Thread id.
 * @param string $body            Note text.
 * @param int    $author_id       WP user id of the author.
 * @return int Inserted row id, or 0 on failure.
 */
function zignites_chat_inbox_add_note($conversation_id, $body, $author_id) {
    global $wpdb;
    $conversation_id = (int) $conversation_id;
    $body = zignites_chat_sanitize_textarea($body);
    if ($conversation_id <= 0 || $body === '') {
        return 0;
    }
    $thread = zignites_chat_inbox_get_thread($conversation_id);
    if ($thread === null) {
        return 0;
    }
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $wpdb->insert(
        zignites_chat_messages_table_name(),
        [
            'conversation_id' => $conversation_id,
            'phone'           => (string) $thread['phone'],
            'direction'       => 'note',
            'body'            => $body,
            'author_id'       => max(0, (int) $author_id),
            'created_at'      => current_time('mysql'),
        ],
        ['%d', '%s', '%s', '%s', '%d', '%s']
    );
    return (int) $wpdb->insert_id;
}

/**
 * Shape a conversation DB row into the array the admin/AJAX layer returns.
 *
 * Pure (no DB) so it can be unit-tested. Casts types and only exposes the
 * fields the Inbox UI needs.
 *
 * @param array $row Conversation row (ARRAY_A).
 * @return array Presented thread.
 */
function zignites_chat_inbox_present_thread($row) {
    if (!is_array($row)) {
        return [];
    }
    return [
        'id'              => isset($row['id']) ? (int) $row['id'] : 0,
        'phone'           => isset($row['phone']) ? (string) $row['phone'] : '',
        'name'            => isset($row['customer_name']) ? (string) $row['customer_name'] : '',
        'excerpt'         => isset($row['last_excerpt']) ? (string) $row['last_excerpt'] : '',
        'last_direction'  => isset($row['last_direction']) ? (string) $row['last_direction'] : '',
        'unread'          => isset($row['unread_count']) ? (int) $row['unread_count'] : 0,
        'last_message_at' => isset($row['last_message_at']) ? (string) $row['last_message_at'] : '',
        'last_inbound_at' => isset($row['last_inbound_at']) ? (string) $row['last_inbound_at'] : '',
        'agent_id'        => isset($row['agent_id']) ? (int) $row['agent_id'] : 0,
    ];
}

/**
 * Shape a message DB row into the array the admin/AJAX layer returns.
 *
 * Pure (no DB) so it can be unit-tested.
 *
 * @param array $row Message row (ARRAY_A).
 * @return array Presented message.
 */
function zignites_chat_inbox_present_message($row) {
    if (!is_array($row)) {
        return [];
    }
    return [
        'id'         => isset($row['id']) ? (int) $row['id'] : 0,
        'direction'  => isset($row['direction']) ? zignites_chat_inbox_normalize_direction($row['direction']) : 'out',
        'body'       => isset($row['body']) ? (string) $row['body'] : '',
        'status'     => isset($row['status']) ? (string) $row['status'] : '',
        'created_at' => isset($row['created_at']) ? (string) $row['created_at'] : '',
        'author_id'  => isset($row['author_id']) ? (int) $row['author_id'] : 0,
    ];
}

/**
 * Whether an inbound message with this provider message id is already stored.
 *
 * Providers re-deliver webhooks until they get a 200, so inbound capture
 * dedupes on the provider message id before inserting. Empty ids are treated
 * as "not seen" (nothing to match on) — callers should still record them.
 *
 * @param string $message_id Provider message id (Twilio SID / Meta wamid).
 * @return bool True when a matching inbound row already exists.
 */
function zignites_chat_inbox_inbound_exists($message_id) {
    global $wpdb;
    $message_id = (string) $message_id;
    if ($message_id === '') {
        return false;
    }
    $table = zignites_chat_messages_table_name();
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $found = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT id FROM {$table} WHERE message_id = %s AND direction = 'in' LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $message_id
        )
    );
    return $found !== null;
}

/**
 * Record a message and upsert its conversation thread in one call.
 *
 * Ensures a thread exists for the phone, inserts the message row, then writes
 * the derived thread aggregates (last_message_at, excerpt, unread_count, …)
 * via the pure builders above. This is the single entry point inbound capture
 * (I2) and agent replies (I4) call.
 *
 * @param array $args {
 *   @type string $phone         Required. Customer phone (normalized internally).
 *   @type string $direction     'in' or 'out'. Defaults to 'out'.
 *   @type string $body          Message text.
 *   @type string $provider      'twilio' | 'cloud' | ''.
 *   @type string $message_id    Provider message id.
 *   @type string $status        Initial status (e.g. 'received', 'sent').
 *   @type string $customer_name Optional display name (only set if thread has none).
 * }
 * @return array{conversation_id:int, message_id:int}|WP_Error
 */
function zignites_chat_inbox_record_message($args) {
    global $wpdb;

    $phone = isset($args['phone']) ? zignites_chat_normalize_phone($args['phone']) : '';
    if ($phone === '') {
        return new WP_Error('zignites_chat_inbox_phone_required', __('A phone number is required to record a message.', 'zignites-chat'));
    }

    $now = current_time('mysql');
    $conversations = zignites_chat_conversations_table_name();
    $messages      = zignites_chat_messages_table_name();

    // Ensure the thread exists so we have an id to attach the message to.
    $thread = zignites_chat_inbox_get_thread_by_phone($phone);
    if ($thread === null) {
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->insert(
            $conversations,
            [
                'phone'      => $phone,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            ['%s', '%s', '%s']
        );
        $conversation_id = (int) $wpdb->insert_id;
        $thread = null;
    } else {
        $conversation_id = (int) $thread['id'];
    }

    if ($conversation_id <= 0) {
        return new WP_Error('zignites_chat_inbox_thread_failed', __('Could not create the conversation thread.', 'zignites-chat'));
    }

    // Insert the message row.
    $row = zignites_chat_inbox_build_message_row($args, $conversation_id, $now);
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $wpdb->insert($messages, $row['data'], $row['format']);
    $message_id = (int) $wpdb->insert_id;

    // Update the derived thread aggregates.
    $update = zignites_chat_inbox_build_thread_update($thread, array_merge($args, ['phone' => $phone]), $now);
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $wpdb->update($conversations, $update, ['id' => $conversation_id]);

    return [
        'conversation_id' => $conversation_id,
        'message_id'      => $message_id,
    ];
}
