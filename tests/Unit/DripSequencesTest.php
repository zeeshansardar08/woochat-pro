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
}
