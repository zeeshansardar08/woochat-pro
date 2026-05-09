<?php
declare(strict_types=1);

namespace WooChatPro\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class WebhooksTest extends TestCase
{
    public function test_signature_uses_sha256_hmac_with_prefix(): void
    {
        $body = '{"event":"message.sent","data":{}}';
        $secret = 'shhh-this-is-a-test-secret';
        $expected = 'sha256=' . hash_hmac('sha256', $body, $secret);

        $this->assertSame($expected, \wcwp_webhook_signature($body, $secret));
    }

    public function test_signature_returns_empty_for_empty_body_or_secret(): void
    {
        $this->assertSame('', \wcwp_webhook_signature('', 'secret'));
        $this->assertSame('', \wcwp_webhook_signature('body', ''));
    }

    public function test_signature_is_stable_for_same_inputs(): void
    {
        $a = \wcwp_webhook_signature('payload', 'k');
        $b = \wcwp_webhook_signature('payload', 'k');
        $this->assertSame($a, $b);
    }

    public function test_signature_changes_with_body(): void
    {
        $a = \wcwp_webhook_signature('payload-a', 'k');
        $b = \wcwp_webhook_signature('payload-b', 'k');
        $this->assertNotSame($a, $b);
    }

    public function test_signature_changes_with_secret(): void
    {
        $a = \wcwp_webhook_signature('payload', 'k1');
        $b = \wcwp_webhook_signature('payload', 'k2');
        $this->assertNotSame($a, $b);
    }

    public function test_event_keys_includes_known_lifecycle_events(): void
    {
        $keys = array_keys(\wcwp_webhook_event_keys());
        $this->assertContains('message.sent', $keys);
        $this->assertContains('message.delivered', $keys);
        $this->assertContains('message.clicked', $keys);
        $this->assertContains('message.failed', $keys);
        $this->assertContains('customer.opted_out', $keys);
    }

    public function test_payload_envelope_has_event_fired_at_data(): void
    {
        $env = \wcwp_webhook_payload('message.sent', ['order_id' => 42]);

        $this->assertSame('message.sent', $env['event']);
        $this->assertArrayHasKey('fired_at', $env);
        $this->assertNotSame('', $env['fired_at']);
        // ISO 8601 with trailing Z
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/', $env['fired_at']);
        $this->assertSame(['order_id' => 42], $env['data']);
    }

    public function test_payload_coerces_non_array_data_to_array(): void
    {
        $env = \wcwp_webhook_payload('message.sent', null);
        $this->assertSame([], $env['data']);
    }

    public function test_sanitize_drops_invalid_url(): void
    {
        $this->assertNull(\wcwp_sanitize_webhook(['url' => '', 'events' => ['message.sent']]));
        $this->assertNull(\wcwp_sanitize_webhook(['url' => 'not-a-url', 'events' => ['message.sent']]));
        $this->assertNull(\wcwp_sanitize_webhook(['url' => 'ftp://example.com', 'events' => ['message.sent']]));
    }

    public function test_sanitize_drops_when_no_valid_events(): void
    {
        $this->assertNull(\wcwp_sanitize_webhook(['url' => 'https://example.com', 'events' => []]));
        $this->assertNull(\wcwp_sanitize_webhook(['url' => 'https://example.com', 'events' => ['nonsense.event']]));
    }

    public function test_sanitize_dedupes_and_filters_events(): void
    {
        $clean = \wcwp_sanitize_webhook([
            'url'    => 'https://example.com/wcwp',
            'events' => ['message.sent', 'message.sent', 'nonsense.event', 'message.delivered'],
            'id'     => 'wh_fixed',
            'secret' => 'static-secret',
            'created_at' => '2026-05-09 12:34:56',
        ]);

        $this->assertNotNull($clean);
        $this->assertSame(['message.sent', 'message.delivered'], $clean['events']);
        $this->assertSame('https://example.com/wcwp', $clean['url']);
        $this->assertSame('wh_fixed', $clean['id']);
        $this->assertSame('static-secret', $clean['secret']);
        $this->assertTrue($clean['active']);
    }

    public function test_sanitize_generates_id_and_secret_when_missing(): void
    {
        $clean = \wcwp_sanitize_webhook([
            'url'    => 'https://example.com/wcwp',
            'events' => ['message.sent'],
        ]);

        $this->assertNotNull($clean);
        $this->assertStringStartsWith('wh_', $clean['id']);
        $this->assertNotSame('', $clean['secret']);
    }

    public function test_filter_webhooks_for_event_returns_only_active_subscribed(): void
    {
        $webhooks = [
            ['id' => 'a', 'active' => true,  'events' => ['message.sent']],
            ['id' => 'b', 'active' => true,  'events' => ['customer.opted_out']],
            ['id' => 'c', 'active' => false, 'events' => ['message.sent']], // inactive — drop
            ['id' => 'd', 'active' => true,  'events' => ['message.sent', 'message.delivered']],
        ];

        $matches = \wcwp_filter_webhooks_for_event($webhooks, 'message.sent');

        $this->assertCount(2, $matches);
        $this->assertSame(['a', 'd'], array_column($matches, 'id'));
    }

    public function test_filter_webhooks_for_event_returns_empty_for_unknown(): void
    {
        $this->assertSame([], \wcwp_filter_webhooks_for_event([], 'message.sent'));
        $this->assertSame([], \wcwp_filter_webhooks_for_event([['id' => 'a', 'active' => true, 'events' => ['x']]], ''));
    }
}
