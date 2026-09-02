=== Online Admission Manager ===
Contributors: biswazit
Tags: admission, form, education, school, college
Requires at least: 5.8
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.1.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Complete online admission form for schools and colleges, with academic records, file uploads, an admin panel, admission-window control, email confirmation, CSV export, and a payment QR code.

== Description ==

Online Admission Manager adds a fully featured admission/enquiry form to any page via a shortcode. It is built for schools, colleges, and coaching institutes that want to collect admission applications online without a third-party form builder.

**Features**

* Single shortcode: `[admission_form]`
* Personal information, parents' details, addresses, academic history (repeatable rows), and document uploads in one form
* Adjustable header: upload your own logo, set its width, edit the institute title and an optional tagline — logo, title, and tagline each have their own independent alignment and size, all from Settings with a live preview
* A "Print Form" button opens a clean, read-only print preview in a new tab, populated with everything entered so far (including a live passport photo preview next to the applicant's name), ready to print or save as PDF
* Related short fields (contact numbers, nationality/Aadhar/country, etc.) line up in a single row on desktop and stack automatically on mobile
* File uploads (photo, payment proof, scanned documents) capped at 300KB per file, validated by extension, size, and actual file content
* Aadhar numbers are encrypted at rest and masked in the admin panel and CSV exports; a "Reveal" button decrypts on demand for authorized admins, with access logging
* A honeypot field and basic per-IP rate limiting help filter out bot submissions
* Admission window control — set a start/end date and the form automatically disables itself outside that window
* Email confirmation to the applicant plus a notification to the admin on every submission
* Admin panel to browse, view, and delete submissions, with CSV export of everything (including academic records)
* Optional payment QR code shown next to the payment-proof upload field
* Mobile-friendly, edge-to-edge responsive layout
* Built-in update checker against GitHub Releases, so WordPress shows update notifications the same way it would for a wordpress.org-hosted plugin

**Shortcode**

Add this to any page or post:

`[admission_form]`

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/`, or install the zip via Plugins → Add New → Upload Plugin.
2. Activate the plugin through the "Plugins" menu in WordPress.
3. Go to **Admissions → Settings** to set your institute name, tagline, logo, admission window, and email settings.
4. Add `[admission_form]` to any page where you want the form to appear.

== Frequently Asked Questions ==

= Can I position the logo, title, and tagline independently? =

Yes. Each one has its own alignment (left/center/right), and the logo and the two text elements each have their own size control, all in Admissions → Settings → Header / Branding, with a live preview.

= Does printing show the data the applicant entered? =

Yes. Clicking "Print Form" opens a new tab with a clean summary of everything filled in so far — including a preview of the uploaded passport photo next to the applicant's name — ready to print or save as a PDF. Nothing is sent to the server to generate this; it's built entirely from what's currently in the browser.

= Is the Aadhar number printed on the form? =

No — by design. The Aadhar number is collected and stored (encrypted) but deliberately left off the print preview, since printed pages are easy to misplace or photograph. It remains visible (masked, with an admin-only "Reveal" option) in the admin panel.

= What happens to applications if I deactivate or delete the plugin? =

By default, nothing is deleted — your submissions, uploaded files, and settings remain in the database so you don't lose data if the plugin is deactivated by mistake. If you want a clean uninstall, tick "Delete all submissions, uploaded files, and settings when this plugin is removed" in Settings before deleting the plugin.

= What's the maximum file size for uploads? =

300KB per file (passport photo, payment proof, and each scanned document). This is enforced both in the browser and on the server.

= How do plugin updates work? =

The plugin checks the GitHub repository's Releases for a newer version every 12 hours, and you can also click "Check for Updates" in Settings for an immediate check. When a newer release is found, it shows up on the normal Plugins page with an "Update now" link, just like a wordpress.org plugin.

== Screenshots ==

1. Admission form with independently adjustable logo, title, and tagline.
2. Settings page with live header preview.
3. Print preview tab showing entered data and photo.
4. Admin entries list with CSV export.

== Changelog ==

= 1.1.1 =
* Fixed a fatal error on PHP 7.4–7.4.x: the update checker used `str_ends_with()`, a PHP 8.0+ function, despite the plugin declaring `Requires PHP: 7.4`. Replaced with a 7.4-compatible check.

= 1.1.0 =
* Logo, title, and tagline now have fully independent alignment and sizing, instead of sharing one alignment setting.
* Reworked "Print Form" to open a dedicated print-preview tab populated with the applicant's actual entered data and a live preview of the uploaded passport photo next to their name — the previous approach attempted to print the live form directly, which most browsers do not render reliably for input/textarea values.
* Print and Submit buttons moved to the bottom of the form, side by side (Print on the left, Submit on the right).
* Related short fields (e.g. WhatsApp/alternate numbers, nationality/Aadhar/country) are grouped into a single inline row on desktop, and stack automatically on narrower screens.
* Aadhar numbers are now encrypted at rest (libsodium, with an OpenSSL AES-256-GCM fallback) and shown masked in the admin panel and CSV export; a "Reveal" button decrypts on demand and logs the access.
* Added a honeypot field and basic per-IP rate limiting on form submission to reduce bot/spam entries.
* Corrected the uploads-folder `.htaccess`: the previous rule blocked all direct access, including the admin panel's own "View" links to uploaded files; it now only blocks script execution and directory listing.
* Added a GitHub Releases-based update checker, so available updates show on the normal Plugins page with release notes and one-click install.
* Various hardening: DB schema migrations now run safely on update (not just on activation), and several smaller escaping/consistency fixes.

= 1.0.0 =
* First public release.
* Added adjustable logo width, institute title, and tagline (with font size and alignment controls) on the form header.
* Added matching print-header support so the printed form reflects the same header settings.
* Fixed mobile layout so the form uses the full available width instead of leaving large side gutters.
* Added nonce protection to the CSV export and delete-entry admin actions.
* Added real file-content (MIME) validation on uploads, in addition to extension and size checks.
* Removed the external jquery-print CDN dependency; printing now uses the browser's native print with dedicated print CSS.
* Added an explicit "delete data on uninstall" opt-in so data is never silently removed.
* General code cleanup, internationalization (i18n) coverage, and escaping/sanitization hardening throughout.

== Upgrade Notice ==

= 1.1.1 =
Fixes a fatal error on PHP 7.4 sites (undefined function in the update checker). Recommended for everyone, required if your host runs PHP 7.4.

= 1.1.0 =
Aadhar numbers are now encrypted going forward; existing entries are read and displayed normally and become encrypted the next time they're saved. No action needed, but back up your database before updating as good practice.

== Credits ==

Developed by Biswajit – https://biswazit.in
