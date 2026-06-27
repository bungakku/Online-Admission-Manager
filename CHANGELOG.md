# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/).

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
