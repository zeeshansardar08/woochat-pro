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
        foreach (['all_customers', 'recent_orders', 'product_purchased', 'category_purchased', 'min_spend', 'location', 'win_back'] as $key) {
            $this->assertArrayHasKey($key, $types);
        }
    }

    public function test_order_match_product(): void
    {
        $order = ['product_ids' => [10, 20, 30], 'category_ids' => [], 'country' => ''];
        $this->assertTrue(\zignites_chat_campaign_order_contributes_match($order, 'product_purchased', ['product_ids' => [20]]));
        $this->assertFalse(\zignites_chat_campaign_order_contributes_match($order, 'product_purchased', ['product_ids' => [99]]));
        $this->assertFalse(\zignites_chat_campaign_order_contributes_match($order, 'product_purchased', ['product_ids' => []]));
    }

    public function test_order_match_category(): void
    {
        $order = ['product_ids' => [], 'category_ids' => [5, 7], 'country' => ''];
        $this->assertTrue(\zignites_chat_campaign_order_contributes_match($order, 'category_purchased', ['category_ids' => [7, 8]]));
        $this->assertFalse(\zignites_chat_campaign_order_contributes_match($order, 'category_purchased', ['category_ids' => [8]]));
    }

    public function test_order_match_location_is_case_insensitive(): void
    {
        $order = ['product_ids' => [], 'category_ids' => [], 'country' => 'gb'];
        $this->assertTrue(\zignites_chat_campaign_order_contributes_match($order, 'location', ['countries' => ['US', 'GB']]));
        $this->assertFalse(\zignites_chat_campaign_order_contributes_match($order, 'location', ['countries' => ['US']]));
        $empty = ['product_ids' => [], 'category_ids' => [], 'country' => ''];
        $this->assertFalse(\zignites_chat_campaign_order_contributes_match($empty, 'location', ['countries' => ['US']]));
    }

    public function test_order_match_returns_false_for_aggregate_segments(): void
    {
        $order = ['product_ids' => [1], 'category_ids' => [1], 'country' => 'US'];
        $this->assertFalse(\zignites_chat_campaign_order_contributes_match($order, 'min_spend', []));
        $this->assertFalse(\zignites_chat_campaign_order_contributes_match($order, 'all_customers', []));
    }

    public function test_qualifies_min_spend(): void
    {
        $now = 1_800_000_000;
        $this->assertTrue(\zignites_chat_campaign_phone_qualifies(['spend' => 150.0], 'min_spend', ['min_spend' => 100], $now));
        $this->assertFalse(\zignites_chat_campaign_phone_qualifies(['spend' => 50.0], 'min_spend', ['min_spend' => 100], $now));
        // A zero/absent threshold qualifies nobody (misconfiguration guard).
        $this->assertFalse(\zignites_chat_campaign_phone_qualifies(['spend' => 999.0], 'min_spend', ['min_spend' => 0], $now));
    }

    public function test_qualifies_win_back(): void
    {
        $now = 1_800_000_000;
        $old = $now - (40 * DAY_IN_SECONDS);
        $recent = $now - (5 * DAY_IN_SECONDS);
        $this->assertTrue(\zignites_chat_campaign_phone_qualifies(['last_ts' => $old], 'win_back', ['days' => 30], $now));
        $this->assertFalse(\zignites_chat_campaign_phone_qualifies(['last_ts' => $recent], 'win_back', ['days' => 30], $now));
        // Never ordered (last_ts 0) does not qualify.
        $this->assertFalse(\zignites_chat_campaign_phone_qualifies(['last_ts' => 0], 'win_back', ['days' => 30], $now));
    }

    public function test_qualifies_match_segments_use_matched_flag(): void
    {
        $now = 1_800_000_000;
        $this->assertTrue(\zignites_chat_campaign_phone_qualifies(['matched' => true], 'product_purchased', [], $now));
        $this->assertFalse(\zignites_chat_campaign_phone_qualifies(['matched' => false], 'location', [], $now));
    }

    public function test_qualifies_all_and_recent_always_true(): void
    {
        $now = 1_800_000_000;
        $this->assertTrue(\zignites_chat_campaign_phone_qualifies([], 'all_customers', [], $now));
        $this->assertTrue(\zignites_chat_campaign_phone_qualifies([], 'recent_orders', [], $now));
    }

    public function test_csv_to_int_ids(): void
    {
        $this->assertSame([42, 108, 256], \zignites_chat_csv_to_int_ids('42, 108 ,256'));
        $this->assertSame([5], \zignites_chat_csv_to_int_ids('5, 0, -3, abc'));
        $this->assertSame([7], \zignites_chat_csv_to_int_ids('7, 7, 7'));
        $this->assertSame([], \zignites_chat_csv_to_int_ids(''));
    }

    public function test_build_segment_meta_product_and_category(): void
    {
        $this->assertSame(
            ['product_ids' => [10, 20]],
            \zignites_chat_build_campaign_segment_meta('product_purchased', ['product_ids' => '10, 20'])
        );
        $this->assertSame(
            ['category_ids' => [3]],
            \zignites_chat_build_campaign_segment_meta('category_purchased', ['category_ids' => '3'])
        );
    }

    public function test_build_segment_meta_min_spend_and_winback(): void
    {
        $this->assertSame(['min_spend' => 99.5], \zignites_chat_build_campaign_segment_meta('min_spend', ['min_spend' => '99.5']));
        $this->assertSame(['days' => 45], \zignites_chat_build_campaign_segment_meta('win_back', ['winback_days' => '45']));
        // Defaults when blank.
        $this->assertSame(['days' => 60], \zignites_chat_build_campaign_segment_meta('win_back', []));
    }

    public function test_build_segment_meta_location_validates_codes(): void
    {
        $meta = \zignites_chat_build_campaign_segment_meta('location', ['countries' => 'us, GB, x, 12, ae']);
        $this->assertSame(['countries' => ['US', 'GB', 'AE']], $meta);
    }

    public function test_build_segment_meta_unknown_is_empty(): void
    {
        $this->assertSame([], \zignites_chat_build_campaign_segment_meta('all_customers', []));
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
