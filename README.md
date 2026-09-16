# AEO Kit — Answer Engine Optimization for WordPress

> Make your WordPress site **quotable by AI answer engines** (ChatGPT, Claude, Perplexity, Google AI Overviews, Gemini). On-site `llms.txt` + rich JSON-LD schema + citation-ready summaries. **100% local — no API, no cloud, no per-use cost.**

[![License: GPL v2+](https://img.shields.io/badge/License-GPLv2%2B-blue.svg)](LICENSE)
![WordPress](https://img.shields.io/badge/WordPress-5.8%2B-21759B?logo=wordpress&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-777BB4?logo=php&logoColor=white)
![No API cost](https://img.shields.io/badge/API_cost-%240-2EA043)

---

## Status

Last reviewed: September 2026 · release v2026.09

## Why

AI answer engines now decide who gets cited. Most "AI SEO" plugins solve this by sending your content to **their** paid cloud — recurring cost, privacy exposure, vendor lock-in.

**AEO Kit does the opposite.** Every feature runs as plain PHP on your own server. No API key. No metered backend. Disable it and your content is untouched.

## Features

| Feature | What it does |
|---|---|
| **`/llms.txt` generator** | Plain-text map of your best content following the [llmstxt.org](https://llmstxt.org) convention, cached + auto-refreshed on edit. |
| **JSON-LD schema** | Organization / LocalBusiness / Person · WebSite (+ sitelinks search) · Article / BlogPosting · BreadcrumbList · FAQPage — one clean `@graph`. |
| **Citation summaries** | One quotable sentence per page → feeds `llms.txt` **and** the page description. |
| **FAQ blocks** | Per-post Q/A pairs → accessible `[aeo_faq]` accordion + valid FAQPage schema. |
| **Zero-config defaults** | Pulls identity from site title, tagline, and site icon out of the box. |

## Install

1. Copy the `aeo-kit` folder into `wp-content/plugins/` (or zip it and upload via **Plugins → Add New → Upload**).
2. Activate.
3. **Settings → AEO Kit** — set identity, toggle schema types.
4. Visit `https://yoursite.com/llms.txt` to confirm.
5. On any post: add a citation summary + FAQ pairs, drop `[aeo_faq]` in the content.

> Seeing a 404 on `/llms.txt`? Go to **Settings → Permalinks** and hit Save once to flush rewrite rules.

## Verify your schema

- [Google Rich Results Test](https://search.google.com/test/rich-results)
- [Schema.org Validator](https://validator.schema.org/)

## Structure

```
aeo-kit/
├── aeo-kit.php                 # Bootstrap + activation hooks
├── uninstall.php               # Clean removal
├── readme.txt                  # wp.org readme
├── includes/
│   ├── class-aeo-kit.php       # Core loader (singleton)
│   ├── class-aeo-settings.php  # Settings → AEO Kit
│   ├── class-aeo-schema.php    # JSON-LD @graph output
│   ├── class-aeo-llms-txt.php  # /llms.txt route + cache
│   ├── class-aeo-faq.php       # FAQPage schema + [aeo_faq]
│   └── class-aeo-meta-box.php  # Per-post summary + FAQ editor
└── assets/
    ├── admin.css / admin.js    # Settings + meta-box UI
    └── public.css              # Front-end FAQ accordion
```

## Hooks for developers

```php
// Modify the schema graph before output.
add_filter( 'aeo_kit_schema_graph', function ( $graph ) {
	return $graph;
} );

// Modify the generated llms.txt body.
add_filter( 'aeo_kit_llms_txt', function ( $body, $lines ) {
	return $body;
}, 10, 2 );
```

## License

GPL-2.0-or-later. Built by [SkynetLabs](https://www.skynetjoe.com) — AI automation agency. PRs welcome.
