=== Zignites Chat – WhatsApp Order Notifications & Chat for WooCommerce ===
Contributors: zeeshansardar08
Tags: woocommerce, whatsapp, order notifications, whatsapp chat, customer messaging
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Send WhatsApp order notifications, add a floating chat widget, and let customers reach you instantly. Supports Twilio and WhatsApp Cloud API.

== Description ==

**Zignites Chat** connects your WooCommerce store to WhatsApp — the messaging platform with 90%+ open rates. Send automated order notifications, let customers chat with your team via a floating widget, and never miss a sale to slow communication.

= Why WhatsApp for Your Store? =

Email open rates average 20%. WhatsApp open rates exceed 90%. Your customers are already on WhatsApp every day. Zignites Chat bridges your store and their inbox.

= Free Features =

* **Order Notifications** — Automatically send a WhatsApp message when a new order is placed. Fully customisable template with order ID, customer name, total, and currency.
* **Manual Message Button** — Send a WhatsApp message directly from the WooCommerce order edit screen with one click.
* **Floating Chat Widget** — Add a floating WhatsApp chat button to your storefront so customers can contact you instantly.
* **FAQ Chatbot** — Configure keyword-based auto-replies so the widget answers common questions before routing customers to WhatsApp.
* **Gutenberg Blocks** — WhatsApp button and chatbot widget blocks for the block editor.
* **Dual Provider Support** — Works with both Twilio and WhatsApp Cloud API (Meta). Choose what fits your setup and budget.
* **Test Mode** — Log messages instead of sending them so you can verify your setup before going live.
* **Log Viewer** — View, filter, download, and clear the message log directly from the admin.
* **Opt-out Management** — GDPR-friendly: customers can reply STOP (or any keyword you configure) to opt out. Their number is added to a suppression list and will never receive messages again.
* **Template Library** — Pre-built message templates to get you started quickly.
* **Privacy Tools** — Built-in data export and erasure hooks for WordPress's personal data tools.

= Pro Features =

Upgrade to [Zignites Chat Pro](https://zignites.com/plugins/zignites-chat-pro) for advanced automation:

* **Cart Recovery** — Send automated WhatsApp reminders when customers abandon their cart. Recover lost revenue with high open-rate messaging.
* **Analytics Dashboard** — Track message delivery, click-through rates, and revenue attributed to WhatsApp.
* **A/B Testing** — Test two message templates against each other and automatically use the winner.
* **Follow-up Scheduler** — Send post-purchase follow-ups automatically to drive reviews, upsells, and repeat orders.
* **Bulk Campaigns** — Send targeted WhatsApp messages to customer segments for promotions and re-engagement.
* **AI Chatbot** — GPT-powered auto-replies for complex customer questions beyond your FAQ list.
* **Webhooks** — Connect Zignites Chat to external tools and automation platforms via webhooks.
* **Multi-Agent Routing** — Route customer chats to different team members automatically.
* **Priority Support** — Direct email support with faster response times.

= Who Is This For? =

* WooCommerce store owners who want fast, direct customer communication.
* Stores with customers in regions where WhatsApp is the primary messaging platform (South Asia, Middle East, Latin America, Southeast Asia, Africa).
* Businesses that want to move beyond slow email with a channel customers already use every day.

= Privacy & Data =

Zignites Chat sends data to the following third-party services **only when explicitly configured by the site administrator**:

* **Twilio** — for sending WhatsApp messages via the Twilio API ([Privacy Policy](https://www.twilio.com/legal/privacy)).
* **Meta / WhatsApp Cloud API** — for sending WhatsApp messages via the Cloud API ([Privacy Policy](https://www.facebook.com/privacy/policy/)).

No data is sent to any external service without the administrator explicitly entering credentials and enabling the feature. No data is ever sent to Zignites servers.

== Installation ==

1. Upload the `zignites-chat` folder to `/wp-content/plugins/`, or install directly from the WordPress plugin screen.
2. Activate the plugin through the **Plugins** menu.
3. Go to **Zignites Chat → General Settings**.
4. Choose your API provider (Twilio or WhatsApp Cloud) and enter your credentials.
5. Go to **Messaging** to customise your order notification template and send a test message.
6. Optionally enable the chat widget under **Chatbot**.

= Minimum Requirements =

* WordPress 6.0 or greater
* WooCommerce 7.0 or greater
* PHP 7.4 or greater

== Frequently Asked Questions ==

= Does this plugin require WooCommerce? =

Yes. Zignites Chat will not activate unless WooCommerce is installed and active.

= Which WhatsApp APIs are supported? =

Both Twilio's WhatsApp API and Meta's WhatsApp Cloud API are fully supported. You configure credentials for one provider in General Settings.

= Do I need a paid Twilio or WhatsApp Cloud account? =

Twilio requires a paid account for production use. WhatsApp Cloud API is free for the first 1,000 conversations per month with a Meta Business account.

= Can customers opt out of messages? =

Yes. When a customer replies with STOP (or any keyword you define), their number is added to a suppression list and they will never receive messages again. You can also manage this list manually in General Settings.

= Is this plugin GDPR compliant? =

Yes. Zignites Chat includes opt-out management, a suppression list, and integrates with WordPress's built-in personal data export and erasure tools.

= Does this work with WooCommerce HPOS (High-Performance Order Storage)? =

Yes. Zignites Chat is fully compatible with WooCommerce HPOS.

= Can I customise the order notification message? =

Yes. The message template supports `{name}`, `{order_id}`, `{total}`, and `{currency_symbol}` placeholders.

= Will the plugin slow down my store? =

No. Messages are dispatched via WordPress cron. The frontend performance of your store is not affected.

== Screenshots ==

1. Dashboard — quick actions and plugin overview.
2. General Settings — configure your WhatsApp API provider and credentials.
3. Messaging — customise your order notification template and send a test message.
4. Chatbot — configure the floating chat widget and FAQ auto-replies.
5. Logs — filter, view, download, and clear the message log.
6. Chat widget on the storefront — floating WhatsApp button with FAQ auto-reply.

== Changelog ==

= 1.0.0 =
* Initial release.
* Automatic order notifications via WhatsApp (Twilio + Cloud API).
* Manual WhatsApp message button on the order edit screen.
* Floating chat widget with FAQ keyword matching.
* Gutenberg blocks for the WhatsApp button and chat widget.
* Test mode with log viewer (filter, download, clear).
* Opt-out management with suppression list.
* Template library with pre-built message templates.
* GDPR privacy tools (data export and erasure).

== Upgrade Notice ==

= 1.0.0 =
Initial release of Zignites Chat.
