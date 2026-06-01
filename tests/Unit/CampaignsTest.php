<?php
declare(strict_types=1);

namespace ZignitesChat\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class CampaignsTest extends TestCase
{
    protected function setUp(): void
    {
        $GLOBALS['zignites_chat_test_options'] = [];
        $GLOBALS['zignites_chat_test_currency_symbol'] = '&#36;';
        $GLOBALS['zignites_chat_test_bloginfo'] = ['name' => 'Acme Store'];
    }

    public function test_render_substitutes_supported_placeholders(): void
    {
        $template = 'Hi {name}, big news from {site}! Save {currency_symbol}10.';
        $result = \zignites_chat_campaign_render_message($template, 'Sara');

        $this->assertSame('Hi Sara, big news from Acme Store! Save $10.', $result);
    }

    public function test_render_handles_blank_name_and_site(): void
    {
        $GLOBALS['zignites_chat_test_bloginfo'] = ['name' => ''];
        $result = \zignites_chat_campaign_render_message('Hello {name} from {site}', '');
        $this->assertSame('Hello  from ', $result);
    }

    public function test_render_does_not_substitute_unsupported_placeholders(): void
    {
        // Bulk sends have no order/cart context — order placeholders must
        // pass through untouched rather than being silently blanked.
        $result = \zignites_chat_campaign_render_message('Order {order_id} total {total}', 'X');
        $this->assertSame('Order {order_id} total {total}', $result);
    }

    public function test_segment_types_lists_expected_segments(): void
    {
        $types = \zignites_chat_campaign_segment_types();
        $this->assertArrayHasKey('all_customers', $types);
        $this->assertArrayHasKey('recent_orders', $types);
    }

    public function test_normalize_datetime(): void
    {
        $this->assertSame('2026-06-10 09:30:00', \zignites_chat_normalize_datetime('2026-06-10T09:30'));
        $this->assertSame('2026-06-10 09:30:00', \zignites_chat_normalize_datetime('2026-06-10 09:30'));
        $this->assertSame('2026-06-10 09:30:45', \zignites_chat_normalize_datetime('2026-06-10 09:30:45'));
        $this->assertSame('', \zignites_chat_normalize_datetime(''));
        $this->assertSame('', \zignites_chat_normalize_datetime('not a date'));
        $this->assertSame('', \zignites_chat_normalize_datetime('2026-13-40 99:99'));
    }

    public function test_resolve_schedule_future_is_scheduled(): void
    {
        $result = \zignites_chat_campaign_resolve_schedule('2026-06-10T09:30', '2026-06-01 12:00:00');
        $this->assertSame('scheduled', $result['status']);
        $this->assertSame('2026-06-10 09:30:00', $result['scheduled_at']);
    }

    public function test_resolve_schedule_past_or_now_is_queued(): void
    {
        $past = \zignites_chat_campaign_resolve_schedule('2026-05-01 09:30', '2026-06-01 12:00:00');
        $this->assertSame('queued', $past['status']);
        $this->assertNull($past['scheduled_at']);

        // Exactly now (not strictly future) sends immediately.
        $now = \zignites_chat_campaign_resolve_schedule('2026-06-01 12:00:00', '2026-06-01 12:00:00');
        $this->assertSame('queued', $now['status']);
    }

    public function test_resolve_schedule_blank_is_queued(): void
    {
        $result = \zignites_chat_campaign_resolve_schedule('', '2026-06-01 12:00:00');
        $this->assertSame('queued', $result['status']);
        $this->assertNull($result['scheduled_at']);
    }

    public function test_resolve_schedule_invalid_is_queued(): void
    {
        $result = \zignites_chat_campaign_resolve_schedule('garbage', '2026-06-01 12:00:00');
        $this->assertSame('queued', $result['status']);
        $this->assertNull($result['scheduled_at']);
    }

    public function test_filter_excluded_removes_matching_phones(): void
    {
        $recipients = [
            ['phone' => '15551110001', 'name' => 'A'],
            ['phone' => '15551110002', 'name' => 'B'],
            ['phone' => '15551110003', 'name' => 'C'],
        ];
        $out = \zignites_chat_campaign_filter_excluded($recipients, ['15551110002']);
        $this->assertCount(2, $out);
        $this->assertSame('15551110001', $out[0]['phone']);
        $this->assertSame('15551110003', $out[1]['phone']);
    }

    public function test_filter_excluded_empty_exclusion_returns_all(): void
    {
        $recipients = [['phone' => '15551110001', 'name' => 'A']];
        $this->assertSame($recipients, \zignites_chat_campaign_filter_excluded($recipients, []));
    }

    public function test_filter_excluded_handles_non_array(): void
    {
        $this->assertSame([], \zignites_chat_campaign_filter_excluded('nope', ['1']));
    }
}
