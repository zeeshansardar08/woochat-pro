=== Zignites Chat Pro – WhatsApp Marketing for WooCommerce ===
Contributors: zeeshansardar08
Tags: woocommerce, whatsapp, order notifications, abandoned cart, marketing
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.1.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

The complete WhatsApp marketing suite for WooCommerce — order notifications, cart recovery, follow-ups, campaigns, analytics, multi-agent chat, and webhooks.

== Description ==

**Zignites Chat Pro** is the premium edition of Zignites Chat. It turns WhatsApp into a full marketing channel for your WooCommerce store: transactional updates, abandoned cart recovery, scheduled follow-ups, broadcast campaigns, conversation-level analytics, and a smart multi-agent chatbot that can fall back to GPT. You bring your own Twilio or Meta WhatsApp Cloud API account — messages always go out under your business number.

= What you get with Pro =

* **Automatic order notifications** — Customers receive a WhatsApp confirmation the moment an order moves to processing or completed. Templates support `{name}`, `{order_id}`, `{total}`, `{currency_symbol}` and more.
* **Abandoned cart recovery** — Captures cart abandonment via the Store API and sends a WhatsApp reminder with a one-click resume link after a configurable delay.
* **Post-purchase follow-up scheduler** — Schedule a second message after each order (review request, support check-in, upsell), optionally generated with GPT.
* **Bulk WhatsApp campaigns** — Broadcast to customer segments (all customers, recent buyers, custom phone lists) with throttled chunked delivery so your provider's rate limits stay safe.
* **Conversion analytics dashboard** — Track sent / delivered / failed / opted-out events, filter by type and date, export to CSV, and attribute orders to recovery / follow-up messages.
* **A/B testing** — Split test message templates on order notifications, cart recovery, and follow-ups, with per-variant analytics.
* **Multi-agent chatbot** — Route incoming chats to multiple WhatsApp agents (round-robin or assigned), with a fully customisable bubble colour, icon and welcome message.
* **GPT fallback** — When a customer asks something not in your FAQ rule set, optionally hand off to a configurable GPT endpoint (OpenAI-compatible) for a smart answer.
* **Outbound webhooks** — Push message and order-engagement events to external systems (CRMs, automations, data warehouses) with HMAC-signed payloads and retry-with-backoff.
* **Manual send from the order screen** — A WhatsApp button on every WooCommerce order so support can reach out in one click.
* **Gutenberg blocks** — Drop a Chatbot block or WhatsApp CTA button block into any page or post.
* **Opt-out & suppression list** — Twilio HMAC-SHA1 and Meta HMAC-SHA256 verified opt-out webhook. Customers who reply STOP/UNSUBSCRIBE are added to a permanent block-list.
* **Test mode** — Log outgoing messages to a local file instead of sending — perfect for staging.
* **Two providers, your choice** — Twilio WhatsApp or Meta WhatsApp Cloud API.
* **Privacy-first** — Built-in WordPress data exporter / eraser, GDPR-friendly.
* **HPOS-compatible** — Works with WooCommerce High-Performance Order Storage.

= Licensing =

Zignites Chat Pro is licensed via Freemius. After installation, open **Zignites Chat → Account** to activate your license. Your license unlocks automatic updates straight from the WordPress dashboard for the duration of your subscription.

= Privacy & third-party services =

Zignites Chat Pro does not contact any external service until you, the administrator, enter credentials and enable a feature. When configured, it communicates with:

* **Twilio** — for sending WhatsApp messages via the Twilio API ([Terms](https://www.twilio.com/legal/tos) | [Privacy](https://www.twilio.com/legal/privacy)).
* **Meta / WhatsApp Cloud API** — for sending WhatsApp messages via the Cloud API ([Terms](https://developers.facebook.com/terms/) | [Privacy](https://www.facebook.com/privacy/policy/)).
* **OpenAI (optional)** — only if the GPT follow-up or chatbot fallback is enabled, and only with an API key you provide ([Terms](https://openai.com/policies/terms-of-use) | [Privacy](https://openai.com/privacy/)).
* **Freemius** — for license validation, billing, and update delivery ([Privacy](https://freemius.com/privacy/)).

No personal data is shared with any third party unless the corresponding feature is configured and enabled by the site administrator.

== Installation ==

1. Make sure **WooCommerce** is installed and active.
2. Upload the `zignites-chat-pro` folder to `/wp-content/plugins/`, or install via **Plugins → Add New → Upload Plugin**.
3. Activate the plugin through the **Plugins** screen.
4. Open **Zignites Chat → Account** and activate your license.
5. On the **General** tab, choose your provider (Twilio or Meta WhatsApp Cloud API) and paste in your credentials, then click **Test Connection**.
6. On the **Messaging** tab, customise the order message template and send yourself a test message.
7. Enable cart recovery, follow-up scheduler, campaigns, analytics, and the chatbot on their respective tabs.

== Frequently Asked Questions ==

= Does this plugin require WooCommerce? =

Yes. Zignites Chat Pro self-deactivates if WooCommerce is not active.

= Which WhatsApp APIs are supported? =

Twilio WhatsApp API and Meta WhatsApp Cloud API. You only need to configure one.

= Do you provide the WhatsApp number, or do I bring my own? =

You bring your own. Messages go out from your business number under your provider account, which means deliverability, pricing, and compliance are all controlled by you.

= Will customers be charged for messages? =

No. Standard WhatsApp messaging fees are paid by you to your chosen provider. Customers receive messages like any normal WhatsApp message.

= Can customers opt out? =

Yes. Customers who reply STOP or UNSUBSCRIBE (configurable keywords) are added to a suppression list via a secured REST webhook.

= How do updates work? =

License-holders receive automatic updates through the standard WordPress updates screen, delivered via Freemius. As long as your subscription is active, you'll see new versions appear alongside core / theme / other plugin updates.

= Can I migrate from the free version? =

Yes. Deactivate Zignites Chat (free) and activate Zignites Chat Pro. All your settings, FAQ rules, and message templates carry over — they share the same option keys.

= Is it compatible with WooCommerce HPOS? =

Yes. Zignites Chat Pro declares compatibility with WooCommerce Custom Order Tables.

= Does it support GDPR / personal data requests? =

Yes. The plugin registers WordPress data exporter and eraser handlers so customer requests under **Tools → Export / Erase Personal Data** include messages, cart attempts, and campaign records.

= Can I test the plugin without sending real WhatsApp messages? =

Yes. Enable **Test Mode** in the General settings — messages are written to `wp-content/uploads/zignites-chat/zignites-chat.log` instead of being sent.

== Changelog ==

= 1.1.0 =
* First Pro release branched from Zignites Chat 1.0.0.
* All paid features unlocked: cart recovery, follow-up scheduler, bulk campaigns, analytics dashboard, multi-agent chatbot, GPT fallback, A/B testing, outbound webhooks, full log viewer with CSV export.
* Freemius integration for license validation and automatic updates.
* New "Test Connection" button on General Settings for Twilio and Meta Cloud API.
* Hardened error handling across campaign delivery, cart recovery queue, follow-up scheduler, webhook dispatch, and GPT fallback.

= 1.0.0 =
* See the free version changelog at https://wordpress.org/plugins/zignites-chat.

== Upgrade Notice ==

= 1.1.0 =
First Pro release. License via Freemius for automatic updates.
