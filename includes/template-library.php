<?php
/**
 * Pre-written template library.
 *
 * Static catalog of WhatsApp message starters organised by industry ×
 * message kind (order / cart_recovery / followup). Surfaced on the admin
 * tabs as a "Browse template library" modal next to each textarea so a
 * fresh install has a sensible starting point instead of staring at a
 * blank box.
 *
 * Adding a new industry, kind, or template is a one-stop edit here; the
 * modal UI iterates whatever wcwp_get_template_library() returns.
 *
 * Each template entry:
 *   - kind: 'order' | 'cart_recovery' | 'followup'
 *   - name: short label shown on the card (translated)
 *   - body: message body with WooChat placeholders (translated)
 *
 * Placeholders by kind (matching what the existing dispatchers
 * substitute — see tab-messaging.php / tab-cart-recovery.php /
 * tab-scheduler.php):
 *   - order:         {name}, {order_id}, {total}, {currency_symbol}
 *   - cart_recovery: {items}, {total}, {currency_symbol}, {cart_url}
 *   - followup:      {name}, {order_id}, {total}, {currency_symbol},
 *                    {status}, {date}
 */

if (!defined('ABSPATH')) exit;

/**
 * Return the full template library, grouped by industry.
 *
 * @return array<string, array{label:string, templates: array<int, array{kind:string,name:string,body:string}>}>
 */
function wcwp_get_template_library() {
    $library = [
        'fashion' => [
            'label'     => __('Fashion & Apparel', 'woochat-pro'),
            'templates' => [
                [
                    'kind' => 'order',
                    'name' => __('Stylish confirmation', 'woochat-pro'),
                    'body' => __("Hi {name}! 👗 Your order #{order_id} is confirmed — total {total} {currency_symbol}. We'll text you the moment it ships ✨", 'woochat-pro'),
                ],
                [
                    'kind' => 'order',
                    'name' => __('VIP packing', 'woochat-pro'),
                    'body' => __("Thanks {name}! Order #{order_id} ({total} {currency_symbol}) is being styled and packed with care. 💌", 'woochat-pro'),
                ],
                [
                    'kind' => 'order',
                    'name' => __('Drop-in confirmation', 'woochat-pro'),
                    'body' => __("Yay {name}! 🛍️ Order #{order_id} for {total} {currency_symbol} is in. New favourites incoming!", 'woochat-pro'),
                ],
                [
                    'kind' => 'cart_recovery',
                    'name' => __('FOMO nudge', 'woochat-pro'),
                    'body' => __("Hey, these gems are still waiting in your bag 👀\n\n{items}\n\nTotal: {total} {currency_symbol}\nGrab them before they sell out → {cart_url}", 'woochat-pro'),
                ],
                [
                    'kind' => 'cart_recovery',
                    'name' => __('Wishlist saved', 'woochat-pro'),
                    'body' => __("Don't miss out — your wishlist items are saved:\n\n{items}\n\nTotal: {total} {currency_symbol}\nCheckout in seconds: {cart_url}", 'woochat-pro'),
                ],
                [
                    'kind' => 'cart_recovery',
                    'name' => __('Friendly reminder', 'woochat-pro'),
                    'body' => __("👋 Quick reminder — your cart is saved.\n\n{items}\nTotal: {total} {currency_symbol}\n{cart_url}", 'woochat-pro'),
                ],
                [
                    'kind' => 'followup',
                    'name' => __('Loving the look?', 'woochat-pro'),
                    'body' => __("Hey {name}! 👋 How are you loving order #{order_id}? Reply with a 💖 if you want first dibs on next drops!", 'woochat-pro'),
                ],
                [
                    'kind' => 'followup',
                    'name' => __('Quick review ask', 'woochat-pro'),
                    'body' => __("Hi {name}! It's been a few days since order #{order_id} — we'd love to hear what you think. Got a sec for a quick review?", 'woochat-pro'),
                ],
                [
                    'kind' => 'followup',
                    'name' => __('VIP invite', 'woochat-pro'),
                    'body' => __("{name}! Hope you're rocking your new pieces 🌟 Order #{order_id} treating you well? Reply YES to join our VIP list.", 'woochat-pro'),
                ],
            ],
        ],
        'food' => [
            'label'     => __('Food & Restaurants', 'woochat-pro'),
            'templates' => [
                [
                    'kind' => 'order',
                    'name' => __('Order received', 'woochat-pro'),
                    'body' => __("Hi {name}! 🍽️ Order #{order_id} is in — total {total} {currency_symbol}. We'll let you know when it's on the way.", 'woochat-pro'),
                ],
                [
                    'kind' => 'order',
                    'name' => __('Cooking now', 'woochat-pro'),
                    'body' => __("Thanks {name}! Order #{order_id} ({total} {currency_symbol}) is being prepared fresh. ETA in your confirmation email.", 'woochat-pro'),
                ],
                [
                    'kind' => 'order',
                    'name' => __('Made with love', 'woochat-pro'),
                    'body' => __("{name}, order received! 👨‍🍳 #{order_id} for {total} {currency_symbol}. Cooking with love.", 'woochat-pro'),
                ],
                [
                    'kind' => 'cart_recovery',
                    'name' => __('Hungry yet?', 'woochat-pro'),
                    'body' => __("Hi! Hungry yet? 🍽️\n\nYour cart:\n{items}\n\nTotal: {total} {currency_symbol}\nFinish your order: {cart_url}", 'woochat-pro'),
                ],
                [
                    'kind' => 'cart_recovery',
                    'name' => __('Selection saved', 'woochat-pro'),
                    'body' => __("Still thinking about it? Your selection is saved:\n\n{items}\n\nTotal: {total} {currency_symbol}\n{cart_url}", 'woochat-pro'),
                ],
                [
                    'kind' => 'cart_recovery',
                    'name' => __('Quick checkout', 'woochat-pro'),
                    'body' => __("Quick reminder! 🛒\n\n{items}\nTotal: {total} {currency_symbol}\n\nCheckout in 30 seconds: {cart_url}", 'woochat-pro'),
                ],
                [
                    'kind' => 'followup',
                    'name' => __('How was the meal?', 'woochat-pro'),
                    'body' => __("Hi {name}! Hope you enjoyed order #{order_id}. We'd love a quick review — reply with anything from 1-5 ⭐", 'woochat-pro'),
                ],
                [
                    'kind' => 'followup',
                    'name' => __('Favourites for next time', 'woochat-pro'),
                    'body' => __("Hey {name}! 🍴 How was your meal from order #{order_id}? Got any favourites you'd like next time?", 'woochat-pro'),
                ],
                [
                    'kind' => 'followup',
                    'name' => __('Specials opt-in', 'woochat-pro'),
                    'body' => __("{name}, thanks again for ordering with us! Order #{order_id} treating you well? Reply YES if you'd like to hear about specials.", 'woochat-pro'),
                ],
            ],
        ],
        'services' => [
            'label'     => __('Services & Consulting', 'woochat-pro'),
            'templates' => [
                [
                    'kind' => 'order',
                    'name' => __('Booking confirmed', 'woochat-pro'),
                    'body' => __("Hi {name}, thank you for your booking. Reference #{order_id}, total {total} {currency_symbol}. Confirmation details have been emailed to you.", 'woochat-pro'),
                ],
                [
                    'kind' => 'order',
                    'name' => __('Next steps incoming', 'woochat-pro'),
                    'body' => __("Hello {name}, your order #{order_id} for {total} {currency_symbol} has been received. We will be in touch shortly to confirm next steps.", 'woochat-pro'),
                ],
                [
                    'kind' => 'order',
                    'name' => __('Open line', 'woochat-pro'),
                    'body' => __("Thank you {name}. Order #{order_id} ({total} {currency_symbol}) is confirmed. Please reply if you have any questions.", 'woochat-pro'),
                ],
                [
                    'kind' => 'cart_recovery',
                    'name' => __('Unfinished booking', 'woochat-pro'),
                    'body' => __("Hi, you have an unfinished booking with us:\n\n{items}\n\nTotal: {total} {currency_symbol}\n\nResume here: {cart_url}", 'woochat-pro'),
                ],
                [
                    'kind' => 'cart_recovery',
                    'name' => __('Selection saved', 'woochat-pro'),
                    'body' => __("Hello, just a friendly reminder that your selection is still saved:\n\n{items}\n\nTotal: {total} {currency_symbol}\nComplete your booking: {cart_url}", 'woochat-pro'),
                ],
                [
                    'kind' => 'cart_recovery',
                    'name' => __('Time-limited hold', 'woochat-pro'),
                    'body' => __("Reminder: your cart is held for a limited time.\n\n{items}\nTotal: {total} {currency_symbol}\n{cart_url}", 'woochat-pro'),
                ],
                [
                    'kind' => 'followup',
                    'name' => __('Open for questions', 'woochat-pro'),
                    'body' => __("Hi {name}, thank you again for choosing us. If you have any questions about order #{order_id}, simply reply to this message.", 'woochat-pro'),
                ],
                [
                    'kind' => 'followup',
                    'name' => __('Feedback request', 'woochat-pro'),
                    'body' => __("Hello {name}, we hope everything went smoothly with order #{order_id}. We would appreciate any feedback you can share.", 'woochat-pro'),
                ],
                [
                    'kind' => 'followup',
                    'name' => __("What's next?", 'woochat-pro'),
                    'body' => __("{name}, thanks for trusting us with your project. Order #{order_id} is now complete on our end. Let us know how we can help next.", 'woochat-pro'),
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
    return (array) apply_filters('wcwp_template_library', $library);
}

/**
 * Return all templates of a given kind, flattened across industries.
 *
 * Each returned entry adds `industry_id` and `industry_label` so the
 * caller can group / label without re-walking the library.
 *
 * @param string $kind One of: order, cart_recovery, followup.
 * @return array<int, array{kind:string,name:string,body:string,industry_id:string,industry_label:string}>
 */
function wcwp_get_templates_by_kind($kind) {
    $kind = is_string($kind) ? trim($kind) : '';
    if ($kind === '') return [];

    $library = wcwp_get_template_library();
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
