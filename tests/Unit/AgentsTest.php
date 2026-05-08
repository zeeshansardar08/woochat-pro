<?php
declare(strict_types=1);

namespace WooChatPro\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class AgentsTest extends TestCase
{
    protected function setUp(): void
    {
        $GLOBALS['wcwp_test_options'] = [];
    }

    public function test_sanitize_drops_rows_with_blank_name_or_phone(): void
    {
        $input = wp_json_encode([
            ['name' => 'Sales',   'phone' => '+1 (415) 555-0100'],
            ['name' => '',        'phone' => '+14155550101'],   // blank name → drop
            ['name' => 'Returns', 'phone' => ''],                // blank phone → drop
            ['name' => '',        'phone' => ''],                // both blank → drop
        ]);

        $sanitized = json_decode(\wcwp_sanitize_agents_json($input), true);

        $this->assertCount(1, $sanitized);
        $this->assertSame('Sales', $sanitized[0]['name']);
        // Phone normalized to digits only — wa.me does not want punctuation.
        $this->assertSame('14155550100', $sanitized[0]['phone']);
    }

    public function test_sanitize_returns_empty_array_for_garbage(): void
    {
        $this->assertSame('[]', \wcwp_sanitize_agents_json('not json'));
        $this->assertSame('[]', \wcwp_sanitize_agents_json(null));
        $this->assertSame('[]', \wcwp_sanitize_agents_json(['not', 'a', 'list', 'of', 'agents']));
    }

    public function test_get_agents_drops_malformed_persisted_rows(): void
    {
        // Defense in depth: even if something hand-edited the option to a
        // partly-valid shape, the read accessor only returns clean rows.
        $GLOBALS['wcwp_test_options']['wcwp_agents'] = wp_json_encode([
            ['name' => 'OK',   'phone' => '14155550100'],
            ['name' => 'Half', 'phone' => ''],
            'not an object',
        ]);

        $agents = \wcwp_get_agents();
        $this->assertCount(1, $agents);
        $this->assertSame('OK', $agents[0]['name']);
    }

    public function test_routing_mode_sanitizer_pins_to_valid_values(): void
    {
        $this->assertSame('single', \wcwp_sanitize_agent_routing_mode('single'));
        $this->assertSame('random', \wcwp_sanitize_agent_routing_mode('random'));
        $this->assertSame('single', \wcwp_sanitize_agent_routing_mode('rotation'));
        $this->assertSame('single', \wcwp_sanitize_agent_routing_mode(''));
    }
}
