=== Nexura Redirects – 301 Redirects, 404 Monitor & DB Migration ===
Contributors: prokashsarker2026, nexurasecurity
Tags: 301 redirect, 404 error log, redirection, search replace, url redirect
Requires at least: 5.6
Tested up to: 7.1
Stable tag: 1.1.2
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Fast 301 redirect manager, 404 error monitor & safe serialized search replace for WordPress. Fix broken links and migrate domains without losing SEO.

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

### 🎯 Real-World Problems Nexura Solves for You

* **Trailing Slash Inconsistencies (`/page` vs `/page/`):** Visitors and crawlers often add or omit trailing slashes. Nexura evaluates both formats so your redirects never fail or trigger 404 errors.
* **Preserving Paid Campaign UTM Tracking:** When redirecting landing pages, Nexura can pass all query parameters (`?utm_source=...`) directly to the destination URL, ensuring your Google Analytics and Meta Ads attribution remains accurate.
* **Stopping Infinite Redirect Loops (`ERR_TOO_MANY_REDIRECTS`):** Built-in loop detection checks whether a target points to itself or relative cycles, preventing browser loop errors before they happen.
* **1-Click Migration from Other Redirect Plugins:** Switching from John Godley's Redirection, Rank Math, Yoast SEO, or Simple 301 Redirects? Simply upload your exported CSV file. Nexura auto-detects header columns and regex patterns seamlessly.
* **Zero Database Bloat on Shared Hosting:** Keep your database lean. Nexura includes an automated daily WP-Cron log pruner and configurable retention settings (none, day, week, month) to prevent server slowdowns.
* **Broken Layouts During Domain Migrations:** Basic find-and-replace corrupts PHP serialized strings. Nexura's Serialized Search & Replace tool safely recalculates string lengths so Elementor, Divi, and WooCommerce never break.

### 🔒 Built With Data Safety in Mind

Our Serialized Search & Replace tool is engineered to handle the most complex WordPress database structures. It recursively processes serialized arrays and objects, recalculating string lengths automatically. Your widgets, themes, page builders (Elementor, Divi, Beaver Builder), and WooCommerce product data remain perfectly intact during domain migrations.

### 🌍 Translation Ready

Nexura Redirects ships with full internationalization support and includes translations for 10+ languages: Bengali, German, French, Spanish, Italian, Portuguese (Brazil), Dutch, Russian, Japanese, and Arabic.

== Installation ==

1. Upload the `nexura-redirects` folder to your `/wp-content/plugins/` directory, or install it directly through **Plugins > Add New > Upload Plugin**.
2. Activate **Nexura Redirects** from the WordPress Plugins screen.
3. Navigate to the **Redirects** menu in your WordPress admin sidebar.
4. Start creating 301 redirects, monitor 404 errors, or use the Migration tool to safely search and replace your database.

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
Yes! Nexura Redirects provides universal CSV/JSON import compatibility. You can directly upload exported CSV files from John Godley's Redirection, Rank Math, Yoast SEO Premium, Simple 301 Redirects, or 301 Redirects by WebFactory. The importer automatically detects column headers and regex flags.

= How does Nexura handle trailing slashes (/)? =
Nexura includes smart trailing slash tolerance by default. A rule created for `/example` will match both `/example` and `/example/` (and vice-versa), eliminating broken redirects caused by trailing slash discrepancies.

= Will my marketing campaign UTM tracking parameters be preserved during redirection? =
Yes. In the Options tab under Default Query Matching, select "Pass query parameters to target". Any parameters attached to the request (such as `?utm_source=facebook&utm_campaign=spring`) will automatically be appended to the destination URL.

= How does Nexura protect against infinite redirect loops? =
Nexura compares the normalized relative path and host of the destination against the incoming request before executing the redirect. If the target matches the current requested URL, the redirection is stopped safely, preventing `ERR_TOO_MANY_REDIRECTS` browser errors.

= Does it support regex or wildcard redirects? =
Yes! Nexura Redirects fully supports regular expression (regex) matching with capture groups ($1, $2, etc.) for advanced redirection patterns. You can easily enable regex for any rule with the checkbox.

== Screenshots ==

1. **Redirects Manager** — Create, edit, and manage all your 301/302/307/308 redirects from a clean, familiar WordPress admin interface.
2. **404 Error Monitor** — Automatically log broken URLs with visitor details. One-click redirect creation from any 404 entry.
3. **Groups** — Organize your redirects into logical groups (WordPress, Apache, Nginx) for easy management.
4. **Import & Export** — Backup and restore redirect rules using CSV or JSON. Migrate between environments effortlessly.
5. **Site Settings** — Relocate your entire site, force HTTPS, configure canonical domains, and manage site aliases.
6. **Options Panel** — Configure log retention, IP privacy, auto-redirect behavior, and advanced matching preferences.
7. **Setup Wizard** — A guided setup wizard helps new users configure the plugin in seconds.

== Changelog ==

= 1.1.2 =
* Fix: Prevented MySQL strict mode (NO_ZERO_DATE) errors during table creation on modern MySQL 5.7+ / 8.0+ and MariaDB.
* Fix: Resolved external URL redirect stripping and safely enabled external redirection with allowed_redirect_hosts filter.
* Fix: Implemented full CSV import parsing with header detection and auto-format detection.
* Fix: Added scheduled daily WP-Cron log pruning task for redirect and 404 logs based on retention settings.
* Fix: Added real visitor IP detection behind Cloudflare proxies (HTTP_CF_CONNECTING_IP) and IP validation.
* Fix: Added dedicated Migration (Serialized Search & Replace) tab in admin interface with memory-safe chunking.
* Fix: Implemented single delete and bulk delete with CSRF nonce verification in 404 list table.
* Fix: Added pre-population of source URL when clicking "Add Redirect" from 404 logs.
* Fix: Scoped admin asset enqueuing strictly to plugin pages and post edit screens.
* Fix: Synchronized Setup Wizard option keys with plugin settings.
* Fix: Resolved infinite circular redirect loop on permalink change reversals.
* Tweak: Optimized database pagination queries across all list tables to remove slow subqueries.

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

== Upgrade Notice ==

= 1.1.2 =
Recommended upgrade. Includes crucial database optimizations, modern MySQL strict mode compatibility, Cloudflare visitor IP logging support, and enhanced security hardening.

= 1.1.1 =
Includes important fixes for site relocation settings, auto-redirect monitoring, and SQL performance.

= 1.1.0 =
Major update introducing 10 language translations, UI performance enhancements, and expanded SEO compatibility.
