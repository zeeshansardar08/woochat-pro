<?php
if (!defined('ABSPATH')) exit;
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- View partial: variables are scoped to the render function that includes this file.

$zignites_chat_quiet_on    = get_option('zignites_chat_quiet_hours_enabled', 'no');
$zignites_chat_quiet_start = get_option('zignites_chat_quiet_start', '21:00');
$zignites_chat_quiet_end   = get_option('zignites_chat_quiet_end', '08:00');
$zignites_chat_tz          = function_exists('wp_timezone_string') ? wp_timezone_string() : get_option('timezone_string');
?>
<h2><?php esc_html_e('Quiet Hours', 'zignites-chat'); ?></h2>
<p class="description">
    <?php esc_html_e('Pause marketing messages (cart recovery, campaigns, follow-ups) during a nightly window. Deferred sends resume automatically when the window ends. Transactional messages — order confirmations, COD confirmations and status updates — are never held back.', 'zignites-chat'); ?>
</p>

<table class="form-table">
    <tr>
        <th scope="row"><label for="zignites_chat_quiet_hours_enabled"><?php esc_html_e('Enable quiet hours', 'zignites-chat'); ?></label></th>
        <td>
            <select name="zignites_chat_quiet_hours_enabled" id="zignites_chat_quiet_hours_enabled">
                <option value="no" <?php selected($zignites_chat_quiet_on, 'no'); ?>><?php esc_html_e('No', 'zignites-chat'); ?></option>
                <option value="yes" <?php selected($zignites_chat_quiet_on, 'yes'); ?>><?php esc_html_e('Yes', 'zignites-chat'); ?></option>
            </select>
        </td>
    </tr>
    <tr>
        <th scope="row"><label for="zignites_chat_quiet_start"><?php esc_html_e('Quiet from', 'zignites-chat'); ?></label></th>
        <td><input type="time" name="zignites_chat_quiet_start" id="zignites_chat_quiet_start" value="<?php echo esc_attr($zignites_chat_quiet_start); ?>" /></td>
    </tr>
    <tr>
        <th scope="row"><label for="zignites_chat_quiet_end"><?php esc_html_e('Quiet until', 'zignites-chat'); ?></label></th>
        <td>
            <input type="time" name="zignites_chat_quiet_end" id="zignites_chat_quiet_end" value="<?php echo esc_attr($zignites_chat_quiet_end); ?>" />
            <p class="description">
                <?php
                /* translators: %s: site timezone string. */
                printf(esc_html__('Times are in your store timezone (%s). An end earlier than the start means the window runs overnight.', 'zignites-chat'), esc_html($zignites_chat_tz ? $zignites_chat_tz : 'UTC'));
                ?>
            </p>
        </td>
    </tr>
</table>
