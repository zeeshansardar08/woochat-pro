# WooChat — Development Progress Tracker

Tracks execution of `WooChat-Master-Development-Prompt.md`. Work the phases **in order**.
Each task must be verified before the next begins.

**Status legend:** ✅ Done · 🟡 Partial · ⬜ Not started · ⏭️ Skipped

Current branch: `free`

---

## PHASE 1 — Rename & Restructure

### Task 1.1 — Rename Core Files — ✅ Done (commit 64080b7 + author fix)
- [x] `woochat-pro.php` → `woochat.php`
- [x] Plugin header updated, version `1.0.0`
- [x] Text domain `woochat-pro` → `woochat` (no PHP refs remain)
- [x] `WooChat Pro` → `WooChat` brand strings (upsell strings kept)
- [x] `languages/woochat-pro.pot` → `woochat.pot`
- [x] `class-wcwp-plugin.php` + `WCWP_PLUGIN_FILE` updated
- [x] `composer.json` name updated
- [x] `Author: Zignite` → `Zignites`

### Task 1.2 — Convert Tabs to Admin Submenus — ✅ Done
- [x] Replace single `add_menu_page()` with menu + 11 submenus
- [x] Per-page render functions (Dashboard, General, Messaging, Chatbot,
      Cart Recovery, Scheduler, Campaigns, Analytics, Logs, Webhooks, License)
- [x] New Dashboard page (`admin/views/dashboard.php`) — stats, quick links, Pro teasers
- [x] `wcwp_render_pro_upgrade_notice()` reusable function
- [x] Strip tab switcher from `admin-premium.js`, tab CSS from `admin-premium.css`
- [x] Per-page conditional `admin_enqueue_scripts` (hook checks)
- [x] Strip tab wrapper divs from each `admin/views/tab-*.php`
- [x] Per-page settings groups (avoids cross-page option wipe via options.php)
- [x] Pro-gated pages render upgrade card when no license active
- [x] Fixed analytics/logs filter URLs + redirect URLs in log-viewer.php / webhooks.php

> Note: `tab-scheduler.php` / `tab-analytics.php` still carry inline `wcwp-pro-banner`
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
- [x] 4.2 Pro feature gating behind `wcwp_is_pro_active()`:
  - Cart Recovery / Scheduler / Campaigns / Analytics / Webhooks — page-gated (Phase 1)
  - Chatbot — basic widget + FAQ + single agent are Free; GPT replies,
    color/icon customizer and multi-agent routing are Pro
  - A/B testing — gated in the Messaging view and in the `wcwp_ab_get_template`
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
- [🟡] 5.1 Plugin Check — code-side audit complete; PHPCS green (39 files,
      zero errors), 95 PHPUnit tests pass. Running the actual Plugin Check
      plugin needs a WP admin session (user action).
- [🟡] 5.2 Submission checklist — code items verified:
  - ABSPATH guards on every PHP file
  - All POST/GET/REQUEST/COOKIE/SERVER input sanitized + unslashed
  - All admin output escaped; all 6 `printf` calls escape args
  - 18 AJAX/admin-post handlers all verify nonces; privileged ones check caps
  - Text domain consistently `woochat`; no `woochat-pro` refs; .pot present
  - Removed self-hosted updater (`update-checker.php` + `docs/`) — .org
    handles updates; the updater stays only on `master` (Pro)
  - `uninstall.php` — clears all options (+3 that were missed:
    `wcwp_test_phone`, `wcwp_test_message`, `wcwp_pro_notice_dismissed`),
    drops all 4 tables, and now clears all 6 cron hooks
  - Fixed: daily `wcwp_cleanup_analytics` cron was never unscheduled on
    deactivation — now cleared in `Plugin::deactivate()`
  - readme.txt has a Privacy & Data section disclosing Twilio / Meta / OpenAI
  - Remaining (user action): create PNG icon/banner/screenshots
- [ ] 5.3 Create plugin assets (icon, banner, screenshots) — user action
- [ ] 5.4 Submit to WordPress.org — user action

---

## PHASE 6 — Freemius Integration — ⬜ Not started
- [ ] 6.1 Freemius account + product setup (user action)
- [ ] 6.2 Integrate Freemius SDK, replace `license-manager.php`

---

## PHASE 7 — Pro Version Polish — ⬜ Not started
- [ ] 7.1 Test all Pro features end-to-end
- [ ] 7.2 Error handling hardening
- [ ] 7.3 "Test Connection" button on General Settings

---

## Next Action
**Phase 1, Task 1.2** — convert tabs to submenus (plus the quick `Author: Zignites` fix in Task 1.1).
