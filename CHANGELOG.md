# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/).

## [1.1.11] - 2026-09-11

### Fixed
- **Critical**: form submissions failed with a WordPress-rendered 404 ("Page not found") and no record was ever saved, on every environment tested (live Hostinger hosting with security disabled, a fresh local Laragon install with zero security software, and a from-scratch AlmaLinux + Virtualmin LEMP VM) — proving this was a genuine code bug, not an environment, hosting, caching, WAF, or permalink issue (all of which were investigated and ruled out first).
  - Root cause: the Full Name field's `name` attribute was `name="name"`, colliding with WordPress core's own reserved `name` public query variable (`WP::$public_query_vars`), which WordPress uses to look up a page/post by its slug. WordPress's request router (`WP::parse_request()`) falls back to `$_POST` for any recognized query variable not otherwise set — so on submission, `$_POST['name']` (whatever the applicant typed) was picked up as an override, WordPress tried to find a page whose slug matched that typed name, found none, and correctly rendered its own 404 per its own routing logic. GET requests were never affected since `$_POST` is empty for them — exactly matching every symptom observed (page loads fine, only submission fails; 100% reproducible; no PHP error logged, since nothing crashed — WordPress was correctly following its own rules given input it was never designed to receive this way).
  - Renamed the field's `name`/`data-field` attributes to `applicant_name` throughout the form, the required-fields check, the `$_POST` read in the submission handler, and the print-preview JS. The internal PHP array key and `wp_admission_submissions.name` database column are unchanged — no schema change, no effect on existing data.
  - Cross-checked every other form field name against WordPress core's authoritative `public_query_vars` list (`m, p, posts, w, cat, ..., name, category_name, tag, ..., page_id, ...`) — confirmed no other field in this form collides.
- Also hardened against a second, separate issue found via the same debug-log investigation: a real-world test install had a stored DB-version option indicating the schema was current, while the actual `wp_admission_submissions` table did not exist (most likely from copying plugin files into `wp-content/plugins/` without WordPress ever running the actual activation hook). The submission handler now checks for the table's existence immediately before inserting and calls `adm_mgr_activate()` to recreate it if missing, rather than failing with a raw database error and no submitted-data record.
- Verified the required-fields validation logic directly: confirmed a submission using the renamed field passes as expected, and confirmed the old field name alone is correctly treated as a missing required field (not a silent bypass). Verified the table self-heal check via an isolated mock: does nothing when the table exists, calls `adm_mgr_activate()` exactly once when it doesn't.

## [1.1.10] - 2026-09-10

### Added
- "Remove" button next to each of the three file upload fields (passport photo, scanned documents, payment proof). Browsers provide no built-in way to clear a selected file/files once chosen other than picking a different one, which real-world testing showed wasn't obvious to applicants. Clicking it clears the corresponding `<input type="file">` and, for the passport photo, resets its live preview back to the placeholder state (clearing a file input's value programmatically doesn't fire a native `change` event in any current browser, so this is handled explicitly).

### Fixed
- Name and permanent address fields were styled with `text-transform: uppercase` (`.adm-uppercase` class) so they visually appeared as block letters while typing, but this is a CSS-only effect — the actual submitted value stayed in whatever case the applicant typed, meaning the saved record, admin panel view, CSV export, and confirmation email all showed mixed/lowercase text despite the form visually showing uppercase. Added a live JS transform (preserving cursor position while typing) so the actual input value matches what's displayed, plus a server-side `adm_mgr_to_uppercase()` backstop (using `mb_strtoupper()` when available for correct handling of accented characters, falling back to `strtoupper()`) applied when the submission is saved, covering applicants with JavaScript disabled.
- Verified `adm_mgr_to_uppercase()` in isolation: basic ASCII, mixed-case, and multi-line address input all uppercase correctly; confirmed graceful fallback behavior when `mbstring` isn't available.

## [1.1.9] - 2026-09-09

### Fixed
- Orphaned uploaded files in `adm_mgr_handle_submission()`: the passport photo (and payment proof, if provided) were written to disk before later steps ran, but if any later step failed — an invalid scanned document, `adm_mgr_encrypt()` failing, or the `$wpdb->insert()` call failing — the function returned early without deleting the files already written, leaking them on disk indefinitely with no submission row ever referencing them.
- Added `adm_mgr_delete_uploaded_files()`, a shared helper (also used to refactor the existing per-submission `adm_mgr_delete_files()`, which now calls it, removing duplicated URL-to-path conversion logic). The submission handler now accumulates every successfully-uploaded path into `$uploaded_paths` and calls this helper before all four possible early returns that can occur after at least one file has been uploaded: payment-proof validation failure, scanned-document validation failure, Aadhar encryption failure, and DB insert failure.
- Verified with an isolated test harness using real files on a temp filesystem (not just mocks): confirmed the helper deletes exactly the requested files and leaves others untouched, handles empty/null entries gracefully, and confirmed the refactored `adm_mgr_delete_files()` still correctly removes all files for the admin "delete entry" action. Also confirmed by inspection that `adm_mgr_upload_file()` never writes a file to disk before returning `false` — so the only orphan risk was ever previously-successful uploads within the same request, which this closes.

## [1.1.8] - 2026-09-08

### Security
- Addressed audit finding Immediate #3: the Aadhar encryption key was derived solely from `AUTH_KEY`/`SECURE_AUTH_KEY`, which WordPress's own hardening guidance recommends rotating (e.g. after a suspected compromise). Rotating them silently changes the derived key, and `adm_mgr_decrypt()` would fail to authenticate every previously-encrypted Aadhar number with no visible symptom — the "Reveal" action would return success with a blank value rather than an error.
- `adm_mgr_get_encryption_key()` now checks for an optional `ADM_MGR_ENCRYPTION_KEY` constant in `wp-config.php` first — a dedicated, plugin-specific secret that's never affected by WordPress's own salt rotation. Documented in README.md as the recommended setup for any site with real Aadhar data. Falls back to the original `AUTH_KEY`/`SECURE_AUTH_KEY`-derived behavior for backward compatibility if not defined.
- Added `adm_mgr_get_key_fingerprint()` (a one-way, non-reversible hash of the current key — reveals nothing about the key itself) plus `adm_mgr_check_key_rotation()`, hooked to `admin_init`, which compares the current fingerprint against a stored baseline on every admin page load and sets a pending-warning flag if they differ. `adm_mgr_key_rotation_notice()` then shows a persistent `admin_notices` warning explaining what happened, what to do (restore an old wp-config.php backup if available, to re-encrypt affected entries before finalizing the rotation), and a dismiss/acknowledge action (`adm_mgr_handle_ack_key_rotation()`) that accepts the new key as the baseline going forward.
- `adm_mgr_decrypt()` now returns `false` specifically when decryption fails (wrong/rotated key) as opposed to `''` for a genuinely empty stored value — these were previously indistinguishable. `adm_mgr_ajax_reveal_aadhar()` checks for `false` and returns an explicit `wp_send_json_error()` (409) with a clear explanation, instead of `wp_send_json_success()` with a blank value. No JS changes needed — the existing Reveal button handler already displays `data.message` on any error response.
- New options `adm_mgr_key_fingerprint` and `adm_mgr_key_rotation_pending` added to the uninstall cleanup list.
- Verified all of the above with an isolated test harness across 12 scenarios: dedicated-key round-trip, fingerprint stability, first-run baseline (no false warning), same-key no-warning, actual rotation correctly detected (using two separate PHP processes to simulate before/after key material, since constants can't be redefined mid-process), decrypt-with-wrong-key returns `false`, genuinely-empty-value still returns `''`, the AJAX handler's error/success/empty paths, the acknowledge handler correctly clearing state, and the pre-existing `AUTH_KEY`/`SECURE_AUTH_KEY` fallback path remaining unchanged for backward compatibility.

## [1.1.7] - 2026-09-07

### Fixed
- Identified the actual cause of the "update shown as available on both the Dashboard and Plugins list, even immediately after updating" issue, using the v1.1.6 diagnostics panel and screenshots from a real affected site (`mylab.biswazit.in`): the site runs **LiteSpeed Cache**, which maintains its own object-cache layer underneath WordPress's standard `get_site_transient()`/`set_site_transient()`/`delete_site_transient()` functions. Calling those functions correctly updates WordPress's logical view of the transient, but LiteSpeed Cache doesn't automatically know to purge its own cache in response — it requires an explicit `do_action('litespeed_purge_all')` signal, which is LiteSpeed's documented integration point for exactly this kind of scenario (confirmed against community precedent using the same `upgrader_process_complete` hook this plugin already uses).
- `adm_mgr_refresh_after_update()` now fires that purge when LiteSpeed Cache is detected active (`defined('LSCWP_V')` or `class_exists('\LiteSpeed\Purge')`), immediately after the existing WordPress-level transient rebuild from v1.1.4/v1.1.5. Verified in isolation: purge fires only when LiteSpeed is present and only for this plugin's own update, not for other plugins or when LiteSpeed isn't installed.

## [1.1.6] - 2026-09-06

### Added
- Diagnostics only, not a fix: the v1.1.4/v1.1.5 attempts at resolving "update must be applied twice, once from the Dashboard's Updates page and again from the Plugins list" have not fully resolved it on at least one real production site, despite tracing through WordPress core's `wp_update_plugins()` throttling logic (which varies by triggering hook: 0s via `upgrader_process_complete`, 1 minute via `load-update-core.php`, 1 hour via `load-plugins.php`) and confirming the existing fix's reasoning holds up against that logic. Rather than ship a fourth unverified guess, added a read-only "Update-checker diagnostics" panel to Admissions → Settings, showing: the running `ADM_MGR_VERSION`, the update transient's `checked[]` version and `last_checked` time for this plugin, whether it's present in `response[]` (update available) or `no_update[]` (confirmed current), and our own GitHub-release cache state. No side effects — safe to view/screenshot repeatedly while reproducing the issue, to get direct evidence of exactly where the two admin screens diverge.

## [1.1.5] - 2026-09-05

### Fixed
- Regression from v1.1.4: a duplicate "View details" link appeared on the Plugins list row (confirmed via screenshot — both labeled identically, present even with no update pending). Root cause: populating `$transient->no_update[ADM_MGR_BASENAME]` in v1.1.4 (needed to fix cross-screen update-status consistency) is the same signal WordPress core uses to decide whether to render its own native "View details" thickbox link — which started appearing alongside the one `adm_mgr_plugin_row_meta()` added manually since v1.1.2. Simplified that function to only remove the default "Visit plugin site" link; WordPress's now-automatic native "View details" link already correctly uses this plugin's `adm_mgr_plugins_api_details()` data. (This same v1.1.4 change is also why an "Enable/Disable auto-updates" toggle started appearing — both are WordPress core features gated on the same no_update/response presence check.)
- Further strengthened the v1.1.4 fix for the update sometimes requiring two separate "Update Now" actions (e.g. once from the Dashboard, again from the Plugins list) before it cleared everywhere: `adm_mgr_refresh_after_update()` now calls `wp_update_plugins()` directly and synchronously right after clearing both caches, forcing an immediate rebuild of the transient within the same request — rather than clearing it and relying on whichever admin screen happens to be visited next to trigger the rebuild.
- Verified both changes with the same isolated test-harness approach used for prior fixes (extracted the real functions, mocked transients/`wp_update_plugins()`): confirmed the row-meta function now returns exactly one link removal and adds nothing, and confirmed the refresh hook now calls `wp_update_plugins()` exactly once and leaves the transient freshly rebuilt rather than empty.

## [1.1.4] - 2026-09-05

### Fixed
- Update-checker inconsistency: a pending update could show as "Available" on one admin screen (e.g. Plugins list) even after already being applied from another (e.g. the Dashboard's Updates page), requiring the update to be triggered a second time before it cleared everywhere. Two contributing gaps addressed in `adm_mgr_inject_update_transient()` and a new `adm_mgr_refresh_after_update()`:
  - When up to date, the plugin is now explicitly written into `$transient->no_update` (not just removed from `$transient->response`). WordPress core distinguishes "not yet checked" from "checked, no update" by presence in `no_update`, and different admin screens don't all key off `response` alone — leaving a plugin in neither array is a known source of cross-screen inconsistency for custom (non-wordpress.org) update checkers.
  - Added an `upgrader_process_complete` hook that force-clears both our own 12-hour `adm_mgr_latest_release` cache and WordPress's shared `update_plugins` transient the moment this plugin finishes updating (single or bulk update). WordPress's own post-upgrade cleanup only clears its own transient — it has no way to know about our plugin-specific cache, which could otherwise still be serving a response computed just before the upgrade completed.
- Verified both changes with an isolated test harness (extracted the real functions, mocked `$wpdb`/transients) covering: up-to-date vs. update-available transient shape, a stale `response` entry being correctly cleared on re-check, and the post-upgrade hook correctly firing only for this plugin across single-update, bulk-update, wrong-plugin, wrong-type, and install (non-update) scenarios.

## [1.1.3] - 2026-09-05

### Security
- Aadhar numbers saved before v1.1.0 (when encryption at rest was introduced) were never migrated and remained in plaintext in the `aadhar_number` column indefinitely — the encryption feature only applied to new submissions going forward. Added `adm_mgr_migrate_legacy_aadhar()`, a one-time, self-healing migration hooked to `plugins_loaded`: it selects rows whose `aadhar_number` doesn't already look like ciphertext (no `sb1:`/`gcm1:` prefix), encrypts them in place, and backfills `aadhar_last4` from the plaintext before overwriting it. Runs in batches of 200 on normal admin page loads (not all at once during activation) so it can't time out on a large table, and is idempotent — safe to run repeatedly, and automatically resumes if interrupted. A one-time `admin_notices` message confirms how many records were migrated once the pass completes with nothing left to do.
- Added `adm_mgr_aadhar_migration_done` and `adm_mgr_aadhar_migrated_count` options (cleaned up on uninstall alongside the plugin's other options).

## [1.1.2] - 2026-09-02

### Changed
- Replaced all references to the author's personal domain (`https://biswazit.in`, which is being retired) with this GitHub repository URL: plugin header `Author URI`, the `plugins_api` "View version details" info popup, `readme.txt` Credits, and `README.md` Credits. No functional or database changes.
- Plugins list page: added a "Check for updates" action link next to Deactivate/Settings, reusing the same handler and nonce as the existing button in Admissions → Settings, so an on-demand check no longer requires navigating there first.
- Plugins list page: replaced the default "Visit plugin site" row-meta link (derived from the `Plugin URI` header) with "View details", matching the convention used by WordPress.org-hosted plugins. Opens the existing `plugins_api` thickbox populated with live version/changelog data from GitHub.
- Moved the "Checked GitHub for the latest release" confirmation notice to a global `admin_notices` hook so it displays correctly regardless of whether the manual check was triggered from Settings or the new Plugins-list link.

## [1.1.1] - 2026-08-31

### Fixed
- Fatal error on PHP 7.4–7.4.x: `adm_mgr_fetch_latest_release()` called `str_ends_with()`, which was only introduced in PHP 8.0, despite the plugin declaring `Requires PHP: 7.4`. This function runs from the update-checker on effectively every wp-admin page load once a release has assets, so on PHP 7.4 hosts it produced a fatal error (`Call to undefined function str_ends_with()`) instead of a graceful check. Replaced with a 7.4-safe `substr()` comparison. No functional or schema changes otherwise.

## [1.1.0] - 2026-06-27

### Added
- Independent alignment and sizing for the logo, title, and tagline — previously they shared a single alignment setting that didn't actually separate them visually.
- New print preview: "Print Form" opens a dedicated tab built from the form's current values (not the live inputs themselves), including a live preview of the uploaded passport photo shown next to the applicant's name.
- Desktop field grouping: related short fields (contact numbers, nationality/Aadhar/country, sex/blood group, etc.) lay out inline in a single row on desktop and stack on mobile.
- Aadhar number encryption at rest (libsodium `crypto_secretbox`, falling back to OpenSSL AES-256-GCM), with masked display (last 4 digits) in the admin panel and CSV export, and an admin-only "Reveal" action that is logged.
- Honeypot field and per-IP rate limiting (5 submissions / 10 minutes) on the public form to cut down on bot/spam entries.
- GitHub Releases-based update checker: the plugin now appears with normal WordPress "update available" notices, version details, and one-click "Update now", checked every 12 hours or on demand via a "Check for Updates" button in Settings.
- DB migrations now also run on plugin **update** (via `plugins_loaded` + a stored DB version check), not only on activation.

### Changed
- Print/Submit buttons moved to the bottom of the form: Print on the left, Submit on the right.
- Settings page's Header/Branding section reorganized so each element's (logo/title/tagline) controls — image/text, size, alignment — are grouped together.
- Uploads directory `.htaccess` rewritten to only block script execution and directory listing, not all direct file access (see Fixed).

### Fixed
- **Printing produced a blank page with no entered data.** The previous implementation tried to print the live form via `visibility`/`position` CSS tricks; browsers do not reliably render `<input>`/`<textarea>` values when printed this way. Printing is now a static, pre-rendered snapshot built from the form's actual values at click-time.
- **Header alignment had no real effect / wasn't independent.** Logo, title, and tagline now each carry their own `text-align`, set from three separate Settings controls instead of one shared one.
- **Uploads directory `.htaccess` blocked all direct access**, including the admin panel's own "View" links to uploaded photos/documents (an unreported but real bug introduced in 1.0.0). It now only blocks execution of script-like file extensions.

## [1.0.0] - 2026-06-27

### Added
- First public release.
- Adjustable header: logo width, institute title, optional tagline, font sizes, and alignment, configurable in **Admissions → Settings** with a live preview.
- Print output now reproduces the same adjustable header (logo, title, tagline) as the on-screen form.
- "Delete data on uninstall" opt-in checkbox in Settings, so submissions and uploads are preserved by default if the plugin is deactivated or removed.
- Real file-content (MIME) validation on all uploads via `wp_check_filetype_and_ext()`, in addition to extension and size checks.
- Nonce protection on the CSV export and delete-entry admin actions (previously relied on capability checks only).

### Changed
- Mobile layout: removed the oversized outer padding/margins that left large empty gutters on phones; the form now fills the available width edge-to-edge.
- Printing now uses the browser's native `window.print()` and a dedicated `@media print` stylesheet, removing the external `jquery-print` CDN dependency.
- Moved all inline `<style>`/`<script>` blocks out of the shortcode output into the properly enqueued `assets/style.css` and `assets/script.js`.
- Settings page reorganized into clear sections (Header/Branding, Payment, Admission Window & Notifications).
- Database table creation now uses `dbDelta()`-compatible SQL (removed `ALTER TABLE ... ADD COLUMN IF NOT EXISTS`, which is unreliable across MySQL/MariaDB versions).
- Frontend assets are now only enqueued on singular pages/posts that actually contain the `[admission_form]` shortcode.

### Fixed
- CSRF exposure on the "Export CSV" and "Delete entry" admin links — both now require a valid nonce.
- Uninstall no longer silently destroys all admission data; it is gated behind an explicit Settings checkbox.
- Aadhar number is now validated server-side as exactly 12 digits, matching the existing client-side `pattern` attribute.
