=== Nexura Redirects – 301 Redirects, 404 Monitor & DB Migration ===
Contributors: prokashsarker2026, nexurasecurity
Tags: 301 redirect, 404 error log, redirection, search replace, url redirect
Requires at least: 5.6
Tested up to: 7.0.2
Stable tag: 1.1.1
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

The fastest 301 redirect manager, 404 error monitor, and serialized search & replace tool for WordPress. Migrate domains safely without breaking SEO.

== Description ==

### The Most Powerful WordPress Redirect & Migration Plugin

**Nexura Redirects** is a lightweight, all-in-one **301 redirect manager**, **404 error monitor**, and **database migration** plugin built for WordPress. Whether you are migrating to a new domain, changing permalink structures, or recovering broken links — Nexura Redirects keeps your SEO rankings safe and your visitors happy.

Stop losing traffic to broken links and 404 errors. Nexura Redirects automates the entire process with an intelligent **404 error logger**, automatic **301 redirections** on URL changes, and a serialization-safe **search and replace engine** that won't corrupt your database.

Built for speed. Zero bloat. No third-party dependencies. Runs 100% natively inside your WordPress dashboard.

### 🚀 Key Features

= 301 Redirect Manager =
Create, edit, and manage **301, 302, 307, and 308 redirects** with a powerful admin interface. Supports bulk actions, inline editing, CSV/JSON import & export, and redirect groups for easy organization. Track hits and last-accessed timestamps for every redirect rule.

= 404 Error Monitor & Logger =
Automatically detect and log every **404 "Page Not Found" error** on your site. View the broken URL, visitor IP, user agent, and referrer. Quickly create a redirect from any 404 entry with one click — no traffic left behind.

= Auto-Redirect on URL Changes =
Whenever a published post, page, or custom post type URL changes, Nexura Redirects **automatically creates a 301 redirect** from the old URL to the new one. No manual work required. Fully configurable from the Options panel.

= Serialized Search & Replace =
Safely migrate your WordPress database from one domain to another. Our advanced engine correctly handles **PHP serialized data** — recalculating string lengths so your Elementor layouts, theme options, widgets, and WooCommerce settings remain intact.

= Site Relocate & Canonical URLs =
Redirect your entire site to a new domain with one setting. Force **HTTPS**, add or remove **www**, and configure **site aliases** so traffic from old domains is automatically redirected to your primary domain.

= Import & Export =
Backup and restore your redirect rules using **CSV or JSON** format. Easily migrate redirect configurations between staging, development, and production environments.

= Privacy & GDPR Compliant Logging =
Full control over what gets logged. Choose between **full IP**, **anonymized IP**, or **no IP logging**. Set independent retention policies for redirect logs and 404 logs. Delete all plugin data with a single click.

### 💡 Why Choose Nexura Redirects Over Other Plugins?

* **Ultra-Lightweight** — No external API calls, no JavaScript frameworks, no bloat. Pure PHP + native WordPress APIs.
* **Serialization-Safe Migration** — Unlike basic find-and-replace tools, we safely handle serialized arrays so Elementor, WPBakery, and theme customizer data never breaks.
* **Complete Privacy Control** — GDPR-friendly IP anonymization, configurable log retention, and a "Delete All Data" button.
* **One-Click 404 Recovery** — See a broken URL? Click once to create a redirect. Done.
* **Developer Friendly** — Clean, well-documented codebase. Passes WordPress PHPCS standards. Hooks and filters for extensibility.

### 🔒 Built With Data Safety in Mind

Our Serialized Search & Replace tool is engineered to handle the most complex WordPress database structures. It recursively processes serialized arrays and objects, recalculating string lengths automatically. Your widgets, themes, page builders (Elementor, Divi, Beaver Builder), and WooCommerce product data remain perfectly intact during domain migrations.

### 🌍 Translation Ready

Nexura Redirects ships with full internationalization support and includes translations for 10+ languages: Bengali, German, French, Spanish, Italian, Portuguese (Brazil), Dutch, Russian, Japanese, and Arabic.

== Installation ==

1. Upload the `nexura-redirects` folder to your `/wp-content/plugins/` directory, or install it directly through **Plugins > Add New > Upload Plugin**.
2. Activate **Nexura Redirects** from the WordPress Plugins screen.
3. Navigate to **Tools > Nexura Redirects** in your admin menu.
4. Start creating 301 redirects, monitor 404 errors, or use the Search & Replace tool to migrate your database.

== Frequently Asked Questions ==

= Does this plugin slow down my website? =
No. Nexura Redirects is meticulously coded for performance. It uses efficient indexed database queries and WordPress object caching to ensure redirect lookups happen in under a millisecond. There is zero impact on your frontend page load speed.

= Will the Search and Replace tool break my Elementor pages? =
Absolutely not. Our advanced Search and Replace engine safely handles PHP serialized data by recursively unserializing, replacing, and re-serializing with correct string length calculations. Your Elementor layouts, theme settings, widgets, and WooCommerce data remain perfectly intact.

= Can I redirect old URLs to external domains? =
Yes. You can set up redirects to point to any internal page on your site, or to any external URL on a different domain.

= Does it automatically track 404 errors? =
Yes. The 404 Error Monitor logs every request that results in a "Not Found" error, including the visitor IP, user agent, and referrer. You can review this log and create redirects directly from the 404 list with one click.

= Can I disable the 404 Monitor to save database space? =
Yes. You can set the 404 log retention to "No logs" in the Options tab. You can also independently control redirect log retention and IP logging preferences.

= Is Nexura Redirects GDPR compliant? =
Yes. You have full control over IP logging — choose between full IP, anonymized IP (using WordPress's native `wp_privacy_anonymize_ip()`), or no IP logging at all. You can also delete all stored plugin data with a single button click.

= What happens if I uninstall the plugin? =
Uninstalling the plugin via the WordPress dashboard will safely drop all custom database tables (redirects, groups, redirect logs, 404 logs) and delete all plugin options from the database, leaving zero orphaned data behind.

= Can I import redirects from another plugin? =
Yes. You can import redirects using CSV or JSON format. Export your rules from your current redirect plugin, format them as CSV with source and target URL columns, and import them into Nexura Redirects.

= Does it support regex or wildcard redirects? =
The architecture is built to support regex and wildcard matching in a future update. Currently, exact URL matching and path-only matching are fully supported.

== Screenshots ==

1. **Redirects Manager** — Create, edit, and manage all your 301/302/307/308 redirects from a clean, familiar WordPress admin interface.
2. **404 Error Monitor** — Automatically log broken URLs with visitor details. One-click redirect creation from any 404 entry.
3. **Groups** — Organize your redirects into logical groups (WordPress, Apache, Nginx) for easy management.
4. **Import & Export** — Backup and restore redirect rules using CSV or JSON. Migrate between environments effortlessly.
5. **Site Settings** — Relocate your entire site, force HTTPS, configure canonical domains, and manage site aliases.
6. **Options Panel** — Configure log retention, IP privacy, auto-redirect behavior, and advanced matching preferences.
7. **Setup Wizard** — A guided setup wizard helps new users configure the plugin in seconds.

== Changelog ==

= 1.1.1 =
* Fix: Fully implemented backend logic for the Site tab (Relocate, Canonical Domains, HTTPS, Aliases).
* Fix: Auto Redirects now correctly respect the "Monitor URL changes" setting.
* Fix: Logger engine now strictly follows privacy settings for Redirect Logs, 404 Logs, and IP Logging.
* Fix: Missing form tags and button types in Import/Export tab preventing imports.
* Fix: "Delete plugin data" button now correctly deletes plugin data from the database.
* Fix: Uninstall routine now safely drops all orphaned groups and options data.
* Tweak: Resolved all PHPCS warnings and improved SQL query safety across the codebase.

= 1.1.0 =
* Enhancement: Massive SEO improvements in plugin description and tags.
* Feature: Added full translation support with POT file and 10 default languages (bn_BD, de_DE, fr_FR, es_ES, it_IT, pt_BR, nl_NL, ru_RU, ja, ar).
* Tweak: Refactored and improved UI text for a better user experience.

= 1.0.9 =
* Initial public release with Core Redirects, 404 Monitor, and Serialized Search & Replace.
