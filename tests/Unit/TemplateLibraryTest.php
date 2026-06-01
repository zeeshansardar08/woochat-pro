<?php
declare(strict_types=1);

namespace ZignitesChat\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class TemplateLibraryTest extends TestCase
{
    public function test_library_has_at_least_three_industries(): void
    {
        $library = \zignites_chat_get_template_library();
        $this->assertIsArray($library);
        $this->assertGreaterThanOrEqual(3, count($library), 'Library should ship with at least three industries.');
    }

    public function test_each_industry_has_label_and_templates(): void
    {
        $library = \zignites_chat_get_template_library();
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
        $library = \zignites_chat_get_template_library();
        // The free version ships order templates only.
        $valid_kinds = ['order'];
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

    public function test_each_industry_has_order_templates_only(): void
    {
        $library = \zignites_chat_get_template_library();
        foreach ($library as $industry_id => $industry) {
            $kinds = array_unique(array_column($industry['templates'], 'kind'));
            sort($kinds);
            $this->assertSame(
                ['order'],
                $kinds,
                "Industry $industry_id should ship order templates only in the free version."
            );
        }
    }

    public function test_get_templates_by_kind_returns_only_matching_kind(): void
    {
        $orders = \zignites_chat_get_templates_by_kind('order');
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
        $this->assertSame([], \zignites_chat_get_templates_by_kind('does-not-exist'));
    }

    public function test_get_templates_by_kind_returns_empty_for_blank_input(): void
    {
        $this->assertSame([], \zignites_chat_get_templates_by_kind(''));
    }

    public function test_order_templates_reference_order_id_placeholder(): void
    {
        $orders = \zignites_chat_get_templates_by_kind('order');
        $this->assertNotEmpty($orders);
        foreach ($orders as $t) {
            $this->assertStringContainsString('{order_id}', $t['body'], "Order template '{$t['name']}' missing {order_id} placeholder.");
        }
    }

    public function test_pro_only_kinds_are_not_shipped(): void
    {
        // Cart recovery and follow-up are Pro-only; the free library must
        // not surface them.
        $this->assertSame([], \zignites_chat_get_templates_by_kind('cart_recovery'));
        $this->assertSame([], \zignites_chat_get_templates_by_kind('followup'));
    }
}
