<?php
declare(strict_types=1);

namespace ZignitesChat\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class RateLimiterTest extends TestCase
{
    public function test_fresh_state_starts_a_window_and_allows(): void
    {
        $r = \zignites_chat_rate_window_evaluate([], 1000, 60, 60);
        $this->assertTrue($r['allowed']);
        $this->assertSame(['count' => 1, 'start' => 1000], $r['state']);
    }

    public function test_allows_until_max_within_window(): void
    {
        // 59 already used in a window that started at t=1000, now t=1010.
        $r = \zignites_chat_rate_window_evaluate(['count' => 59, 'start' => 1000], 1010, 60, 60);
        $this->assertTrue($r['allowed']);
        $this->assertSame(['count' => 60, 'start' => 1000], $r['state']);
    }

    public function test_denies_when_max_reached_and_leaves_state_unchanged(): void
    {
        $state = ['count' => 60, 'start' => 1000];
        $r = \zignites_chat_rate_window_evaluate($state, 1010, 60, 60);
        $this->assertFalse($r['allowed']);
        $this->assertSame($state, $r['state']);
    }

    public function test_window_resets_after_it_elapses(): void
    {
        // Saturated window from t=1000; now t=1060 (>= 60s later) → reset.
        $r = \zignites_chat_rate_window_evaluate(['count' => 60, 'start' => 1000], 1060, 60, 60);
        $this->assertTrue($r['allowed']);
        $this->assertSame(['count' => 1, 'start' => 1060], $r['state']);
    }

    public function test_boundary_just_before_reset_still_denies(): void
    {
        // 1s before the window elapses, still saturated → deny.
        $r = \zignites_chat_rate_window_evaluate(['count' => 60, 'start' => 1000], 1059, 60, 60);
        $this->assertFalse($r['allowed']);
    }

    public function test_max_is_floored_to_one(): void
    {
        // A nonsensical max of 0 must not deny the very first send.
        $r = \zignites_chat_rate_window_evaluate([], 1000, 0, 60);
        $this->assertTrue($r['allowed']);
    }
}
