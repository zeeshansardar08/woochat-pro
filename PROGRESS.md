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

### P0 — WhatsApp approved message templates (HSM) — ✅ Code complete (`feat/pro-p0-whatsapp-templates`; smoke test pending)
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

### P1 — Provider delivery/read receipts → analytics
`delivered`/`read` are never ingested from providers today (only a generic
AJAX endpoint). Wire Twilio StatusCallback + Meta status webhooks into the
analytics event statuses. — ⬜ Not started

### P1 — Two-way team inbox
Ingest inbound Cloud API / Twilio messages (signatures already verified in
`optout.php`) into a conversation view so agents can reply in-window. — ⬜

### P1 — Scheduled & recurring campaigns
Campaigns are send-now only; add "send at <datetime>" + recent-recipient
exclusion. — ⬜

### P2 — Richer campaign segments
By product/category purchased, lifetime spend, location, and win-back
(no order in N days). Extend the paginated resolver in `campaigns.php`. — ⬜

### P2 — Media messages
Images / PDF (receipts, product images) via provider media endpoints. — ⬜

### P2 — Revenue dashboard widget
Surface `match_conversions()` per campaign/cart/followup as a revenue panel
on Analytics. — ⬜

### P3 — Modernize GPT
Replace `gpt-3.5-turbo` default with a current model; optional store-catalog
context for the chatbot. — ⬜

### Quality backlog (fold into the above as touched)
- [ ] Retire legacy `license-manager.php` once Freemius migration completes
- [ ] Central outbound rate limiter/queue shared by cart/scheduler/campaigns

---

## Next Action
**P0 / 8.0.7** — smoke-test template sends against a live Meta WABA, then
merge `feat/pro-p0-whatsapp-templates` into `pro`. After that, start **P1
(provider delivery/read receipts → analytics)**.
