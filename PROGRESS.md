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

## Next Action
**Central outbound rate limiter — done on `feat/pro-outbound-rate-limiter`.**
Open the PR against `pro`. The only remaining backlog item is retiring
`license-manager.php`, which is **blocked** on the Freemius credentials
migration (user action) — nothing actionable until those are in.

Pending live verifications (user action): two-way inbox Twilio/Meta smoke test
(PR #71, merged); GPT catalog context (PR #72, merged); and observing the rate
limiter defer under a saturated burst.

Status snapshot (as of 2026-06-02): P0, all P1/P2, the two-way inbox (P1), and
GPT modernization (P3) are **merged into `pro`**; the central rate limiter is
the latest branch awaiting PR. The free build shipped from `master`. The Pro
feature backlog is now empty apart from the Freemius-blocked license cleanup
and the outstanding live smoke tests.
