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
 * This file is the pure data layer only — mirroring inbox I1:
 *   - the enrollments table (idempotent dbDelta) + migration v9,
 *   - sequence-definition storage (option `zignites_chat_sequences`) with a
 *     sanitizer + normalizing getters,
 *   - pure, unit-tested helpers for delay maths, step lookup, scheduling and
 *     message rendering.
 *
 * Enrollment + trigger wiring, the cron sender, and the admin CRUD UI land in
 * later increments on top of these primitives.
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
