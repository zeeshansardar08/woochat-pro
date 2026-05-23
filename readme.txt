=== Zignites Chat – Order Notifications & Chat Widget for WooCommerce ===
Contributors: zeeshansardar08
Tags: woocommerce, whatsapp, order notifications, chat widget, abandoned cart
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Order notifications, chat widget, and customer messaging for WooCommerce — via Twilio or Meta WhatsApp Cloud API.

== Description ==

**Zignites Chat** turns WhatsApp into the messaging layer for your WooCommerce store. The moment a customer places an order, they get a WhatsApp confirmation on their phone. When they have a question on your site, a floating chat bubble answers from a FAQ rule set you control. You bring your own Twilio or Meta WhatsApp Cloud API account, so messages go out under your business number — not a third-party relay.

WhatsApp open rates are dramatically higher than email, which makes it the ideal channel for transactional updates that customers actually care about (order received, shipping, support replies).

= What you can do with the free version =

* **Automatic order notifications** — When an order moves to *processing* or *completed*, the customer receives a WhatsApp message using a template you customize with placeholders like `{name}`, `{order_id}`, `{total}` and `{currency_symbol}`.
* **Manual send from the order screen** — A WhatsApp button is added to each WooCommerce order so you can reach out to a customer with one click.
* **Floating chatbot widget** — A 💬 bubble appears on every page. Customers click it and the bot answers from a JSON rule set you define (returns policy, shipping info, order tracking, etc.). Includes a customizable welcome message.
* **Gutenberg blocks** — Drop a *Chatbot* block or a *WhatsApp CTA Button* block into any page or post.
* **Opt-out & suppression list** — Customers who reply STOP or UNSUBSCRIBE are added to a permanent block-list via a secured REST webhook (supports Twilio HMAC-SHA1 and Meta HMAC-SHA256 signature verification). Admins can also add numbers manually.
* **Test mode** — Toggle test mode to log outgoing messages to a local file instead of actually sending them — perfect for trying the plugin before paying for WhatsApp credits.
* **Two providers, your choice** — Configure either **Twilio WhatsApp** (Account SID + Auth Token + WhatsApp-enabled number) or **Meta WhatsApp Cloud API** (access token + phone number ID).
* **Privacy-first** — Built-in WordPress data exporter and eraser integrations so you can honour customer data requests out of the box.
* **HPOS-compatible** — Works with WooCommerce High-Performance Order Storage (Custom Order Tables).

= Who it's for =

* WooCommerce store owners who want higher open rates on order updates than email gives them.
* Stores that lose support time to repetitive FAQs and want a self-serve chat widget.
* Businesses that want a clean, native way to add WhatsApp to checkout — without scattering wa.me links across the theme.

= Pro features (optional upgrade) =

Zignites Chat Pro extends the free version with revenue-recovery and engagement tools:

* **Abandoned cart recovery** — Capture cart abandonment and send a WhatsApp reminder with a one-click resume link after a configurable delay.
* **Post-purchase follow-up scheduler** — Schedule a second message after each order (e.g. review request, support check-in), optionally GPT-generated.
* **Bulk campaigns** — Send WhatsApp blasts to customer segments (all customers, recent buyers, etc.) with throttled delivery.
* **Analytics dashboard** — Track sent / delivered / failed / opted-out events, filter by type and date, export to CSV, and see conversion attribution.
* **A/B testing** — Split test message templates on orders, cart recovery, and follow-ups.
* **Outbound webhooks** — Push message and order-engagement events to external systems (CRM, automations).
* **Chatbot enhancements** — Custom bubble colour, icon, multi-agent routing, and an optional GPT fallback for unmatched questions.

Pro is licensed via the **Zignites Chat → License** screen — the free version works fully on its own and shows clear upgrade prompts only where Pro features would appear.

= Privacy & third-party services =

Zignites Chat does not contact any external service until you, the administrator, enter credentials and enable a feature. When configured, it communicates with:

* **Twilio** — for sending WhatsApp messages via the Twilio API ([Terms](https://www.twilio.com/legal/tos) | [Privacy](https://www.twilio.com/legal/privacy)).
* **Meta / WhatsApp Cloud API** — for sending WhatsApp messages via the Cloud API ([Terms](https://developers.facebook.com/terms/) | [Privacy](https://www.facebook.com/privacy/policy/)).
* **OpenAI (optional)** — only if the Pro GPT follow-up or chatbot fallback is enabled, and only with an API key you provide ([Terms](https://openai.com/policies/terms-of-use) | [Privacy](https://openai.com/privacy/)).
* **Zignites license server** — only when you enter a Pro license key, to validate activation status ([Privacy](https://zignites.com/privacy)).

No personal data is shared with any third party unless the corresponding feature is configured and enabled by the site administrator.

== Installation ==

1. Make sure **WooCommerce** is installed and active.
2. Upload the `zignites-chat` folder to `/wp-content/plugins/`, or install via **Plugins → Add New**.
3. Activate the plugin through the **Plugins** screen.
4. Open the **Zignites Chat** menu in the WordPress admin sidebar.
5. On the **General** tab, choose your provider (Twilio or Meta WhatsApp Cloud API) and paste in your credentials.
6. On the **Messaging** tab, customise the order message template and send yourself a test message.
7. On the **Chatbot** tab, enable the front-end widget and define your FAQ rules.
8. (Optional) On the **License** tab, paste a Zignites Chat Pro key to unlock cart recovery, follow-ups, campaigns, analytics, and more.

== Frequently Asked Questions ==

= Does this plugin require WooCommerce? =

Yes. Zignites Chat self-deactivates if WooCommerce is not active.

= Which WhatsApp APIs are supported? =

Twilio WhatsApp API and Meta WhatsApp Cloud API. You only need to configure one.

= Do you provide the WhatsApp number, or do I bring my own? =

You bring your own. Messages go out from your business number under your provider account, which means deliverability, pricing, and compliance are all controlled by you.

= Will customers be charged for messages? =

No. Standard WhatsApp messaging fees are paid by you to your chosen provider (Twilio or Meta). Customers receive messages like any normal WhatsApp message.

= Can customers opt out? =

Yes. Customers who reply STOP or UNSUBSCRIBE (configurable keywords) are added to a suppression list via a secured REST webhook. You can also add numbers to the suppression list manually.

= Is the chatbot widget customisable? =

The free version lets you set the welcome message and define FAQ rules. Bubble colour, icon, multi-agent routing, and GPT fallback are part of Zignites Chat Pro.

= Is it compatible with WooCommerce HPOS (High-Performance Order Storage)? =

Yes. Zignites Chat declares compatibility with WooCommerce Custom Order Tables.

= Does it support GDPR / personal data requests? =

Yes. The plugin registers data exporter and eraser handlers with WordPress so customer requests under **Tools → Export / Erase Personal Data** include messages, cart attempts, and campaign records associated with their email or phone.

= Can I test the plugin without sending real WhatsApp messages? =

Yes. Enable **Test Mode** in the General settings — messages are written to `wp-content/uploads/zignites-chat/zignites-chat.log` instead of being sent.

== Screenshots ==

1. The Zignites Chat dashboard — at-a-glance message count, delivery rate, and license status.
2. General settings — choose your provider (Twilio or Meta WhatsApp Cloud API) and enter credentials.
3. Messaging settings — customise the order notification template with merge tags and send a test message.
4. Chatbot settings — enable the widget, set the welcome message, and define FAQ rules.
5. The floating chatbot widget on the storefront, opened with the welcome message.
6. The WhatsApp message a customer receives after placing an order.
7. The manual "Send WhatsApp" action on the WooCommerce order list.
8. The opt-out and suppression list management screen.
9. The Gutenberg WhatsApp CTA button block inside the editor.

== Changelog ==

= 1.0.0 =
* Initial release on WordPress.org.
* Automatic WhatsApp order notifications via Twilio or Meta WhatsApp Cloud API.
* Floating front-end chatbot widget with FAQ rules.
* Manual WhatsApp send button on WooCommerce orders.
* Opt-out webhook with Twilio and Meta signature verification.
* Suppression list management.
* Gutenberg blocks for chatbot embed and WhatsApp CTA button.
* Test mode with local message logging.
* WordPress privacy exporter and eraser integration.
* HPOS (WooCommerce Custom Order Tables) compatible.

== Upgrade Notice ==

= 1.0.0 =
Initial public release.
