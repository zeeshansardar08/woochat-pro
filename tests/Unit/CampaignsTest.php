<?php
declare(strict_types=1);

namespace WooChatPro\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class CampaignsTest extends TestCase
{
    protected function setUp(): void
    {
        $GLOBALS['wcwp_test_options'] = [];
        $GLOBALS['wcwp_test_currency_symbol'] = '&#36;';
        $GLOBALS['wcwp_test_bloginfo'] = ['name' => 'Acme Store'];
    }

    public function test_render_substitutes_supported_placeholders(): void
    {
        $template = 'Hi {name}, big news from {site}! Save {currency_symbol}10.';
        $result = \wcwp_campaign_render_message($template, 'Sara');

        $this->assertSame('Hi Sara, big news from Acme Store! Save $10.', $result);
    }

    public function test_render_handles_blank_name_and_site(): void
    {
        $GLOBALS['wcwp_test_bloginfo'] = ['name' => ''];
        $result = \wcwp_campaign_render_message('Hello {name} from {site}', '');
        $this->assertSame('Hello  from ', $result);
    }

    public function test_render_does_not_substitute_unsupported_placeholders(): void
    {
        // Bulk sends have no order/cart context — order placeholders must
        // pass through untouched rather than being silently blanked.
        $result = \wcwp_campaign_render_message('Order {order_id} total {total}', 'X');
        $this->assertSame('Order {order_id} total {total}', $result);
    }

    public function test_segment_types_lists_expected_segments(): void
    {
        $types = \wcwp_campaign_segment_types();
        $this->assertArrayHasKey('all_customers', $types);
        $this->assertArrayHasKey('recent_orders', $types);
    }
}
