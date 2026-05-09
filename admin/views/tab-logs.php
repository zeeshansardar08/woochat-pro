<?php
if (!defined('ABSPATH')) exit;

$wcwp_log_keyword  = isset($_GET['wcwp_log_q'])     ? sanitize_text_field(wp_unslash($_GET['wcwp_log_q']))     : '';
$wcwp_log_tag      = isset($_GET['wcwp_log_tag'])   ? sanitize_text_field(wp_unslash($_GET['wcwp_log_tag']))   : '';
$wcwp_log_lines    = isset($_GET['wcwp_log_lines']) ? max(50, min(2000, (int) $_GET['wcwp_log_lines']))        : 200;
$wcwp_log_message  = isset($_GET['wcwp_log_msg'])   ? sanitize_text_field(wp_unslash($_GET['wcwp_log_msg']))   : '';

$wcwp_log_file     = wcwp_get_log_file();
$wcwp_log_exists   = is_file($wcwp_log_file);
$wcwp_log_size     = wcwp_log_size_bytes();
$wcwp_log_lines_raw = $wcwp_log_exists ? wcwp_log_tail_lines($wcwp_log_file, $wcwp_log_lines) : [];

$wcwp_log_all_entries = [];
foreach ($wcwp_log_lines_raw as $wcwp_log_line) {
    $wcwp_log_all_entries[] = wcwp_log_parse_line($wcwp_log_line);
}
$wcwp_log_tags_available = wcwp_log_tags_present($wcwp_log_all_entries);
$wcwp_log_filtered = wcwp_log_filter_lines($wcwp_log_lines_raw, $wcwp_log_keyword, $wcwp_log_tag);

$wcwp_log_download_url = wp_nonce_url(
    add_query_arg(['action' => 'wcwp_log_download'], admin_url('admin-post.php')),
    'wcwp_log_download',
    'wcwp_log_download_nonce'
);
$wcwp_log_clear_url = wp_nonce_url(
    add_query_arg(['action' => 'wcwp_log_clear'], admin_url('admin-post.php')),
    'wcwp_log_clear',
    'wcwp_log_clear_nonce'
);
?>
<div id="wcwp-tab-content-logs" class="wcwp-tab-content" style="display:none;">
    <h2><?php esc_html_e('Logs', 'woochat-pro'); ?></h2>
    <p class="description">
        <?php
        printf(
            /* translators: %s is the absolute path to the plugin log file */
            esc_html__('Reading from %s', 'woochat-pro'),
            '<code>' . esc_html($wcwp_log_file) . '</code>'
        );
        ?>
        — <?php
        if ($wcwp_log_exists) {
            printf(
                /* translators: %s is the file size, already i18n-formatted (e.g. "12.4 KB") */
                esc_html__('current size: %s', 'woochat-pro'),
                esc_html(size_format($wcwp_log_size, 1) ?: '0 B')
            );
        } else {
            esc_html_e('the file does not exist yet — it is created the first time the plugin writes a log entry.', 'woochat-pro');
        }
        ?>
    </p>

    <?php if ($wcwp_log_message === 'cleared') : ?>
        <div class="notice notice-success is-dismissible" style="margin:10px 0;"><p><?php esc_html_e('Log file cleared.', 'woochat-pro'); ?></p></div>
    <?php elseif ($wcwp_log_message === 'fail') : ?>
        <div class="notice notice-error is-dismissible" style="margin:10px 0;"><p><?php esc_html_e('Could not clear the log file. Check write permissions for wp-content/uploads/woochat-pro/.', 'woochat-pro'); ?></p></div>
    <?php elseif ($wcwp_log_message === 'empty') : ?>
        <div class="notice notice-warning is-dismissible" style="margin:10px 0;"><p><?php esc_html_e('Log file does not exist yet — nothing to download.', 'woochat-pro'); ?></p></div>
    <?php endif; ?>

    <div class="wcwp-log-filters" style="margin:12px 0;display:flex;gap:8px;flex-wrap:wrap;align-items:end;">
        <div>
            <label for="wcwp_log_q"><?php esc_html_e('Keyword', 'woochat-pro'); ?></label><br>
            <input type="text" id="wcwp_log_q" name="wcwp_log_q" value="<?php echo esc_attr($wcwp_log_keyword); ?>" placeholder="opt-out, sent, error" />
        </div>
        <div>
            <label for="wcwp_log_tag"><?php esc_html_e('Source', 'woochat-pro'); ?></label><br>
            <select id="wcwp_log_tag" name="wcwp_log_tag">
                <option value=""><?php esc_html_e('All sources', 'woochat-pro'); ?></option>
                <?php foreach ($wcwp_log_tags_available as $wcwp_log_t) : ?>
                    <option value="<?php echo esc_attr($wcwp_log_t); ?>" <?php selected($wcwp_log_tag, $wcwp_log_t); ?>><?php echo esc_html($wcwp_log_t); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="wcwp_log_lines"><?php esc_html_e('Lines', 'woochat-pro'); ?></label><br>
            <select id="wcwp_log_lines" name="wcwp_log_lines">
                <?php foreach ([100, 200, 500, 1000, 2000] as $wcwp_log_n) : ?>
                    <option value="<?php echo esc_attr((string) $wcwp_log_n); ?>" <?php selected($wcwp_log_lines, $wcwp_log_n); ?>><?php echo esc_html((string) $wcwp_log_n); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <button type="button" class="button button-primary" id="wcwp-log-filter-button"><?php esc_html_e('Apply', 'woochat-pro'); ?></button>
        </div>
        <div>
            <a class="button" href="<?php echo esc_url($wcwp_log_download_url); ?>"><span class="dashicons dashicons-download" style="vertical-align:middle;line-height:28px;"></span> <?php esc_html_e('Download log', 'woochat-pro'); ?></a>
        </div>
        <div>
            <a class="button" href="<?php echo esc_url($wcwp_log_clear_url); ?>" id="wcwp-log-clear-button" style="color:#b32d2e;"><span class="dashicons dashicons-trash" style="vertical-align:middle;line-height:28px;"></span> <?php esc_html_e('Clear log', 'woochat-pro'); ?></a>
        </div>
    </div>

    <?php
    $wcwp_log_total_shown = count($wcwp_log_all_entries);
    $wcwp_log_total_match = count($wcwp_log_filtered);
    ?>
    <p class="description" style="margin:6px 0;">
        <?php
        printf(
            /* translators: 1: number of matching entries, 2: total entries in the current window */
            esc_html__('Showing %1$d of %2$d recent entries.', 'woochat-pro'),
            (int) $wcwp_log_total_match,
            (int) $wcwp_log_total_shown
        );
        ?>
        <?php if ($wcwp_log_total_shown >= $wcwp_log_lines) : ?>
            <em><?php esc_html_e('Older entries are not shown — increase "Lines" or download the full log.', 'woochat-pro'); ?></em>
        <?php endif; ?>
    </p>

    <table class="widefat striped" style="margin-top:6px;">
        <thead>
            <tr>
                <th style="width:200px;"><?php esc_html_e('Source', 'woochat-pro'); ?></th>
                <th><?php esc_html_e('Message', 'woochat-pro'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($wcwp_log_filtered)) : ?>
                <?php foreach (array_reverse($wcwp_log_filtered) as $wcwp_log_entry) : ?>
                    <tr>
                        <td><code style="font-size:0.92em;"><?php echo esc_html($wcwp_log_entry['tag'] !== '' ? $wcwp_log_entry['tag'] : '—'); ?></code></td>
                        <td><pre style="white-space:pre-wrap;font-size:0.95em;margin:0;"><?php echo esc_html($wcwp_log_entry['message']); ?></pre></td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr><td colspan="2"><?php
                    if (!$wcwp_log_exists) {
                        esc_html_e('Log file does not exist yet.', 'woochat-pro');
                    } elseif ($wcwp_log_total_shown === 0) {
                        esc_html_e('Log file is empty.', 'woochat-pro');
                    } else {
                        esc_html_e('No entries match the current filters.', 'woochat-pro');
                    }
                ?></td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
