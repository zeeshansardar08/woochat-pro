<?php
declare(strict_types=1);

namespace ZignitesChat\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class StatusNotificationsTest extends TestCase
{
    public function test_normalize_strips_wc_prefix(): void
    {
        $this->assertSame('shipped', \zignites_chat_status_normalize('wc-shipped'));
        $this->assertSame('processing', \zignites_chat_status_normalize('processing'));
        $this->assertSame('', \zignites_chat_status_normalize(''));
    }

    public function test_should_notify(): void
    {
        $config = [
            'shipped'   => ['enabled' => 'yes', 'template' => 'Your order shipped!'],
            'on-hold'   => ['enabled' => 'no',  'template' => 'On hold'],
            'completed' => ['enabled' => 'yes', 'template' => '   '], // blank → no
        ];
        $this->assertTrue(\zignites_chat_status_should_notify('shipped', $config));
        $this->assertTrue(\zignites_chat_status_should_notify('wc-shipped', $config)); // normalized
        $this->assertFalse(\zignites_chat_status_should_notify('on-hold', $config));   // disabled
        $this->assertFalse(\zignites_chat_status_should_notify('completed', $config)); // blank template
        $this->assertFalse(\zignites_chat_status_should_notify('refunded', $config));  // absent
        $this->assertFalse(\zignites_chat_status_should_notify('shipped', 'nope'));
    }

    public function test_extract_tracking_reads_latest_item(): void
    {
        $items = [
            ['tracking_number' => 'OLD123', 'tracking_provider' => 'UPS'],
            [
                'tracking_number'            => 'TRK999',
                'formatted_tracking_provider'=> 'DHL',
                'formatted_tracking_link'    => 'https://track.dhl/TRK999',
            ],
        ];
        $t = \zignites_chat_extract_tracking($items);
        $this->assertSame('TRK999', $t['number']);
        $this->assertSame('DHL', $t['carrier']);
        $this->assertSame('https://track.dhl/TRK999', $t['url']);
    }

    public function test_extract_tracking_handles_empty_and_partial(): void
    {
        $this->assertSame(['number' => '', 'url' => '', 'carrier' => ''], \zignites_chat_extract_tracking([]));
        $this->assertSame(['number' => '', 'url' => '', 'carrier' => ''], \zignites_chat_extract_tracking('nope'));

        $partial = \zignites_chat_extract_tracking([['tracking_number' => 'N1']]);
        $this->assertSame('N1', $partial['number']);
        $this->assertSame('', $partial['url']);
        $this->assertSame('', $partial['carrier']);
    }

    public function test_render_substitutes_placeholders(): void
    {
        $out = \zignites_chat_status_render(
            'Hi {name}, order #{order_id} is {status}. Track: {tracking_url}',
            [
                '{name}'         => 'Sam',
                '{order_id}'     => 42,
                '{status}'       => 'Shipped',
                '{tracking_url}' => 'https://t/abc',
            ]
        );
        $this->assertSame('Hi Sam, order #42 is Shipped. Track: https://t/abc', $out);
    }

    public function test_sanitize_notifications(): void
    {
        $raw = [
            'shipped'   => ['enabled' => 'yes', 'template' => '  Shipped!  '],
            'on-hold'   => ['enabled' => 'maybe', 'template' => 'Hold'],
            ''          => ['enabled' => 'yes', 'template' => 'x'], // empty slug dropped
            'bad'       => 'not-an-array',                          // dropped
        ];
        $clean = \zignites_chat_status_sanitize_notifications($raw);
        $this->assertSame('yes', $clean['shipped']['enabled']);
        $this->assertSame('Shipped!', $clean['shipped']['template']);
        $this->assertSame('no', $clean['on-hold']['enabled']); // invalid → no
        $this->assertArrayNotHasKey('', $clean);
        $this->assertArrayNotHasKey('bad', $clean);
    }
}
