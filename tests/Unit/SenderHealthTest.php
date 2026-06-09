<?php
declare(strict_types=1);

namespace ZignitesChat\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class SenderHealthTest extends TestCase
{
    public function test_endpoint_builds_phone_node_url(): void
    {
        $url = \zignites_chat_health_endpoint('109876');
        $this->assertStringContainsString('/109876?', $url);
        $this->assertStringContainsString('fields=quality_rating,messaging_limit_tier', $url);
        $this->assertStringContainsString('display_phone_number', $url);
    }

    public function test_endpoint_empty_phone_returns_empty(): void
    {
        $this->assertSame('', \zignites_chat_health_endpoint('   '));
    }

    public function test_normalize_maps_node_and_uppercases(): void
    {
        $out = \zignites_chat_health_normalize([
            'quality_rating'       => 'green',
            'messaging_limit_tier' => 'tier_1k',
            'display_phone_number' => '+1 555-0100',
            'verified_name'        => 'Acme Co',
            'name_status'          => 'approved',
        ]);
        $this->assertSame('GREEN', $out['quality_rating']);
        $this->assertSame('TIER_1K', $out['messaging_tier']);
        $this->assertSame('+1 555-0100', $out['display_phone_number']);
        $this->assertSame('Acme Co', $out['verified_name']);
        $this->assertSame('APPROVED', $out['name_status']);
    }

    public function test_normalize_defaults_missing_to_unknown(): void
    {
        $out = \zignites_chat_health_normalize([]);
        $this->assertSame('UNKNOWN', $out['quality_rating']);
        $this->assertSame('UNKNOWN', $out['messaging_tier']);
        $this->assertSame('', $out['display_phone_number']);

        $junk = \zignites_chat_health_normalize('nope');
        $this->assertSame('UNKNOWN', $junk['quality_rating']);
    }

    public function test_quality_meta_levels(): void
    {
        $this->assertSame('green', \zignites_chat_health_quality_meta('GREEN')['level']);
        $this->assertSame('yellow', \zignites_chat_health_quality_meta('yellow')['level']);
        $this->assertSame('red', \zignites_chat_health_quality_meta('RED')['level']);
        // Unknown / unrecognised ratings fall through to the grey "unknown" level.
        $this->assertSame('unknown', \zignites_chat_health_quality_meta('')['level']);
        $this->assertSame('unknown', \zignites_chat_health_quality_meta('NA')['level']);
        // Each level carries a label + colour.
        $green = \zignites_chat_health_quality_meta('GREEN');
        $this->assertSame('High', $green['label']);
        $this->assertSame('#46b450', $green['color']);
    }

    public function test_tier_label_maps_known_tiers(): void
    {
        $this->assertStringContainsString('1,000', \zignites_chat_health_tier_label('TIER_1K'));
        $this->assertStringContainsString('100,000', \zignites_chat_health_tier_label('tier_100k'));
        $this->assertSame('Unlimited', \zignites_chat_health_tier_label('TIER_UNLIMITED'));
    }

    public function test_tier_label_unknown_for_unrecognised(): void
    {
        $this->assertSame('Unknown', \zignites_chat_health_tier_label('TIER_WHATEVER'));
        $this->assertSame('Unknown', \zignites_chat_health_tier_label(''));
    }
}
