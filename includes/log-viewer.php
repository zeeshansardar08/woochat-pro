<?php
/**
 * Admin-side log viewer.
 *
 * Surfaces the plugin's log file (`wp-content/uploads/woochat-pro/
 * woochat-pro.log` — see wcwp_get_log_file()) inside the WooChat
 * Settings page so admins don't have to SFTP into uploads/ to debug a
 * failed send. Every write into that file already goes through the
 * shared `error_log($msg, 3, $log_file)` pattern in messaging.php and
 * cart-recovery.php; this module only adds *read* paths.
 *
 * Provides three operations:
 *   1. Tail-and-display: read the last N lines, parse each into
 *      tag/message, render as a filterable table. The reverse-chunk
 *      reader (`wcwp_log_tail_lines()`) avoids loading the whole file
 *      into memory.
 *   2. Download: stream the raw file as text/plain to the admin so they
 *      can attach it to a support ticket. Capability + nonce gated.
 *   3. Clear: truncate the file in place. Capability + nonce gated.
 *      Truncation (vs. unlink) keeps the existing fd's of any
 *      concurrent writers valid and preserves the .htaccess/index.php
 *      siblings that wcwp_get_log_file() set up.
 */

if (!defined('ABSPATH')) exit;

add_action('admin_post_wcwp_log_download', 'wcwp_log_download_handler');
add_action('admin_post_wcwp_log_clear', 'wcwp_log_clear_handler');

/**
 * Read the last N lines of a file by reverse-chunking from EOF.
 *
 * Returns lines in chronological order (oldest → newest). Handles
 * arbitrary file sizes without loading the whole thing into memory.
 * Stops early once the requested line count is reached. Empty lines
 * are stripped.
 *
 * Tested in tests/Unit/LogViewerTest.php with a synthetic file.
 *
 * @param string $file       Absolute path to the log file.
 * @param int    $max_lines  Cap on returned lines (defaults to 200).
 * @param int    $chunk_size Chunk read size in bytes (defaults to 4096).
 * @return string[] Lines, oldest first.
 */
function wcwp_log_tail_lines($file, $max_lines = 200, $chunk_size = 4096) {
    if ($max_lines < 1) return [];
    if (!is_string($file) || !is_file($file) || !is_readable($file)) return [];

    $fp = @fopen($file, 'rb');
    if (!$fp) return [];

    fseek($fp, 0, SEEK_END);
    $position = ftell($fp);
    if ($position === 0) {
        fclose($fp);
        return [];
    }

    $buffer = '';
    $line_count = 0;
    $chunk_size = max(512, (int) $chunk_size);

    while ($line_count <= $max_lines && $position > 0) {
        $read = (int) min($chunk_size, $position);
        $position -= $read;
        fseek($fp, $position);
        $chunk = fread($fp, $read);
        if ($chunk === false) break;
        $buffer = $chunk . $buffer;
        $line_count = substr_count($buffer, "\n");
    }
    fclose($fp);

    $lines = preg_split('/\r?\n/', $buffer);
    if (!is_array($lines)) return [];

    // Strip empties before slicing — a trailing newline yields a phantom
    // empty element, and the leading element may be a partial line from
    // mid-chunk; slicing first would lose one real line per phantom.
    $out = [];
    foreach ($lines as $line) {
        $line = rtrim($line);
        if ($line === '') continue;
        $out[] = $line;
    }

    if (count($out) > $max_lines) {
        $out = array_slice($out, -$max_lines);
    }
    return $out;
}

/**
 * Parse one log line into [tag, message, raw].
 *
 * Lines written by messaging.php / cart-recovery.php look like:
 *   [WooChat Pro] message text
 *   [WooChat Pro - Cart Recovery] message text
 *   [WooChat Pro - MANUAL] message text
 *
 * The bracketed prefix is the "tag" we surface in the filter dropdown.
 * Lines without a recognised prefix get an empty tag and the whole
 * line as the message — better than dropping them.
 *
 * Pure helper, no side effects.
 *
 * @param string $line Raw log line (no trailing newline).
 * @return array{tag:string, message:string, raw:string}
 */
function wcwp_log_parse_line($line) {
    $line = (string) $line;
    if ($line === '') {
        return ['tag' => '', 'message' => '', 'raw' => ''];
    }
    if (preg_match('/^\[(WooChat Pro(?: - [^\]]+)?)\]\s*(.*)$/', $line, $matches)) {
        return [
            'tag'     => $matches[1],
            'message' => $matches[2],
            'raw'     => $line,
        ];
    }
    return ['tag' => '', 'message' => $line, 'raw' => $line];
}

/**
 * Return parsed log entries, optionally filtered by tag and keyword.
 *
 * Pure-ish: pulls the raw lines via wcwp_log_tail_lines() then runs
 * everything else in PHP so the filter logic itself is testable
 * (caller can pass already-parsed lines via the alt arg).
 *
 * @param string[] $lines    Raw lines (oldest first).
 * @param string   $keyword  Substring to match (case-insensitive). Empty = no keyword filter.
 * @param string   $tag      Exact tag match. Empty = no tag filter.
 * @return array<int, array{tag:string, message:string, raw:string}>
 */
function wcwp_log_filter_lines($lines, $keyword = '', $tag = '') {
    if (!is_array($lines)) return [];

    $keyword = is_string($keyword) ? trim($keyword) : '';
    $tag     = is_string($tag) ? trim($tag) : '';

    $out = [];
    foreach ($lines as $line) {
        $parsed = wcwp_log_parse_line($line);
        if ($tag !== '' && $parsed['tag'] !== $tag) continue;
        if ($keyword !== '' && stripos($parsed['raw'], $keyword) === false) continue;
        $out[] = $parsed;
    }
    return $out;
}

/**
 * Distinct tags present in the supplied entries.
 *
 * Used to populate the filter dropdown so admins only see tags that
 * actually exist in their current log window — no dead options.
 *
 * @param array<int, array{tag:string}> $entries
 * @return string[]
 */
function wcwp_log_tags_present($entries) {
    if (!is_array($entries)) return [];
    $seen = [];
    foreach ($entries as $entry) {
        $tag = isset($entry['tag']) ? (string) $entry['tag'] : '';
        if ($tag === '') continue;
        $seen[$tag] = true;
    }
    $out = array_keys($seen);
    sort($out);
    return $out;
}

function wcwp_log_size_bytes() {
    $file = wcwp_get_log_file();
    if (!is_file($file)) return 0;
    $size = @filesize($file);
    return $size === false ? 0 : (int) $size;
}

function wcwp_log_download_handler() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Unauthorized', 'woochat-pro'), '', ['response' => 403]);
    }
    check_admin_referer('wcwp_log_download', 'wcwp_log_download_nonce');

    $file = wcwp_get_log_file();
    if (!is_file($file) || !is_readable($file)) {
        wp_safe_redirect(add_query_arg(['page' => 'wcwp-settings', 'tab' => 'logs', 'wcwp_log_msg' => 'empty'], admin_url('admin.php')));
        exit;
    }

    $filename = 'woochat-pro-' . gmdate('Ymd-His') . '.log';
    nocache_headers();
    header('Content-Type: text/plain; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . (string) filesize($file));
    @readfile($file); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_readfile -- streaming a controlled path inside uploads/, capability + nonce gated.
    exit;
}

function wcwp_log_clear_handler() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Unauthorized', 'woochat-pro'), '', ['response' => 403]);
    }
    check_admin_referer('wcwp_log_clear', 'wcwp_log_clear_nonce');

    $file = wcwp_get_log_file();
    $msg  = 'cleared';
    if (is_file($file)) {
        // Truncate in place (vs. unlink) so concurrent fds in messaging.php
        // / cart-recovery.php stay valid and the .htaccess/index.php
        // siblings created by wcwp_get_log_file() are preserved.
        $fp = @fopen($file, 'wb');
        if ($fp) {
            @ftruncate($fp, 0);
            fclose($fp);
        } else {
            $msg = 'fail';
        }
    }

    wp_safe_redirect(add_query_arg([
        'page'         => 'wcwp-settings',
        'tab'          => 'logs',
        'wcwp_log_msg' => $msg,
    ], admin_url('admin.php')));
    exit;
}
