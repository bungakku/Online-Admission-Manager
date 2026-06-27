=== Online Admission Manager ===
Contributors: Biswajit Thokchom
Tags: admission, form, education, school, college
Requires at least: 5.8
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Complete online admission form for schools and colleges, with academic records, file uploads, an admin panel, admission-window control, email confirmation, CSV export, and a payment QR code.

== Description ==

Online Admission Manager adds a fully featured admission/enquiry form to any page via a shortcode. It is built for schools, colleges, and coaching institutes that want to collect admission applications online without a third-party form builder.

**Features**

* Single shortcode: `[admission_form]`
* Personal information, parents' details, addresses, academic history (repeatable rows), and document uploads in one form
* Adjustable header: upload your own logo, set its width, edit the institute title and an optional tagline, choose font sizes and alignment — all from Settings
* The same header (logo, title, tagline) is reproduced on the printed form
* "Print Form" button so applicants can print a hard copy of what they entered
* File uploads (photo, payment proof, scanned documents) capped at 300KB per file, validated by both extension and actual file content
* Admission window control — set a start/end date and the form automatically disables itself outside that window
* Email confirmation to the applicant plus a notification to the admin on every submission
* Admin panel to browse, view, and delete submissions, with CSV export of everything (including academic records)
* Optional payment QR code shown next to the payment-proof upload field
* Mobile-friendly, edge-to-edge responsive layout

**Shortcode**

Add this to any page or post:

`[admission_form]`

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/`, or install the zip via Plugins → Add New → Upload Plugin.
2. Activate the plugin through the "Plugins" menu in WordPress.
3. Go to **Admissions → Settings** to set your institute name, tagline, logo, admission window, and email settings.
4. Add `[admission_form]` to any page where you want the form to appear.

== Frequently Asked Questions ==

= Can I change the logo size? =

Yes. Go to Admissions → Settings → Header / Branding and use the Logo Width slider. The live preview shows the result immediately, and the same width is used when the form is printed.

= Does the printed form show my logo and title? =

Yes. The print stylesheet reuses the same header settings (logo, title, tagline, sizes, alignment) configured in Settings.

= What happens to applications if I deactivate or delete the plugin? =

By default, nothing is deleted — your submissions, uploaded files, and settings remain in the database so you don't lose data if the plugin is deactivated by mistake. If you want a clean uninstall, tick "Delete all submissions, uploaded files, and settings when this plugin is removed" in Settings before deleting the plugin.

= What's the maximum file size for uploads? =

300KB per file (passport photo, payment proof, and each scanned document). This is enforced both in the browser and on the server.

== Screenshots ==

1. Admission form with adjustable header.
2. Settings page with live header preview.
3. Admin entries list with CSV export.

== Changelog ==

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

= 1.0.0 =
First public release.

== Credits ==

Developed by Biswajit – https://biswazit.in
