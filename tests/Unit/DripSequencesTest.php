<?php
declare(strict_types=1);

namespace ZignitesChat\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class DripSequencesTest extends TestCase
{
    public function test_delay_to_seconds_by_unit(): void
    {
        $this->assertSame(5 * MINUTE_IN_SECONDS, \zignites_chat_seq_delay_to_seconds(5, 'minutes'));
        $this->assertSame(2 * HOUR_IN_SECONDS, \zignites_chat_seq_delay_to_seconds(2, 'hours'));
        $this->assertSame(3 * DAY_IN_SECONDS, \zignites_chat_seq_delay_to_seconds(3, 'days'));
    }

    public function test_delay_to_seconds_clamps_and_defaults_unit(): void
    {
        $this->assertSame(0, \zignites_chat_seq_delay_to_seconds(-4, 'days'));
        // Unknown unit falls back to minutes.
        $this->assertSame(7 * MINUTE_IN_SECONDS, \zignites_chat_seq_delay_to_seconds(7, 'weeks'));
    }

    public function test_step_count_and_get_step(): void
    {
        $seq = ['steps' => [['message' => 'a'], ['message' => 'b']]];
        $this->assertSame(2, \zignites_chat_seq_step_count($seq));
        $this->assertSame('b', \zignites_chat_seq_get_step($seq, 1)['message']);
        $this->assertNull(\zignites_chat_seq_get_step($seq, 5));
        $this->assertSame(0, \zignites_chat_seq_step_count(['no_steps' => true]));
    }

    public function test_next_run_at_offsets_from_base(): void
    {
        $seq  = ['steps' => [
            ['delay_value' => 0, 'delay_unit' => 'minutes', 'message' => 'now'],
            ['delay_value' => 2, 'delay_unit' => 'days', 'message' => 'later'],
        ]];
        $base = 1000000;
        $this->assertSame($base, \zignites_chat_seq_next_run_at($seq, 0, $base));
        $this->assertSame($base + 2 * DAY_IN_SECONDS, \zignites_chat_seq_next_run_at($seq, 1, $base));
        // Out-of-range step → 0 (sentinel for "no more steps").
        $this->assertSame(0, \zignites_chat_seq_next_run_at($seq, 9, $base));
    }

    public function test_render_message_substitutes(): void
    {
        $out = \zignites_chat_seq_render_message('Hi {name} from {site}', ['{name}' => 'Ana', '{site}' => 'Shop']);
        $this->assertSame('Hi Ana from Shop', $out);
        $this->assertSame('literal', \zignites_chat_seq_render_message('literal', 'nope'));
    }

    public function test_sanitize_step_drops_empty_and_normalizes(): void
    {
        $this->assertNull(\zignites_chat_seq_sanitize_step(['message' => '   ']));
        $this->assertNull(\zignites_chat_seq_sanitize_step('nope'));

        $step = \zignites_chat_seq_sanitize_step(['delay_value' => -3, 'delay_unit' => 'weeks', 'message' => 'Hello']);
        $this->assertSame(0, $step['delay_value']);
        $this->assertSame('minutes', $step['delay_unit']); // invalid unit falls back
        $this->assertSame('Hello', $step['message']);
    }

    public function test_sanitize_sequences_filters_and_dedupes(): void
    {
        $raw = [
            ['id' => 'welcome', 'name' => 'Welcome', 'enabled' => 'yes', 'trigger' => 'order_completed',
                'steps' => [['delay_value' => 1, 'delay_unit' => 'days', 'message' => 'Thanks!']]],
            ['id' => '', 'trigger' => 'optin', 'steps' => [['message' => 'x']]],            // no id → dropped
            ['id' => 'bad', 'trigger' => 'not_a_trigger', 'steps' => [['message' => 'x']]],  // bad trigger → dropped
            ['id' => 'nosteps', 'trigger' => 'optin', 'steps' => []],                        // no usable steps → dropped
            ['id' => 'welcome', 'name' => 'Override', 'trigger' => 'optin',                  // dup id → last wins
                'steps' => [['message' => 'Hi']]],
        ];
        $clean = \zignites_chat_seq_sanitize_sequences($raw);
        $this->assertCount(1, $clean);
        $this->assertSame('welcome', $clean[0]['id']);
        $this->assertSame('Override', $clean[0]['name']);
        $this->assertSame('optin', $clean[0]['trigger']);
        $this->assertSame('no', $clean[0]['enabled']); // last entry had no enabled key
    }

    public function test_sanitize_sequences_non_array(): void
    {
        $this->assertSame([], \zignites_chat_seq_sanitize_sequences('nope'));
    }

    public function test_format_mysql_is_utc_deterministic(): void
    {
        $this->assertSame('1970-01-01 00:00:00', \zignites_chat_seq_format_mysql(0));
        $this->assertSame('1970-01-12 13:46:40', \zignites_chat_seq_format_mysql(1000000));
    }

    public function test_build_enrollment_row_schedules_first_step(): void
    {
        $seq = [
            'id'    => 'welcome',
            'steps' => [['delay_value' => 2, 'delay_unit' => 'days', 'message' => 'hi']],
        ];
        $base = 1000000;
        $row  = \zignites_chat_seq_build_enrollment_row($seq, '15551234567', $base, ['{name}' => 'Ana']);

        $this->assertSame('welcome', $row['sequence_id']);
        $this->assertSame('15551234567', $row['phone']);
        $this->assertSame('active', $row['status']);
        $this->assertSame(0, $row['current_step']);
        $this->assertNull($row['last_step_at']);
        $this->assertSame(\zignites_chat_seq_format_mysql($base), $row['enrolled_at']);
        $this->assertSame(\zignites_chat_seq_format_mysql($base + 2 * DAY_IN_SECONDS), $row['next_run_at']);
        $this->assertSame('{"{name}":"Ana"}', $row['context']);
    }

    public function test_build_enrollment_row_zero_delay_runs_at_enroll(): void
    {
        $seq = ['id' => 's', 'steps' => [['delay_value' => 0, 'delay_unit' => 'minutes', 'message' => 'x']]];
        $row = \zignites_chat_seq_build_enrollment_row($seq, '123', 555, []);
        $this->assertSame($row['enrolled_at'], $row['next_run_at']);
        $this->assertSame('[]', $row['context']);
    }

    public function test_plan_advance_schedules_next_step(): void
    {
        $seq = ['id' => 'w', 'steps' => [
            ['delay_value' => 0, 'delay_unit' => 'minutes', 'message' => 'a'],
            ['delay_value' => 1, 'delay_unit' => 'hours', 'message' => 'b'],
        ]];
        $now  = 2000000;
        $plan = \zignites_chat_seq_plan_advance($seq, 0, $now);

        $this->assertSame('active', $plan['status']);
        $this->assertSame(1, $plan['current_step']);
        $this->assertSame(\zignites_chat_seq_format_mysql($now + HOUR_IN_SECONDS), $plan['next_run_at']);
        $this->assertSame(\zignites_chat_seq_format_mysql($now), $plan['last_step_at']);
    }

    public function test_plan_advance_completes_after_last_step(): void
    {
        $seq = ['id' => 'w', 'steps' => [
            ['delay_value' => 0, 'delay_unit' => 'minutes', 'message' => 'a'],
        ]];
        $now  = 2000000;
        $plan = \zignites_chat_seq_plan_advance($seq, 0, $now);

        $this->assertSame('completed', $plan['status']);
        $this->assertSame(1, $plan['current_step']);
        $this->assertNull($plan['next_run_at']);
        $this->assertSame(\zignites_chat_seq_format_mysql($now), $plan['last_step_at']);
    }

    public function test_shape_counts_buckets_by_sequence_and_status(): void
    {
        $rows = [
            ['sequence_id' => 'welcome', 'status' => 'active', 'c' => 3],
            ['sequence_id' => 'welcome', 'status' => 'completed', 'c' => 5],
            ['sequence_id' => 'welcome', 'status' => 'cancelled', 'c' => 1],
            ['sequence_id' => 'winback', 'status' => 'active', 'c' => 2],
            ['sequence_id' => 'winback', 'status' => 'weird', 'c' => 4], // unknown status: only totalled
        ];
        $out = \zignites_chat_seq_shape_counts($rows);

        $this->assertSame(['active' => 3, 'completed' => 5, 'cancelled' => 1, 'total' => 9], $out['welcome']);
        $this->assertSame(2, $out['winback']['active']);
        $this->assertSame(6, $out['winback']['total']);
    }

    public function test_shape_counts_handles_junk(): void
    {
        $this->assertSame([], \zignites_chat_seq_shape_counts('nope'));
        $this->assertSame([], \zignites_chat_seq_shape_counts([['status' => 'active', 'c' => 1]])); // no sequence_id
    }

    public function test_winback_window_brackets_the_slice(): void
    {
        $now = 100 * DAY_IN_SECONDS;
        $win = \zignites_chat_seq_winback_window($now, 60);
        $this->assertSame($now - 61 * DAY_IN_SECONDS, $win['after']);
        $this->assertSame($now - 60 * DAY_IN_SECONDS, $win['before']);
        // The slice is always exactly one day wide.
        $this->assertSame(DAY_IN_SECONDS, $win['before'] - $win['after']);
    }

    public function test_winback_window_clamps_days(): void
    {
        $now = 1000000;
        $win = \zignites_chat_seq_winback_window($now, 0); // clamps to 1
        $this->assertSame($now - 2 * DAY_IN_SECONDS, $win['after']);
        $this->assertSame($now - 1 * DAY_IN_SECONDS, $win['before']);
    }
}
