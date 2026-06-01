<?php
declare(strict_types=1);

namespace ZignitesChat\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class WaTemplatesTest extends TestCase
{
    protected function setUp(): void
    {
        $GLOBALS['zignites_chat_test_options'] = [];
        $GLOBALS['zignites_chat_test_is_pro'] = true;
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['zignites_chat_test_options'], $GLOBALS['zignites_chat_test_is_pro']);
    }

    public function test_resolve_values_substitutes_in_order(): void
    {
        $out = \zignites_chat_wa_template_resolve_values(
            ['{name}', 'Order #{order_id}', '{total} {currency_symbol}'],
            ['{name}' => 'Sara', '{order_id}' => 42, '{total}' => '19.99', '{currency_symbol}' => '$']
        );
        $this->assertSame(['Sara', 'Order #42', '19.99 $'], $out);
    }

    public function test_resolve_values_collapses_newlines_and_runs_of_spaces(): void
    {
        // WhatsApp body params cannot contain newlines or 4+ spaces.
        $out = \zignites_chat_wa_template_resolve_values(
            ["Line one\nLine    two\tend"],
            []
        );
        $this->assertSame(['Line one Line two end'], $out);
    }

    public function test_resolve_values_handles_non_array(): void
    {
        $this->assertSame([], \zignites_chat_wa_template_resolve_values('nope', []));
    }

    public function test_build_components_empty_for_no_values(): void
    {
        $this->assertSame([], \zignites_chat_wa_template_build_components([]));
        $this->assertSame([], \zignites_chat_wa_template_build_components('nope'));
    }

    public function test_build_components_shape(): void
    {
        $components = \zignites_chat_wa_template_build_components(['Sara', '42']);
        $this->assertSame([
            [
                'type'       => 'body',
                'parameters' => [
                    ['type' => 'text', 'text' => 'Sara'],
                    ['type' => 'text', 'text' => '42'],
                ],
            ],
        ], $components);
    }

    public function test_sanitize_coerces_and_caps(): void
    {
        $raw = [
            'order' => [
                'enabled'   => 'yes',
                'name'      => '  order_confirmation  ',
                'language'  => 'en_US',
                'variables' => array_merge(
                    array_fill(0, 20, '{name}'), // 20 entries — should cap at 15
                    ['']                         // empty dropped
                ),
            ],
            'bogus_type' => ['enabled' => 'yes', 'name' => 'x'], // unknown — dropped
        ];

        $clean = \zignites_chat_sanitize_wa_templates($raw);

        $this->assertArrayHasKey('order', $clean);
        $this->assertArrayNotHasKey('bogus_type', $clean);
        $this->assertSame('yes', $clean['order']['enabled']);
        $this->assertSame('order_confirmation', $clean['order']['name']);
        $this->assertCount(15, $clean['order']['variables']);

        // Every known type is present with a default entry.
        foreach (array_keys(\zignites_chat_wa_template_types()) as $type) {
            $this->assertArrayHasKey($type, $clean);
        }
        $this->assertSame('no', $clean['cart_recovery']['enabled']);
    }

    public function test_sanitize_handles_non_array(): void
    {
        $this->assertSame([], \zignites_chat_sanitize_wa_templates('nope'));
    }

    public function test_is_active_requires_cloud_provider(): void
    {
        $GLOBALS['zignites_chat_test_options']['zignites_chat_api_provider'] = 'twilio';
        $GLOBALS['zignites_chat_test_options']['zignites_chat_wa_templates'] = [
            'order' => ['enabled' => 'yes', 'name' => 'order_confirmation', 'language' => 'en_US', 'variables' => []],
        ];
        $this->assertFalse(\zignites_chat_wa_template_is_active('order'));
    }

    public function test_is_active_requires_pro(): void
    {
        $GLOBALS['zignites_chat_test_is_pro'] = false;
        $GLOBALS['zignites_chat_test_options']['zignites_chat_api_provider'] = 'cloud';
        $GLOBALS['zignites_chat_test_options']['zignites_chat_wa_templates'] = [
            'order' => ['enabled' => 'yes', 'name' => 'order_confirmation', 'language' => 'en_US', 'variables' => []],
        ];
        $this->assertFalse(\zignites_chat_wa_template_is_active('order'));
    }

    public function test_is_active_requires_enabled_and_name(): void
    {
        $GLOBALS['zignites_chat_test_options']['zignites_chat_api_provider'] = 'cloud';

        $GLOBALS['zignites_chat_test_options']['zignites_chat_wa_templates'] = [
            'order' => ['enabled' => 'no', 'name' => 'order_confirmation', 'language' => 'en_US', 'variables' => []],
        ];
        $this->assertFalse(\zignites_chat_wa_template_is_active('order'), 'disabled entry is inactive');

        $GLOBALS['zignites_chat_test_options']['zignites_chat_wa_templates'] = [
            'order' => ['enabled' => 'yes', 'name' => '', 'language' => 'en_US', 'variables' => []],
        ];
        $this->assertFalse(\zignites_chat_wa_template_is_active('order'), 'empty name is inactive');
    }

    public function test_maybe_apply_template_is_noop_when_inactive(): void
    {
        $GLOBALS['zignites_chat_test_options']['zignites_chat_api_provider'] = 'twilio';
        $context = ['type' => 'order', 'order_id' => 5];
        $this->assertSame($context, \zignites_chat_maybe_apply_template('order', ['{name}' => 'Sara'], $context));
    }

    public function test_maybe_apply_template_attaches_descriptor_when_active(): void
    {
        $GLOBALS['zignites_chat_test_options']['zignites_chat_api_provider'] = 'cloud';
        $GLOBALS['zignites_chat_test_options']['zignites_chat_wa_templates'] = [
            'order' => [
                'enabled'   => 'yes',
                'name'      => 'order_confirmation',
                'language'  => 'en_US',
                'variables' => ['{name}', '{order_id}'],
            ],
        ];

        $context = \zignites_chat_maybe_apply_template('order', [
            '{name}'     => 'Sara',
            '{order_id}' => 42,
        ], ['type' => 'order', 'order_id' => 42]);

        $this->assertArrayHasKey('template', $context);
        $this->assertSame('order_confirmation', $context['template']['name']);
        $this->assertSame('en_US', $context['template']['language']);
        $this->assertSame([
            [
                'type'       => 'body',
                'parameters' => [
                    ['type' => 'text', 'text' => 'Sara'],
                    ['type' => 'text', 'text' => '42'],
                ],
            ],
        ], $context['template']['components']);
        // Original context keys preserved.
        $this->assertSame('order', $context['type']);
        $this->assertSame(42, $context['order_id']);
    }

    public function test_maybe_apply_defaults_blank_language_to_en_us(): void
    {
        $GLOBALS['zignites_chat_test_options']['zignites_chat_api_provider'] = 'cloud';
        $GLOBALS['zignites_chat_test_options']['zignites_chat_wa_templates'] = [
            'order' => ['enabled' => 'yes', 'name' => 'order_confirmation', 'language' => '', 'variables' => []],
        ];
        $context = \zignites_chat_maybe_apply_template('order', [], []);
        $this->assertSame('en_US', $context['template']['language']);
        $this->assertSame([], $context['template']['components']);
    }
}
