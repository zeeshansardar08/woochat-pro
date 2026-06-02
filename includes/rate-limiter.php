<?php
/**
 * Central outbound rate limiter.
 *
 * The bulk send channels (cart recovery, follow-up scheduler, bulk campaigns)
 * each run on their own cron and previously throttled independently, so a
 * concurrent burst across all three could still exceed the provider's
 * per-second WhatsApp throughput and hurt the sender number's quality rating.
 *
 * This module provides a single shared budget those channels consult before
 * each send. It is advisory: when the budget is exhausted the caller stops its
 * current run and resumes on the next cron tick — no message is ever marked
 * failed because of the limiter.
 *
 * The window evaluator is pure (and unit-tested); acquire() wraps it with an
 * option-backed counter.
 *
 * @package Zignites_Chat
 */

if (!defined('ABSPATH')) exit;

/**
 * Maximum outbound sends allowed per window across all bulk channels.
 *
 * Default 60/minute (~1 msg/sec — Twilio's default WhatsApp throughput and a
 * safe Meta Cloud rate). Filterable so high-tier numbers can raise it and
 * new/low-rated numbers can lower it.
 *
 * @return int
 */
function zignites_chat_outbound_rate_max() {
    return max(1, (int) apply_filters('zignites_chat_outbound_rate_per_minute', 60));
}

/**
 * Length of the rate window in seconds (default 60).
 *
 * @return int
 */
function zignites_chat_outbound_rate_window() {
    return max(1, (int) apply_filters('zignites_chat_outbound_rate_window', MINUTE_IN_SECONDS));
}

/**
 * Pure fixed-window evaluator.
 *
 * Given the current counter state, decides whether one more send is allowed
 * and returns the next state. A window resets once $window seconds have passed
 * since it started; within a live window, sends are allowed until $max is hit.
 *
 * @param array $state  ['count' => int, 'start' => int unix-ts] (may be empty).
 * @param int   $now    Current unix timestamp.
 * @param int   $max    Max sends per window.
 * @param int   $window Window length in seconds.
 * @return array{allowed: bool, state: array{count:int, start:int}}
 */
function zignites_chat_rate_window_evaluate($state, $now, $max, $window) {
    $count = (is_array($state) && isset($state['count'])) ? (int) $state['count'] : 0;
    $start = (is_array($state) && isset($state['start'])) ? (int) $state['start'] : 0;
    $now   = (int) $now;
    $max   = max(1, (int) $max);
    $window = max(1, (int) $window);

    // Fresh or expired window → start a new one and allow.
    if ($start <= 0 || ($now - $start) >= $window) {
        return ['allowed' => true, 'state' => ['count' => 1, 'start' => $now]];
    }

    // Live window with budget left.
    if ($count < $max) {
        return ['allowed' => true, 'state' => ['count' => $count + 1, 'start' => $start]];
    }

    // Budget exhausted — deny, state unchanged.
    return ['allowed' => false, 'state' => ['count' => $count, 'start' => $start]];
}

/**
 * Try to consume one unit of the shared outbound budget.
 *
 * Callers (the bulk cron loops) check this immediately before each send; when
 * it returns false they should stop the current run and let the next cron tick
 * resume. Returns true when sending is allowed.
 *
 * @return bool
 */
function zignites_chat_outbound_rate_acquire() {
    /** @var bool $enabled Filter to false to disable the limiter entirely. */
    if (!apply_filters('zignites_chat_outbound_rate_limit_enabled', true)) {
        return true;
    }

    $state = get_option('zignites_chat_outbound_rate_state', []);
    if (!is_array($state)) {
        $state = [];
    }

    $eval = zignites_chat_rate_window_evaluate(
        $state,
        time(),
        zignites_chat_outbound_rate_max(),
        zignites_chat_outbound_rate_window()
    );

    // Only write when the state actually changed (a denial leaves it as-is).
    if ($eval['state'] !== $state) {
        update_option('zignites_chat_outbound_rate_state', $eval['state'], false);
    }

    return $eval['allowed'];
}
