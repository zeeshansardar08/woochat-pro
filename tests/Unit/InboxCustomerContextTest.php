<?php
declare(strict_types=1);

namespace ZignitesChat\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class InboxCustomerContextTest extends TestCase
{
    public function test_aggregate_counts_and_sums(): void
    {
        $rows = [
            ['id' => 3, 'total' => 25.50, 'status' => 'Processing'],
            ['id' => 2, 'total' => 10.00, 'status' => 'Completed'],
            ['id' => 1, 'total' => 4.50,  'status' => 'Completed'],
        ];
        $agg = \zignites_chat_inbox_aggregate_customer_context($rows);
        $this->assertSame(3, $agg['order_count']);
        $this->assertSame(40.0, $agg['total_spent']);
        $this->assertCount(3, $agg['orders']);
    }

    public function test_aggregate_respects_recent_limit(): void
    {
        $rows = [];
        for ($i = 0; $i < 8; $i++) {
            $rows[] = ['id' => $i, 'total' => 1.0];
        }
        $agg = \zignites_chat_inbox_aggregate_customer_context($rows, 5);
        $this->assertSame(8, $agg['order_count']);   // count all
        $this->assertCount(5, $agg['orders']);       // list only the recent N
        $this->assertSame(8.0, $agg['total_spent']);
    }

    public function test_aggregate_handles_empty_and_garbage(): void
    {
        $agg = \zignites_chat_inbox_aggregate_customer_context([]);
        $this->assertSame(0, $agg['order_count']);
        $this->assertSame(0.0, $agg['total_spent']);
        $this->assertSame([], $agg['orders']);

        $agg2 = \zignites_chat_inbox_aggregate_customer_context('nope');
        $this->assertSame(0, $agg2['order_count']);

        // Non-array rows are skipped, missing totals count as 0.
        $agg3 = \zignites_chat_inbox_aggregate_customer_context([
            'bad',
            ['id' => 1], // no total
        ]);
        $this->assertSame(1, $agg3['order_count']);
        $this->assertSame(0.0, $agg3['total_spent']);
    }
}
