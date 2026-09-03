# Online Admission Manager

A WordPress plugin that adds a complete online admission/enquiry form to any page via a shortcode — built for schools, colleges, and coaching institutes.

[![Release](https://img.shields.io/badge/release-v1.1.2-blue.svg)](https://github.com/bungakku/Online-Admission-Manager/releases)
[![License: GPL v2+](https://img.shields.io/badge/license-GPLv2%2B-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)

## Features

- One shortcode: `[admission_form]`
- Personal info, parents' details, addresses, repeatable academic records, and document uploads
- **Independently adjustable header** — logo, institute title, and an optional tagline each have their own size and alignment (left/center/right), configurable from **Admissions → Settings** with a live preview
- **Print preview** — "Print Form" opens a new tab with a clean, read-only snapshot of everything entered so far, including a live preview of the uploaded passport photo next to the applicant's name, ready to print or save as PDF
- **Desktop field grouping** — related short fields (contact numbers, nationality/Aadhar/country, sex/blood group, etc.) line up inline on desktop and stack automatically on mobile
- File uploads capped at 300KB, validated by extension, size, *and* actual file content (MIME sniffing)
- **Aadhar encryption at rest** (libsodium, OpenSSL AES-256-GCM fallback), masked admin display, audit-logged "Reveal" action
- Honeypot + basic per-IP rate limiting against bot submissions
- Admission window control (start/end date) — the form auto-disables outside the window
- Email confirmation to applicants + admin notification on submission
- Admin panel: browse, view, delete entries; CSV export of all data including academic records
- Optional payment QR code next to the payment-proof upload
- Fully responsive — the form uses the full available width on mobile instead of leaving large side gutters
- **Self-updating** — checks GitHub Releases and surfaces updates on the normal WordPress Plugins page

## Installation

1. Download the latest release zip from the [Releases page](https://github.com/bungakku/Online-Admission-Manager/releases).
2. In your WordPress admin, go to **Plugins → Add New → Upload Plugin** and upload the zip, or extract it into `/wp-content/plugins/admission-manager/`.
3. Activate the plugin.
4. Go to **Admissions → Settings** to configure your institute name, tagline, logo, admission window, and email settings.
5. Add `[admission_form]` to any page or post.

Once installed, future updates published as GitHub Releases will show up automatically on the Plugins page — no need to manually re-upload.

## Development

```
admission-manager/
├── admission-manager.php   # Plugin bootstrap, admin pages, shortcode, form handling, updater
├── assets/
│   ├── style.css            # Frontend styles (on-screen form)
│   ├── print.css             # Standalone styles for the print-preview tab
│   └── script.js              # Frontend behavior (jQuery + vanilla JS, no external deps)
├── readme.txt                 # WordPress.org-style readme
├── README.md                   # This file
└── CHANGELOG.md
```

No build step is required — everything is plain PHP/CSS/JS, enqueued the standard WordPress way.

### Local linting

```bash
php -l admission-manager.php
```

### Releasing an update

1. Bump `Version:` and `ADM_MGR_VERSION` (and `ADM_MGR_DB_VERSION` if the DB schema changed) in `admission-manager.php`, and update `readme.txt` / `CHANGELOG.md`.
2. Tag and push, then create a GitHub Release from that tag (the release notes become what WordPress shows under "View version details").
3. Existing installs will see the update within 12 hours, or immediately via Settings → Check for Updates.

## Security notes

- All admin actions that change state (settings save, delete entry, CSV export, reveal Aadhar, manual update check) are nonce-protected and capability-checked.
- Uploaded files are validated by extension, size (≤300KB), and real MIME type via `wp_check_filetype_and_ext()`.
- The upload directory blocks directory listing and execution of script-like file extensions; uploaded files themselves remain directly viewable, since the admin panel links to them directly.
- Aadhar numbers are encrypted at rest and only ever decrypted on an explicit, logged admin action.
- A honeypot field and per-IP rate limit reduce automated/bot submissions.
- Data is **not** deleted on plugin removal unless you explicitly opt in via Settings, to avoid accidental data loss.

## Contributing

Issues and pull requests are welcome. Please open an issue first to discuss significant changes.

## License

GPL v2 or later — see [LICENSE](LICENSE).

== Credits ==

Developed by Biswajit – https://github.com/bungakku/Online-Admission-Manager
