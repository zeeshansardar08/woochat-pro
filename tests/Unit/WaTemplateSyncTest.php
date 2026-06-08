<?php
declare(strict_types=1);

namespace ZignitesChat\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class WaTemplateSyncTest extends TestCase
{
    public function test_endpoint_builds_graph_url(): void
    {
        $url = \zignites_chat_wa_template_endpoint('123456', 50);
        $this->assertStringContainsString('/123456/message_templates', $url);
        $this->assertStringContainsString('fields=name,status,language,category,components', $url);
        $this->assertStringContainsString('limit=50', $url);
    }

    public function test_endpoint_empty_waba_returns_empty(): void
    {
        $this->assertSame('', \zignites_chat_wa_template_endpoint('  '));
    }

    public function test_endpoint_clamps_limit(): void
    {
        $this->assertStringContainsString('limit=1', \zignites_chat_wa_template_endpoint('w', 0));
        $this->assertStringContainsString('limit=250', \zignites_chat_wa_template_endpoint('w', 9999));
    }

    public function test_count_body_params_uses_highest_index(): void
    {
        $components = [
            ['type' => 'HEADER', 'text' => 'Hi {{9}}'], // header ignored
            ['type' => 'BODY', 'text' => 'Hi {{1}}, your order {{2}} total {{3}}.'],
            ['type' => 'BUTTONS'],
        ];
        $this->assertSame(3, \zignites_chat_wa_count_body_params($components));
    }

    public function test_count_body_params_handles_no_body_and_junk(): void
    {
        $this->assertSame(0, \zignites_chat_wa_count_body_params([]));
        $this->assertSame(0, \zignites_chat_wa_count_body_params('nope'));
        $this->assertSame(0, \zignites_chat_wa_count_body_params([['type' => 'BODY', 'text' => 'no vars here']]));
    }

    public function test_count_body_params_is_lowercase_type_tolerant(): void
    {
        $this->assertSame(2, \zignites_chat_wa_count_body_params([
            ['type' => 'body', 'text' => '{{1}} and {{2}}'],
        ]));
    }

    public function test_normalize_templates_maps_nodes(): void
    {
        $data = [
            'data' => [
                [
                    'name'       => 'order_confirmation',
                    'language'   => 'en_US',
                    'status'     => 'approved',
                    'category'   => 'utility',
                    'components'  => [['type' => 'BODY', 'text' => 'Hi {{1}}']],
                ],
                ['no_name' => true], // skipped
            ],
        ];
        $out = \zignites_chat_wa_sync_normalize_templates($data);
        $this->assertCount(1, $out);
        $this->assertSame('order_confirmation', $out[0]['name']);
        $this->assertSame('APPROVED', $out[0]['status']);
        $this->assertSame('UTILITY', $out[0]['category']);
        $this->assertSame(1, $out[0]['body_params']);
    }

    public function test_normalize_templates_handles_missing_data(): void
    {
        $this->assertSame([], \zignites_chat_wa_sync_normalize_templates([]));
        $this->assertSame([], \zignites_chat_wa_sync_normalize_templates('nope'));
        $this->assertSame([], \zignites_chat_wa_sync_normalize_templates(['data' => 'x']));
    }
}
