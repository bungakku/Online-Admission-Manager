# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/).

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
