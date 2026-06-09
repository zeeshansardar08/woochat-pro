# Zignites Chat Pro — Pre-Release Manual QA Checklist

Internal QA doc. **Not shipped to customers** (excluded from the release zip via
`.distignore` / `bin/build-release.ps1`).

The automated suite (`vendor/bin/phpunit`) only covers pure helpers. Everything
below — the provider sends, webhooks, AJAX, cron and DB glue — has **no
automated coverage**, so it must be exercised by hand against a real Twilio
account and a real Meta WhatsApp Cloud API account before each public release.

Mark each box `[x]` as it passes. File a bug (don't tick) on any deviation from
the **Expected** result. Sign off at the bottom only when every 🔴 item passes
on **both providers**.

> 🔴 = release blocker (must pass) · 🟡 = important · ⚪ = nice-to-have

---

## 0. Test build & environment

- Version under test: `__________`  (must match `zignites-chat.php` header, `readme.txt` Stable tag, `ZIGNITES_CHAT_VERSION`)
- Tester: `__________`   Date: `__________`

Run each 🔴 row on the full matrix:

| Axis | Values to cover |
|---|---|
| Provider | Twilio · Meta WhatsApp Cloud API |
| WooCommerce HPOS | Enabled · Disabled (legacy post storage) |
| PHP | 7.4 (min) · current (8.x) |
| WordPress | `Requires at least` (6.0) · current |
| License state | Active (Freemius) · Inactive (Pro gates locked) |

- [ ] 🔴 Install the **built zip** (`bin/build-release.ps1` output), not the dev folder — confirms the distributable actually works.
- [ ] 🔴 Activating with WooCommerce inactive shows the dependency notice and self-deactivates; activating with WooCommerce active boots cleanly (no PHP notices/warnings with `WP_DEBUG` on).
- [ ] 🟡 `WP_DEBUG_LOG` is clean of plugin notices/deprecations through a full pass.

---

## 1. Licensing & Pro gating (Freemius)

- [ ] 🔴 Fresh install (no license): Pro features are gated/locked; free features work.
- [ ] 🔴 Activate a license via the Account screen → Pro features unlock without a reload glitch.
- [ ] 🔴 Deactivate the license → Pro gates re-lock; no fatal errors.
- [ ] 🟡 Confirm Freemius is in **production** mode (real purchase → activation → entitlement), not sandbox.
- [ ] 🟡 `ZIGNITES_CHAT_DEV_UNLOCK` override unlocks Pro on a staging site and is clearly not for production.
- [ ] 🟡 Legacy `license-manager.php` path does not contradict Freemius entitlement (no "licensed in one, locked in the other").

---

## 2. Provider connectivity

- [ ] 🔴 **General Settings** save token/SID/phone for each provider; "Test connection" succeeds.
- [ ] 🔴 Send a **test message** (Messaging tab) to your own number — arrives on WhatsApp.
- [ ] 🔴 Invalid credentials produce a readable error, not a fatal/blank screen.
- [ ] 🟡 Test Mode ON: messages are simulated/logged, not actually sent; badge shows on Messaging.

---

## 3. Inbound webhook & two-way inbox  ⚠️ flagged-pending gap

Point the provider's inbound webhook at `…/wp-json/zignites-chat/v1/inbound` (or `/optout`).

- [ ] 🔴 **Twilio**: reply to your store number → message appears in the Inbox thread (signature `x-twilio-signature` verified).
- [ ] 🔴 **Meta**: reply to your store number → message appears in the Inbox thread (`X-Hub-Signature-256` verified with the App Secret set).
- [ ] 🔴 A request with a **bad/forged signature** is rejected (HTTP 401) and nothing is recorded.
- [ ] 🔴 Inbox: send a **reply** from the agent UI → customer receives it; thread updates.
- [ ] 🟡 Assign a thread to an agent; add an internal note — both persist and render.
- [ ] 🟡 Canned replies insert correctly.
- [ ] 🟡 Inbox email notification fires when enabled.
- [ ] ⚪ Inbound from an unknown number creates a new thread with ProfileName (Twilio) / contact name (Meta).

---

## 4. Delivery receipts

- [ ] 🔴 **Twilio**: status callback at `…/v1/status/twilio` flips a sent message to delivered/read/failed in Analytics.
- [ ] 🔴 **Meta**: status objects on the inbound webhook update delivery state; a status-only payload returns 200 without creating a message.
- [ ] 🟡 Failed send is recorded as `failed` with a reason and surfaces in the log.

---

## 5. Transactional messaging

- [ ] 🔴 Order → **processing/completed** sends the order notification with `{name} {order_id} {total} {currency_symbol}` resolved.
- [ ] 🔴 **COD confirmation**: a new COD order sends the confirm/cancel prompt; customer reply **Confirm** / **Cancel** updates the order status; COD stat cards increment.
- [ ] 🔴 **Status notifications**: configured status transitions send the mapped message.
- [ ] 🔴 **Opt-in capture**: checkout opt-in checkbox stores consent; fires the `optin` drip trigger.
- [ ] 🟡 Manual "Send WhatsApp" button on the order screen works.
- [ ] 🟡 Transactional sends are **not** blocked by quiet hours or marketing consent (only marketing is).

---

## 6. Marketing automations

- [ ] 🔴 **Cart recovery**: abandon a cart (Store API), wait the delay → reminder with resume link; respects consent + quiet hours + rate limit.
- [ ] 🔴 **Follow-up scheduler**: post-order follow-up fires after N days; GPT variant works when enabled; bounded retries on transient failure.
- [ ] 🔴 **Bulk campaign**: create a campaign to a segment → chunked, throttled delivery; suppression list skipped; counts (sent/failed/skipped) accurate; scheduled send queues until its time.
- [ ] 🔴 **Drip sequences**: each trigger enrolls correctly and steps send on schedule —
  - [ ] `order_completed`
  - [ ] `optin`
  - [ ] `win_back` (daily scan enrolls a customer whose last order is N days old)
  - [ ] `browse_abandon` (logged-in customer with billing phone views a product, daily scan enrolls with `{product}`/`{product_url}`)
- [ ] 🟡 **Back-in-stock**: subscribe on an out-of-stock product; restock → alert sends.
- [ ] 🟡 **Review request**: fires N days after the delivered status; per-order dedupe holds.
- [ ] 🟡 Sequence enrollment is idempotent (re-trigger does not restart/duplicate).

---

## 7. Consent, compliance & throttling

- [ ] 🔴 **Opt-out / STOP**: customer replies STOP/UNSUBSCRIBE → added to suppression list; subsequent marketing sends are blocked; transactional still allowed per policy.
- [ ] 🔴 **Marketing consent gate**: with "require consent" on, non-consented numbers are skipped by marketing automations.
- [ ] 🔴 **Quiet hours**: marketing sends during the window are deferred and resume after it (overnight window handled); nothing dropped.
- [ ] 🔴 **Rate limiter under burst**  ⚠️ flagged-pending: queue a campaign large enough to exceed the per-minute budget → the run stops at the cap, remaining recipients carry to the next tick, none lost or double-sent.
- [ ] 🟡 **Meta App Secret missing notice**: on Cloud provider with empty App Secret, the admin notice shows and links to General Settings; setting the secret clears it.

---

## 8. Chatbot & GPT  ⚠️ flagged-pending gap

- [ ] 🔴 Chatbot widget renders on the front end (bubble colour/icon/welcome as configured).
- [ ] 🔴 FAQ rule match returns the right answer.
- [ ] 🔴 **GPT fallback** (catalog context): an off-FAQ question hands off to the GPT endpoint and returns a relevant reply; product/catalog context is included when enabled.
- [ ] 🔴 GPT endpoint failure degrades gracefully (no fatal; admin GPT-error notice shows).
- [ ] 🟡 Front-end `nopriv` GPT AJAX is nonce-gated, Pro-gated, length-capped, and **IP rate-limited** (hammer it → 429).
- [ ] 🟡 Multi-agent routing (round-robin / assigned) directs chats correctly.

---

## 8b. Templates & sender health (Cloud)

- [ ] 🟡 **WA template mapping**: each message type maps to an approved template; names/variable counts match WhatsApp Manager; sends use templates outside the 24h window.
- [ ] 🟡 **Sync from Meta**: pulls approved templates; autocomplete + reference list populate.
- [ ] 🟡 **Sender health panel** (dashboard, Cloud only): Refresh pulls quality rating + messaging tier; hidden on Twilio/free.

---

## 9. Analytics & A/B

- [ ] 🟡 Sent / delivered / read / clicked / failed counts increment correctly; date/type/phone filters work.
- [ ] 🟡 CSV export downloads and matches the filtered view.
- [ ] 🟡 Attributed-orders / revenue figures are sane.
- [ ] 🟡 A/B test splits variants and reports per-variant stats.

---

## 10. Admin UI & blocks

- [ ] 🟡 **Dark mode** is readable on every tab — spot-check the recently fixed ones: Campaigns, WhatsApp Templates, COD, Analytics cards, Opt-in, Sequences, and the Logs/Analytics filter toolbars (controls aligned, primary button not oversized).
- [ ] 🟡 Gutenberg **Chatbot block** and **WhatsApp CTA button block** insert and render on the front end.
- [ ] ⚪ Onboarding wizard runs once on first install and can be dismissed.

---

## 11. Privacy & uninstall

- [ ] 🔴 With "delete data on uninstall" **off**, uninstall keeps options/tables.
- [ ] 🔴 With it **on**, uninstall removes plugin options, custom tables, cron events, view-tracking user meta, and the upload log dir.
- [ ] 🟡 GDPR exporter returns the customer's events/carts/campaign records; eraser removes them.
- [ ] 🟡 No secrets (tokens/keys) appear in the log file.

---

## 12. Cross-cutting

- [ ] 🔴 Full pass with **HPOS enabled** and again **disabled** — phone matching, order lookups and meta queries behave identically.
- [ ] 🟡 No PHP 7.4 syntax/runtime issues (run the matrix's 7.4 leg).
- [ ] ⚪ Multisite: network-activate, confirm per-site isolation (if multisite is supported).

---

## Sign-off

| Provider | HPOS | All 🔴 pass? | Tester | Date |
|---|---|---|---|---|
| Twilio | on |  |  |  |
| Twilio | off |  |  |  |
| Meta Cloud | on |  |  |  |
| Meta Cloud | off |  |  |  |

**Release approved for public:** ☐ Yes ☐ No — notes: `________________________`
