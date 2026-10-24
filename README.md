# AEO Kit

**A WordPress plugin that makes your site easier for AI answer engines to quote: `/llms.txt`, clean JSON-LD and FAQ blocks, all in plain PHP.**

![License: GPL v2+](https://img.shields.io/badge/License-GPLv2%2B-blue) ![WordPress 5.8+](https://img.shields.io/badge/WordPress-5.8%2B-21759B) ![PHP 7.4+](https://img.shields.io/badge/PHP-7.4%2B-777BB4)

Many "AI SEO" plugins send your content to a paid cloud. This one runs on your own server with no API key and no metered backend.

## What it does

- Serves `/llms.txt` following the llmstxt.org convention, cached and refreshed automatically.
- Outputs a JSON-LD `@graph`: Organization / LocalBusiness / Person, WebSite, Article / BlogPosting, BreadcrumbList and FAQPage.
- Adds a per-post citation summary that feeds both `llms.txt` and the page description.
- Adds per-post FAQ pairs, shown with the `[aeo_faq]` shortcode as an accessible accordion plus FAQPage schema.
- Takes identity defaults from your site title, tagline and site icon.

## Quickstart

1. Copy the `aeo-kit` folder into `wp-content/plugins/` (or zip it and use **Plugins > Add New > Upload**).
2. Activate it, then open **Settings > AEO Kit** and set identity and schema types.
3. Visit `https://yoursite.com/llms.txt`. On a 404, save **Settings > Permalinks** once to flush rewrite rules.
4. On a post, add a citation summary and FAQ pairs, and put `[aeo_faq]` in the content.
5. Check the output with Google's Rich Results Test and the Schema.org validator.

Developers can filter the schema graph before output; see `includes/class-aeo-schema.php`.

Pair it with [aeo-radar](https://github.com/waseemnasir2k26/aeo-radar), which scores how quotable each post is.

## For you if

You run a WordPress site and want structured data and an `llms.txt` without a subscription.

## Status and limits

- Nobody can promise an answer engine will cite you. This removes technical friction; it does not guarantee citations.
- If another SEO plugin also prints schema, test for duplicate graphs.
- GPL-2.0-or-later. Last reviewed October 2026.

## Built by SkynetLabs

Built by [SkynetLabs](https://skynetjoe.com). Questions: info@skynetjoe.com

Need this wired into your business? SkynetLabs builds voice agents, n8n back offices and sites that convert → https://skynetjoe.com
