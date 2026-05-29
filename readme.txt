=== AEO Kit — Answer Engine Optimization (llms.txt + Schema) ===
Contributors: skynetlabs
Tags: aeo, llms.txt, schema, json-ld, faq
Requires at least: 5.8
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Make your site quotable by AI answer engines. On-site llms.txt + JSON-LD schema + citation summaries. 100% local — no API, no cloud, no per-use cost.

== Description ==

AI answer engines (ChatGPT, Claude, Perplexity, Google AI Overviews, Gemini) increasingly decide who gets cited. **AEO Kit** makes your WordPress content easy for them to read, understand, and quote — without sending a single byte to an external service.

Everything runs on your own server. There is **no API key, no cloud backend, and no per-request cost.** Most "AI SEO" plugins phone home to a paid service; AEO Kit does the opposite.

= What it does =

* **Generates an on-site `/llms.txt`** — a clean, plain-text map of your best content following the [llmstxt.org](https://llmstxt.org) convention, so LLMs can find and summarize your pages.
* **Outputs rich JSON-LD schema** — Organization / LocalBusiness / Person, WebSite (with sitelinks search box), Article / BlogPosting, BreadcrumbList, and FAQPage — combined into one clean `@graph`.
* **Citation summaries** — write one crisp, quotable sentence per page that feeds both `/llms.txt` and the page description.
* **FAQ blocks** — add question/answer pairs per post; AEO Kit renders an accessible accordion via the `[aeo_faq]` shortcode and emits valid FAQPage schema.
* **Zero config to start** — sensible defaults pull from your site title, tagline, and site icon.

= Why local-only matters =

* No recurring cost — it never calls a metered API.
* No privacy exposure — your content is never sent to a third party.
* No vendor lock-in — disable it and your content is untouched.

== Installation ==

1. Upload the `aeo-kit` folder to `/wp-content/plugins/`, or install through the Plugins screen.
2. Activate the plugin.
3. Go to **Settings → AEO Kit** to set your identity and toggle schema types.
4. Visit `https://yoursite.com/llms.txt` to confirm it is live.
5. Edit any post to add a citation summary and FAQ pairs, then drop `[aeo_faq]` into the content.

== Frequently Asked Questions ==

= Does this send my data anywhere or cost money to run? =

No. AEO Kit is 100% local PHP. It makes no external API calls and has no paid tier requirement to function.

= Will it conflict with Yoast or Rank Math schema? =

AEO Kit emits its own `@graph`. If your SEO plugin already outputs Organization/Article schema, disable the overlapping toggles in Settings → AEO Kit to avoid duplicates. The FAQ and llms.txt features are unique and safe to leave on.

= Where is llms.txt served from? =

A rewrite rule maps `/llms.txt` to a dynamically generated, cached text response. If you see a 404, go to Settings → Permalinks and click Save to flush rewrite rules.

= Can I control which content appears in llms.txt? =

Yes — choose which post types to include in Settings → AEO Kit, and write a per-post citation summary on each edit screen.

== Changelog ==

= 1.0.0 =
* Initial release: /llms.txt generator, JSON-LD schema (Organization, WebSite, Article, Breadcrumb, FAQPage), per-post citation summary, FAQ meta box + [aeo_faq] shortcode.

== Upgrade Notice ==

= 1.0.0 =
First release.
