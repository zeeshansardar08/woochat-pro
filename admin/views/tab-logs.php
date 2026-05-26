<?php
if (!defined('ABSPATH')) exit;
// phpcs:disable WordPress.Security.NonceVerification.Recommended -- The GET query string is read only to pre-fill display filters; no state-changing action is taken here.

$zignites_chat_log_keyword  = isset($_GET['zignites_chat_log_q'])     ? sanitize_text_field(wp_unslash($_GET['zignites_chat_log_q']))     : '';
$zignites_chat_log_tag      = isset($_GET['zignites_chat_log_tag'])   ? sanitize_text_field(wp_unslash($_GET['zignites_chat_log_tag']))   : '';
$zignites_chat_log_message  = isset($_GET['zignites_chat_log_msg'])   ? sanitize_text_field(wp_unslash($_GET['zignites_chat_log_msg']))   : '';

$zignites_chat_log_lines    = isset($_GET['zignites_chat_log_lines']) ? max(50, min(500, (int) $_GET['zignites_chat_log_lines'])) : 200;

$zignites_chat_log_file     = zignites_chat_get_log_file();
$zignites_chat_log_exists   = is_file($zignites_chat_log_file);
$zignites_chat_log_size     = zignites_chat_log_size_bytes();
$zignites_chat_log_lines_raw = $zignites_chat_log_exists ? zignites_chat_log_tail_lines($zignites_chat_log_file, $zignites_chat_log_lines) : [];

$zignites_chat_log_all_entries = [];
foreach ($zignites_chat_log_lines_raw as $zignites_chat_log_line) {
    $zignites_chat_log_all_entries[] = zignites_chat_log_parse_line($zignites_chat_log_line);
}
$zignites_chat_log_tags_available = zignites_chat_log_tags_present($zignites_chat_log_all_entries);
$zignites_chat_log_filtered = zignites_chat_log_filter_lines($zignites_chat_log_lines_raw, $zignites_chat_log_keyword, $zignites_chat_log_tag);

$zignites_chat_log_download_url = wp_nonce_url(
    add_query_arg(['action' => 'zignites_chat_log_download'], admin_url('admin-post.php')),
    'zignites_chat_log_download',
    'zignites_chat_log_download_nonce'
);
$zignites_chat_log_clear_url = wp_nonce_url(
    add_query_arg(['action' => 'zignites_chat_log_clear'], admin_url('admin-post.php')),
    'zignites_chat_log_clear',
    'zignites_chat_log_clear_nonce'
);
?>
    <p class="description">
        <?php
        printf(
            /* translators: %s is the absolute path to the plugin log file */
            esc_html__('Reading from %s', 'zignites-chat'),
            '<code>' . esc_html($zignites_chat_log_file) . '</code>'
        );
        ?>
        — <?php
        if ($zignites_chat_log_exists) {
            printf(
                /* translators: %s is the file size, already i18n-formatted (e.g. "12.4 KB") */
                esc_html__('current size: %s', 'zignites-chat'),
                esc_html(size_format($zignites_chat_log_size, 1) ?: '0 B')
            );
        } else {
            esc_html_e('the file does not exist yet — it is created the first time the plugin writes a log entry.', 'zignites-chat');
        }
        ?>
    </p>

    <?php if ($zignites_chat_log_message === 'cleared') : ?>
        <div class="notice notice-success is-dismissible" style="margin:10px 0;"><p><?php esc_html_e('Log file cleared.', 'zignites-chat'); ?></p></div>
    <?php elseif ($zignites_chat_log_message === 'fail') : ?>
        <div class="notice notice-error is-dismissible" style="margin:10px 0;"><p><?php esc_html_e('Could not clear the log file. Check write permissions for wp-content/uploads/zignites-chat/.', 'zignites-chat'); ?></p></div>
    <?php elseif ($zignites_chat_log_message === 'empty') : ?>
        <div class="notice notice-warning is-dismissible" style="margin:10px 0;"><p><?php esc_html_e('Log file does not exist yet — nothing to download.', 'zignites-chat'); ?></p></div>
    <?php endif; ?>

    <div class="zignites-chat-log-filters">
        <div>
            <label for="zignites_chat_log_q"><?php esc_html_e('Keyword', 'zignites-chat'); ?></label>
            <input type="text" id="zignites_chat_log_q" name="zignites_chat_log_q" value="<?php echo esc_attr($zignites_chat_log_keyword); ?>" placeholder="opt-out, sent, error" style="width:180px;" />
        </div>
        <div>
            <label for="zignites_chat_log_tag"><?php esc_html_e('Source', 'zignites-chat'); ?></label>
            <select id="zignites_chat_log_tag" name="zignites_chat_log_tag">
                <option value=""><?php esc_html_e('All sources', 'zignites-chat'); ?></option>
                <?php foreach ($zignites_chat_log_tags_available as $zignites_chat_log_t) : ?>
                    <option value="<?php echo esc_attr($zignites_chat_log_t); ?>" <?php selected($zignites_chat_log_tag, $zignites_chat_log_t); ?>><?php echo esc_html($zignites_chat_log_t); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="zignites_chat_log_lines"><?php esc_html_e('Lines', 'zignites-chat'); ?></label>
            <select id="zignites_chat_log_lines" name="zignites_chat_log_lines">
                <?php foreach ([50, 100, 200, 500] as $zignites_chat_log_n) : ?>
                    <option value="<?php echo esc_attr((string) $zignites_chat_log_n); ?>" <?php selected($zignites_chat_log_lines, $zignites_chat_log_n); ?>><?php echo esc_html((string) $zignites_chat_log_n); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label>&nbsp;</label>
            <button type="button" class="button button-primary" id="zignites-chat-log-filter-button"><?php esc_html_e('Apply', 'zignites-chat'); ?></button>
        </div>
        <div>
            <label>&nbsp;</label>
            <a class="button" href="<?php echo esc_url($zignites_chat_log_download_url); ?>"><span class="dashicons dashicons-download" style="vertical-align:middle;"></span> <?php esc_html_e('Download', 'zignites-chat'); ?></a>
        </div>
        <div>
            <label>&nbsp;</label>
            <a class="button" href="<?php echo esc_url($zignites_chat_log_clear_url); ?>" id="zignites-chat-log-clear-button" style="color:#b32d2e;"><span class="dashicons dashicons-trash" style="vertical-align:middle;"></span> <?php esc_html_e('Clear', 'zignites-chat'); ?></a>
        </div>
    </div>

    <?php
    $zignites_chat_log_total_shown = count($zignites_chat_log_all_entries);
    $zignites_chat_log_total_match = count($zignites_chat_log_filtered);
    ?>
    <p class="description" style="margin:6px 0;">
        <?php
        printf(
            /* translators: 1: number of matching entries, 2: total entries in the current window */
            esc_html__('Showing %1$d of %2$d recent entries.', 'zignites-chat'),
            (int) $zignites_chat_log_total_match,
            (int) $zignites_chat_log_total_shown
        );
        ?>
        <?php if ($zignites_chat_log_total_shown >= $zignites_chat_log_lines) : ?>
            <em><?php esc_html_e('Older entries are not shown — increase "Lines" or download the full log.', 'zignites-chat'); ?></em>
        <?php endif; ?>
    </p>

    <table class="widefat striped" style="margin-top:6px;">
        <thead>
            <tr>
                <th style="width:200px;"><?php esc_html_e('Source', 'zignites-chat'); ?></th>
                <th><?php esc_html_e('Message', 'zignites-chat'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($zignites_chat_log_filtered)) : ?>
                <?php foreach (array_reverse($zignites_chat_log_filtered) as $zignites_chat_log_entry) : ?>
                    <tr>
                        <td><code style="font-size:0.92em;"><?php echo esc_html($zignites_chat_log_entry['tag'] !== '' ? $zignites_chat_log_entry['tag'] : '—'); ?></code></td>
                        <td><pre style="white-space:pre-wrap;font-size:0.95em;margin:0;"><?php echo esc_html($zignites_chat_log_entry['message']); ?></pre></td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr><td colspan="2"><?php
                    if (!$zignites_chat_log_exists) {
                        esc_html_e('Log file does not exist yet.', 'zignites-chat');
                    } elseif ($zignites_chat_log_total_shown === 0) {
                        esc_html_e('Log file is empty.', 'zignites-chat');
                    } else {
                        esc_html_e('No entries match the current filters.', 'zignites-chat');
                    }
                ?></td></tr>
            <?php endif; ?>
        </tbody>
    </table>
