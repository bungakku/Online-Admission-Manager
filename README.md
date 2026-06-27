# Online Admission Manager

A WordPress plugin that adds a complete online admission/enquiry form to any page via a shortcode — built for schools, colleges, and coaching institutes.

[![Release](https://img.shields.io/badge/release-v1.0.0-blue.svg)](https://github.com/biswazit/admission-manager/releases)
[![License: GPL v2+](https://img.shields.io/badge/license-GPLv2%2B-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)

## Features

- One shortcode: `[admission_form]`
- Personal info, parents' details, addresses, repeatable academic records, and document uploads
- **Adjustable header** — logo (with width control), institute title, and an optional tagline, with font size and alignment controls, configurable from **Admissions → Settings** with a live preview
- The same header is reproduced on the **printed form**
- Native browser printing (no external JS dependency)
- File uploads capped at 300KB, validated by extension, size, *and* actual file content (MIME sniffing)
- Admission window control (start/end date) — the form auto-disables outside the window
- Email confirmation to applicants + admin notification on submission
- Admin panel: browse, view, delete entries; CSV export of all data including academic records
- Optional payment QR code next to the payment-proof upload
- Fully responsive — the form uses the full available width on mobile instead of leaving large side gutters

## Installation

1. Download the latest release zip from the [Releases page](https://github.com/biswazit/admission-manager/releases).
2. In your WordPress admin, go to **Plugins → Add New → Upload Plugin** and upload the zip, or extract it into `/wp-content/plugins/admission-manager/`.
3. Activate the plugin.
4. Go to **Admissions → Settings** to configure your institute name, tagline, logo, admission window, and email settings.
5. Add `[admission_form]` to any page or post.

## Development

This is a single-file plugin plus two static assets:

```
admission-manager/
├── admission-manager.php   # Plugin bootstrap, admin pages, shortcode, form handling
├── assets/
│   ├── style.css            # Frontend + print styles
│   └── script.js             # Frontend behavior (jQuery, no external deps)
├── readme.txt                # WordPress.org-style readme
├── README.md                  # This file
└── CHANGELOG.md
```

No build step is required — everything is plain PHP/CSS/JS, enqueued the standard WordPress way.

### Local linting

```bash
php -l admission-manager.php
```

## Security notes

- All admin actions that change state (settings save, delete entry, CSV export) are nonce-protected and capability-checked (`manage_options`).
- Uploaded files are validated by extension, size (≤300KB), and real MIME type via `wp_check_filetype_and_ext()`.
- The upload directory is created with a `.htaccess` denying direct access and an empty `index.html` to prevent directory listing.
- Data is **not** deleted on plugin removal unless you explicitly opt in via Settings, to avoid accidental data loss.

## Contributing

Issues and pull requests are welcome. Please open an issue first to discuss significant changes.

## License

GPL v2 or later — see [LICENSE](LICENSE).

== Credits ==

Developed by Biswajit – https://biswazit.in
