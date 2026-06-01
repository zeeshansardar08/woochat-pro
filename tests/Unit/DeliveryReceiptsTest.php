<?php
declare(strict_types=1);

namespace ZignitesChat\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class DeliveryReceiptsTest extends TestCase
{
    public function test_status_rank_orders_the_funnel(): void
    {
        $this->assertSame(0, \zignites_chat_analytics_status_rank('pending'));
        $this->assertSame(1, \zignites_chat_analytics_status_rank('sent'));
        $this->assertSame(2, \zignites_chat_analytics_status_rank('delivered'));
        $this->assertSame(3, \zignites_chat_analytics_status_rank('read'));
        $this->assertSame(4, \zignites_chat_analytics_status_rank('clicked'));
        $this->assertSame(-1, \zignites_chat_analytics_status_rank('failed'));
        $this->assertSame(-1, \zignites_chat_analytics_status_rank('whatever'));
    }

    public function test_transition_advances_funnel_forward(): void
    {
        $this->assertSame('delivered', \zignites_chat_analytics_resolve_status_transition('sent', 'delivered'));
        $this->assertSame('read', \zignites_chat_analytics_resolve_status_transition('delivered', 'read'));
        $this->assertSame('sent', \zignites_chat_analytics_resolve_status_transition('', 'sent'));
        $this->assertSame('sent', \zignites_chat_analytics_resolve_status_transition('pending', 'sent'));
    }

    public function test_transition_never_moves_backward(): void
    {
        $this->assertNull(\zignites_chat_analytics_resolve_status_transition('delivered', 'sent'));
        $this->assertNull(\zignites_chat_analytics_resolve_status_transition('read', 'delivered'));
        $this->assertNull(\zignites_chat_analytics_resolve_status_transition('clicked', 'read'));
        // Same status is a no-op.
        $this->assertNull(\zignites_chat_analytics_resolve_status_transition('delivered', 'delivered'));
    }

    public function test_failed_only_while_in_flight(): void
    {
        $this->assertSame('failed', \zignites_chat_analytics_resolve_status_transition('pending', 'failed'));
        $this->assertSame('failed', \zignites_chat_analytics_resolve_status_transition('sent', 'failed'));
        $this->assertSame('failed', \zignites_chat_analytics_resolve_status_transition('', 'failed'));
        // Already delivered/read/clicked is never flipped to failed.
        $this->assertNull(\zignites_chat_analytics_resolve_status_transition('delivered', 'failed'));
        $this->assertNull(\zignites_chat_analytics_resolve_status_transition('read', 'failed'));
    }

    public function test_failed_is_sticky(): void
    {
        $this->assertNull(\zignites_chat_analytics_resolve_status_transition('failed', 'delivered'));
        $this->assertNull(\zignites_chat_analytics_resolve_status_transition('failed', 'sent'));
    }

    public function test_operational_states_are_untouched(): void
    {
        $this->assertNull(\zignites_chat_analytics_resolve_status_transition('test', 'delivered'));
        $this->assertNull(\zignites_chat_analytics_resolve_status_transition('opted_out', 'delivered'));
        $this->assertNull(\zignites_chat_analytics_resolve_status_transition('invalid', 'read'));
    }

    public function test_blank_incoming_is_noop(): void
    {
        $this->assertNull(\zignites_chat_analytics_resolve_status_transition('sent', ''));
    }

    public function test_map_twilio_status(): void
    {
        $this->assertSame('sent', \zignites_chat_map_twilio_status('queued'));
        $this->assertSame('sent', \zignites_chat_map_twilio_status('sending'));
        $this->assertSame('sent', \zignites_chat_map_twilio_status('Sent'));
        $this->assertSame('delivered', \zignites_chat_map_twilio_status('delivered'));
        $this->assertSame('read', \zignites_chat_map_twilio_status('read'));
        $this->assertSame('failed', \zignites_chat_map_twilio_status('failed'));
        $this->assertSame('failed', \zignites_chat_map_twilio_status('undelivered'));
        $this->assertSame('', \zignites_chat_map_twilio_status('something_else'));
    }

    public function test_map_meta_status(): void
    {
        $this->assertSame('sent', \zignites_chat_map_meta_status('sent'));
        $this->assertSame('delivered', \zignites_chat_map_meta_status('delivered'));
        $this->assertSame('read', \zignites_chat_map_meta_status('read'));
        $this->assertSame('failed', \zignites_chat_map_meta_status('failed'));
        $this->assertSame('', \zignites_chat_map_meta_status('deleted'));
    }

    public function test_ingest_meta_statuses_handles_non_array(): void
    {
        $this->assertSame(0, \zignites_chat_ingest_meta_statuses('nope'));
        $this->assertSame(0, \zignites_chat_ingest_meta_statuses([]));
    }
}
