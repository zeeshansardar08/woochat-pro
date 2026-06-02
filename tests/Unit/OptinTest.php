<?php
declare(strict_types=1);

namespace ZignitesChat\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class OptinTest extends TestCase
{
    public function test_log_add_inserts_and_updates(): void
    {
        $log = \zignites_chat_optin_log_add([], '14155550100', '2026-06-03 10:00:00', 'checkout');
        $this->assertSame(['time' => '2026-06-03 10:00:00', 'source' => 'checkout'], $log['14155550100']);

        // Re-adding the same phone refreshes the entry, not a duplicate key.
        $log = \zignites_chat_optin_log_add($log, '14155550100', '2026-06-03 11:00:00', 'account');
        $this->assertCount(1, $log);
        $this->assertSame('2026-06-03 11:00:00', $log['14155550100']['time']);
        $this->assertSame('account', $log['14155550100']['source']);
    }

    public function test_log_add_ignores_empty_phone_and_bad_log(): void
    {
        $this->assertSame([], \zignites_chat_optin_log_add([], '', 't', 's'));
        $this->assertSame(
            ['14155550100' => ['time' => 't', 'source' => 's']],
            \zignites_chat_optin_log_add('not-an-array', '14155550100', 't', 's')
        );
    }

    public function test_decide_blocked_opted_out_always_blocks(): void
    {
        // Opted out → blocked regardless of consent / requirement.
        $this->assertTrue(\zignites_chat_optin_decide_blocked(true, false, true));
        $this->assertTrue(\zignites_chat_optin_decide_blocked(true, true, true));
    }

    public function test_decide_blocked_consent_required(): void
    {
        // Required + no consent → blocked.
        $this->assertTrue(\zignites_chat_optin_decide_blocked(false, true, false));
        // Required + has consent → allowed.
        $this->assertFalse(\zignites_chat_optin_decide_blocked(false, true, true));
    }

    public function test_decide_blocked_not_required_allows_without_consent(): void
    {
        $this->assertFalse(\zignites_chat_optin_decide_blocked(false, false, false));
        $this->assertFalse(\zignites_chat_optin_decide_blocked(false, false, true));
    }
}
