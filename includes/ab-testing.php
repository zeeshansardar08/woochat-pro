<?php
/**
 * Template A/B testing.
 *
 * Lets admins run a 50/50 split between two variants of each automated
 * message (Order confirmation / Cart recovery / Follow-up) and compare
 * which one converts better. Reuses the analytics events table — the
 * variant id is written into the event's `meta.ab_variant` field at
 * send time, then the results helper joins those events to subsequent
 * WC orders via the existing wcwp_analytics_match_conversions() pure
 * matcher (PR #49).
 *
 * Variant assignment is deterministic per recipient: the same phone (or
 * order id) always lands on the same variant, so a customer who
 * abandons a cart twice won't be measured under inconsistent variants
 * and a re-fired retry won't suddenly switch them. The splitter uses
 * `crc32(kind:key) & 1`, which gives a stable, uniform 50/50 split on
 * both 32-bit and 64-bit PHP.
 *
 * Storage:
 *   - wcwp_{kind}_template       — variant A (existing option, untouched).
 *   - wcwp_{kind}_template_b     — variant B (new option, autoload off).
 *   - wcwp_{kind}_ab_enabled     — yes/no master toggle (new, default 'no').
 *
 * When the toggle is OFF, or B is empty, every send falls through to
 * variant A — behavior is byte-identical to a pre-PR install.
 */

if (!defined('ABSPATH')) exit;

/**
 * Map of A/B-eligible message kinds to their option keys + defaults.
 *
 * Filterable so a third party could in theory add a new kind, but the
 * plugin only wires the three automated paths today.
 *
 * @return array<string, array{label:string, option_a:string, option_b:string, option_enabled:string, default_a:string}>
 */
function wcwp_ab_kinds() {
    $kinds = [
        'order' => [
            'label'          => __('Order confirmation', 'woochat-pro'),
            'option_a'       => 'wcwp_order_message_template',
            'option_b'       => 'wcwp_order_message_template_b',
            'option_enabled' => 'wcwp_order_message_ab_enabled',
            'default_a'      => 'Hi {name}, thanks for your order #{order_id}! Total: {total} {currency_symbol}.',
        ],
        'cart_recovery' => [
            'label'          => __('Cart recovery', 'woochat-pro'),
            'option_a'       => 'wcwp_cart_recovery_message',
            'option_b'       => 'wcwp_cart_recovery_message_b',
            'option_enabled' => 'wcwp_cart_recovery_ab_enabled',
            'default_a'      => "👋 Hey! You left items in your cart:\n\n{items}\n\nTotal: {total} {currency_symbol}\nClick here to complete your order: {cart_url}",
        ],
        'followup' => [
            'label'          => __('Follow-up', 'woochat-pro'),
            'option_a'       => 'wcwp_followup_template',
            'option_b'       => 'wcwp_followup_template_b',
            'option_enabled' => 'wcwp_followup_ab_enabled',
            'default_a'      => 'Hi {name}, thanks again for your order #{order_id}! Reply if you have any questions.',
        ],
    ];

    /**
     * Filter the A/B-eligible message kinds.
     *
     * @param array $kinds Kind-keyed config map.
     */
    return (array) apply_filters('wcwp_ab_kinds', $kinds);
}

/**
 * Pick a variant deterministically for a given (kind, key) pair.
 *
 * Same input → same variant on every call, on any host. Distribution
 * across many keys is roughly 50/50 (CRC32 LSB is uniform). Works on
 * both 32-bit and 64-bit PHP because we mask with 1 instead of using
 * modulo on a possibly-signed int.
 *
 * @param string     $kind One of the kinds returned by wcwp_ab_kinds().
 * @param string|int $key  Stable per-recipient key (phone or order id).
 * @return string 'a' or 'b'.
 */
function wcwp_ab_pick_variant($kind, $key) {
    $kind = (string) $kind;
    $key  = (string) $key;
    if ($kind === '' || $key === '') return 'a';
    return ((crc32($kind . ':' . $key) & 1) === 0) ? 'a' : 'b';
}

/**
 * Resolve the template that should be sent for one specific recipient.
 *
 * Returns ['variant' => 'a'|'b', 'template' => string]. Always falls
 * back to variant A (the existing option) if A/B is disabled, B is
 * empty, or the kind is unknown — so callers can pass the result
 * straight through without an extra null check.
 *
 * @param string     $kind       Kind id from wcwp_ab_kinds().
 * @param string|int $stable_key Per-recipient key — phone for cart
 *                               recovery, order id for orders/followups.
 * @return array{variant:string, template:string}
 */
function wcwp_ab_get_template($kind, $stable_key) {
    $kinds = wcwp_ab_kinds();
    if (!isset($kinds[$kind])) {
        return ['variant' => 'a', 'template' => ''];
    }
    $cfg = $kinds[$kind];

    $a_template = (string) get_option($cfg['option_a'], $cfg['default_a']);
    $enabled    = get_option($cfg['option_enabled'], 'no') === 'yes';
    $b_template = $enabled ? (string) get_option($cfg['option_b'], '') : '';

    if (!$enabled || trim($b_template) === '') {
        return ['variant' => 'a', 'template' => $a_template];
    }

    $variant  = wcwp_ab_pick_variant($kind, $stable_key);
    $template = $variant === 'b' ? $b_template : $a_template;
    return ['variant' => $variant, 'template' => $template];
}

/**
 * Partition analytics events by their stored variant.
 *
 * Pure helper extracted so the partitioning logic is unit-testable
 * without DB or option fixtures. Events without a recognised variant
 * are dropped (they predate A/B or were sent with the toggle off).
 *
 * @param array $events Analytics event rows from wcwp_analytics_get_events().
 * @return array{a: array<int, array>, b: array<int, array>}
 */
function wcwp_ab_partition_events_by_variant($events) {
    $out = ['a' => [], 'b' => []];
    if (!is_array($events)) return $out;

    foreach ($events as $e) {
        $meta = $e['meta'] ?? [];
        $variant = is_array($meta) && isset($meta['ab_variant']) ? (string) $meta['ab_variant'] : '';
        if ($variant === 'a' || $variant === 'b') {
            $out[$variant][] = $e;
        }
    }
    return $out;
}

/**
 * Aggregate per-variant performance for one kind, including conversions.
 *
 * Returns:
 *   [
 *     'a' => [ 'sent', 'conversions', 'revenue', 'cvr' ],
 *     'b' => [ 'sent', 'conversions', 'revenue', 'cvr' ],
 *     'winner'      => 'a' | 'b' | null,
 *     'window_days' => int,
 *     'total_sent'  => int,
 *   ]
 *
 * `sent` counts events whose status reached a deliverable terminal
 * state (sent / delivered / clicked) — the same definition used for
 * conversion eligibility in PR #49. `cvr` is conversions / sent (0 when
 * sent is 0). `winner` is null until both variants reach the minimum
 * sample size (default 30 each, filterable) so admins don't crown a
 * winner off three sends.
 *
 * @param string $kind    Kind id from wcwp_ab_kinds().
 * @param array  $filters Same shape as wcwp_analytics_get_events() filters
 *                        (date_from, date_to). The type filter is forced
 *                        to $kind here.
 * @return array
 */
function wcwp_ab_get_results($kind, $filters = []) {
    $window_days = (int) apply_filters('wcwp_analytics_attribution_window_days', 7);
    if ($window_days < 1) $window_days = 7;
    $window_seconds = $window_days * DAY_IN_SECONDS;

    $min_sample = (int) apply_filters('wcwp_ab_min_sample_size', 30);
    if ($min_sample < 1) $min_sample = 30;

    $blank = [
        'sent'        => 0,
        'conversions' => 0,
        'revenue'     => 0.0,
        'cvr'         => 0.0,
    ];
    $base = [
        'a'           => $blank,
        'b'           => $blank,
        'winner'      => null,
        'window_days' => $window_days,
        'total_sent'  => 0,
    ];

    $kinds = wcwp_ab_kinds();
    if (!isset($kinds[$kind])) {
        return $base;
    }

    $event_filters = is_array($filters) ? $filters : [];
    $event_filters['type'] = $kind;
    unset($event_filters['status']);
    $events = wcwp_analytics_get_events(5000, $event_filters);

    $partitioned = wcwp_ab_partition_events_by_variant($events);

    // Build the eligible-event lists (sent/delivered/clicked + phone) per
    // variant, plus the combined time window for one shared order fetch.
    $eligible_by_variant = ['a' => [], 'b' => []];
    $earliest = PHP_INT_MAX;
    $latest   = 0;
    $sent_counts = ['a' => 0, 'b' => 0];

    foreach (['a', 'b'] as $variant) {
        foreach ($partitioned[$variant] as $e) {
            $status = $e['status'] ?? '';
            if (!in_array($status, ['sent', 'delivered', 'clicked'], true)) continue;
            $sent_counts[$variant]++;
            if (empty($e['phone'])) continue;
            $time = isset($e['time']) ? strtotime((string) $e['time']) : 0;
            if (!$time) continue;
            $eligible_by_variant[$variant][] = [
                'event_id'   => (string) ($e['id'] ?? ''),
                'phone_norm' => wcwp_normalize_phone($e['phone']),
                'time'       => $time,
            ];
            if ($time < $earliest) $earliest = $time;
            if ($time > $latest)   $latest   = $time;
        }
    }

    $total_eligible = count($eligible_by_variant['a']) + count($eligible_by_variant['b']);

    // Fetch orders once for the combined window — both variants attribute
    // against the same order universe (a customer who got variant B can't
    // somehow buy via variant A's window).
    $order_records = [];
    if ($total_eligible > 0 && function_exists('wc_get_orders')) {
        $orders = wc_get_orders([
            'limit'        => 5000,
            'date_created' => gmdate('Y-m-d\TH:i:s', $earliest) . '...' . gmdate('Y-m-d\TH:i:s', $latest + $window_seconds),
            'status'       => ['wc-processing', 'wc-on-hold', 'wc-completed'],
            'orderby'      => 'date',
            'order'        => 'ASC',
        ]);
        if (is_array($orders)) {
            foreach ($orders as $order) {
                if (!is_object($order) || !method_exists($order, 'get_billing_phone')) continue;
                $phone_norm = wcwp_normalize_phone($order->get_billing_phone());
                if ($phone_norm === '') continue;
                $created = $order->get_date_created();
                if (!$created) continue;
                $order_records[] = [
                    'order_id'   => (int) $order->get_id(),
                    'phone_norm' => $phone_norm,
                    'time'       => (int) $created->getTimestamp(),
                    'total'      => (float) $order->get_total(),
                ];
            }
        }
    }

    $results = $base;
    foreach (['a', 'b'] as $variant) {
        $sent = $sent_counts[$variant];
        if (!empty($eligible_by_variant[$variant]) && !empty($order_records)) {
            $match = wcwp_analytics_match_conversions($eligible_by_variant[$variant], $order_records, $window_seconds);
            $conversions = (int) $match['conversions'];
            $revenue     = (float) $match['revenue'];
        } else {
            $conversions = 0;
            $revenue     = 0.0;
        }
        $results[$variant] = [
            'sent'        => $sent,
            'conversions' => $conversions,
            'revenue'     => $revenue,
            'cvr'         => $sent > 0 ? ($conversions / $sent) : 0.0,
        ];
    }

    $results['total_sent'] = $results['a']['sent'] + $results['b']['sent'];

    if ($results['a']['sent'] >= $min_sample && $results['b']['sent'] >= $min_sample) {
        if ($results['a']['cvr'] > $results['b']['cvr']) {
            $results['winner'] = 'a';
        } elseif ($results['b']['cvr'] > $results['a']['cvr']) {
            $results['winner'] = 'b';
        }
    }

    return $results;
}
