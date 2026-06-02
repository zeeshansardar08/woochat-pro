<?php
declare(strict_types=1);

namespace ZignitesChat\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class InboxAgentTest extends TestCase
{
    public function test_scope_to_agent_filter(): void
    {
        // 'all' / empty → no filter (null).
        $this->assertNull(\zignites_chat_inbox_scope_to_agent_filter('all', 7));
        $this->assertNull(\zignites_chat_inbox_scope_to_agent_filter('', 7));
        // 'mine' → current user.
        $this->assertSame(7, \zignites_chat_inbox_scope_to_agent_filter('mine', 7));
        // 'unassigned' → 0.
        $this->assertSame(0, \zignites_chat_inbox_scope_to_agent_filter('unassigned', 7));
        // Numeric → that agent.
        $this->assertSame(42, \zignites_chat_inbox_scope_to_agent_filter('42', 7));
        // Garbage → no filter.
        $this->assertNull(\zignites_chat_inbox_scope_to_agent_filter('bogus', 7));
    }

    public function test_agent_name(): void
    {
        $agents = [3 => 'Alice', 9 => 'Bob'];
        $this->assertSame('', \zignites_chat_inbox_agent_name(0, $agents));      // unassigned
        $this->assertSame('Alice', \zignites_chat_inbox_agent_name(3, $agents));
        // Unknown id → fallback label (stubbed __ returns the format string).
        $this->assertSame('User #5', \zignites_chat_inbox_agent_name(5, $agents));
    }

    public function test_present_thread_includes_agent_id(): void
    {
        $present = \zignites_chat_inbox_present_thread(['id' => 1, 'agent_id' => '4']);
        $this->assertSame(4, $present['agent_id']);
        // Absent agent_id defaults to 0 (unassigned).
        $this->assertSame(0, \zignites_chat_inbox_present_thread(['id' => 1])['agent_id']);
    }
}
