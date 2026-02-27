=== WooChat Pro – WhatsApp for WooCommerce ===
Contributors: zeecreatives
Tags: woocommerce, whatsapp, cart recovery, chatbot, order notification
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0.1
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Send WhatsApp messages for WooCommerce orders, recover abandoned carts, and engage customers with a smart chatbot widget.

== Description ==

**WooChat Pro** is a powerful, lightweight WordPress plugin that integrates WhatsApp messaging directly into WooCommerce, helping store owners increase conversions, recover abandoned carts, and engage customers faster.

= Core Features =

* **Order Notifications** – Send WhatsApp messages when orders are placed or completed.
* **Cart Recovery** – Automatically remind users via WhatsApp if they abandon their carts.
* **Smart Chatbot Widget** – Floating WhatsApp bot that answers FAQs and links to support.
* **License Control System** – Lock premium features behind license keys.
* **Follow-up Scheduler** – Schedule personalised follow-up messages after orders.
* **Analytics Dashboard** – Track sent, delivered, and clicked message statistics.
* **GPT Integration** – Optionally generate follow-up copy with a GPT endpoint.

= Privacy & Data =

WooChat Pro sends data to the following third-party services when configured by the site administrator:

* **Twilio** – for sending WhatsApp messages via the Twilio API ([Privacy Policy](https://www.twilio.com/legal/privacy)).
* **Meta / WhatsApp Cloud API** – for sending WhatsApp messages via the Cloud API ([Privacy Policy](https://www.facebook.com/privacy/policy/)).
* **OpenAI (optional)** – if the GPT follow-up feature is enabled ([Privacy Policy](https://openai.com/privacy/)).

No data is sent to any external service without the administrator explicitly configuring credentials and enabling the feature.

== Installation ==

1. Upload the `woochat-pro` folder to the `/wp-content/plugins/` directory, or install via the WordPress plugin screen.
2. Activate the plugin through the **Plugins** screen.
3. Go to **WooChat** in the admin menu and enter your Twilio or WhatsApp Cloud API credentials.
4. Configure order messages, cart recovery, chatbot, and other features.
5. Optionally enter a license key to unlock Pro features.

== Frequently Asked Questions ==

= Does this plugin require WooCommerce? =

Yes. WooChat Pro will deactivate itself if WooCommerce is not active.

= Which WhatsApp APIs are supported? =

Twilio WhatsApp API and Meta WhatsApp Cloud API.

= Is the chatbot widget customisable? =

Yes. You can change the bubble colour, text colour, icon, and welcome message from the settings page.

== Changelog ==

= 1.0.1 =
* Security hardening and coding-standards compliance.
* HPOS (High-Performance Order Storage) compatibility.
* Full internationalisation (i18n) support.
* Moved log file to uploads directory.
* Fixed XSS in chatbot widget.
* Added nonce verification to analytics tracking AJAX.
* Improved input sanitisation for cart recovery data.
* Password-masked all API secret fields.

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.0.1 =
Security and compatibility update. Recommended for all users.
