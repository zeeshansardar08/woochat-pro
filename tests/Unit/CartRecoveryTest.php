<?php
declare(strict_types=1);

namespace WooChatPro\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class CartRecoveryTest extends TestCase
{
    protected function setUp(): void
    {
        $GLOBALS['wcwp_test_options'] = [];
        // WC normally returns HTML entities like `&#36;`; the plugin's helper
        // decodes to `$` for plain-text WhatsApp output.
        $GLOBALS['wcwp_test_currency_symbol'] = '&#36;';
    }

    public function test_render_substitutes_default_template_placeholders(): void
    {
        $items = ['• Widget × 2', '• Gadget × 1'];
        $total = '49.99';
        $cart_url = 'https://example.com/cart';

        $result = \wcwp_render_cart_recovery_message($items, $total, $cart_url);

        $this->assertStringContainsString('• Widget × 2', $result);
        $this->assertStringContainsString('• Gadget × 1', $result);
        $this->assertStringContainsString('49.99', $result);
        $this->assertStringContainsString('https://example.com/cart', $result);
        // Currency symbol decoded by wcwp_currency_symbol_text():
        $this->assertStringContainsString('$', $result);

        // No placeholder leaks through:
        $this->assertStringNotContainsString('{items}', $result);
        $this->assertStringNotContainsString('{total}', $result);
        $this->assertStringNotContainsString('{cart_url}', $result);
        $this->assertStringNotContainsString('{currency_symbol}', $result);
    }

    public function test_render_uses_custom_template_from_option(): void
    {
        $GLOBALS['wcwp_test_options']['wcwp_cart_recovery_message'] =
            'Items: {items}; Total: {total} {currency_symbol}; URL: {cart_url}';

        $result = \wcwp_render_cart_recovery_message(['•Foo'], '99.50', 'https://shop.test/c');

        $this->assertSame(
            'Items: •Foo; Total: 99.50 $; URL: https://shop.test/c',
            $result
        );
    }
}
