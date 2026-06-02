<?php
declare(strict_types=1);

namespace ZignitesChat\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class BackInStockTest extends TestCase
{
    public function test_is_instock(): void
    {
        $this->assertTrue(\zignites_chat_stock_is_instock('instock'));
        $this->assertFalse(\zignites_chat_stock_is_instock('outofstock'));
        $this->assertFalse(\zignites_chat_stock_is_instock('onbackorder'));
        $this->assertFalse(\zignites_chat_stock_is_instock(''));
    }

    public function test_render_message_substitutes(): void
    {
        $out = \zignites_chat_stock_render_message(
            '{product} is back! Buy: {product_url} — {site}',
            [
                '{product}'     => 'Blue Mug',
                '{product_url}' => 'https://shop/mug',
                '{site}'        => 'My Store',
            ]
        );
        $this->assertSame('Blue Mug is back! Buy: https://shop/mug — My Store', $out);
    }

    public function test_render_message_with_non_array_values(): void
    {
        $this->assertSame('literal', \zignites_chat_stock_render_message('literal', 'nope'));
    }
}
