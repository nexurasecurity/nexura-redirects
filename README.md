# Nexura Redirects — 301 Redirect Manager, 404 Monitor & DB Migration for WordPress

<div align="center">
  <a href="https://wordpress.org/plugins/nexura-redirects/">
    <img src="https://img.shields.io/badge/WordPress.org-DOWNLOAD_FREE-0073aa?logo=wordpress&style=for-the-badge" alt="Download Nexura Redirects on WordPress.org">
  </a>
</div>

<div align="center">
  <img src="https://img.shields.io/badge/REQUIRES_WP-5.6+-0073aa?labelColor=555555&style=flat-square" alt="Requires WordPress 5.6+">
  <img src="https://img.shields.io/badge/REQUIRES_PHP-7.4+-0073aa?labelColor=555555&style=flat-square" alt="Requires PHP 7.4+">
  <img src="https://img.shields.io/badge/TESTED_UP_TO-7.1-73c713?labelColor=555555&style=flat-square" alt="Tested up to WordPress 7.1">
  <img src="https://img.shields.io/badge/STABLE_TAG-1.1.2-73c713?labelColor=555555&style=flat-square" alt="Stable Tag 1.1.2">
  <img src="https://img.shields.io/badge/LICENSE-GPLv2-0073aa?labelColor=555555&style=flat-square" alt="License GPLv2 or later">
</div>
<div align="center">
  <a href="https://github.com/nexurasecurity/nexura-redirects/actions"><img src="https://img.shields.io/github/actions/workflow/status/nexurasecurity/nexura-redirects/ci.yml?label=CI&logo=github&style=flat-square" alt="CI Status"></a>
  <a href="https://github.com/nexurasecurity/nexura-redirects/stargazers"><img src="https://img.shields.io/github/stars/nexurasecurity/nexura-redirects?style=flat-square&logo=github" alt="GitHub Stars"></a>
  <a href="https://github.com/nexurasecurity/nexura-redirects/network/members"><img src="https://img.shields.io/github/forks/nexurasecurity/nexura-redirects?style=flat-square&logo=github" alt="GitHub Forks"></a>
  <a href="https://github.com/nexurasecurity/nexura-redirects/issues"><img src="https://img.shields.io/github/issues/nexurasecurity/nexura-redirects?style=flat-square&color=73c713" alt="GitHub Issues"></a>
</div>

---

> **The all-in-one WordPress redirect plugin** — 301 redirect manager, 404 error monitor, and serialization-safe database migration tool. Fix broken links, recover lost SEO traffic, and safely migrate domains without corrupting your database.

---

## 📋 Table of Contents

- [Overview](#overview)
- [Key Features](#key-features)
- [Why Nexura Redirects?](#why-nexura-redirects)
- [Real-World Problems Solved](#real-world-problems-solved)
- [Installation](#installation)
- [Frequently Asked Questions](#frequently-asked-questions)
- [Screenshots](#screenshots)
- [Changelog](#changelog)
- [License](#license)

---

## Overview

**Nexura Redirects** is a lightweight, ultra-fast, all-in-one **301 redirect manager**, **404 error monitor**, and **database migration** plugin built for WordPress. Whether you are migrating to a new domain, changing permalink structures, or recovering broken links — Nexura Redirects keeps your SEO rankings safe and your visitors happy.

Stop losing traffic to broken links and 404 errors. Nexura Redirects automates the entire process with an intelligent **404 error logger**, automatic **301 redirections** on URL changes, and a serialization-safe **search and replace engine** that will never corrupt your database.

Built for speed. Zero bloat. No third-party dependencies. Runs 100% natively inside your WordPress dashboard.

---

## Key Features

### 🔀 301 Redirect Manager
Create, edit, and manage **301, 302, 307, and 308 redirects** with a powerful admin interface. Supports bulk actions, inline editing, CSV/JSON import & export, and redirect groups for easy organization. Track hits and last-accessed timestamps for every redirect rule.

### 🚨 404 Error Monitor & Logger
Automatically detect and log every **404 "Page Not Found" error** on your site. View the broken URL, visitor IP, user agent, and referrer. Quickly create a redirect from any 404 entry with one click — no traffic left behind.

### ⚡ Auto-Redirect on URL Changes
Whenever a published post, page, or custom post type URL changes, Nexura Redirects **automatically creates a 301 redirect** from the old URL to the new one. No manual work required. Fully configurable from the Options panel.

### 🗄️ Serialized Search & Replace (Database Migration)
Safely migrate your WordPress database from one domain to another. Our advanced engine correctly handles **PHP serialized data** — recalculating string lengths so your Elementor layouts, theme options, widgets, and WooCommerce settings remain perfectly intact.

### 🌐 Site Relocate & Canonical URLs
Redirect your entire site to a new domain with one setting. Force **HTTPS**, add or remove **www**, and configure **site aliases** so traffic from old domains is automatically redirected to your primary domain.

### 📤 Import & Export
Backup and restore your redirect rules using **CSV or JSON** format. Easily migrate redirect configurations between staging, development, and production environments.

### 🔒 Privacy & GDPR Compliant Logging
Full control over what gets logged. Choose between **full IP**, **anonymized IP**, or **no IP logging**. Set independent retention policies for redirect logs and 404 logs. Delete all plugin data with a single click.

---

## Why Nexura Redirects?

| Feature | Nexura Redirects | Basic Redirect Plugins |
|---|---|---|
| Serialization-safe DB migration | ✅ | ❌ |
| 404 monitor with 1-click redirect | ✅ | ❌ |
| Auto-redirect on permalink change | ✅ | ❌ |
| GDPR IP anonymization | ✅ | ❌ |
| Regex & wildcard support | ✅ | ⚠️ Partial |
| CSV/JSON import from competitors | ✅ | ❌ |
| Infinite redirect loop protection | ✅ | ❌ |
| UTM parameter passthrough | ✅ | ❌ |
| Zero external dependencies | ✅ | ✅ |

- **Ultra-Lightweight** — No external API calls, no JavaScript frameworks, no bloat. Pure PHP + native WordPress APIs.
- **Serialization-Safe Migration** — Unlike basic find-and-replace tools, safely handles serialized arrays so Elementor, WPBakery, and theme customizer data never breaks.
- **Complete Privacy Control** — GDPR-friendly IP anonymization, configurable log retention, and a "Delete All Data" button.
- **One-Click 404 Recovery** — See a broken URL? Click once to create a redirect. Done.
- **Developer Friendly** — Clean, well-documented codebase. Passes WordPress PHPCS standards. Hooks and filters for extensibility.
- **10+ Languages** — Bengali, German, French, Spanish, Italian, Portuguese (Brazil), Dutch, Russian, Japanese, and Arabic.

---

## Real-World Problems Solved

| Problem | How Nexura Solves It |
|---|---|
| Trailing slash mismatches (`/page` vs `/page/`) | Smart trailing slash tolerance — rules match both formats automatically |
| UTM tracking lost during redirect | Pass all query parameters (`?utm_source=...`) to the destination URL |
| Infinite redirect loops (`ERR_TOO_MANY_REDIRECTS`) | Built-in loop detection stops circular redirects before they happen |
| Migrating from Redirection / Rank Math / Yoast | 1-click CSV import with auto header & regex detection |
| Database bloat on shared hosting | Daily WP-Cron log pruner with configurable retention (day/week/month) |
| Broken Elementor/Divi layouts after domain move | Serialized Search & Replace safely recalculates PHP string lengths |

---

## Installation

1. Upload the `nexura-redirects` folder to your `/wp-content/plugins/` directory, **or** install it directly via **Plugins › Add New › Upload Plugin**.
2. Activate **Nexura Redirects** from the WordPress Plugins screen.
3. Navigate to the **Redirects** menu in your WordPress admin sidebar.
4. Start creating 301 redirects, monitor 404 errors, or use the Migration tool to safely search and replace your database.

---

## Frequently Asked Questions

<details>
<summary><strong>Does this plugin slow down my website?</strong></summary>

No. Nexura Redirects uses efficient indexed database queries and WordPress object caching to ensure redirect lookups happen in under a millisecond. There is zero impact on your frontend page load speed.
</details>

<details>
<summary><strong>Will the Search and Replace tool break my Elementor pages?</strong></summary>

Absolutely not. The advanced Search and Replace engine safely handles PHP serialized data by recursively unserializing, replacing, and re-serializing with correct string length calculations. Your Elementor layouts, theme settings, widgets, and WooCommerce data remain perfectly intact.
</details>

<details>
<summary><strong>Can I redirect old URLs to external domains?</strong></summary>

Yes. You can set up redirects to point to any internal page or to any external URL on a different domain.
</details>

<details>
<summary><strong>Does it automatically track 404 errors?</strong></summary>

Yes. The 404 Error Monitor logs every request that results in a "Not Found" error, including the visitor IP, user agent, and referrer. You can review the log and create redirects directly from the 404 list with one click.
</details>

<details>
<summary><strong>Is Nexura Redirects GDPR compliant?</strong></summary>

Yes. Full control over IP logging — choose between full IP, anonymized IP (using WordPress's native `wp_privacy_anonymize_ip()`), or no IP logging at all. You can delete all stored plugin data with a single button click.
</details>

<details>
<summary><strong>Can I import redirects from another plugin?</strong></summary>

Yes! Nexura Redirects supports universal CSV/JSON import from **John Godley's Redirection**, **Rank Math**, **Yoast SEO Premium**, **Simple 301 Redirects**, and **301 Redirects by WebFactory**. The importer automatically detects column headers and regex flags.
</details>

<details>
<summary><strong>What happens if I uninstall the plugin?</strong></summary>

Uninstalling via the WordPress dashboard will safely drop all custom database tables (redirects, groups, redirect logs, 404 logs) and delete all plugin options, leaving zero orphaned data behind.
</details>

<details>
<summary><strong>Does it support regex or wildcard redirects?</strong></summary>

Yes! Nexura Redirects fully supports regular expression (regex) matching with capture groups (`$1`, `$2`, etc.) for advanced redirection patterns. Enable regex for any rule with the checkbox.
</details>

<details>
<summary><strong>How does Nexura handle trailing slashes?</strong></summary>

A rule created for `/example` will match both `/example` and `/example/` (and vice-versa), eliminating broken redirects caused by trailing slash discrepancies.
</details>

<details>
<summary><strong>Will my UTM tracking parameters be preserved during redirection?</strong></summary>

Yes. In the Options tab under **Default Query Matching**, select "Pass query parameters to target". Any parameters such as `?utm_source=facebook&utm_campaign=spring` will automatically be appended to the destination URL.
</details>

---

## Screenshots

| # | Screen | Description |
|---|---|---|
| 1 | Redirects Manager | Create, edit, and manage all your 301/302/307/308 redirects from a clean admin interface |
| 2 | 404 Error Monitor | Automatically log broken URLs with visitor details. One-click redirect creation from any 404 entry |
| 3 | Groups | Organize your redirects into logical groups (WordPress, Apache, Nginx) for easy management |
| 4 | Import & Export | Backup and restore redirect rules using CSV or JSON. Migrate between environments effortlessly |
| 5 | Site Settings | Relocate your entire site, force HTTPS, configure canonical domains, and manage site aliases |
| 6 | Options Panel | Configure log retention, IP privacy, auto-redirect behavior, and advanced matching preferences |
| 7 | Setup Wizard | A guided setup wizard helps new users configure the plugin in seconds |

---

## Changelog

### 1.1.2
- **Fix:** Prevented MySQL strict mode (`NO_ZERO_DATE`) errors during table creation on MySQL 5.7+ / 8.0+ and MariaDB.
- **Fix:** Resolved external URL redirect stripping and safely enabled external redirection with `allowed_redirect_hosts` filter.
- **Fix:** Implemented full CSV import parsing with header detection and auto-format detection.
- **Fix:** Added scheduled daily WP-Cron log pruning task for redirect and 404 logs based on retention settings.
- **Fix:** Added real visitor IP detection behind Cloudflare proxies (`HTTP_CF_CONNECTING_IP`) and IP validation.
- **Fix:** Added dedicated Migration (Serialized Search & Replace) tab in admin interface with memory-safe chunking.
- **Fix:** Implemented single delete and bulk delete with CSRF nonce verification in 404 list table.
- **Fix:** Added pre-population of source URL when clicking "Add Redirect" from 404 logs.
- **Fix:** Scoped admin asset enqueuing strictly to plugin pages and post edit screens.
- **Fix:** Synchronized Setup Wizard option keys with plugin settings.
- **Fix:** Resolved infinite circular redirect loop on permalink change reversals.
- **Tweak:** Optimized database pagination queries across all list tables to remove slow subqueries.

### 1.1.1
- **Fix:** Fully implemented backend logic for the Site tab (Relocate, Canonical Domains, HTTPS, Aliases).
- **Fix:** Auto Redirects now correctly respect the "Monitor URL changes" setting.
- **Fix:** Logger engine now strictly follows privacy settings for Redirect Logs, 404 Logs, and IP Logging.
- **Fix:** Missing form tags and button types in Import/Export tab preventing imports.
- **Fix:** "Delete plugin data" button now correctly deletes plugin data from the database.
- **Fix:** Uninstall routine now safely drops all orphaned groups and options data.
- **Tweak:** Resolved all PHPCS warnings and improved SQL query safety across the codebase.

### 1.1.0
- **Enhancement:** Massive SEO improvements in plugin description and tags.
- **Feature:** Added full translation support with POT file and 10 default languages (bn_BD, de_DE, fr_FR, es_ES, it_IT, pt_BR, nl_NL, ru_RU, ja, ar).
- **Tweak:** Refactored and improved UI text for a better user experience.

### 1.0.9
- Initial public release with Core Redirects, 404 Monitor, and Serialized Search & Replace.

---

## Upgrade Notice

**1.1.2** — Recommended upgrade. Includes crucial database optimizations, modern MySQL strict mode compatibility, Cloudflare visitor IP logging support, and enhanced security hardening.

---

## License

This plugin is licensed under the **GPL v2 or later**.
See the [LICENSE](LICENSE) file for full details, or visit [https://www.gnu.org/licenses/gpl-2.0.html](https://www.gnu.org/licenses/gpl-2.0.html).

---

<div align="center">
  Made with ❤️ by <a href="https://github.com/nexurasecurity">Nexura Security</a> &nbsp;·&nbsp;
  <a href="https://wordpress.org/plugins/nexura-redirects/">WordPress.org</a> &nbsp;·&nbsp;
  <a href="https://github.com/nexurasecurity/nexura-redirects/issues">Report a Bug</a>
</div>
