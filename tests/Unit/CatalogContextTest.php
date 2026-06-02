<?php
declare(strict_types=1);

namespace ZignitesChat\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class CatalogContextTest extends TestCase
{
    public function test_builds_lines_with_name_and_price(): void
    {
        $out = \zignites_chat_build_catalog_context([
            ['name' => 'Blue Mug', 'price' => '$12.00'],
            ['name' => 'Red Hat', 'price' => '$25.00'],
        ]);
        $this->assertSame("- Blue Mug — \$12.00\n- Red Hat — \$25.00", $out);
    }

    public function test_omits_price_when_empty_and_strips_tags(): void
    {
        $out = \zignites_chat_build_catalog_context([
            ['name' => '<b>Sticker</b>', 'price' => ''],
        ]);
        $this->assertSame('- Sticker', $out);
    }

    public function test_skips_invalid_or_unnamed_rows(): void
    {
        $out = \zignites_chat_build_catalog_context([
            'not-an-array',
            ['price' => '$5.00'],          // no name → skipped
            ['name' => '   ', 'price' => '$1'], // blank name → skipped
            ['name' => 'Valid', 'price' => '$9'],
        ]);
        $this->assertSame('- Valid — $9', $out);
    }

    public function test_empty_input_returns_empty_string(): void
    {
        $this->assertSame('', \zignites_chat_build_catalog_context([]));
        $this->assertSame('', \zignites_chat_build_catalog_context('nope'));
    }

    public function test_respects_max_chars_but_always_keeps_first_line(): void
    {
        $products = [
            ['name' => 'First product', 'price' => '$10'],
            ['name' => 'Second product', 'price' => '$20'],
            ['name' => 'Third product', 'price' => '$30'],
        ];
        // Cap small enough to admit only the first line.
        $out = \zignites_chat_build_catalog_context($products, 12);
        $this->assertSame('- First product — $10', $out);
        $this->assertStringNotContainsString('Second', $out);
    }

    public function test_first_line_kept_even_when_longer_than_cap(): void
    {
        // A tiny cap must not produce an empty result — the first line stays.
        $out = \zignites_chat_build_catalog_context([['name' => 'Only', 'price' => '$1']], 1);
        $this->assertSame('- Only — $1', $out);
    }
}
