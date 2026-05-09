<?php
declare(strict_types=1);

namespace WooChatPro\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class PrivacyTest extends TestCase
{
    public function test_phone_match_suffix_returns_last_eight_digits(): void
    {
        $this->assertSame('12345678', \wcwp_privacy_phone_match_suffix('+1 (555) 412-345-678'));
    }

    public function test_phone_match_suffix_returns_full_string_when_short(): void
    {
        $this->assertSame('1234567', \wcwp_privacy_phone_match_suffix('1234567'));
    }

    public function test_phone_match_suffix_strips_non_digits(): void
    {
        $this->assertSame('23456789', \wcwp_privacy_phone_match_suffix('+92 (300) 123-456-789'));
    }

    public function test_phone_match_suffix_returns_empty_for_no_digits(): void
    {
        $this->assertSame('', \wcwp_privacy_phone_match_suffix('abc'));
        $this->assertSame('', \wcwp_privacy_phone_match_suffix(''));
    }

    public function test_format_event_row_returns_well_formed_export_item(): void
    {
        $row = [
            'event_id'        => 'evt_123',
            'type'            => 'order',
            'status'          => 'sent',
            'phone'           => '+15551234567',
            'order_id'        => 42,
            'message_preview' => 'Thanks for your order!',
            'provider'        => 'twilio',
            'message_id'      => 'SMxxx',
            'created_at'      => '2026-05-09 12:34:56',
        ];
        $item = \wcwp_privacy_format_event_row($row);

        $this->assertSame('woochat-pro-events', $item['group_id']);
        $this->assertSame('wcwp-event-evt_123', $item['item_id']);
        $this->assertNotSame('', $item['group_label']);

        $byName = [];
        foreach ($item['data'] as $field) $byName[$field['name']] = $field['value'];
        $this->assertSame('order', $byName['Type']);
        $this->assertSame('sent', $byName['Status']);
        $this->assertSame('+15551234567', $byName['Phone']);
        $this->assertSame('42', $byName['Order ID']);
        $this->assertSame('twilio', $byName['Provider']);
        $this->assertSame('SMxxx', $byName['Message ID']);
        $this->assertSame('Thanks for your order!', $byName['Preview']);
        $this->assertSame('2026-05-09 12:34:56', $byName['Date']);
    }

    public function test_format_event_row_handles_missing_fields_gracefully(): void
    {
        $item = \wcwp_privacy_format_event_row(['event_id' => 'evt_x']);

        $this->assertSame('wcwp-event-evt_x', $item['item_id']);
        // All eight fields are present even when source row is sparse —
        // WP privacy export tolerates blank values but expects the schema.
        $this->assertCount(8, $item['data']);
    }

    public function test_format_cart_row_returns_well_formed_export_item(): void
    {
        $row = [
            'id'         => 7,
            'phone'      => '+15551234567',
            'total'      => '49.95',
            'cart_json'  => '[{"name":"Widget"}]',
            'status'     => 'pending',
            'consent'    => 'yes',
            'attempts'   => 1,
            'created_at' => '2026-05-09 12:34:56',
        ];
        $item = \wcwp_privacy_format_cart_row($row);

        $this->assertSame('woochat-pro-carts', $item['group_id']);
        $this->assertSame('wcwp-cart-7', $item['item_id']);

        $byName = [];
        foreach ($item['data'] as $field) $byName[$field['name']] = $field['value'];
        $this->assertSame('+15551234567', $byName['Phone']);
        $this->assertSame('49.95', $byName['Total']);
        $this->assertSame('[{"name":"Widget"}]', $byName['Items']);
        $this->assertSame('1', $byName['Attempts']);
    }

    public function test_format_campaign_recipient_row_uses_provided_campaign_name(): void
    {
        $row = [
            'id'            => 11,
            'campaign_id'   => 5,
            'phone'         => '+15551234567',
            'customer_name' => 'Jane Doe',
            'status'        => 'sent',
            'sent_at'       => '2026-05-09 12:34:56',
        ];
        $item = \wcwp_privacy_format_campaign_recipient_row($row, 'April promo blast');

        $this->assertSame('wcwp-campaign-recipient-11', $item['item_id']);

        $byName = [];
        foreach ($item['data'] as $field) $byName[$field['name']] = $field['value'];
        $this->assertSame('April promo blast', $byName['Campaign']);
        $this->assertSame('Jane Doe', $byName['Customer name']);
    }

    public function test_format_campaign_recipient_row_falls_back_to_campaign_id(): void
    {
        $row = [
            'id'          => 11,
            'campaign_id' => 5,
            'phone'       => '+15551234567',
        ];
        $item = \wcwp_privacy_format_campaign_recipient_row($row, '');

        $byName = [];
        foreach ($item['data'] as $field) $byName[$field['name']] = $field['value'];
        $this->assertSame('#5', $byName['Campaign']);
    }

    public function test_filter_rows_by_normalized_phone_drops_non_matches(): void
    {
        $rows = [
            ['id' => 1, 'phone' => '+1 (555) 123-4567'],   // matches
            ['id' => 2, 'phone' => '+1 (999) 000-0000'],   // does not match
            ['id' => 3, 'phone' => '15551234567'],          // matches (different format, same digits)
            ['id' => 4],                                     // skipped — no phone
            ['id' => 5, 'phone' => 'abc'],                  // skipped — empty after normalisation
        ];
        $lookup = ['15551234567' => true];

        $matched = \wcwp_privacy_filter_rows_by_normalized_phone($rows, $lookup);

        $this->assertCount(2, $matched);
        $this->assertSame([1, 3], array_column($matched, 'id'));
    }

    public function test_filter_rows_by_normalized_phone_returns_empty_for_empty_inputs(): void
    {
        $this->assertSame([], \wcwp_privacy_filter_rows_by_normalized_phone([], ['x' => true]));
        $this->assertSame([], \wcwp_privacy_filter_rows_by_normalized_phone([['phone' => 'x']], []));
    }
}
