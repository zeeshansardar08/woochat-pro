<?php
declare(strict_types=1);

namespace ZignitesChat\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class AnalyticsTest extends TestCase
{
    private function event(string $id, string $phone, int $time): array
    {
        return ['event_id' => $id, 'phone_norm' => $phone, 'time' => $time];
    }

    private function order(int $id, string $phone, int $time, float $total): array
    {
        return ['order_id' => $id, 'phone_norm' => $phone, 'time' => $time, 'total' => $total];
    }

    public function test_match_conversions_attributes_order_within_window(): void
    {
        $t = 1_700_000_000;
        $events = [$this->event('e1', '15551234567', $t)];
        $orders = [$this->order(101, '15551234567', $t + (2 * DAY_IN_SECONDS), 49.99)];

        $result = \zignites_chat_analytics_match_conversions($events, $orders, 7 * DAY_IN_SECONDS);

        $this->assertSame(1, $result['conversions']);
        $this->assertEqualsWithDelta(49.99, $result['revenue'], 0.0001);
        $this->assertSame(['e1'], array_values($result['matched']));
    }

    public function test_match_conversions_ignores_orders_outside_window(): void
    {
        $t = 1_700_000_000;
        $events = [$this->event('e1', '15551234567', $t)];
        $orders = [$this->order(101, '15551234567', $t + (8 * DAY_IN_SECONDS), 49.99)];

        $result = \zignites_chat_analytics_match_conversions($events, $orders, 7 * DAY_IN_SECONDS);

        $this->assertSame(0, $result['conversions']);
        $this->assertSame(0.0, $result['revenue']);
    }

    public function test_match_conversions_ignores_orders_before_event(): void
    {
        $t = 1_700_000_000;
        $events = [$this->event('e1', '15551234567', $t)];
        // Pre-existing customer history — must not be attributed.
        $orders = [$this->order(101, '15551234567', $t - 3600, 49.99)];

        $result = \zignites_chat_analytics_match_conversions($events, $orders, 7 * DAY_IN_SECONDS);

        $this->assertSame(0, $result['conversions']);
    }

    public function test_match_conversions_first_event_wins_for_shared_phone(): void
    {
        $t = 1_700_000_000;
        // Two events to same phone. Only one matching order — earlier event claims it.
        $events = [
            $this->event('late',  '15551234567', $t + 3600),
            $this->event('early', '15551234567', $t),
        ];
        $orders = [$this->order(101, '15551234567', $t + 7200, 25.0)];

        $result = \zignites_chat_analytics_match_conversions($events, $orders, 7 * DAY_IN_SECONDS);

        $this->assertSame(1, $result['conversions']);
        $this->assertSame(['early'], array_values($result['matched']));
    }

    public function test_match_conversions_does_not_double_count_single_order(): void
    {
        $t = 1_700_000_000;
        $events = [
            $this->event('e1', '15551234567', $t),
            $this->event('e2', '15551234567', $t + 60),
        ];
        $orders = [$this->order(101, '15551234567', $t + 3600, 30.0)];

        $result = \zignites_chat_analytics_match_conversions($events, $orders, 7 * DAY_IN_SECONDS);

        $this->assertSame(1, $result['conversions']);
        $this->assertEqualsWithDelta(30.0, $result['revenue'], 0.0001);
    }

    public function test_match_conversions_two_phones_two_orders_two_conversions(): void
    {
        $t = 1_700_000_000;
        $events = [
            $this->event('e1', '15551111111', $t),
            $this->event('e2', '15552222222', $t + 100),
        ];
        $orders = [
            $this->order(101, '15551111111', $t + 3600, 10.0),
            $this->order(102, '15552222222', $t + 7200, 20.0),
        ];

        $result = \zignites_chat_analytics_match_conversions($events, $orders, 7 * DAY_IN_SECONDS);

        $this->assertSame(2, $result['conversions']);
        $this->assertEqualsWithDelta(30.0, $result['revenue'], 0.0001);
    }

    public function test_match_conversions_zero_window_returns_empty(): void
    {
        $t = 1_700_000_000;
        $events = [$this->event('e1', '15551234567', $t)];
        $orders = [$this->order(101, '15551234567', $t + 60, 5.0)];

        $result = \zignites_chat_analytics_match_conversions($events, $orders, 0);

        $this->assertSame(0, $result['conversions']);
        $this->assertSame(0.0, $result['revenue']);
    }

    public function test_match_conversions_skips_events_with_empty_phone(): void
    {
        $t = 1_700_000_000;
        $events = [$this->event('e1', '', $t)];
        $orders = [$this->order(101, '15551234567', $t + 60, 5.0)];

        $result = \zignites_chat_analytics_match_conversions($events, $orders, DAY_IN_SECONDS);

        $this->assertSame(0, $result['conversions']);
    }

    public function test_bucket_revenue_by_type_groups_and_sums(): void
    {
        $matched = [
            101 => 'e1', // cart_recovery
            102 => 'e2', // cart_recovery
            103 => 'e3', // followup
        ];
        $event_types = ['e1' => 'cart_recovery', 'e2' => 'cart_recovery', 'e3' => 'followup'];
        $order_totals = [101 => 50.0, 102 => 25.0, 103 => 80.0];

        $out = \zignites_chat_analytics_bucket_revenue_by_type($matched, $event_types, $order_totals);

        $this->assertSame(2, $out['cart_recovery']['conversions']);
        $this->assertEqualsWithDelta(75.0, $out['cart_recovery']['revenue'], 0.0001);
        $this->assertSame(1, $out['followup']['conversions']);
        $this->assertEqualsWithDelta(80.0, $out['followup']['revenue'], 0.0001);
    }

    public function test_bucket_revenue_unknown_type_and_missing_total(): void
    {
        $matched = [201 => 'eX', 202 => 'eY'];
        $event_types = ['eX' => '']; // empty -> unknown; eY absent -> unknown
        $order_totals = [201 => 10.0]; // 202 missing -> 0

        $out = \zignites_chat_analytics_bucket_revenue_by_type($matched, $event_types, $order_totals);

        $this->assertArrayHasKey('unknown', $out);
        $this->assertSame(2, $out['unknown']['conversions']);
        $this->assertEqualsWithDelta(10.0, $out['unknown']['revenue'], 0.0001);
    }

    public function test_bucket_revenue_handles_empty(): void
    {
        $this->assertSame([], \zignites_chat_analytics_bucket_revenue_by_type([], [], []));
        $this->assertSame([], \zignites_chat_analytics_bucket_revenue_by_type('nope', [], []));
    }
}
