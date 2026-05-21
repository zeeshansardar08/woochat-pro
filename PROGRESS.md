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

## PHASE 2 — Security Fixes

### Task 2.1 — Prepared SQL Queries — 🟡 Partial (commit e623b49)
- [ ] Full re-audit of `$wpdb->get_results/get_row/get_var/query` for missing `prepare()`
- [ ] Confirm `analytics.php`, `campaigns.php`, `privacy.php`, `uninstall.php` covered

### Task 2.2 — Escape Admin View Output — 🟡 Partial (commit e623b49)
- [ ] Full re-audit of all `echo` in `admin/views/` for escaping

### Task 2.3 — Replace file_put_contents with WP_Filesystem — ⬜ Not started
- [ ] `includes/helpers.php:323-324` — convert to `$wp_filesystem->put_contents()`

### Task 2.4 — Verify AJAX Nonce + Capability Checks — ⬜ Not verified
- [ ] Audit every `wp_ajax_` handler for nonce + `current_user_can`

### Task 2.5 — Run PHPCS — ⬜ Not verified
- [ ] `vendor/bin/phpcs --standard=WordPress` clean of errors

---

## PHASE 3 — Code Quality & Optimization — ⬜ Not started
- [ ] 3.1 Add PHPDoc blocks to public functions
- [ ] 3.2 Conditional script loading per page
- [ ] 3.3 Minify JS/CSS assets (ship `.js` + `.min.js`)
- [ ] 3.4 Move chatbot widget inline CSS → `assets/css/chatbot-widget.css`
- [ ] 3.5 Run full PHPUnit suite, fix breakage

---

## PHASE 4 — Free Version Preparation — 🟡 Partial
- [x] 4.1 `free` branch created
- [ ] 4.2 Pro feature gating behind `wcwp_is_pro_active()`
- [ ] 4.3 In-plugin Pro upsell notices (5 touchpoints)
- [ ] 4.4 Update upgrade modal comparison table

---

## PHASE 5 — WordPress.org Submission Prep — ⬜ Not started
- [ ] 5.1 Run official Plugin Check plugin, fix all errors
- [ ] 5.2 Complete submission checklist
- [ ] 5.3 Create plugin assets (icon, banner, screenshots)
- [ ] 5.4 Submit to WordPress.org

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
