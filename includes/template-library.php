<?php
/**
 * Pre-written template library.
 *
 * Static catalog of WhatsApp order-message starters organised by
 * industry. Surfaced on the Messaging tab as a "Browse template library"
 * modal next to the order-message textarea so a fresh install has a
 * sensible starting point instead of staring at a blank box.
 *
 * Adding a new industry or template is a one-stop edit here; the modal UI
 * iterates whatever zignites_chat_get_template_library() returns.
 *
 * Each template entry:
 *   - kind: 'order' (the only kind shipped in the free version)
 *   - name: short label shown on the card (translated)
 *   - body: message body with Zignites Chat placeholders (translated)
 *
 * Order placeholders (matching what tab-messaging.php substitutes):
 *   {name}, {order_id}, {total}, {currency_symbol}
 */

if (!defined('ABSPATH')) exit;

/**
 * Return the full template library, grouped by industry.
 *
 * @return array<string, array{label:string, templates: array<int, array{kind:string,name:string,body:string}>}>
 */
function zignites_chat_get_template_library() {
    $library = [
        'fashion' => [
            'label'     => __('Fashion & Apparel', 'zignites-chat'),
            'templates' => [
                [
                    'kind' => 'order',
                    'name' => __('Stylish confirmation', 'zignites-chat'),
                    'body' => __("Hi {name}! 👗 Your order #{order_id} is confirmed — total {total} {currency_symbol}. We'll text you the moment it ships ✨", 'zignites-chat'),
                ],
                [
                    'kind' => 'order',
                    'name' => __('VIP packing', 'zignites-chat'),
                    'body' => __("Thanks {name}! Order #{order_id} ({total} {currency_symbol}) is being styled and packed with care. 💌", 'zignites-chat'),
                ],
                [
                    'kind' => 'order',
                    'name' => __('Drop-in confirmation', 'zignites-chat'),
                    'body' => __("Yay {name}! 🛍️ Order #{order_id} for {total} {currency_symbol} is in. New favourites incoming!", 'zignites-chat'),
                ],
            ],
        ],
        'food' => [
            'label'     => __('Food & Restaurants', 'zignites-chat'),
            'templates' => [
                [
                    'kind' => 'order',
                    'name' => __('Order received', 'zignites-chat'),
                    'body' => __("Hi {name}! 🍽️ Order #{order_id} is in — total {total} {currency_symbol}. We'll let you know when it's on the way.", 'zignites-chat'),
                ],
                [
                    'kind' => 'order',
                    'name' => __('Cooking now', 'zignites-chat'),
                    'body' => __("Thanks {name}! Order #{order_id} ({total} {currency_symbol}) is being prepared fresh. ETA in your confirmation email.", 'zignites-chat'),
                ],
                [
                    'kind' => 'order',
                    'name' => __('Made with love', 'zignites-chat'),
                    'body' => __("{name}, order received! 👨‍🍳 #{order_id} for {total} {currency_symbol}. Cooking with love.", 'zignites-chat'),
                ],
            ],
        ],
        'services' => [
            'label'     => __('Services & Consulting', 'zignites-chat'),
            'templates' => [
                [
                    'kind' => 'order',
                    'name' => __('Booking confirmed', 'zignites-chat'),
                    'body' => __("Hi {name}, thank you for your booking. Reference #{order_id}, total {total} {currency_symbol}. Confirmation details have been emailed to you.", 'zignites-chat'),
                ],
                [
                    'kind' => 'order',
                    'name' => __('Next steps incoming', 'zignites-chat'),
                    'body' => __("Hello {name}, your order #{order_id} for {total} {currency_symbol} has been received. We will be in touch shortly to confirm next steps.", 'zignites-chat'),
                ],
                [
                    'kind' => 'order',
                    'name' => __('Open line', 'zignites-chat'),
                    'body' => __("Thank you {name}. Order #{order_id} ({total} {currency_symbol}) is confirmed. Please reply if you have any questions.", 'zignites-chat'),
                ],
            ],
        ],
    ];

    /**
     * Filter the template library before it is consumed.
     *
     * Lets a third party register a new industry or replace the default
     * copy without touching core. Each industry must have a `label`
     * (string) and `templates` (array of templates with kind/name/body).
     *
     * @param array $library Industry-keyed library.
     */
    return (array) apply_filters('zignites_chat_template_library', $library);
}

/**
 * Return all templates of a given kind, flattened across industries.
 *
 * Each returned entry adds `industry_id` and `industry_label` so the
 * caller can group / label without re-walking the library.
 *
 * @param string $kind Template kind. Only 'order' ships in the free version.
 * @return array<int, array{kind:string,name:string,body:string,industry_id:string,industry_label:string}>
 */
function zignites_chat_get_templates_by_kind($kind) {
    $kind = is_string($kind) ? trim($kind) : '';
    if ($kind === '') return [];

    $library = zignites_chat_get_template_library();
    $out = [];
    foreach ($library as $industry_id => $industry) {
        $label = isset($industry['label']) ? (string) $industry['label'] : (string) $industry_id;
        $templates = isset($industry['templates']) && is_array($industry['templates']) ? $industry['templates'] : [];
        foreach ($templates as $t) {
            if (($t['kind'] ?? '') !== $kind) continue;
            $out[] = [
                'kind'           => (string) $t['kind'],
                'name'           => (string) ($t['name'] ?? ''),
                'body'           => (string) ($t['body'] ?? ''),
                'industry_id'    => (string) $industry_id,
                'industry_label' => $label,
            ];
        }
    }
    return $out;
}
