# Zignites Chat — Development Progress Tracker

Tracks execution of `Zignites Chat-Master-Development-Prompt.md`. Work the phases **in order**.
Each task must be verified before the next begins.

**Status legend:** ✅ Done · 🟡 Partial · ⬜ Not started · ⏭️ Skipped

Current branch: `free`

---

## PHASE 1 — Rename & Restructure

### Task 1.1 — Rename Core Files — ✅ Done (commit 64080b7 + author fix)
- [x] `zignites-chat-pro.php` → `zignites-chat.php`
- [x] Plugin header updated, version `1.0.0`
- [x] Text domain `zignites-chat-pro` → `zignites-chat` (no PHP refs remain)
- [x] `Zignites Chat Pro` → `Zignites Chat` brand strings (upsell strings kept)
- [x] `languages/zignites-chat-pro.pot` → `zignites-chat.pot`
- [x] `class-zignites-chat-plugin.php` + `ZIGNITES_CHAT_PLUGIN_FILE` updated
- [x] `composer.json` name updated
- [x] `Author: Zignite` → `Zignites`

### Task 1.2 — Convert Tabs to Admin Submenus — ✅ Done
- [x] Replace single `add_menu_page()` with menu + 11 submenus
- [x] Per-page render functions (Dashboard, General, Messaging, Chatbot,
      Cart Recovery, Scheduler, Campaigns, Analytics, Logs, Webhooks, License)
- [x] New Dashboard page (`admin/views/dashboard.php`) — stats, quick links, Pro teasers
- [x] `zignites_chat_render_pro_upgrade_notice()` reusable function
- [x] Strip tab switcher from `admin-premium.js`, tab CSS from `admin-premium.css`
- [x] Per-page conditional `admin_enqueue_scripts` (hook checks)
- [x] Strip tab wrapper divs from each `admin/views/tab-*.php`
- [x] Per-page settings groups (avoids cross-page option wipe via options.php)
- [x] Pro-gated pages render upgrade card when no license active
- [x] Fixed analytics/logs filter URLs + redirect URLs in log-viewer.php / webhooks.php

> Note: `tab-scheduler.php` / `tab-analytics.php` still carry inline `zignites-chat-pro-banner`
> markup — now dead code (pages gate before the view loads). Clean up in Phase 4.

### Task 1.3 — Update readme.txt — 🟡 Partial
- [x] Title, short/long description, tags, changelog updated for v1.0.0
- [ ] Verify full content matches prompt (FAQ, screenshots, Pro features section)

---

## PHASE 2 — Security Fixes — ✅ Done

### Task 2.1 — Prepared SQL Queries — ✅ Done
- [x] Audited all `$wpdb->get_results/get_row/get_var/query` calls
- [x] All queries use `$wpdb->prepare()`; the only non-prepared ones
      (analytics GROUP BY, uninstall DROP TABLE) have no user input and
      carry justified `phpcs:ignore` comments

### Task 2.2 — Escape Admin View Output — ✅ Done (commit e623b49)
- [x] All `echo` in `admin/views/` use `esc_*` / `wp_kses`

### Task 2.3 — Replace file_put_contents with WP_Filesystem — ✅ Done
- [x] `includes/helpers.php` — `.htaccess`/`index.php` writes now use
      `$wp_filesystem->put_contents()`

### Task 2.4 — Verify AJAX Nonce + Capability Checks — ✅ Done
- [x] All 16 `wp_ajax_` handlers verify a nonce; privileged endpoints
      check capabilities; `nopriv` endpoints correctly use nonce-only

### Task 2.5 — Run PHPCS — ✅ Done
- [x] `vendor/bin/phpcs` — 40/40 files, zero errors

---

## PHASE 3 — Code Quality & Optimization — ✅ Done (3.3 skipped)
- [x] 3.1 PHPDoc blocks added to substantive public functions in
      helpers / cart-recovery / analytics / campaigns (messaging already
      fully documented; trivial one-line sanitizers left undocumented)
- [x] 3.2 Conditional script loading — verified; per-page enqueue done in
      Phase 1.2, frontend scripts already gate on pro + feature-enabled
- [⏭] 3.3 Minify JS/CSS — **skipped by decision**: the repo `.gitignore`
      explicitly rejects a build step; minification is not a WordPress.org
      requirement, so assets stay as readable source
- [x] 3.4 Chatbot widget inline CSS → `assets/css/chatbot-widget.css`
      (colors passed as CSS custom properties; enqueued conditionally)
- [x] 3.5 Full PHPUnit suite green — 95 tests, 489 assertions

---

## PHASE 4 — Free Version Preparation — ✅ Done
- [x] 4.1 `free` branch created
- [x] 4.2 Pro feature gating behind `zignites_chat_is_pro_active()`:
  - Cart Recovery / Scheduler / Campaigns / Analytics / Webhooks — page-gated (Phase 1)
  - Chatbot — basic widget + FAQ + single agent are Free; GPT replies,
    color/icon customizer and multi-agent routing are Pro
  - A/B testing — gated in the Messaging view and in the `zignites_chat_ab_get_template`
    runtime
  - Logs — Free capped at last 50 entries; download/export is Pro (handler
    also rejects non-Pro)
- [x] 4.3 Upsell notices: dismissible post-activation admin notice; Pro cards
    on gated pages; A/B upsell in the message editor; log-viewer upsell.
    (Order-list touchpoint skipped — no clean per-edit-screen hook)
- [x] 4.4 Upgrade modal comparison table rewritten to the final Free/Pro split

> Decision recap: chatbot split = "basic Free, AI Pro"; free-tier caps applied
> for logs (50, no export), A/B testing, and single-agent routing.

---

## PHASE 5 — WordPress.org Submission Prep — 🟡 Partial (code-side done)
- [✅] 5.1 Plugin Check — ran the official Plugin Check; worked through every
      finding:
  - **Renamed plugin** (trademark blocker): "WhatsApp" and "Woo" are
    restricted. New name "Zignites Chat – Order Notifications & Customer Chat
    for WooCommerce", slug/text-domain `zignites-chat`, code prefix
    `wcwp_`/`WCWP_` → `zignites_chat_`/`ZIGNITES_CHAT_`. Main file, class
    file, provider files and `.pot` renamed to match.
  - ERRORs fixed: block `apiVersion` 3, `date()`→`gmdate()`, i18n
    translators-comment placement, `Tested up to` 7.0, file ops → WP_Filesystem
    / justified `phpcs:ignore`, removed dead `uuid-polyfill.js`
    (`library_core_files`).
  - WARNINGs fixed: dropped `load_plugin_textdomain` / `print_r`, swapped
    custom sanitizer wrappers for core fns, prefixed `uninstall.php` globals,
    file-level `phpcs:disable` for custom-table DB sniffs and display-filter
    NonceVerification (with justifications).
  - Dev-only files (`.github`, `phpcs.xml.dist`, `tests/`, `PROGRESS.md`,
    `.claude`) stay out of the free build — handled by the separate clean repo.
  - PHPCS green (39 files), 95 PHPUnit tests pass.
- [🟡] 5.2 Submission checklist — code items verified:
  - ABSPATH guards on every PHP file
  - All POST/GET/REQUEST/COOKIE/SERVER input sanitized + unslashed
  - All admin output escaped; all 6 `printf` calls escape args
  - 18 AJAX/admin-post handlers all verify nonces; privileged ones check caps
  - Text domain consistently `zignites-chat`; no `zignites-chat-pro` refs; .pot present
  - Removed self-hosted updater (`update-checker.php` + `docs/`) — .org
    handles updates; the updater stays only on `master` (Pro)
  - `uninstall.php` — clears all options (+3 that were missed:
    `zignites_chat_test_phone`, `zignites_chat_test_message`, `zignites_chat_pro_notice_dismissed`),
    drops all 4 tables, and now clears all 6 cron hooks
  - Fixed: daily `zignites_chat_cleanup_analytics` cron was never unscheduled on
    deactivation — now cleared in `Plugin::deactivate()`
  - readme.txt has a Privacy & Data section disclosing Twilio / Meta / OpenAI
  - Remaining (user action): create PNG icon/banner/screenshots
- [ ] 5.3 Create plugin assets (icon, banner, screenshots) — user action
- [ ] 5.4 Submit to WordPress.org — user action

---

## PHASE 6 — Freemius Integration — 🟡 Partial (scaffolded on `pro` branch)
- [ ] 6.1 Freemius account + product setup (user action — credentials needed)
- [🟡] 6.2 Integrate Freemius SDK — SDK vendored at `/freemius/`,
       `includes/freemius.php` declares `zignites_chat_pro_freemius()` with
       `fs_dynamic_init()` and is_premium_only=true. Placeholder
       `FREEMIUS_PLUGIN_ID` and `pk_FREEMIUS_PUBLIC_KEY` remain — user must
       paste the real values from the Freemius dashboard.
       `zignites_chat_is_pro_active()` already bridges to the Freemius
       singleton when present, so the unlock flips automatically once
       credentials are in. Legacy `license-manager.php` left in place for
       compatibility with sites that activated a key pre-migration.

---

## PHASE 7 — Pro Version Polish — 🟡 Partial (in progress on `pro` branch)
- [🟡] 7.1 Test all Pro features end-to-end — smoke test plan drafted
       in chat; user action to run against a live store with a real
       Twilio / Meta sandbox.
- [✅] 7.2 Error handling hardening — GPT call sites (chatbot fallback
       + follow-up scheduler) no longer swallow failures; new
       `zignites_chat_record_gpt_error()` helper + admin notice surfaces
       the most recent failure with one-click dismiss. Other Pro
       modules (campaign chunking, cart-recovery queue, webhook retry)
       already had bounded retries + per-row state.
- [✅] 7.3 "Test Connection" button on General Settings — verifies the
       active provider's credentials via a cheap GET against Twilio
       Accounts / Meta phone-info; works against the values currently
       in the form so admins can validate keys before saving them.

---

## PHASE 8 — Pro Enhancements Roadmap (post-1.1)

Tracks the agreed Pro backlog. Work top-down by priority; each task ships
on its own `feat/pro-*` branch off `pro`, with tests + PHPCS green before
merge.

### P0 — WhatsApp approved message templates (HSM) — ✅ Merged into `pro` (live smoke test pending)
Meta's Cloud API forbids free-form business-initiated messages outside the
24h customer-service window — cart recovery, follow-ups, and bulk campaigns
must use **pre-approved templates** or the sender number gets quality-rated
down and eventually blocked. Add template (HSM) support so those sends are
deliverable in production.
- [x] 8.0.1 Data model + module (`includes/wa-templates.php`):
      `zignites_chat_wa_templates` option (per message-type: enabled, name,
      language, ordered variable map), getters, sanitizer, component
      builder, `maybe_apply_template()` helper
- [x] 8.0.2 Cloud provider `send_template()` (type=template envelope; shared
      `dispatch()` with `send()`)
- [x] 8.0.3 Dispatcher routes to template send when a descriptor is attached
      (Cloud + Pro), free-form text stays the fallback/preview
- [x] 8.0.4 Wire consumers: order confirmation, cart recovery, follow-up,
      campaigns all routed through maybe_apply_template()
- [x] 8.0.5 Settings UI — dedicated "WhatsApp Templates" Pro submenu
      (`admin/views/tab-wa-templates.php`): per-type enable + template name +
      language + ordered variable rows; Cloud-only notice; option cleaned on
      uninstall
- [x] 8.0.6 Tests — builder/sanitizer/routing (13 cases); PHPCS green; 108
      tests pass
- [ ] 8.0.7 Manual smoke test against a live Meta WABA with a real approved
      template (user action)

### P1 — Provider delivery/read receipts → analytics — ✅ Merged into `pro` (live smoke test pending)
`delivered`/`read` were never ingested from providers (only a generic AJAX
endpoint). Now wired end-to-end:
- [x] 8.1.1 Monotonic analytics status core: status_rank(),
      resolve_status_transition() (funnel only advances; failed only
      in-flight + sticky; operational states untouched),
      apply_receipt_by_message_id()
- [x] 8.1.2 `includes/delivery-receipts.php`: Twilio StatusCallback REST
      endpoint (signed), Twilio/Meta status mappers, webhook dispatch on
      delivered/read/failed, Meta status extract/ingest helpers
- [x] 8.1.3 Twilio provider attaches StatusCallback URL per send
- [x] 8.1.4 Meta statuses ingested via the existing opt-out webhook (shared
      callback URL) + GET verification handshake (hub.challenge)
- [x] 8.1.5 General Settings: Webhook Verify Token field + callback URL
      display; option registered + cleaned on uninstall
- [x] 8.1.6 `message.read` webhook event; 16 unit tests; 121 pass, PHPCS green
- [ ] 8.1.7 Smoke test against live Twilio + Meta sandboxes (user action)

### P1 — Two-way team inbox — ✅ Merged-ready on `feat/pro-inbox` (I1–I5 done; live smoke test pending)
Ingest inbound Cloud API / Twilio messages (signatures already verified in
`optout.php` / the receipts webhook) into conversation threads so agents can
read and reply within WhatsApp's 24h service window.

Planned increments (each its own commit; tests + PHPCS green per step):
- [x] I1 — Schema + storage (`includes/inbox.php`): `{prefix}zignites_chat_conversations`
      (one row per phone: last_message_at, **last_inbound_at**, last_excerpt,
      last_direction, unread_count, agent_id) + `{prefix}zignites_chat_messages`
      (direction in/out, body, provider, message_id, status, created_at).
      Migration v6 (idempotent dbDelta, wired into the runner + activation hook
      + uninstall drop). Pure helpers: normalize_direction, make_excerpt,
      window_is_open (24h check), build_message_row, build_thread_update.
      `zignites_chat_inbox_record_message()` upserts thread + inserts message.
      8 unit tests; 156 pass, PHPCS green.
- [x] I2 — Inbound capture (`includes/inbox-capture.php`): a `/inbound` REST
      alias reusing the signature/token-verified opt-out handler; capture step
      wired into `zignites_chat_optout_webhook_handler()` (runs before opt-out
      so a keyword message is still threaded). Pure normalizers:
      `normalize_twilio_inbound`, `normalize_meta_messages` (+ type-aware
      `extract_meta_message_body`: text/button/interactive/media-caption).
      Dedupes on provider message id (`zignites_chat_inbox_inbound_exists`) so
      webhook retries don't double-insert; Pro-gated. 6 unit tests; 162 pass,
      PHPCS green.
- [x] I3 — Admin Inbox view (`includes/inbox-admin.php`, `admin/views/tab-inbox.php`,
      `assets/js/inbox.js`, `assets/css/inbox.css`): new Pro "Inbox" submenu —
      two-pane layout (conversation list, unread-first + search; thread panel).
      Read helpers: `get_threads`, `count_threads`, `total_unread`,
      `get_messages` (latest N or after_id for polling), `mark_read`; pure
      presenters `present_thread`/`present_message`. AJAX:
      `zignites_chat_inbox_threads` (list) + `zignites_chat_inbox_thread`
      (messages; clears unread on open, supports after_id polling). 24h window
      banner via `window_is_open`. Pro-gated upsell card added. 4 unit tests
      for the presenters; 164 pass, PHPCS green.
- [x] I4 — Agent reply (`includes/inbox-admin.php` + composer in
      `tab-inbox.php`/`inbox.js`): `zignites_chat_inbox_reply` AJAX (cap +
      nonce + Pro gated) sends a free-form reply through
      `zignites_chat_send_whatsapp_message()` (opt-out + analytics handled by
      the dispatcher), records the outbound message, and marks the thread
      read. The 24h window is enforced server-side (rejects when closed) and
      in the UI (composer disabled + "template required" note when
      `window_is_open` is false; Ctrl/Cmd+Enter sends). 164 pass, PHPCS green.
- [x] I5 — Outbound mirroring: `zignites_chat_send_whatsapp_message()` mirrors
      successful sends (order/cart/follow-up/campaign) into the matching thread
      via `record_message` (direction 'out'). Default mirrors into **existing
      threads only** so one-way notifications don't flood the inbox; the
      `zignites_chat_inbox_mirror_create_threads` filter opts into creating new
      threads, and `zignites_chat_inbox_mirror_outbound` can disable it
      entirely. The I4 reply path sets `skip_inbox_mirror` to avoid
      double-recording. Test-mode sends short-circuit before the mirror.
- [x] Tests for all pure helpers (18 inbox unit tests across
      InboxTest/InboxCaptureTest); 164 pass, PHPCS green.
- [ ] Live smoke test against Twilio + Meta sandboxes (user action).

Open questions — **resolved 2026-06-02**: (1) retention reuses the existing
`data_retention_days` (a prune pass will drop inbox rows on the same window —
to wire in a later increment); (2) any `manage_woocommerce` user can read +
reply to any thread, `agent_id` is informational only (no claim/assign gating);
(3) the 24h service window is surfaced as a banner + disabled free-form reply
box once `last_inbound_at` is >24h old (`zignites_chat_inbox_window_is_open()`
is the pure check, landed in I1).

### P1 — Scheduled campaigns — ✅ Merged into `pro` (live smoke test pending)
Campaigns were send-now only; added "send at <datetime>" + recent-recipient
exclusion.
- [x] schema: campaigns.scheduled_at column (migration v4, dbDelta)
- [x] campaign_create() respects a future send time → status 'scheduled'
      (pure resolve_schedule() + validating normalize_datetime())
- [x] promoter cron (every 5 min) flips due 'scheduled' campaigns to queued
      and kicks off the first chunk; unscheduled on deactivate, cleared on
      uninstall
- [x] recent-recipient exclusion: skip phones that got a bulk message in the
      last N days (pure filter_excluded() + analytics lookup)
- [x] UI: schedule (datetime-local) + skip-recent fields, Scheduled column in
      the list, AJAX handler wired
- [x] fixed a latent bug: campaigns.js guarded on a stale wrapper id
      (`zignites-chat-tab-content-campaigns`) that no longer existed, so the
      create form's JS never ran — restored the wrapper
- [x] 11 unit tests (normalize/resolve/filter); 129 pass, PHPCS green
- [ ] live smoke test (create a scheduled campaign on a real store)
- Note: "recurring" (repeat weekly/monthly) deferred to a later enhancement

### P2 — Richer campaign segments — ✅ Merged into `pro` (live smoke test pending)
Added five segments beyond all_customers/recent_orders by refactoring the
resolver to aggregate per customer:
- [x] product_purchased / category_purchased / location (per-order match)
- [x] min_spend (lifetime spend) / win_back (no order in N days) (aggregate)
- [x] pure, tested helpers: order_contributes_match(), phone_qualifies(),
      build_campaign_segment_meta(), csv_to_int_ids()
- [x] UI: per-segment meta pickers (product IDs, category multiselect, min
      spend, country multiselect, win-back days) toggled by segment; AJAX
      builds segment_meta via the pure builder
- [x] 13 new unit tests; 142 pass, PHPCS green; logic smoke-tested
- [ ] live smoke test against a store with real orders (user action)

### P2 — Media messages — ✅ Merged into `pro` (live smoke test pending)
Send images / documents over WhatsApp by public URL.
- [x] reusable plumbing: includes/media.php (normalize_media_type,
      validate_media_url with same-site host allowlist to block SSRF,
      build_media_descriptor), provider send_media() (Cloud link envelope +
      Twilio MediaUrl via a shared request()), dispatcher media-first routing
- [x] consumer: campaign attachment — schema v5 (media_url/media_type),
      campaign_create stores a validated descriptor, send attaches it with
      the rendered text as caption (mutually exclusive with the bulk template)
- [x] UI: WP Media Library picker on the campaign form (wp_enqueue_media),
      AJAX passes media_url + mime
- [x] 3 unit tests (type/host helpers); 148 pass, PHPCS green; smoke-tested
- [ ] live send test of an image + a PDF campaign (user action)
- Note: manual order-screen attachment + product-image auto-attach reuse the
  same plumbing — left as later enhancements

### P2 — Revenue dashboard widget — ✅ Merged into `pro` (live smoke test pending)
Surface attributed revenue per channel on Analytics.
- [x] get_revenue_by_type(): one global first-event-wins match (reusing
      match_conversions()), matched orders bucketed by the winning event's
      type — no double counting; also feeds the existing Attributed-orders
      card so orders are fetched once
- [x] pure bucket_revenue_by_type() + type_label() helpers
- [x] "Revenue by channel" table on the Analytics tab (per-channel
      conversions + revenue, with a total row)
- [x] 3 new unit tests; 145 pass, PHPCS green; smoke-tested
- [ ] live verification on a store with attributed orders (user action)

### P3 — Modernize GPT — 🟡 In progress on `feat/pro-gpt-modernization`
Replace `gpt-3.5-turbo` default with a current model; optional store-catalog
context for the chatbot.
- [x] Central `zignites_chat_default_gpt_model()` (default `gpt-4o-mini`,
      filterable) replaces the scattered `gpt-3.5-turbo` literals in
      chatbot-engine, scheduler, and the Scheduler settings field + help text.
      Only affects installs that never saved an explicit model.
- [x] Opt-in store-catalog context (`includes/catalog-context.php`): pure,
      tested `build_catalog_context()` + cached `get_catalog_context()`
      (wc_get_products by popularity, capped, 1h transient invalidated on
      product save/update and toggle change). Injected into the chatbot system
      prompt when `zignites_chat_chatbot_catalog_context` is on.
- [x] UI: Pro-gated "Product catalog context" select on the Chatbot tab;
      setting registered in the chatbot group + cleaned on uninstall.
- [x] 6 unit tests for the builder; 170 pass, PHPCS green.
- [ ] Live verification: enable GPT fallback + catalog context, ask the bot a
      product/price question (user action).
- Decision: default-only model bump (existing saved values untouched);
  catalog context off by default, top-20 products, name + price only.

### Quality backlog (fold into the above as touched)
- [ ] Retire legacy `license-manager.php` once Freemius migration completes
      (blocked: needs real Freemius credentials — user action)
- [x] Central outbound rate limiter shared by cart/scheduler/campaigns —
      done on `feat/pro-outbound-rate-limiter`. `includes/rate-limiter.php`:
      pure fixed-window evaluator (`zignites_chat_rate_window_evaluate`) +
      option-backed `zignites_chat_outbound_rate_acquire()`. Default 60/min
      (~1 msg/sec), filterable (`zignites_chat_outbound_rate_per_minute` /
      `_window` / `_limit_enabled`). **Advisory**: each bulk cron loop
      (cart-recovery queue, campaign chunk, follow-up handler) consults
      acquire() before a send and, when the cap is hit, stops the run / defers
      the event so remaining rows are picked up next tick — nothing is marked
      failed by the limiter. State option cleaned on uninstall. 6 unit tests;
      176 pass, PHPCS green. (Order confirmations are intentionally not gated —
      transactional, low-volume.)

---

## PHASE 9 — Pro Roadmap v2 (category-leader features)

Tiered roadmap agreed 2026-06-02 after a product review. The plugin already
covers a full WhatsApp suite; this phase pushes from "complete" to "category
leader." Work top-down; each item ships on its own `feat/pro-*` branch off
`pro`, tests + PHPCS green before merge. Build in reviewable increments like
the two-way inbox.

### Tier 1 — highest leverage

#### T1.1 — COD order confirmation / verification — ✅ Complete (C1–C3); live smoke test pending
The #1 lever in WhatsApp-heavy, cash-on-delivery markets (India, MENA, SEA,
LATAM): cut fake/abandoned COD orders and return-to-origin (RTO) cost by asking
the customer to confirm via WhatsApp. Sends an approved template with quick-reply
buttons (Confirm / Cancel) on a new COD order; the customer's button tap (already
captured inbound by the inbox in I2) flips the WooCommerce order status.
Planned increments:
- [x] C1 — Module + settings + send-on-COD-order (`includes/cod-confirmation.php`,
      `admin/views/tab-cod.php`): a `cod_confirmation` template type added to the
      WA Templates page; a Pro "COD Confirmation" settings tab (enable, COD
      gateways [default `cod`], fallback message, confirm/cancel keywords,
      on-confirm [`processing`] / on-cancel [`cancelled`] statuses). Sends on
      `woocommerce_checkout_order_processed` + the Store-API equivalent for COD
      orders (idempotent via `_zignites_chat_cod_status` meta), attaching the
      approved template via `maybe_apply_template('cod_confirmation')` with the
      rendered text as fallback. Pure tested helpers: `is_cod_gateway`,
      `classify_reply` (whole-word, cancel-wins-ties), `sanitize_gateways`.
      Settings cleaned on uninstall. 6 unit tests; 182 pass, PHPCS green.
- [x] C2 — Inbound matching + status transition: inbox capture now fires a
      decoupled `zignites_chat_inbound_message` action per newly-recorded
      inbound; `zignites_chat_cod_handle_inbound_reply` subscribes, matches the
      sender to a pending COD order (`find_pending_order_by_phone` + pure
      `cod_phone_matches`, suffix-tolerant for country-code differences),
      classifies the reply, transitions the order to the configured
      confirm/cancel status with an order note, sets
      `_zignites_chat_cod_status = confirmed|cancelled`, and sends a filterable
      ack in the now-open 24h window. 3 new unit tests (phone matching);
      185 pass, PHPCS green.
- [x] C3 — Admin surface: COD badge column on the WooCommerce orders screen
      (HPOS + legacy hooks; awaiting/confirmed/cancelled/send-failed with
      coloured pills) + a status-counts stats block on the settings tab
      (`zignites_chat_cod_status_counts`, cheap paginated COUNT queries, cached
      5 min, flushed on each status change). Pure `cod_status_label` helper.
      1 new unit test; 186 pass, PHPCS green.
- Open question resolved at build: business-initiated confirmation must use an
  approved HSM template with quick-reply buttons (free-form interactive only
  works inside the 24h window), so the buttons live in the Meta-approved
  template; the plugin fills variables + reads the button reply.

#### T1.2 — Order-status + shipping/tracking notifications — ✅ Done on `feat/pro-status-tracking-notifications` (live smoke test pending)
Notify on every status (shipped, out-for-delivery, on-hold, refunded,
cancelled), not just processing/completed, with per-status templates and an
injected tracking link/number (pull from common shipment plugins).
- [x] `includes/status-notifications.php`: hooks `woocommerce_order_status_changed`,
      sends a WhatsApp message when an order enters an opted-in status. Master
      toggle + per-status `{enabled, template}` config; all-off by default so it
      never overlaps the classic confirmation until enabled.
- [x] Tracking injection from `_wc_shipment_tracking_items` (WooCommerce
      Shipment Tracking + Advanced Shipment Tracking), exposed as
      `{tracking_number}`/`{tracking_url}`/`{carrier}`; filterable via
      `zignites_chat_order_tracking`.
- [x] Pure, tested helpers: `status_normalize`, `should_notify`,
      `extract_tracking`, `status_render`, `sanitize_notifications`.
- [x] Pro "Status Notifications" submenu/tab: master toggle + a per-status
      table (checkbox + template) over every eligible WC status; upsell card;
      settings cleaned on uninstall.
- [x] 6 unit tests; 192 pass, PHPCS + lint green.
- [ ] Live smoke test: move an order to a tracked status, confirm the WhatsApp
      lands with the tracking link (user action).

#### T1.3 — WhatsApp opt-in capture — ✅ Done on `feat/pro-optin-capture` (live smoke test pending)
Proactive consent: checkout checkbox + a consent log, complementing the
existing opt-out pipeline. Compliance + list growth.
- [x] `includes/optin.php`: consent log option (`zignites_chat_optin_log`,
      phone → {time, source}); `record_optin` / `has_consent` / `get_optin_log`.
      An explicit opt-in clears a prior opt-out (filterable) and fires the
      `customer.opted_in` webhook.
- [x] Classic-checkout checkbox (`woocommerce_review_order_before_submit`)
      with a customizable label + pre-checked option; captured on
      `woocommerce_checkout_order_processed` (order meta `_zignites_chat_optin`
      + consent log).
- [x] Marketing gate: `zignites_chat_marketing_blocked()` (opted-out OR
      consent-required-and-missing) wired into the three bulk senders
      (cart-recovery queue, campaign chunk, follow-up handler); transactional
      sends (order/COD/status) untouched.
- [x] Pure, tested helpers: `optin_log_add`, `optin_decide_blocked`.
- [x] Pro "Opt-in" tab: enable, label, pre-checked, require-consent, plus an
      opted-in count; settings + log cleaned on uninstall.
- [x] 5 unit tests; 197 pass, PHPCS + lint green.
- Note: block-checkout checkbox UI deferred (classic checkout + the
      programmatic `record_optin()` API ship now).
- [ ] Live smoke test: opt in at checkout, confirm the log + that a campaign
      skips a non-consented number when "require consent" is on (user action).

### Tier 2 — turn the inbox into a real helpdesk
Build on the merged two-way inbox (P1):
- [x] T2.1 — Agent assignment + per-agent views — done on
      `feat/pro-inbox-agent-assignment`. `agent_id` is now actionable:
      `assign_thread` storage + an `agent_id` filter on `get_threads`; AJAX
      `zignites_chat_inbox_assign` (claim/assign/unassign, validates the agent
      is an admin/shop-manager); the thread list shows the assignee, the panel
      header has a Claim button + assign dropdown, and a list filter (All /
      Assigned to me / Unassigned). Pure helpers `scope_to_agent_filter`,
      `agent_name`; presenter exposes `agent_id`. 3 unit tests; 200 pass,
      PHPCS + lint green.
- [x] T2.2 — Canned / quick replies — done on `feat/pro-inbox-canned-replies`.
      Saved snippets (`zignites_chat_inbox_canned_replies`, [{title, body}])
      managed via a "Manage quick replies" form on the Inbox page (one
      "Title | Message" per line); a "Quick reply…" dropdown in the composer
      inserts the chosen snippet into the reply box. Pure tested helpers
      `parse_canned_replies` (string/array → normalized list, first-pipe split,
      derives title, caps 50) + `canned_replies_to_text`. 6 unit tests; 206
      pass, PHPCS + JS clean; settings cleaned on uninstall.
- [x] T2.3 — Customer context panel — done on `feat/pro-inbox-customer-context`.
      Opening a thread loads a context strip (orders count, lifetime spend,
      recent orders with edit links) via AJAX `zignites_chat_inbox_context`.
      Orders matched by a last-9-digits `_billing_phone` LIKE query (HPOS-safe)
      then verified with the suffix matcher; aggregated by the pure, tested
      `aggregate_customer_context` (count/sum/recent-limit). 3 unit tests; 209
      pass, PHPCS + JS clean.
- [x] T2.4 — Internal notes — done on `feat/pro-inbox-internal-notes`. Notes
      are stored as message rows with `direction='note'` + a new `author_id`
      column (migration v7: widen `direction` to VARCHAR(8) + add the column),
      so they interleave inline and polling picks them up, but never touch the
      thread's customer-facing aggregates or get sent. `add_note` storage; AJAX
      `zignites_chat_inbox_note`; composer "Internal note" toggle (works even
      when the 24h window is closed) with distinct note styling + author label.
      2 unit tests (note direction + author_id); 211 pass, PHPCS + JS clean.
- [x] T2.5 — Agent email notifications — done on
      `feat/pro-inbox-agent-notifications`. On a new inbound (via the
      `zignites_chat_inbound_message` hook) emails the assigned agent, or all
      managers when unassigned, or a configured override address. Throttled to
      one email per conversation per 15 min (transient + pure
      `notify_should_send`). Pure tested `notify_recipient_ids`. Settings live
      in their own group (`zignites_chat_inbox_notify_group`) so saving them
      doesn't wipe canned replies. 3 unit tests; 214 pass, PHPCS + lint green.

### Tier 3 — platform / automation
- [🟡] T3.1 — Drip & automation sequences (welcome, win-back, browse-abandon)
      as multi-step, rule-based flows rather than discrete features. Biggest
      build; the strategic differentiator. **Built in reviewable increments**
      (decision 2026-06-08), one PR each off `pro`:
  - [x] S1 — Engine foundation, on `feat/pro-drip-sequences`.
        `includes/drip-sequences.php`: the enrollments table
        (`{prefix}zignites_chat_sequence_enrollments`, migration v9 + activation
        + uninstall drop); sequence-definition storage (option
        `zignites_chat_sequences`) with a pure sanitizer + normalizing getters
        (`get_sequences`/`find`/`active_for_trigger`); a trigger catalogue
        (order_completed / optin / win_back / browse_abandon, each with its
        placeholder set); and pure, unit-tested scheduling helpers
        (`delay_to_seconds`, `step_count`, `get_step`, `next_run_at` with
        cumulative per-step offsets, `render_message`, `sanitize_step`).
        No enrollment/cron/UI yet — mirrors inbox I1. 8 unit tests; 244 pass,
        PHPCS green.
  - [x] S2 — Enrollment + trigger wiring, on `feat/pro-drip-enrollment`.
        Added a `context` column (migration v10) holding the placeholder values
        captured when the trigger fires. `zignites_chat_seq_enroll()` is
        idempotent (UNIQUE sequence_id+phone, existence check) and Pro-gated;
        `enroll_all_for_trigger()` fans a phone into every active sequence for a
        trigger. Two entry points: `woocommerce_order_status_completed` (captures
        {name}/{order_id}/{total}/{currency_symbol}/{site}) and a new
        `zignites_chat_customer_opted_in` action fired from
        `zignites_chat_record_optin()` ({name}/{site}). Pure tested helpers
        `seq_format_mysql` + `seq_build_enrollment_row` (schedules step 0 at
        enroll + delay, serializes context). Gating stays at send time (S3).
        3 new unit tests; 247 pass, PHPCS green.
  - [x] S3 — Cron processor + sender, on `feat/pro-drip-sender`. A recurring
        5-minute event (`zignites_chat_process_sequences`, reusing the shared
        `zignites_chat_five_minutes` schedule; unscheduled on deactivate, cleared
        on uninstall) pulls due active enrollments (`next_run_at <= now`, chunked
        30), renders the current step from the stored context, sends via the
        dispatcher (`type=sequence`), then advances. Gating: quiet hours skip the
        whole run; opt-out / missing-consent and a removed/disabled sequence
        cancel the enrollment permanently; the shared rate limiter breaks the run
        (rows stay due for the next tick). Advance is fire-and-forget like the
        other senders. Pure tested helper `seq_plan_advance` (next step → active
        with new next_run_at, or completed). 2 new unit tests; 249 pass, PHPCS
        green.
  - [x] S4 — Admin CRUD UI, on `feat/pro-drip-admin-ui`. A Pro "Sequences"
        submenu (`tab-sequences.php` + `assets/js/sequences.js`): each sequence
        is an editable card (id, name, enabled, trigger, ordered delay+message
        steps); steps and whole sequences are added/removed client-side and the
        `zignites_chat_sequences` option round-trips through the existing
        sanitizer on save (registered in `zignites_chat_sequences_group`), so
        blank/removed cards drop server-side. Existing cards show their live
        enrollment counts (active/completed/cancelled) and a read-only id;
        the trigger dropdown reveals that trigger's placeholders. Upsell card
        added. Pure tested helper `seq_shape_counts` + a grouped-count query
        `seq_enrollment_counts`. 2 new unit tests; 251 pass, PHPCS + JS clean.
  - [ ] S5 — Scan-based triggers: win-back (no order in N days) + browse-abandon
        (viewed product, no order), each a scheduled scan that enrolls matches.

### Quick wins (reuse existing plumbing)
- [x] Q1 — Back-in-stock alerts — done on `feat/pro-back-in-stock`.
      `includes/back-in-stock.php`: a `{prefix}zignites_chat_stock_subs` table
      (migration v8); a "notify me on WhatsApp" form on out-of-stock product
      pages (nopriv AJAX subscribe, re-arms on re-subscribe); on
      `woocommerce_product_set_stock_status` → 'instock' a chunked cron
      processor sends alerts (opt-out-aware, defers on quiet hours, consumes
      the shared rate-limit budget). Pure tested helpers `stock_is_instock`,
      `stock_render_message`. Pro "Back in Stock" tab (enable, heading, message
      template). Table + options + cron cleaned on uninstall; table created on
      activation. 3 unit tests; 223 pass, PHPCS + lint + JS clean.
- [x] Q2 — Review / NPS request post-delivery — done on `feat/pro-review-request`.
      `includes/review-request.php`: when an order enters the configured
      delivered status (default `completed`) it schedules a single
      `zignites_chat_send_review_request` cron event after N days (default 3),
      then sends a WhatsApp review/NPS ask. Reuses the follow-up scheduler
      plumbing — per-order meta dedup (`_zignites_chat_review_scheduled`),
      marketing consent gate, quiet-hours + rate-limiter deferral, and a bounded
      3-attempt retry (30/120-min backoff) with sent/failed terminal flags.
      Placeholders {name}/{order_id}/{product}/{review_url}/{site}; {review_url}
      points at any configurable review or NPS link. Pure tested helpers
      `review_normalize_status`, `review_delay_seconds`, `review_render_message`.
      Pro "Review Requests" tab (enable, trigger status, delay days, link,
      message) + upsell card; settings + cron cleaned on uninstall. 5 unit
      tests; 228 pass, PHPCS green.
- [x] Q3 — Quiet hours — done on `feat/pro-quiet-hours`.
      `includes/quiet-hours.php`: a configurable nightly window (store timezone)
      that defers marketing sends — cart-recovery queue skips the run, campaign
      chunks + follow-ups reschedule to the window's end (resumes automatically,
      nothing dropped). Transactional sends (order/COD/status) are untouched.
      Pure tested helpers: `parse_time_to_minutes`, `in_quiet_hours` (handles
      overnight), `quiet_minutes_until_end`, `quiet_sanitize_time`. Pro "Quiet
      Hours" tab; settings cleaned on uninstall. 6 unit tests; 220 pass, PHPCS
      + lint green.
- [x] Q4 — Template sync from the Meta Graph API — done on
      `feat/pro-template-sync`. `includes/wa-template-sync.php`: a "Sync from
      Meta" card on the WA Templates page pulls approved templates straight from
      `GET /{waba_id}/message_templates` (paginated, capped) using the saved
      Cloud token + a new `zignites_chat_cloud_waba_id` setting. Synced
      templates are cached (`zignites_chat_wa_synced_templates` / `_synced_at`)
      and surfaced two ways: a reference table (name · language · status ·
      category · variable count) and a `<datalist>` of approved names that
      autocompletes the per-type "Template name" inputs — no more typing exact
      names by hand. AJAX `zignites_chat_sync_templates` (cap + nonce + Pro
      gated); pure tested helpers `wa_template_endpoint`,
      `wa_count_body_params` (highest {{n}} in the BODY component),
      `wa_sync_normalize_templates`. Options cleaned on uninstall. 8 unit tests;
      236 pass, PHPCS + JS clean.
- [ ] Q5 — Sender health panel: WABA quality rating + messaging tier on the
      dashboard.

---

## Next Action
**Tier 1 COMPLETE.** T1.1 (COD), T1.2 (status/tracking), T1.3 (opt-in capture)
are all built — T1.1/T1.2 merged into `pro`; T1.3 on `feat/pro-optin-capture`
awaiting PR. Live smoke tests pending on each (user action).

**Tier 2 COMPLETE.** Quick wins **Q3 (quiet hours), Q1 (back-in-stock),
Q2 (review/NPS request) + Q4 (Meta template sync) DONE** (all merged into
`pro`). Remaining quick win: **Q5 sender-health**.

**Tier 3 — T3.1 drip & automation sequences IN PROGRESS** (incremental PRs).
**S1–S4 DONE** (S1/S2/S3 merged into `pro`; S4 admin UI on
`feat/pro-drip-admin-ui` awaiting PR). Drip sequences are now fully usable from
the admin — create/edit sequences with trigger + delay/message steps, enroll on
order-completed / opt-in, walk + send on the 5-minute cron with all marketing
gates. Only **S5 (scan-based win-back + browse-abandon triggers)** remains to
finish T3.1. See PHASE 9 → Tier 3 for the breakdown.

The original Pro backlog is otherwise cleared into `pro`; the only blocked item
is retiring `license-manager.php` (needs the Freemius credentials migration —
user action).

Pending live verifications (user action): two-way inbox Twilio/Meta smoke test
(PR #71, merged); GPT catalog context (PR #72, merged); and observing the rate
limiter defer under a saturated burst.

Status snapshot (as of 2026-06-02): P0, all P1/P2, the two-way inbox (P1), and
GPT modernization (P3) are **merged into `pro`**; the central rate limiter is
the latest branch awaiting PR. The free build shipped from `master`. The Pro
feature backlog is now empty apart from the Freemius-blocked license cleanup
and the outstanding live smoke tests.
