<?php
/**
 * Quiet hours (Pro) — roadmap Q3.
 *
 * Holds back *marketing* WhatsApp sends (cart recovery, campaigns, follow-ups)
 * during a configured nightly window so customers aren't messaged at 3am.
 * Transactional sends (order confirmation, COD, status updates) are never
 * affected. The window is evaluated in the store's timezone.
 *
 * When a marketing send would land inside the window the caller defers it —
 * the cron simply resumes after the window ends; nothing is dropped.
 *
 * The time math is pure and unit-tested; the option-backed wrappers compose it.
 *
 * @package Zignites_Chat
 */

if (!defined('ABSPATH')) exit;

/* -------------------------------------------------------------------------
 * Pure helpers (no DB) — unit-tested
 * ----------------------------------------------------------------------- */

/**
 * Parse an "HH:MM" string into minutes since midnight. Pure.
 *
 * @param string $str Time string, 24-hour.
 * @return int Minutes 0–1439, or -1 when invalid.
 */
function zignites_chat_parse_time_to_minutes($str) {
    $str = trim((string) $str);
    if (!preg_match('/^(\d{1,2}):(\d{2})$/', $str, $m)) {
        return -1;
    }
    $h  = (int) $m[1];
    $mn = (int) $m[2];
    if ($h > 23 || $mn > 59) {
        return -1;
    }
    return $h * 60 + $mn;
}

/**
 * Whether a moment falls inside the quiet window. Pure.
 *
 * Handles overnight windows (start > end, e.g. 21:00–08:00). A start equal to
 * the end is treated as "no quiet hours".
 *
 * @param int $now   Minutes since midnight now.
 * @param int $start Window start (minutes).
 * @param int $end   Window end (minutes).
 * @return bool
 */
function zignites_chat_in_quiet_hours($now, $start, $end) {
    $now   = (int) $now;
    $start = (int) $start;
    $end   = (int) $end;
    if ($start === $end) {
        return false;
    }
    if ($start < $end) {
        return $now >= $start && $now < $end;
    }
    // Overnight window.
    return $now >= $start || $now < $end;
}

/**
 * Minutes remaining until the quiet window ends. Pure.
 *
 * @param int $now   Minutes since midnight now.
 * @param int $start Window start (minutes).
 * @param int $end   Window end (minutes).
 * @return int Minutes until the window ends, or 0 when not currently quiet.
 */
function zignites_chat_quiet_minutes_until_end($now, $start, $end) {
    if (!zignites_chat_in_quiet_hours($now, $start, $end)) {
        return 0;
    }
    $diff = (int) $end - (int) $now;
    if ($diff <= 0) {
        $diff += 1440; // wrap past midnight
    }
    return $diff;
}

/* -------------------------------------------------------------------------
 * Option-backed wrappers
 * ----------------------------------------------------------------------- */

/**
 * Current minute-of-day in the store's timezone.
 *
 * @return int
 */
function zignites_chat_quiet_now_minutes() {
    return ((int) current_time('G')) * 60 + (int) current_time('i');
}

/**
 * Whether marketing sends are currently held back by quiet hours.
 *
 * @return bool
 */
function zignites_chat_quiet_hours_active() {
    if (get_option('zignites_chat_quiet_hours_enabled', 'no') !== 'yes') {
        return false;
    }
    if (function_exists('zignites_chat_is_pro_active') && !zignites_chat_is_pro_active()) {
        return false;
    }
    $start = zignites_chat_parse_time_to_minutes(get_option('zignites_chat_quiet_start', '21:00'));
    $end   = zignites_chat_parse_time_to_minutes(get_option('zignites_chat_quiet_end', '08:00'));
    if ($start < 0 || $end < 0) {
        return false;
    }
    return zignites_chat_in_quiet_hours(zignites_chat_quiet_now_minutes(), $start, $end);
}

/**
 * Seconds until the quiet window ends — used to reschedule a deferred send so
 * it resumes right when messaging is allowed again (min 60s).
 *
 * @return int 0 when not currently quiet.
 */
function zignites_chat_quiet_hours_resume_seconds() {
    $start = zignites_chat_parse_time_to_minutes(get_option('zignites_chat_quiet_start', '21:00'));
    $end   = zignites_chat_parse_time_to_minutes(get_option('zignites_chat_quiet_end', '08:00'));
    if ($start < 0 || $end < 0) {
        return 0;
    }
    $mins = zignites_chat_quiet_minutes_until_end(zignites_chat_quiet_now_minutes(), $start, $end);
    return $mins > 0 ? $mins * 60 : 0;
}

/**
 * Sanitize an "HH:MM" time option, normalizing to zero-padded form.
 *
 * @param mixed $value
 * @return string '' when invalid.
 */
function zignites_chat_quiet_sanitize_time($value) {
    $minutes = zignites_chat_parse_time_to_minutes($value);
    if ($minutes < 0) {
        return '';
    }
    return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
}
