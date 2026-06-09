<?php
/**
 * Drip & automation sequences settings view (T3.1 S4).
 *
 * Renders the configured sequences as editable cards (id, name, enabled,
 * trigger, ordered delay+message steps) inside the options.php form opened by
 * zignites_chat_render_sequences_page(). The whole `zignites_chat_sequences`
 * option round-trips through zignites_chat_seq_sanitize_sequences() on save, so
 * blank/removed cards are dropped server-side. Cards and steps are added or
 * removed client-side by assets/js/sequences.js.
 */
if (!defined('ABSPATH')) exit;
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- View partial: locals/closures are scoped to the including render function.

$zignites_chat_seq_list     = zignites_chat_seq_get_sequences();
$zignites_chat_seq_triggers = zignites_chat_seq_triggers();
$zignites_chat_seq_counts   = zignites_chat_seq_enrollment_counts();
$zignites_chat_seq_units     = array(
    'minutes' => __('minutes', 'zignites-chat'),
    'hours'   => __('hours', 'zignites-chat'),
    'days'    => __('days', 'zignites-chat'),
);

/**
 * Render one step row. $si / $sti may be numeric (existing) or placeholder
 * tokens (__SI__/__STI__) used by the JS templates.
 */
$zignites_chat_render_step = function ($si, $sti, $step) use ($zignites_chat_seq_units) {
    $field = 'zignites_chat_sequences[' . $si . '][steps][' . $sti . ']';
    $value = isset($step['delay_value']) ? (int) $step['delay_value'] : 0;
    $unit  = isset($step['delay_unit']) ? (string) $step['delay_unit'] : 'minutes';
    $msg   = isset($step['message']) ? (string) $step['message'] : '';
    ?>
    <div class="zignites-chat-seq-step" style="border-left:3px solid #dcdcde;padding:8px 12px;margin:8px 0;background:#fbfbfc;">
        <p style="margin:0 0 6px;">
            <?php esc_html_e('Wait', 'zignites-chat'); ?>
            <input type="number" min="0" step="1" class="small-text" name="<?php echo esc_attr($field . '[delay_value]'); ?>" value="<?php echo esc_attr((string) $value); ?>" />
            <select name="<?php echo esc_attr($field . '[delay_unit]'); ?>">
                <?php foreach ($zignites_chat_seq_units as $zignites_chat_unit_key => $zignites_chat_unit_label) : ?>
                    <option value="<?php echo esc_attr($zignites_chat_unit_key); ?>" <?php selected($unit, $zignites_chat_unit_key); ?>><?php echo esc_html($zignites_chat_unit_label); ?></option>
                <?php endforeach; ?>
            </select>
            <?php esc_html_e('then send:', 'zignites-chat'); ?>
            <button type="button" class="button-link zignites-chat-seq-remove-step" style="color:#b32d2e;margin-left:8px;"><?php esc_html_e('remove step', 'zignites-chat'); ?></button>
        </p>
        <textarea class="large-text" rows="2" name="<?php echo esc_attr($field . '[message]'); ?>" placeholder="<?php esc_attr_e('Message… use the placeholders for this trigger', 'zignites-chat'); ?>"><?php echo esc_textarea($msg); ?></textarea>
    </div>
    <?php
};

/**
 * Render one sequence card. $si numeric for existing, '__SI__' for the JS
 * template. $is_new toggles an editable vs read-only id field.
 */
$zignites_chat_render_card = function ($si, $seq, $is_new, $counts) use ($zignites_chat_seq_triggers, $zignites_chat_render_step) {
    $field = 'zignites_chat_sequences[' . $si . ']';
    $id      = isset($seq['id']) ? (string) $seq['id'] : '';
    $name    = isset($seq['name']) ? (string) $seq['name'] : '';
    $enabled = (isset($seq['enabled']) && $seq['enabled'] === 'yes');
    $trigger = isset($seq['trigger']) ? (string) $seq['trigger'] : '';
    $steps   = (isset($seq['steps']) && is_array($seq['steps']) && !empty($seq['steps'])) ? $seq['steps'] : array(array());
    $stat    = ($id !== '' && isset($counts[$id])) ? $counts[$id] : null;
    ?>
    <div class="zignites-chat-seq-card" data-seq-index="<?php echo esc_attr((string) $si); ?>" data-next-step="<?php echo esc_attr((string) count($steps)); ?>" style="border:1px solid #c3c4c7;border-radius:6px;background:#fff;padding:4px 16px 12px;margin:16px 0;">
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row"><?php esc_html_e('Sequence ID', 'zignites-chat'); ?></th>
                <td>
                    <?php if ($is_new) : ?>
                        <input type="text" class="regular-text" name="<?php echo esc_attr($field . '[id]'); ?>" value="<?php echo esc_attr($id); ?>" placeholder="welcome_series" />
                        <p class="description"><?php esc_html_e('Lowercase letters, numbers and underscores. Used as the unique key — pick it once.', 'zignites-chat'); ?></p>
                    <?php else : ?>
                        <code><?php echo esc_html($id); ?></code>
                        <input type="hidden" name="<?php echo esc_attr($field . '[id]'); ?>" value="<?php echo esc_attr($id); ?>" />
                        <?php if ($stat) : ?>
                            <span class="description" style="margin-left:12px;">
                                <?php
                                printf(
                                    /* translators: 1: active count, 2: completed count, 3: cancelled count */
                                    esc_html__('Enrollments — %1$d active, %2$d completed, %3$d cancelled', 'zignites-chat'),
                                    (int) $stat['active'],
                                    (int) $stat['completed'],
                                    (int) $stat['cancelled']
                                );
                                ?>
                            </span>
                        <?php endif; ?>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Name', 'zignites-chat'); ?></th>
                <td><input type="text" class="regular-text" name="<?php echo esc_attr($field . '[name]'); ?>" value="<?php echo esc_attr($name); ?>" placeholder="<?php esc_attr_e('Welcome series', 'zignites-chat'); ?>" /></td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Enabled', 'zignites-chat'); ?></th>
                <td>
                    <label>
                        <input type="hidden" name="<?php echo esc_attr($field . '[enabled]'); ?>" value="no" />
                        <input type="checkbox" name="<?php echo esc_attr($field . '[enabled]'); ?>" value="yes" <?php checked($enabled, true); ?> />
                        <?php esc_html_e('Active — new triggers enroll customers', 'zignites-chat'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Trigger', 'zignites-chat'); ?></th>
                <td>
                    <select class="zignites-chat-seq-trigger" name="<?php echo esc_attr($field . '[trigger]'); ?>">
                        <?php foreach ($zignites_chat_seq_triggers as $zignites_chat_trig_key => $zignites_chat_trig_meta) : ?>
                            <option value="<?php echo esc_attr($zignites_chat_trig_key); ?>" data-placeholders="<?php echo esc_attr(implode(' ', (array) $zignites_chat_trig_meta['placeholders'])); ?>" <?php selected($trigger, $zignites_chat_trig_key); ?>><?php echo esc_html($zignites_chat_trig_meta['label']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description zignites-chat-seq-placeholders"></p>
                </td>
            </tr>
        </table>

        <div class="zignites-chat-seq-steps">
            <?php foreach (array_values($steps) as $zignites_chat_sti => $zignites_chat_step) {
                $zignites_chat_render_step($si, $zignites_chat_sti, is_array($zignites_chat_step) ? $zignites_chat_step : array());
            } ?>
        </div>

        <p>
            <button type="button" class="button zignites-chat-seq-add-step"><?php esc_html_e('+ Add step', 'zignites-chat'); ?></button>
            <button type="button" class="button zignites-chat-seq-remove" style="float:right;color:#b32d2e;"><?php esc_html_e('Remove sequence', 'zignites-chat'); ?></button>
        </p>
    </div>
    <?php
};
?>
<h2><?php esc_html_e('Drip & Automation Sequences', 'zignites-chat'); ?></h2>
<p class="description" style="max-width:780px;">
    <?php esc_html_e('Build multi-step WhatsApp journeys that send themselves. Pick a trigger, then add steps — each step waits a delay and sends a message. Customers are enrolled when the trigger fires and walk the steps automatically. Marketing rules apply: opt-outs and missing consent stop a sequence, and quiet hours / rate limits are respected.', 'zignites-chat'); ?>
</p>

<table class="form-table" role="presentation">
    <tr>
        <th scope="row"><label for="zignites_chat_seq_winback_days"><?php esc_html_e('Win-back inactivity (days)', 'zignites-chat'); ?></label></th>
        <td>
            <input type="number" min="1" step="1" class="small-text" id="zignites_chat_seq_winback_days" name="zignites_chat_seq_winback_days" value="<?php echo esc_attr((string) max(1, (int) get_option('zignites_chat_seq_winback_days', 60))); ?>" />
            <p class="description"><?php esc_html_e('A daily scan enrolls customers into your “win-back” sequences once their most recent order is this many days old. Only used by sequences with the win-back trigger.', 'zignites-chat'); ?></p>
        </td>
    </tr>
    <tr>
        <th scope="row"><label for="zignites_chat_seq_browse_days"><?php esc_html_e('Browse-abandon delay (days)', 'zignites-chat'); ?></label></th>
        <td>
            <input type="number" min="1" step="1" class="small-text" id="zignites_chat_seq_browse_days" name="zignites_chat_seq_browse_days" value="<?php echo esc_attr((string) max(1, (int) get_option('zignites_chat_seq_browse_days', 1))); ?>" />
            <p class="description"><?php esc_html_e('A daily scan enrolls customers into your “browse-abandon” sequences this many days after they last viewed a product without ordering. Only logged-in customers with a billing phone on file are tracked — guests can’t be messaged. Only used by sequences with the browse-abandon trigger.', 'zignites-chat'); ?></p>
        </td>
    </tr>
</table>

<div id="zignites-chat-sequences-list">
    <?php foreach ($zignites_chat_seq_list as $zignites_chat_si => $zignites_chat_seq) {
        $zignites_chat_render_card($zignites_chat_si, $zignites_chat_seq, false, $zignites_chat_seq_counts);
    } ?>
</div>

<p>
    <button type="button" class="button button-secondary" id="zignites-chat-add-sequence"><?php esc_html_e('+ Add sequence', 'zignites-chat'); ?></button>
</p>

<script type="text/template" id="zignites-chat-seq-card-tpl">
    <?php $zignites_chat_render_card('__SI__', array('steps' => array(array())), true, array()); ?>
</script>
<script type="text/template" id="zignites-chat-seq-step-tpl">
    <?php $zignites_chat_render_step('__SI__', '__STI__', array()); ?>
</script>
