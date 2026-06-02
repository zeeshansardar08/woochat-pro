<?php
declare(strict_types=1);

namespace ZignitesChat\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class QuietHoursTest extends TestCase
{
    public function test_parse_time_to_minutes(): void
    {
        $this->assertSame(0, \zignites_chat_parse_time_to_minutes('0:00'));
        $this->assertSame(1260, \zignites_chat_parse_time_to_minutes('21:00'));
        $this->assertSame(480, \zignites_chat_parse_time_to_minutes('08:00'));
        $this->assertSame(1439, \zignites_chat_parse_time_to_minutes('23:59'));
        $this->assertSame(-1, \zignites_chat_parse_time_to_minutes('24:00'));
        $this->assertSame(-1, \zignites_chat_parse_time_to_minutes('9:99'));
        $this->assertSame(-1, \zignites_chat_parse_time_to_minutes('nope'));
    }

    public function test_in_quiet_hours_daytime_window(): void
    {
        // 09:00–17:00 window.
        $start = 540;
        $end = 1020;
        $this->assertTrue(\zignites_chat_in_quiet_hours(600, $start, $end));   // 10:00
        $this->assertFalse(\zignites_chat_in_quiet_hours(540 - 1, $start, $end)); // just before
        $this->assertFalse(\zignites_chat_in_quiet_hours(1020, $start, $end));  // end is exclusive
        $this->assertFalse(\zignites_chat_in_quiet_hours(60, $start, $end));
    }

    public function test_in_quiet_hours_overnight_window(): void
    {
        // 21:00–08:00 overnight.
        $start = 1260;
        $end = 480;
        $this->assertTrue(\zignites_chat_in_quiet_hours(1300, $start, $end));  // 21:40
        $this->assertTrue(\zignites_chat_in_quiet_hours(120, $start, $end));   // 02:00
        $this->assertFalse(\zignites_chat_in_quiet_hours(600, $start, $end));  // 10:00
        $this->assertFalse(\zignites_chat_in_quiet_hours(480, $start, $end));  // 08:00 exclusive
    }

    public function test_in_quiet_hours_equal_start_end_is_off(): void
    {
        $this->assertFalse(\zignites_chat_in_quiet_hours(500, 600, 600));
    }

    public function test_minutes_until_end(): void
    {
        // Overnight 21:00–08:00. At 02:00 → 6h to 08:00.
        $this->assertSame(360, \zignites_chat_quiet_minutes_until_end(120, 1260, 480));
        // At 23:00 → to 08:00 next day = 9h = 540 (wraps past midnight).
        $this->assertSame(540, \zignites_chat_quiet_minutes_until_end(1380, 1260, 480));
        // Not in window → 0.
        $this->assertSame(0, \zignites_chat_quiet_minutes_until_end(600, 1260, 480));
    }

    public function test_sanitize_time(): void
    {
        $this->assertSame('21:00', \zignites_chat_quiet_sanitize_time('21:00'));
        $this->assertSame('08:05', \zignites_chat_quiet_sanitize_time('8:05'));
        $this->assertSame('', \zignites_chat_quiet_sanitize_time('bogus'));
    }
}
