# Changelog

All notable changes to this project are documented in this file.

## [2026.09] - 2026-09-16

- Maintenance review of aeo-kit — a public GPLv2+ WordPress plugin ("AEO Kit", v1.0.0) that adds Answer Engine Optimization features to a WordPress site with no external API.
- Status: plain-PHP plugin (WordPress 5.8+, PHP 7.4+) built from `aeo-kit.php` plus six `includes/` classes covering an on-site `/llms.txt` generator, JSON-LD `@graph` schema (Organization/LocalBusiness/Person, WebSite, Article/BlogPosting, BreadcrumbList, FAQPage), per-post citation summaries, an `[aeo_faq]` shortcode, a settings screen and a meta box; ships admin/public CSS+JS and an `uninstall.php`.
- Reviewed September 2026: README Status section added and a CHANGELOG created; the repo is versioned as v2026.09. No PHP, asset or plugin-header changes were made — the plugin's own `Version: 1.0.0` header and `readme.txt` were deliberately left untouched so the WordPress-side version is not misreported.
- Known gaps: no CHANGELOG before this release; no automated tests, PHPCS/WordPress-coding-standards config or CI; not listed on the WordPress.org plugin directory; last functional commit was May 2026.
