<?php
declare(strict_types=1);

namespace ZignitesChat\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class ReviewRequestTest extends TestCase
{
    public function test_normalize_status_strips_wc_prefix(): void
    {
        $this->assertSame('completed', \zignites_chat_review_normalize_status('wc-completed'));
        $this->assertSame('delivered', \zignites_chat_review_normalize_status('delivered'));
        $this->assertSame('', \zignites_chat_review_normalize_status(''));
    }

    public function test_delay_seconds_converts_days(): void
    {
        $this->assertSame(3 * DAY_IN_SECONDS, \zignites_chat_review_delay_seconds(3));
        $this->assertSame(0, \zignites_chat_review_delay_seconds(0));
    }

    public function test_delay_seconds_clamps_negative_and_casts(): void
    {
        $this->assertSame(0, \zignites_chat_review_delay_seconds(-5));
        $this->assertSame(2 * DAY_IN_SECONDS, \zignites_chat_review_delay_seconds('2'));
    }

    public function test_render_message_substitutes(): void
    {
        $out = \zignites_chat_review_render_message(
            'Hi {name}, rate order #{order_id}: {review_url}',
            [
                '{name}'       => 'Sara',
                '{order_id}'   => 42,
                '{review_url}' => 'https://shop/review',
            ]
        );
        $this->assertSame('Hi Sara, rate order #42: https://shop/review', $out);
    }

    public function test_render_message_with_non_array_values(): void
    {
        $this->assertSame('literal', \zignites_chat_review_render_message('literal', 'nope'));
    }
}
