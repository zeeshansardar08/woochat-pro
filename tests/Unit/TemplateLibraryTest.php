<?php
declare(strict_types=1);

namespace WooChatPro\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class TemplateLibraryTest extends TestCase
{
    public function test_library_has_at_least_three_industries(): void
    {
        $library = \wcwp_get_template_library();
        $this->assertIsArray($library);
        $this->assertGreaterThanOrEqual(3, count($library), 'Library should ship with at least three industries.');
    }

    public function test_each_industry_has_label_and_templates(): void
    {
        $library = \wcwp_get_template_library();
        foreach ($library as $industry_id => $industry) {
            $this->assertArrayHasKey('label', $industry, "Industry $industry_id missing label.");
            $this->assertNotSame('', $industry['label']);
            $this->assertArrayHasKey('templates', $industry, "Industry $industry_id missing templates.");
            $this->assertIsArray($industry['templates']);
            $this->assertNotEmpty($industry['templates'], "Industry $industry_id has no templates.");
        }
    }

    public function test_each_template_has_required_fields_and_known_kind(): void
    {
        $library = \wcwp_get_template_library();
        $valid_kinds = ['order', 'cart_recovery', 'followup'];
        foreach ($library as $industry_id => $industry) {
            foreach ($industry['templates'] as $i => $t) {
                $where = "$industry_id template #$i";
                $this->assertArrayHasKey('kind', $t, "$where missing kind.");
                $this->assertContains($t['kind'], $valid_kinds, "$where has unknown kind '{$t['kind']}'.");
                $this->assertArrayHasKey('name', $t, "$where missing name.");
                $this->assertNotSame('', $t['name'], "$where has empty name.");
                $this->assertArrayHasKey('body', $t, "$where missing body.");
                $this->assertNotSame('', $t['body'], "$where has empty body.");
            }
        }
    }

    public function test_each_industry_covers_all_three_kinds(): void
    {
        $library = \wcwp_get_template_library();
        foreach ($library as $industry_id => $industry) {
            $kinds = array_unique(array_column($industry['templates'], 'kind'));
            sort($kinds);
            $this->assertSame(
                ['cart_recovery', 'followup', 'order'],
                $kinds,
                "Industry $industry_id should have at least one template of each kind."
            );
        }
    }

    public function test_get_templates_by_kind_returns_only_matching_kind(): void
    {
        $orders = \wcwp_get_templates_by_kind('order');
        $this->assertNotEmpty($orders);
        foreach ($orders as $t) {
            $this->assertSame('order', $t['kind']);
            $this->assertArrayHasKey('industry_id', $t);
            $this->assertArrayHasKey('industry_label', $t);
            $this->assertNotSame('', $t['industry_label']);
        }
    }

    public function test_get_templates_by_kind_returns_empty_for_unknown_kind(): void
    {
        $this->assertSame([], \wcwp_get_templates_by_kind('does-not-exist'));
    }

    public function test_get_templates_by_kind_returns_empty_for_blank_input(): void
    {
        $this->assertSame([], \wcwp_get_templates_by_kind(''));
    }

    public function test_cart_recovery_templates_reference_cart_url_placeholder(): void
    {
        // Sanity check: cart_recovery messages without {cart_url} would
        // ship without a clickable link to the cart, defeating the
        // purpose of the message.
        $cart = \wcwp_get_templates_by_kind('cart_recovery');
        $this->assertNotEmpty($cart);
        foreach ($cart as $t) {
            $this->assertStringContainsString('{cart_url}', $t['body'], "Cart recovery template '{$t['name']}' missing {cart_url} placeholder.");
        }
    }

    public function test_followup_templates_reference_order_id_placeholder(): void
    {
        $followup = \wcwp_get_templates_by_kind('followup');
        $this->assertNotEmpty($followup);
        foreach ($followup as $t) {
            $this->assertStringContainsString('{order_id}', $t['body'], "Follow-up template '{$t['name']}' missing {order_id} placeholder.");
        }
    }
}
