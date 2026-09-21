=== Online Admission Manager ===
Contributors: biswazit
Tags: admission, form, education, school, college
Requires at least: 5.8
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.1.22
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Complete online admission form for schools and colleges, with academic records, file uploads, an admin panel, admission-window control, email confirmation, CSV/Excel export, and a payment QR code.

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
* Admin panel to browse, view, and delete submissions, with export to CSV or Excel (.xlsx, with auto-sized columns) — both include academic records as a readable summary
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

= 1.1.22 =
* Fixed: when a submission was rejected (bad phone number, over-long field, expired security check, wrong file type, and so on) the form reloaded completely empty, so a single typo meant retyping the whole application. The form now comes back filled in with everything the applicant entered — including every academic-record row — so they only fix the problem and submit again. For security the Aadhar number is not filled back in, and browsers can't refill file uploads, so a short note asks the applicant to re-enter their Aadhar number and choose their photo and documents again.

= 1.1.21 =
* Fixed: when the form rejected a submission (invalid phone number, over-long field, bad email, and so on), the error message was printed at the very bottom of the page, below the site footer, while the form above reloaded empty — so it looked as though nothing had happened and no reason was given. The message now appears directly above the form, the page scrolls to it, and it is announced to screen readers (role="alert" for errors, role="status" for confirmations). The success confirmation after submitting is scrolled into view and announced the same way.
* The phone-number error message wording was tidied slightly ("spaces, hyphens, brackets and a leading + are allowed").

= 1.1.20 =
* Fixed: contact-number fields (WhatsApp, Alternate, and both parents' contacts) accepted anything at all — letters, a typo'd digit count, several numbers crammed into one box — so an applicant could be saved with a number nobody could ever call. The form now checks that each number looks like a phone number: 7 to 15 digits, with spaces, hyphens, brackets and a leading + allowed (so "+91 98765 43210", "(0361) 234 5678" and foreign numbers all pass), and shows a specific message otherwise. WhatsApp is still required; the other numbers are still optional. Numbers pasted from a contact card or WhatsApp (which often contain invisible characters) are accepted, and numbers are saved exactly as typed.

= 1.1.19 =
* Fixed: deleting an application in the admin panel removed the application and its uploaded files, but left that applicant's academic records behind in the database as orphaned rows. Deleting an entry now removes its academic records too. Applications that have already been deleted in the past may have left such rows behind; they are harmless to exports and are not removed automatically.

= 1.1.18 =
* Fixed: form fields had no length limits matching the database, so an over-long entry (e.g. a very long name, phone number, or an academic-record field) either failed the whole submission with a generic "Something went wrong" message, was silently cut off, or — for academic records — was silently lost. Every free-text field now has a maxlength matching its database column, and the server enforces the same limits with a specific message (e.g. "WhatsApp No. is too long (maximum 20 characters)."). Nothing is uploaded or saved until it's fixed. Normal entries are unaffected.

= 1.1.17 =
* Fixed: an invalid email address on the application form was silently discarded. The application was accepted and saved with a blank email, no confirmation email was ever sent, and the applicant was never told. The form now shows "Please enter a valid email address, or leave the email field blank." and nothing is saved or uploaded until it's fixed. Email remains optional; blank and valid addresses behave exactly as before. This also catches addresses that some browsers accept but WordPress rejects (e.g. "name@localhost").

= 1.1.16 =
* Performance: CSV and Excel exports no longer run one extra database query per applicant to fetch academic records. They now fetch all records in a single batched query (in chunks of 500 applicants), so export time no longer grows with one round-trip per row. Export files are byte-for-byte identical in content to v1.1.15.

= 1.1.15 =
* Academic records now export as a readable summary (e.g. "HSSCE (2025) - Division: 1, Marks: 61%, Board: CBSE, Subjects: Physics, Chemistry") instead of raw JSON, in both CSV and the new Excel export.
* Added a new "Export All to Excel" option alongside CSV. Unlike CSV, which has no concept of column width at all, the Excel file has genuinely auto-sized columns built in — no manual "auto-fit" step needed in your spreadsheet app.

= 1.1.14 =
* Fixed CSV export producing a mangled file containing the entire admin page's HTML with the real data appended at the end, instead of a clean CSV. The export ran from inside the Admissions page's own callback, which WordPress only calls after it has already sent the page's HTML — too late to send file-download headers. Moved the export to run via admin-post.php, a dedicated endpoint that renders no page around it, giving it a clean response to work with.

= 1.1.13 =
* Category field now has a blank "Select" default, matching Sex — applicants must consciously choose rather than silently submitting "Gen" by default.
* Added server-side validation for Sex and Category: only the exact options shown in the form are accepted, closing off a crafted submission storing an arbitrary value in either field.

= 1.1.12 =
* Critical fix: the plugin's own database tables could never actually be created, on any install, ever — the CREATE TABLE statements declared a primary key twice ("id INT AUTO_INCREMENT PRIMARY KEY" plus a separate "PRIMARY KEY (id)" clause), which MySQL/MariaDB correctly rejects with "Multiple primary key defined". Confirmed by reproducing the exact error against a real MariaDB server. Fixed both tables. Existing sites will have their tables correctly created automatically the next time an admin page loads or an application is submitted — no manual steps needed.

= 1.1.11 =
* Critical fix: form submissions were failing with a "Page not found" error and no record ever being saved. Root cause: the Full Name field was named "name", which collides with WordPress's own reserved `name` query variable (used internally to look up a page by its slug). On submission, WordPress's router picked up the typed name as an override and tried to find a page with that name as its slug, found none, and rendered its own 404 — instead of processing the form. Renamed the field internally; nothing changes for applicants filling out the form.
* Hardened submission handling: if the database table is ever missing (confirmed possible in real-world testing), it's now automatically recreated before saving, instead of the submission failing with a database error.

= 1.1.10 =
* Added a "Remove" button next to each file upload field (passport photo, scanned documents, payment proof), since browsers offer no built-in way to clear a selected file once chosen.
* Fixed name and permanent address fields: they were visually styled as uppercase while typing, but the actual value saved, displayed in the admin panel, exported to CSV, and emailed was still whatever case the applicant typed. The real value now matches what's shown.

= 1.1.9 =
* Fixed orphaned uploaded files: if a submission uploaded a passport photo successfully but then failed later in the same request (an invalid payment proof or scanned document, an encryption error, or a database error), the earlier file(s) stayed on disk forever with no submission record ever pointing to them. Uploaded files are now tracked within the request and automatically deleted if any later step fails.

= 1.1.8 =
* Security (audit Immediate #3): the Aadhar encryption key was derived solely from WordPress's AUTH_KEY/SECURE_AUTH_KEY salts, which are meant to be rotated (e.g. after a suspected compromise) — rotating them silently changed the derived key, permanently breaking decryption of every previously-encrypted Aadhar number with no visible symptom. Added support for a dedicated `ADM_MGR_ENCRYPTION_KEY` constant in wp-config.php (recommended for production — see README), which is immune to WordPress's own salt rotation. For sites not using it, the plugin now detects when the effective key has changed and shows an immediate admin warning instead of failing silently later. The "Reveal" action also now returns an explicit error when decryption genuinely fails, instead of silently showing a blank value.

= 1.1.7 =
* Found the actual cause of the persistent "update shown as available on both the Dashboard and Plugins list, even right after updating" issue: LiteSpeed Cache (confirmed present via screenshots from a real affected site) maintains its own object-cache layer underneath WordPress's normal transient functions, and doesn't purge it just because delete_site_transient()/set_site_transient() were called from PHP — it needs an explicit purge signal, which is a documented LiteSpeed integration point. The post-update refresh now fires that purge when LiteSpeed Cache is detected active, in addition to the WordPress-level transient rebuild already added in v1.1.4/v1.1.5.

= 1.1.6 =
* Diagnostics: the two previous attempts (v1.1.4, v1.1.5) at fixing the "update must be applied twice" issue haven't fully resolved it on at least one real site, and further code-only reasoning hasn't identified a definitive cause. Added a read-only "Update-checker diagnostics" panel to Admissions → Settings showing exactly what WordPress's update transient currently holds for this plugin (checked version, last-checked time, response/no_update status, our own release cache) — this is not a fix, it's instrumentation to pin down the real cause with direct evidence instead of guesswork.

= 1.1.5 =
* Fixed a duplicate "View details" link on the Plugins list page, introduced by v1.1.4: populating the update transient's "checked, up to date" data (needed for the previous fix) is also the signal WordPress uses to render its own native "View details" link, which was appearing alongside the one this plugin added manually. Removed the manual one; WordPress's native link already uses this plugin's real changelog data.
* Strengthened the v1.1.4 fix for the update sometimes needing to be applied twice: after this plugin finishes updating, the update-check transient is now rebuilt immediately and synchronously in the same request, instead of waiting for the next admin page visit to trigger it.

= 1.1.4 =
* Fixed an update-checker inconsistency where a plugin update could still show as "Available" on one admin screen (e.g. the Plugins list) after already being applied from another (e.g. the Dashboard), requiring the update to be triggered twice. Two changes: the plugin is now explicitly marked "checked, up to date" (not just removed from the update list) so different WordPress admin screens agree on its status; and our own release-info cache is now force-cleared the moment WordPress finishes updating this plugin, so every screen re-checks fresh instead of possibly reading a cache from just before the update completed.

= 1.1.3 =
* Security: Aadhar numbers saved before v1.1.0 (when encryption was introduced) were never migrated and remained in plaintext in the database indefinitely. Added an automatic, one-time, self-healing migration that encrypts any remaining plaintext Aadhar numbers and backfills the masked last-4-digits field, running quietly in small batches on normal admin page loads. A one-time success notice confirms how many records were migrated.

= 1.1.2 =
* Author URI now points to this GitHub repository instead of biswazit.in, which is being retired. Updated in the plugin header, the "View version details" info popup, readme.txt, and README.md.
* Plugins list page: added a "Check for updates" action link next to Deactivate, so an on-demand check no longer requires visiting Admissions → Settings first.
* Plugins list page: replaced the default "Visit plugin site" meta link with "View details", matching the convention used by WordPress.org-hosted plugins — opens the same information popup with live release notes.

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

= 1.1.22 =
A rejected submission now brings the form back filled in (except the Aadhar number and file uploads) instead of empty. Clear any page cache after updating. No database changes.

= 1.1.21 =
Form error messages now appear directly above the form (and are scrolled to and announced by screen readers) instead of at the very bottom of the page, where they were easy to miss. Clear any page cache after updating. No database changes.

= 1.1.20 =
Contact numbers are now checked to look like real phone numbers (7-15 digits; spaces, hyphens, brackets and a leading + allowed) instead of accepting anything. Numbers are saved exactly as typed. No database changes.

= 1.1.19 =
Deleting an application now also deletes that applicant's academic records instead of leaving them orphaned in the database. No database changes; existing leftover rows are not touched automatically.

= 1.1.18 =
Form fields now have length limits matching the database, enforced in the browser and on the server, so over-long entries get a clear error instead of a generic failure or silent data loss. No database changes.

= 1.1.17 =
Applicants who type an invalid email address now get a clear error instead of having it silently dropped (which meant no confirmation email was sent). Email is still optional. No database changes.

= 1.1.16 =
Makes CSV/Excel export much faster on sites with many applications (one batched query instead of one per applicant). Export content is unchanged. No database changes.

= 1.1.15 =
Adds a new Excel export with auto-sized columns, and academic records now export as readable text instead of raw JSON. No database changes.

= 1.1.14 =
Fixes CSV export producing a mangled file (admin page HTML mixed in with the real data) instead of a clean CSV. No database changes.

= 1.1.13 =
Category now requires an explicit selection (no more silent default), and Sex/Category are validated server-side. No database changes.

= 1.1.12 =
Critical: fixes a SQL error that prevented the plugin's database tables from ever being created successfully. Update immediately. Tables are recreated automatically after updating — no manual database steps needed.

= 1.1.11 =
Critical: fixes form submissions failing with "Page not found" and never being saved (a field name collided with a reserved WordPress variable). Update immediately if applicants have been unable to submit. Clear any page cache after updating. No database schema changes.

= 1.1.10 =
Adds Remove buttons for file uploads and fixes name/address fields not actually being saved in uppercase despite looking that way while typing. No database changes.

= 1.1.9 =
Fixes orphaned uploaded files accumulating on disk when a submission fails partway through (after a photo/document was already uploaded). No database changes.

= 1.1.8 =
Security: adds protection against WordPress security-key rotation silently breaking Aadhar decryption, plus an immediate warning if it's already happened. Consider adding ADM_MGR_ENCRYPTION_KEY to wp-config.php (see README). No database schema changes.

= 1.1.7 =
Fixes the persistent "update shown as available even after updating" issue on sites running LiteSpeed Cache, which needed an explicit purge signal this plugin wasn't sending. No database changes.

= 1.1.6 =
Adds a diagnostics panel to help pin down the update-checker "twice" issue with direct evidence. Not a fix by itself. No database changes.

= 1.1.5 =
Fixes a duplicate "View details" link on the Plugins list (regression from v1.1.4) and further strengthens the fix for updates sometimes needing to be applied twice. No database changes.

= 1.1.4 =
Fixes the update checker sometimes requiring the update to be applied twice (once from the Dashboard, again from the Plugins list) before it stopped showing as available. No database changes.

= 1.1.3 =
Security fix: any Aadhar numbers still stored in plaintext from before v1.1.0 are now automatically encrypted in the background. No action needed, but back up your database before updating as good practice — this update writes to existing records.

= 1.1.2 =
Author link updated from biswazit.in (retiring) to this GitHub repository. Plugins list page now has a "Check for updates" link and a "View details" link (replacing "Visit plugin site"). No database changes.

= 1.1.1 =
Fixes a fatal error on PHP 7.4 sites (undefined function in the update checker). Recommended for everyone, required if your host runs PHP 7.4.

= 1.1.0 =
Aadhar numbers are now encrypted going forward; existing entries are read and displayed normally and become encrypted the next time they're saved. No action needed, but back up your database before updating as good practice.

== Credits ==

Developed by Biswajit – https://github.com/bungakku/Online-Admission-Manager
