<?php
declare(strict_types=1);

namespace WooChatPro\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class AbTestingTest extends TestCase
{
    protected function setUp(): void
    {
        $GLOBALS['wcwp_test_options'] = [];
    }

    public function test_pick_variant_is_deterministic(): void
    {
        $first  = \wcwp_ab_pick_variant('order', '12345');
        $second = \wcwp_ab_pick_variant('order', '12345');
        $third  = \wcwp_ab_pick_variant('order', '12345');

        $this->assertSame($first, $second);
        $this->assertSame($first, $third);
        $this->assertContains($first, ['a', 'b']);
    }

    public function test_pick_variant_returns_a_or_b(): void
    {
        for ($i = 0; $i < 50; $i++) {
            $v = \wcwp_ab_pick_variant('cart_recovery', 'phone-' . $i);
            $this->assertContains($v, ['a', 'b'], "Iteration $i returned unexpected '$v'.");
        }
    }

    public function test_pick_variant_distributes_roughly_evenly(): void
    {
        $a = 0;
        $b = 0;
        // CRC32 is uniform across crafted inputs, so 1000 random keys
        // should land within 40-60% of either bucket. Wide window so the
        // test isn't flaky on edge distributions.
        for ($i = 0; $i < 1000; $i++) {
            $v = \wcwp_ab_pick_variant('order', 'order-' . $i);
            if ($v === 'a') { $a++; } else { $b++; }
        }
        $this->assertGreaterThan(400, $a, 'Variant A should be allocated at least 40% of 1000 keys.');
        $this->assertGreaterThan(400, $b, 'Variant B should be allocated at least 40% of 1000 keys.');
    }

    public function test_pick_variant_is_kind_scoped(): void
    {
        // The same key under two different kinds should be allowed to
        // diverge — kinds are independent splits.
        $a_orders = 0;
        $b_orders = 0;
        $a_carts  = 0;
        $b_carts  = 0;
        for ($i = 0; $i < 100; $i++) {
            $key = 'k' . $i;
            \wcwp_ab_pick_variant('order', $key) === 'a' ? $a_orders++ : $b_orders++;
            \wcwp_ab_pick_variant('cart_recovery', $key) === 'a' ? $a_carts++ : $b_carts++;
        }
        $this->assertSame(100, $a_orders + $b_orders);
        $this->assertSame(100, $a_carts + $b_carts);
    }

    public function test_pick_variant_falls_back_to_a_for_empty_input(): void
    {
        $this->assertSame('a', \wcwp_ab_pick_variant('', 'key'));
        $this->assertSame('a', \wcwp_ab_pick_variant('order', ''));
    }

    public function test_get_template_returns_a_when_disabled(): void
    {
        $GLOBALS['wcwp_test_options']['wcwp_order_message_template'] = 'Variant A body';
        $GLOBALS['wcwp_test_options']['wcwp_order_message_template_b'] = 'Variant B body';
        $GLOBALS['wcwp_test_options']['wcwp_order_message_ab_enabled'] = 'no';

        $picked = \wcwp_ab_get_template('order', '42');

        $this->assertSame('a', $picked['variant']);
        $this->assertSame('Variant A body', $picked['template']);
    }

    public function test_get_template_returns_a_when_b_is_empty(): void
    {
        $GLOBALS['wcwp_test_options']['wcwp_order_message_template'] = 'Variant A body';
        $GLOBALS['wcwp_test_options']['wcwp_order_message_template_b'] = '   '; // whitespace only
        $GLOBALS['wcwp_test_options']['wcwp_order_message_ab_enabled'] = 'yes';

        $picked = \wcwp_ab_get_template('order', '42');

        $this->assertSame('a', $picked['variant']);
        $this->assertSame('Variant A body', $picked['template']);
    }

    public function test_get_template_picks_b_when_enabled_and_key_hashes_to_b(): void
    {
        $GLOBALS['wcwp_test_options']['wcwp_order_message_template'] = 'A body';
        $GLOBALS['wcwp_test_options']['wcwp_order_message_template_b'] = 'B body';
        $GLOBALS['wcwp_test_options']['wcwp_order_message_ab_enabled'] = 'yes';

        // Find a key that lands on B so we can assert the lookup wired up.
        $b_key = null;
        for ($i = 0; $i < 50; $i++) {
            if (\wcwp_ab_pick_variant('order', 'k' . $i) === 'b') {
                $b_key = 'k' . $i;
                break;
            }
        }
        $this->assertNotNull($b_key, 'Could not find a B-mapping key in 50 attempts.');

        $picked = \wcwp_ab_get_template('order', $b_key);
        $this->assertSame('b', $picked['variant']);
        $this->assertSame('B body', $picked['template']);
    }

    public function test_get_template_unknown_kind_returns_a_with_empty_template(): void
    {
        $picked = \wcwp_ab_get_template('not-a-kind', 'k1');
        $this->assertSame('a', $picked['variant']);
        $this->assertSame('', $picked['template']);
    }

    public function test_partition_events_groups_by_variant(): void
    {
        $events = [
            ['id' => 'e1', 'meta' => ['ab_variant' => 'a']],
            ['id' => 'e2', 'meta' => ['ab_variant' => 'b']],
            ['id' => 'e3', 'meta' => ['ab_variant' => 'a']],
            ['id' => 'e4', 'meta' => []],                         // dropped — no variant
            ['id' => 'e5', 'meta' => ['ab_variant' => 'c']],     // dropped — invalid variant
            ['id' => 'e6'],                                        // dropped — no meta
        ];
        $partitioned = \wcwp_ab_partition_events_by_variant($events);

        $this->assertCount(2, $partitioned['a']);
        $this->assertCount(1, $partitioned['b']);
        $this->assertSame('e1', $partitioned['a'][0]['id']);
        $this->assertSame('e3', $partitioned['a'][1]['id']);
        $this->assertSame('e2', $partitioned['b'][0]['id']);
    }

    public function test_partition_events_handles_non_array_input(): void
    {
        $this->assertSame(['a' => [], 'b' => []], \wcwp_ab_partition_events_by_variant([]));
        $this->assertSame(['a' => [], 'b' => []], \wcwp_ab_partition_events_by_variant(null));
    }

    public function test_kinds_includes_all_three_known_kinds(): void
    {
        $kinds = \wcwp_ab_kinds();
        $this->assertArrayHasKey('order', $kinds);
        $this->assertArrayHasKey('cart_recovery', $kinds);
        $this->assertArrayHasKey('followup', $kinds);
        foreach ($kinds as $id => $cfg) {
            $this->assertArrayHasKey('label', $cfg, "Kind $id missing label.");
            $this->assertArrayHasKey('option_a', $cfg);
            $this->assertArrayHasKey('option_b', $cfg);
            $this->assertArrayHasKey('option_enabled', $cfg);
        }
    }
}
