<?php
declare(strict_types=1);

namespace ZignitesChat\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class InboxNotifyTest extends TestCase
{
    public function test_recipient_ids_assigned_thread_notifies_agent_only(): void
    {
        $this->assertSame([5], \zignites_chat_inbox_notify_recipient_ids(5, [1, 2, 3]));
    }

    public function test_recipient_ids_unassigned_notifies_all_managers(): void
    {
        $this->assertSame([1, 2, 3], \zignites_chat_inbox_notify_recipient_ids(0, [1, 2, 3]));
        // Deduped + cast to int.
        $this->assertSame([1, 2], \zignites_chat_inbox_notify_recipient_ids(0, ['1', 2, '2', 1]));
        $this->assertSame([], \zignites_chat_inbox_notify_recipient_ids(0, 'nope'));
    }

    public function test_should_send_throttle(): void
    {
        $window = 900; // 15 min
        // Never notified → send.
        $this->assertTrue(\zignites_chat_inbox_notify_should_send(0, 1000, $window));
        // Within the window → throttled.
        $this->assertFalse(\zignites_chat_inbox_notify_should_send(1000, 1000 + 600, $window));
        // Exactly at the window boundary → send.
        $this->assertTrue(\zignites_chat_inbox_notify_should_send(1000, 1000 + 900, $window));
        // Past the window → send.
        $this->assertTrue(\zignites_chat_inbox_notify_should_send(1000, 1000 + 1200, $window));
    }
}
