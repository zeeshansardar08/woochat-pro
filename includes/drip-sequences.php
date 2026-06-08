<?php
/**
 * Drip & automation sequences (Pro) — roadmap T3.1, increment 1 (engine
 * foundation).
 *
 * A sequence is a named, rule-based automation: a trigger (the entry event —
 * order completed, opt-in, win-back, browse-abandon) plus an ordered list of
 * steps, each a delay + a WhatsApp message. When the trigger fires for a phone
 * the customer is *enrolled*; a background processor then walks the steps,
 * sending each after its delay elapses.
 *
 * Increments so far:
 *   - S1 (engine foundation): the enrollments table (idempotent dbDelta) +
 *     migration v9, sequence-definition storage (option `zignites_chat_sequences`)
 *     with a sanitizer + normalizing getters, and pure unit-tested helpers for
 *     delay maths, step lookup, scheduling and message rendering.
 *   - S2 (enrollment + triggers): a `context` column (migration v10) holding
 *     the placeholder values captured when the trigger fires, idempotent
 *     `zignites_chat_seq_enroll()`, and the order-completed / opt-in entry
 *     points that enroll a phone into every active sequence for that trigger.
 *   - S3 (cron processor + sender): a recurring 5-minute event that sends each
 *     active enrollment's due step through the dispatcher — applying the
 *     marketing gates (quiet hours skip the run, opt-out/consent cancel the
 *     enrollment, the shared rate limiter stops the run) — then advances the
 *     step or completes the enrollment.
 *   - S4 (admin UI): the "Sequences" settings page (CRUD over the option) +
 *     per-sequence enrollment counts.
 *   - S5 (win-back scan): a daily scan that enrolls customers who have gone
 *     quiet (most recent order N days back) into win_back sequences.
 *
 * Browse-abandon scanning is the remaining follow-up (S6).
 *
 * @package Zignites_Chat
 */

if (!defined('ABSPATH')) exit;

/* -------------------------------------------------------------------------
 * Schema
 * ----------------------------------------------------------------------- */

/**
 * Enrollments table — one row per (sequence, phone) currently or previously
 * walking a sequence.
 *
 * @return string Fully-prefixed table name.
 */
function zignites_chat_seq_enrollments_table_name() {
    global $wpdb;
    return $wpdb->prefix . 'zignites_chat_sequence_enrollments';
}

/**
 * Create the sequence enrollments table. dbDelta-based and idempotent: called
 * from migration v9 and from the activation hook.
 *
 * `current_step` is the index of the NEXT step to send; `next_run_at` is when
 * that step is due (indexed with `status` so the processor can pull due rows
 * cheaply). The UNIQUE (sequence_id, phone) key makes enrollment idempotent.
 */
function zignites_chat_create_sequence_enrollments_table() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    $table           = zignites_chat_seq_enrollments_table_name();

    $sql = "CREATE TABLE {$table} (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        sequence_id VARCHAR(64) NOT NULL,
        phone VARCHAR(40) NOT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'active',
        current_step INT UNSIGNED NOT NULL DEFAULT 0,
        next_run_at DATETIME NULL,
        enrolled_at DATETIME NOT NULL,
        last_step_at DATETIME NULL,
        context LONGTEXT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY sequence_phone (sequence_id, phone),
        KEY status_next (status, next_run_at)
    ) {$charset_collate};";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}

/* -------------------------------------------------------------------------
 * Trigger catalogue
 * ----------------------------------------------------------------------- */

/**
 * The entry events a sequence can be triggered by, with the {placeholders}
 * each one resolves in its step messages. Mirrors the wa-templates type map.
 *
 * @return array<string, array{label:string, placeholders:string[]}>
 */
function zignites_chat_seq_triggers() {
    $triggers = array(
        'order_completed' => array(
            'label'        => __('Order completed', 'zignites-chat'),
            'placeholders' => array('{name}', '{order_id}', '{total}', '{currency_symbol}', '{site}'),
        ),
        'optin'           => array(
            'label'        => __('Customer opted in', 'zignites-chat'),
            'placeholders' => array('{name}', '{site}'),
        ),
        'win_back'        => array(
            'label'        => __('Win-back (inactive customer)', 'zignites-chat'),
            'placeholders' => array('{name}', '{site}', '{days_inactive}'),
        ),
        'browse_abandon'  => array(
            'label'        => __('Browse abandonment', 'zignites-chat'),
            'placeholders' => array('{name}', '{site}', '{product}', '{product_url}'),
        ),
    );

    /**
     * Filter the available sequence triggers.
     *
     * @param array $triggers Trigger key => label + placeholders.
     */
    return (array) apply_filters('zignites_chat_seq_triggers', $triggers);
}

/* -------------------------------------------------------------------------
 * Pure helpers (no DB, no globals) — unit-tested
 * ----------------------------------------------------------------------- */

/**
 * Convert a step delay into seconds. Pure.
 *
 * @param int    $value Non-negative magnitude (negatives clamp to 0).
 * @param string $unit  'minutes' | 'hours' | 'days' (unknown → minutes).
 * @return int Seconds.
 */
function zignites_chat_seq_delay_to_seconds($value, $unit) {
    $value = (int) $value;
    if ($value < 0) {
        $value = 0;
    }
    switch ((string) $unit) {
        case 'days':
            return $value * DAY_IN_SECONDS;
        case 'hours':
            return $value * HOUR_IN_SECONDS;
        case 'minutes':
        default:
            return $value * MINUTE_IN_SECONDS;
    }
}

/**
 * Number of steps in a sequence. Pure.
 *
 * @param array $sequence
 * @return int
 */
function zignites_chat_seq_step_count($sequence) {
    if (!is_array($sequence) || !isset($sequence['steps']) || !is_array($sequence['steps'])) {
        return 0;
    }
    return count($sequence['steps']);
}

/**
 * Fetch a single step by index. Pure.
 *
 * @param array $sequence
 * @param int   $index
 * @return array|null Step array, or null when out of range.
 */
function zignites_chat_seq_get_step($sequence, $index) {
    $index = (int) $index;
    if (!is_array($sequence) || !isset($sequence['steps'][$index]) || !is_array($sequence['steps'][$index])) {
        return null;
    }
    return $sequence['steps'][$index];
}

/**
 * Compute when a given step should fire, relative to a base timestamp. Pure.
 *
 * The base is the enrollment time for step 0 and the previous step's send time
 * thereafter — the caller passes whichever applies, so delays accumulate step
 * over step.
 *
 * @param array $sequence
 * @param int   $index   Step index to schedule.
 * @param int   $from_ts Base Unix timestamp.
 * @return int Unix timestamp the step is due, or 0 when the step doesn't exist.
 */
function zignites_chat_seq_next_run_at($sequence, $index, $from_ts) {
    $step = zignites_chat_seq_get_step($sequence, $index);
    if ($step === null) {
        return 0;
    }
    $delay = zignites_chat_seq_delay_to_seconds(
        $step['delay_value'] ?? 0,
        $step['delay_unit'] ?? 'minutes'
    );
    return (int) $from_ts + $delay;
}

/**
 * Render a step message by substituting placeholders. Pure.
 *
 * @param string $template
 * @param array  $values Map of '{placeholder}' => replacement.
 * @return string
 */
function zignites_chat_seq_render_message($template, $values) {
    if (!is_array($values)) {
        return (string) $template;
    }
    return str_replace(array_keys($values), array_values($values), (string) $template);
}

/**
 * Sanitize a single step. Pure. Returns null for an unusable step (no message).
 *
 * @param mixed $raw
 * @return array{delay_value:int, delay_unit:string, message:string}|null
 */
function zignites_chat_seq_sanitize_step($raw) {
    if (!is_array($raw)) {
        return null;
    }
    $message = isset($raw['message']) ? trim(zignites_chat_sanitize_textarea($raw['message'])) : '';
    if ($message === '') {
        return null;
    }
    $value = isset($raw['delay_value']) ? (int) $raw['delay_value'] : 0;
    if ($value < 0) {
        $value = 0;
    }
    $unit = isset($raw['delay_unit']) && in_array($raw['delay_unit'], array('minutes', 'hours', 'days'), true)
        ? $raw['delay_unit']
        : 'minutes';

    return array(
        'delay_value' => $value,
        'delay_unit'  => $unit,
        'message'     => $message,
    );
}

/**
 * Sanitize the whole sequences definition option. Pure (no DB).
 *
 * Drops sequences without an id, a valid trigger, or any usable step; coerces
 * enabled to yes/no; de-duplicates ids (last wins); caps steps per sequence.
 *
 * @param mixed $raw
 * @return array<int, array{id:string, name:string, enabled:string, trigger:string, steps:array}>
 */
function zignites_chat_seq_sanitize_sequences($raw) {
    if (!is_array($raw)) {
        return array();
    }

    $valid_triggers = array_keys(zignites_chat_seq_triggers());
    $max_steps      = (int) apply_filters('zignites_chat_seq_max_steps', 20);
    $by_id          = array();

    foreach ($raw as $entry) {
        if (!is_array($entry)) {
            continue;
        }
        $id = isset($entry['id']) ? sanitize_key((string) $entry['id']) : '';
        if ($id === '') {
            continue;
        }
        $trigger = isset($entry['trigger']) ? (string) $entry['trigger'] : '';
        if (!in_array($trigger, $valid_triggers, true)) {
            continue;
        }

        $steps = array();
        if (isset($entry['steps']) && is_array($entry['steps'])) {
            foreach ($entry['steps'] as $raw_step) {
                $step = zignites_chat_seq_sanitize_step($raw_step);
                if ($step !== null) {
                    $steps[] = $step;
                }
                if (count($steps) >= $max_steps) {
                    break;
                }
            }
        }
        if (empty($steps)) {
            continue;
        }

        $by_id[$id] = array(
            'id'      => $id,
            'name'    => isset($entry['name']) ? sanitize_text_field((string) $entry['name']) : $id,
            'enabled' => (isset($entry['enabled']) && $entry['enabled'] === 'yes') ? 'yes' : 'no',
            'trigger' => $trigger,
            'steps'   => $steps,
        );
    }

    return array_values($by_id);
}

/* -------------------------------------------------------------------------
 * Sequence-definition storage
 * ----------------------------------------------------------------------- */

/**
 * Read all configured sequences, normalized through the sanitizer so callers
 * always get a clean, predictable shape.
 *
 * @return array<int, array{id:string, name:string, enabled:string, trigger:string, steps:array}>
 */
function zignites_chat_seq_get_sequences() {
    return zignites_chat_seq_sanitize_sequences(get_option('zignites_chat_sequences', array()));
}

/**
 * Find a single sequence by id.
 *
 * @param string $id
 * @return array|null
 */
function zignites_chat_seq_find($id) {
    $id = sanitize_key((string) $id);
    foreach (zignites_chat_seq_get_sequences() as $sequence) {
        if ($sequence['id'] === $id) {
            return $sequence;
        }
    }
    return null;
}

/**
 * Active sequences for a given trigger (enabled + at least one step).
 *
 * @param string $trigger
 * @return array<int, array>
 */
function zignites_chat_seq_active_for_trigger($trigger) {
    $trigger = (string) $trigger;
    $out     = array();
    foreach (zignites_chat_seq_get_sequences() as $sequence) {
        if ($sequence['enabled'] === 'yes' && $sequence['trigger'] === $trigger && !empty($sequence['steps'])) {
            $out[] = $sequence;
        }
    }
    return $out;
}

/* -------------------------------------------------------------------------
 * Enrollment
 * ----------------------------------------------------------------------- */

/**
 * Format a Unix timestamp as a MySQL datetime string. Pure.
 *
 * Used for the table's DATETIME columns. The caller passes a WP-local
 * timestamp (current_time('timestamp')) so the stored value is comparable to
 * current_time('mysql') without a timezone round-trip.
 *
 * @param int $ts
 * @return string
 */
function zignites_chat_seq_format_mysql($ts) {
    return gmdate('Y-m-d H:i:s', (int) $ts);
}

/**
 * Build the row to insert for a new enrollment. Pure (no DB).
 *
 * Step 0's delay is measured from the enrollment time, so the first send is
 * scheduled at enroll + step[0] delay. A step-0 delay of 0 schedules it for
 * the next processor tick.
 *
 * @param array  $sequence Sanitized sequence (must have id + steps).
 * @param string $phone    Normalized phone.
 * @param int    $now_ts   Enrollment timestamp (WP-local epoch).
 * @param array  $context  Placeholder => value map captured at trigger time.
 * @return array{sequence_id:string, phone:string, status:string, current_step:int, next_run_at:string, enrolled_at:string, last_step_at:null, context:string}
 */
function zignites_chat_seq_build_enrollment_row($sequence, $phone, $now_ts, $context = array()) {
    $now_mysql = zignites_chat_seq_format_mysql($now_ts);
    $run_ts    = zignites_chat_seq_next_run_at($sequence, 0, $now_ts);

    return array(
        'sequence_id'  => (string) ($sequence['id'] ?? ''),
        'phone'        => (string) $phone,
        'status'       => 'active',
        'current_step' => 0,
        'next_run_at'  => $run_ts ? zignites_chat_seq_format_mysql($run_ts) : $now_mysql,
        'enrolled_at'  => $now_mysql,
        'last_step_at' => null,
        'context'      => wp_json_encode(is_array($context) ? $context : array()),
    );
}

/**
 * Whether a phone is already enrolled in a sequence (any status). Used to keep
 * enrollment idempotent so a repeat trigger doesn't restart the sequence.
 *
 * @param string $sequence_id
 * @param string $phone Normalized phone.
 * @return bool
 */
function zignites_chat_seq_enrollment_exists($sequence_id, $phone) {
    global $wpdb;
    $table = zignites_chat_seq_enrollments_table_name();
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    return (bool) $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$table} WHERE sequence_id = %s AND phone = %s",
        (string) $sequence_id,
        (string) $phone
    ));
}

/**
 * Enroll a phone into a sequence. Idempotent: a number already enrolled in the
 * same sequence is skipped. Gating (opt-out / consent / quiet hours / rate
 * limit) is applied later at send time, not here.
 *
 * @param array|string $sequence Sanitized sequence array or its id.
 * @param string       $phone    Phone in any format.
 * @param array        $context  Placeholder => value map for later rendering.
 * @return bool True when a new enrollment was created.
 */
function zignites_chat_seq_enroll($sequence, $phone, $context = array()) {
    if (is_string($sequence)) {
        $sequence = zignites_chat_seq_find($sequence);
    }
    if (!is_array($sequence) || empty($sequence['id']) || empty($sequence['steps'])) {
        return false;
    }
    if (function_exists('zignites_chat_is_pro_active') && !zignites_chat_is_pro_active()) {
        return false;
    }

    $phone = zignites_chat_normalize_phone($phone);
    if ($phone === '') {
        return false;
    }
    if (zignites_chat_seq_enrollment_exists($sequence['id'], $phone)) {
        return false;
    }

    $row = zignites_chat_seq_build_enrollment_row($sequence, $phone, current_time('timestamp'), $context);

    global $wpdb;
    $table = zignites_chat_seq_enrollments_table_name();
    // last_step_at is left to its NULL column default.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $wpdb->insert(
        $table,
        array(
            'sequence_id'  => $row['sequence_id'],
            'phone'        => $row['phone'],
            'status'       => $row['status'],
            'current_step' => $row['current_step'],
            'next_run_at'  => $row['next_run_at'],
            'enrolled_at'  => $row['enrolled_at'],
            'context'      => $row['context'],
        ),
        array('%s', '%s', '%s', '%d', '%s', '%s', '%s')
    );

    return (bool) $wpdb->insert_id;
}

/**
 * Enroll a phone into every active sequence for a trigger, sharing one context.
 *
 * @param string $trigger
 * @param string $phone
 * @param array  $context
 * @return void
 */
function zignites_chat_seq_enroll_all_for_trigger($trigger, $phone, $context = array()) {
    if (function_exists('zignites_chat_is_pro_active') && !zignites_chat_is_pro_active()) {
        return;
    }
    foreach (zignites_chat_seq_active_for_trigger($trigger) as $sequence) {
        zignites_chat_seq_enroll($sequence, $phone, $context);
    }
}

/* -------------------------------------------------------------------------
 * Triggers
 * ----------------------------------------------------------------------- */

add_action('woocommerce_order_status_completed', 'zignites_chat_seq_enroll_on_order_completed', 20, 1);
add_action('zignites_chat_customer_opted_in', 'zignites_chat_seq_enroll_on_optin', 10, 2);

/**
 * order_completed trigger — enroll the order's billing phone into every active
 * post-purchase sequence, capturing order placeholders for later rendering.
 *
 * @param int $order_id
 * @return void
 */
function zignites_chat_seq_enroll_on_order_completed($order_id) {
    $sequences = zignites_chat_seq_active_for_trigger('order_completed');
    if (empty($sequences)) {
        return;
    }
    $order = function_exists('wc_get_order') ? wc_get_order($order_id) : null;
    if (!$order) {
        return;
    }
    $phone = $order->get_billing_phone();
    if (!$phone) {
        return;
    }

    $context = array(
        '{name}'            => $order->get_billing_first_name(),
        '{order_id}'        => $order->get_id(),
        '{total}'           => $order->get_total(),
        '{currency_symbol}' => function_exists('zignites_chat_currency_symbol_text') ? zignites_chat_currency_symbol_text() : '',
        '{site}'            => function_exists('get_bloginfo') ? get_bloginfo('name') : '',
    );

    foreach ($sequences as $sequence) {
        zignites_chat_seq_enroll($sequence, $phone, $context);
    }
}

/**
 * optin trigger — enroll a freshly-consented phone into every active welcome
 * sequence. Fired by zignites_chat_record_optin().
 *
 * @param string $phone
 * @param string $source
 * @return void
 */
function zignites_chat_seq_enroll_on_optin($phone, $source = '') {
    zignites_chat_seq_enroll_all_for_trigger('optin', $phone, array(
        '{name}' => '',
        '{site}' => function_exists('get_bloginfo') ? get_bloginfo('name') : '',
    ));
}

/* -------------------------------------------------------------------------
 * Cron processor + sender (S3)
 * ----------------------------------------------------------------------- */

// Reuse the 5-minute schedule registered by cart-recovery.php; re-declare the
// guarded filter so the processor doesn't depend on module load order.
add_filter('cron_schedules', function ($schedules) {
    if (!isset($schedules['zignites_chat_five_minutes'])) {
        $schedules['zignites_chat_five_minutes'] = array(
            'interval' => 5 * MINUTE_IN_SECONDS,
            'display'  => __('Every 5 minutes (Zignites Chat)', 'zignites-chat'),
        );
    }
    return $schedules;
});

add_action('init', 'zignites_chat_seq_schedule_cron');
add_action('zignites_chat_process_sequences', 'zignites_chat_seq_process_enrollments');

/**
 * Ensure the recurring sequence processor event is scheduled.
 */
function zignites_chat_seq_schedule_cron() {
    if (!wp_next_scheduled('zignites_chat_process_sequences')) {
        wp_schedule_event(time() + 60, 'zignites_chat_five_minutes', 'zignites_chat_process_sequences');
    }
}

/**
 * Clear the processor event (deactivation / uninstall).
 */
function zignites_chat_seq_unschedule_cron() {
    $timestamp = wp_next_scheduled('zignites_chat_process_sequences');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'zignites_chat_process_sequences');
    }
}

/**
 * Compute the enrollment's state after sending its current step. Pure.
 *
 * The next step's delay is measured from $now_ts (this send time), so delays
 * accumulate step over step. When there is no next step the enrollment is
 * completed (next_run_at cleared).
 *
 * @param array  $sequence     Sanitized sequence.
 * @param int    $current_step Index of the step just sent.
 * @param int    $now_ts       Send timestamp (WP-local epoch).
 * @return array{status:string, current_step:int, next_run_at:?string, last_step_at:string}
 */
function zignites_chat_seq_plan_advance($sequence, $current_step, $now_ts) {
    $next   = (int) $current_step + 1;
    $run_ts = zignites_chat_seq_next_run_at($sequence, $next, $now_ts);
    $last   = zignites_chat_seq_format_mysql($now_ts);

    if ($run_ts) {
        return array(
            'status'       => 'active',
            'current_step' => $next,
            'next_run_at'  => zignites_chat_seq_format_mysql($run_ts),
            'last_step_at' => $last,
        );
    }
    return array(
        'status'       => 'completed',
        'current_step' => $next,
        'next_run_at'  => null,
        'last_step_at' => $last,
    );
}

/**
 * Cron callback: send the due step for each active enrollment, then advance it.
 *
 * Chunked like the other bulk senders. Quiet hours skip the whole run (picked
 * up next tick); the shared rate limiter stops the run mid-way (remaining rows
 * stay due). Opt-out / missing-consent and removed/disabled sequences cancel
 * the enrollment permanently rather than retrying.
 *
 * @return void
 */
function zignites_chat_seq_process_enrollments() {
    if (function_exists('zignites_chat_is_pro_active') && !zignites_chat_is_pro_active()) {
        return;
    }
    // Quiet hours: defer the entire run to a later tick (drip is marketing).
    if (function_exists('zignites_chat_quiet_hours_active') && zignites_chat_quiet_hours_active()) {
        return;
    }

    global $wpdb;
    $table = zignites_chat_seq_enrollments_table_name();
    $now   = current_time('mysql');
    $chunk = max(1, min(100, (int) apply_filters('zignites_chat_seq_chunk_size', 30)));

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    $rows = $wpdb->get_results($wpdb->prepare(
        "SELECT id, sequence_id, phone, current_step, context FROM {$table}
         WHERE status = 'active' AND next_run_at IS NOT NULL AND next_run_at <= %s
         ORDER BY next_run_at ASC LIMIT %d",
        $now,
        $chunk
    ));
    if (!$rows) {
        return;
    }

    foreach ($rows as $row) {
        $sequence = zignites_chat_seq_find($row->sequence_id);

        // Sequence removed or switched off mid-flight → stop this enrollment.
        if (!is_array($sequence) || $sequence['enabled'] !== 'yes') {
            zignites_chat_seq_cancel_enrollment((int) $row->id);
            continue;
        }

        // Marketing gate: opted out, or consent required and missing → permanent.
        if (function_exists('zignites_chat_marketing_blocked') && zignites_chat_marketing_blocked($row->phone)) {
            zignites_chat_seq_cancel_enrollment((int) $row->id);
            continue;
        }

        $step = zignites_chat_seq_get_step($sequence, (int) $row->current_step);
        if ($step === null) {
            // current_step fell out of range (sequence shrank) → done.
            zignites_chat_seq_complete_enrollment((int) $row->id);
            continue;
        }

        // Shared per-minute budget: stop the run when exhausted; the rows left
        // stay due and the next tick resumes. Checked before the send so a
        // saturated window doesn't advance anyone.
        if (function_exists('zignites_chat_outbound_rate_acquire') && !zignites_chat_outbound_rate_acquire()) {
            break;
        }

        $context = json_decode((string) $row->context, true);
        if (!is_array($context)) {
            $context = array();
        }
        $message = zignites_chat_seq_render_message($step['message'], $context);

        if (trim($message) !== '') {
            zignites_chat_send_whatsapp_message($row->phone, $message, false, array(
                'type'        => 'sequence',
                'sequence_id' => $sequence['id'],
                'step'        => (int) $row->current_step,
            ));
        }

        // Fire-and-forget advance (mirrors back-in-stock / status sends): the
        // analytics log records delivery state; we always move the step on.
        $plan = zignites_chat_seq_plan_advance($sequence, (int) $row->current_step, current_time('timestamp'));
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->update(
            $table,
            array(
                'status'       => $plan['status'],
                'current_step' => $plan['current_step'],
                'next_run_at'  => $plan['next_run_at'],
                'last_step_at' => $plan['last_step_at'],
            ),
            array('id' => (int) $row->id),
            array('%s', '%d', '%s', '%s'),
            array('%d')
        );
    }
}

/**
 * Mark an enrollment cancelled (permanent stop, no more sends).
 *
 * @param int $id
 * @return void
 */
function zignites_chat_seq_cancel_enrollment($id) {
    global $wpdb;
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $wpdb->update(
        zignites_chat_seq_enrollments_table_name(),
        array('status' => 'cancelled', 'next_run_at' => null),
        array('id' => (int) $id),
        array('%s', '%s'),
        array('%d')
    );
}

/**
 * Mark an enrollment completed (reached the end of the sequence).
 *
 * @param int $id
 * @return void
 */
function zignites_chat_seq_complete_enrollment($id) {
    global $wpdb;
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $wpdb->update(
        zignites_chat_seq_enrollments_table_name(),
        array('status' => 'completed', 'next_run_at' => null),
        array('id' => (int) $id),
        array('%s', '%s'),
        array('%d')
    );
}

/* -------------------------------------------------------------------------
 * Enrollment counts (admin UI)
 * ----------------------------------------------------------------------- */

/**
 * Shape grouped (sequence_id, status, count) rows into a per-sequence map. Pure.
 *
 * @param array $rows Rows with ->sequence_id, ->status, ->c (or array keys).
 * @return array<string, array{active:int, completed:int, cancelled:int, total:int}>
 */
function zignites_chat_seq_shape_counts($rows) {
    $out = array();
    if (!is_array($rows)) {
        return $out;
    }
    foreach ($rows as $row) {
        $row    = (array) $row;
        $id     = (string) ($row['sequence_id'] ?? '');
        $status = (string) ($row['status'] ?? '');
        $count  = (int) ($row['c'] ?? 0);
        if ($id === '') {
            continue;
        }
        if (!isset($out[$id])) {
            $out[$id] = array('active' => 0, 'completed' => 0, 'cancelled' => 0, 'total' => 0);
        }
        if (isset($out[$id][$status])) {
            $out[$id][$status] += $count;
        }
        $out[$id]['total'] += $count;
    }
    return $out;
}

/**
 * Per-sequence enrollment counts by status, for the admin Sequences page.
 *
 * @return array<string, array{active:int, completed:int, cancelled:int, total:int}>
 */
function zignites_chat_seq_enrollment_counts() {
    global $wpdb;
    $table = zignites_chat_seq_enrollments_table_name();
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    $rows = $wpdb->get_results("SELECT sequence_id, status, COUNT(*) AS c FROM {$table} GROUP BY sequence_id, status");
    return zignites_chat_seq_shape_counts($rows);
}

/* -------------------------------------------------------------------------
 * Win-back scan trigger (S5)
 *
 * A daily scan enrolls customers who have gone quiet into win_back sequences.
 * To stay bounded it only looks at the one-day order slice that falls exactly
 * N days back (N = the configured inactivity threshold): a customer whose most
 * recent order sits in that slice "went quiet" today, so they enroll today.
 * Enrollment idempotency keeps a repeat from re-enrolling them.
 * ----------------------------------------------------------------------- */

add_action('init', 'zignites_chat_seq_schedule_scan_cron');
add_action('zignites_chat_seq_daily_scan', 'zignites_chat_seq_run_winback_scan');

/**
 * Ensure the daily scan event is scheduled.
 */
function zignites_chat_seq_schedule_scan_cron() {
    if (!wp_next_scheduled('zignites_chat_seq_daily_scan')) {
        wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', 'zignites_chat_seq_daily_scan');
    }
}

/**
 * Clear the daily scan event (deactivation / uninstall).
 */
function zignites_chat_seq_unschedule_scan_cron() {
    $timestamp = wp_next_scheduled('zignites_chat_seq_daily_scan');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'zignites_chat_seq_daily_scan');
    }
}

/**
 * Compute the win-back order slice: the one-day window that ends N days before
 * $now_ts and opens N+1 days before. Pure.
 *
 * @param int $now_ts
 * @param int $days Inactivity threshold (clamped to >= 1).
 * @return array{after:int, before:int} Unix timestamps bounding the slice.
 */
function zignites_chat_seq_winback_window($now_ts, $days) {
    $days = max(1, (int) $days);
    return array(
        'after'  => (int) $now_ts - ($days + 1) * DAY_IN_SECONDS,
        'before' => (int) $now_ts - $days * DAY_IN_SECONDS,
    );
}

/**
 * Whether a phone has any completed/processing order placed after $since_ts.
 * Used to drop customers who have actually come back (so they aren't won back).
 *
 * @param string $phone
 * @param int    $since_ts
 * @return bool
 */
function zignites_chat_seq_has_order_since($phone, $since_ts) {
    if (!function_exists('wc_get_orders')) {
        return false;
    }
    $digits = zignites_chat_normalize_phone($phone);
    if ($digits === '') {
        return false;
    }
    $suffix = substr($digits, -9);

    $orders = wc_get_orders(array(
        'limit'        => 5,
        'return'       => 'objects',
        'status'       => array('wc-completed', 'wc-processing'),
        'date_created' => '>' . (int) $since_ts,
        'meta_query'   => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
            array(
                'key'     => '_billing_phone',
                'value'   => $suffix,
                'compare' => 'LIKE',
            ),
        ),
    ));
    if (empty($orders) || !is_array($orders)) {
        return false;
    }
    foreach ($orders as $order) {
        if (!is_object($order)) {
            continue;
        }
        // Confirm the last-digits LIKE wasn't a coincidental collision.
        if (!function_exists('zignites_chat_cod_phone_matches')
            || zignites_chat_cod_phone_matches($digits, $order->get_billing_phone())) {
            return true;
        }
    }
    return false;
}

/**
 * Daily win-back scan: enroll customers whose most recent order falls in the
 * N-days-back slice into every active win_back sequence.
 *
 * @return void
 */
function zignites_chat_seq_run_winback_scan() {
    if (function_exists('zignites_chat_is_pro_active') && !zignites_chat_is_pro_active()) {
        return;
    }
    $sequences = zignites_chat_seq_active_for_trigger('win_back');
    if (empty($sequences) || !function_exists('wc_get_orders')) {
        return;
    }

    $days   = max(1, (int) get_option('zignites_chat_seq_winback_days', 60));
    $window = zignites_chat_seq_winback_window(current_time('timestamp'), $days);
    $limit  = max(1, (int) apply_filters('zignites_chat_seq_winback_scan_limit', 300));

    $orders = wc_get_orders(array(
        'limit'        => $limit,
        'orderby'      => 'date',
        'order'        => 'DESC',
        'return'       => 'objects',
        'status'       => array('wc-completed', 'wc-processing'),
        'date_created' => $window['after'] . '...' . $window['before'],
    ));
    if (empty($orders) || !is_array($orders)) {
        return;
    }

    $site = function_exists('get_bloginfo') ? get_bloginfo('name') : '';
    $seen = array();

    foreach ($orders as $order) {
        if (!is_object($order)) {
            continue;
        }
        $phone  = $order->get_billing_phone();
        $digits = zignites_chat_normalize_phone($phone);
        if ($digits === '' || isset($seen[$digits])) {
            continue;
        }
        $seen[$digits] = true;

        // Skip anyone who ordered again after the slice — they're active.
        if (zignites_chat_seq_has_order_since($digits, $window['before'])) {
            continue;
        }

        $context = array(
            '{name}'          => $order->get_billing_first_name(),
            '{site}'          => $site,
            '{days_inactive}' => $days,
        );
        foreach ($sequences as $sequence) {
            zignites_chat_seq_enroll($sequence, $phone, $context);
        }
    }
}
