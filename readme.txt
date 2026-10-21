=== Better Schema Markup ===
Tags: schema, json-ld, seo, structured data, wpgraphql
Requires at least: 6.6
Tested up to: 7.1
Requires PHP: 8.3
Stable tag: 0.19.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Schema.org JSON-LD for better SEO and rich results. Replaces the schema output of Yoast SEO and Rank Math, with WPGraphQL support.

== Description ==

Better Schema Markup adds schema.org structured data (JSON-LD) to your site so search engines can show rich results.

Get a connected schema graph immediately after activation. Refine it in Settings → Better Schema, or choose a schema type and review the JSON-LD in the block editor.

* Connected WebSite, Organization or Person, WebPage, Article, BlogPosting and breadcrumb entities.
* Event, Recipe, FAQPage, LocalBusiness and video schema, plus WooCommerce product data.
* Replaces only Yoast SEO and Rank Math schema, preserving their other SEO features.
* One shared PHP graph builder for WordPress pages, editor previews and WPGraphQL.
* A frontend URL setting for headless sites, and validated custom JSON-LD for advanced use.
* Translation-ready PHP and JavaScript interfaces.

Developed by [Rick Centerhall](https://rickcenterhall.com/). Schema does not guarantee eligibility for a rich result or improved rankings.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/`.
2. Activate the plugin through the Plugins screen in WordPress.

== Frequently Asked Questions ==

= Will it work without an SEO plugin? =

Yes. The plugin generates useful schema from WordPress data without additional plugins or configuration.

= What happens to Yoast SEO or Rank Math? =

With schema takeover enabled (the default), Better Schema Markup suppresses their JSON-LD only. Titles, descriptions, canonical links, social metadata and sitemaps remain with your SEO plugin. Turn takeover off in Settings → Better Schema to restore its schema and pause Better Schema frontend output. Editor previews and WPGraphQL continue to work. Other plugins and themes may output their own structured data; this plugin does not suppress those.

= Can I import existing schema choices? =

Use Preview import, then Import settings under Settings → Better Schema. Select Yoast or Rank Math explicitly. The importer reads saved post metadata, even if that source plugin has been deactivated. Existing Better Schema settings and source data are preserved. Repeated imports skip existing choices.

Supported explicit schema types and compatible Event, Recipe and FAQ fields are transferred. Source overrides for WordPress-derived properties, unsupported types, unresolved templates and additional entities are reported for manual review. Yoast's "None" article type becomes WebPage, retaining site and page schema. Inherited source defaults, schema generated from source blocks, premium templates and legacy Rank Math detail fields are not migrated. WooCommerce products continue to use WooCommerce data. Review the report and resulting schema before switching a production site.

WP-CLI: `wp better-schema import --source=yoast --dry-run` or `wp better-schema import --source=rank-math`. Omit `--dry-run` to save. The browser interface uses batches of at most 100 posts; an interrupted import can safely be retried.

= Does the plugin send data to external services? =

Vimeo metadata requests are off by default, including after upgrading. To enable them, turn on "Allow Vimeo metadata requests" in Settings → Better Schema and save. Turning the setting off stops requests and omits generated Vimeo schema, including cached metadata. With permission, for Vimeo links or embeds already in post content, the server requests https://vimeo.com/api/oembed.json with the public video URL to obtain its title and thumbnail. Successful results are cached for a week; failures for an hour. No account credentials or full post content are sent. Vimeo receives the public video URL, your server IP address, and standard WordPress HTTP request headers. See [Vimeo terms](https://vimeo.com/terms) and [privacy policy](https://vimeo.com/privacy).

YouTube embed and thumbnail URLs are derived locally without an API request. Video schema contains public YouTube or Vimeo URLs. The optional Test with Google link opens Google's Rich Results Test with the published page URL only when clicked. See [Google terms](https://policies.google.com/terms) and [privacy policy](https://policies.google.com/privacy). The plugin does not add analytics or tracking.

= What happens when I delete the plugin? =

Deactivation keeps your settings. Deletion removes all Better Schema settings, per-post schema metadata and database-backed video caches, including across multisite. Your content, media, and Yoast or Rank Math settings remain intact.

= Where is the source code? =

Original TypeScript sources and build configuration are included in the package. Development, issue tracking and the headless integration guide are at https://github.com/RickOliwer/better-schema-markup. Install the toolchain with Vite+ and Bun, then run `vp install` and `vp build` to rebuild JavaScript. See docs/project-workflow.md in the source repository for testing and packaging.

== Screenshots ==

1. First-run site identity setup.
2. Site identity and schema defaults in Settings → Better Schema.
3. The editor Schema panel with validation hints and JSON-LD preview.
4. SEO plugin takeover and migration controls with an import report.

== What is output where ==

Every page gets one JSON-LD `@graph`. Entities refer to each other by `@id`.

* WooCommerce product (when WooCommerce is active): Product, added to the usual post graph
* Post with the Event, Recipe or FAQ page type: the usual graph with an Event, Recipe or FAQPage entity instead of the Article (a Recipe keeps its author Person)
* Post whose content has a YouTube or Vimeo link or embed: a VideoObject for each video, up to five
* Organization with the local business setting on: the identity is a LocalBusiness with address, opening hours and location
* Post with custom JSON-LD: by default those entities are added to the graph. Choose "Override matching schema" to let them replace the generated WebSite, organization, WebPage, article and similar entities (matched by `@id` or type, keeping the links between entities), or "Replace generated schema" to output only your JSON-LD on that post
* Post: WebSite, Organization/Person, WebPage, BreadcrumbList, Article, Person (author)
* Page: WebSite, Organization/Person, WebPage, BreadcrumbList
* Static front page: WebSite, Organization/Person, WebPage (`about` the identity, no breadcrumbs)
* Front page listing posts: WebSite, Organization/Person, CollectionPage (`about` the identity)
* Posts page, category, tag, custom taxonomy, post type and date archives: WebSite, Organization/Person, CollectionPage, BreadcrumbList
* Author archive: WebSite, Organization/Person, ProfilePage, BreadcrumbList, Person
* Search results: WebSite, Organization/Person, SearchResultsPage
* 404: nothing

In the block editor sidebar or the classic editor's Schema meta box, each post, page and public REST-enabled custom post type has a schema type: Article, Blog post (BlogPosting), Regular page (WebPage), Event, Recipe, FAQ page or No schema. Event, Recipe and FAQ page show their own fields. Posts default to Article and everything else to WebPage until changed in Settings → Better Schema. Individual post choices take precedence over these defaults. WebPage adds no Article. No schema turns off all schema for that post, including the site-wide entities.

The classic editor is supported, including sites using Disable Gutenberg. Schema fields, custom JSON-LD and the preview work in either editor, using the same saved settings. In the classic editor, click Update, Publish or Save Draft to save schema changes with the post. Saving without changing a schema field preserves its existing value and an inherited type default. If the meta box is hidden, enable Schema in Screen Options. JavaScript is required for the controls, and the backend must allow authenticated REST requests for the preview.

== In the editor ==

The Schema panel in the post sidebar shows:

* the schema type picker, with a short explanation of each type;
* hints when Google recommends a property the page is missing, for example a featured image for an article, or a logo for the organization;
* the JSON-LD the page outputs, under "Show JSON-LD". It uses the type picked in the panel, even before you save, and the rest of the saved post; and
* a "Test with Google" link to the Rich Results Test, once the post is published.

Breadcrumbs start at Home. Posts go through their first category by name, skipping the default category ("Uncategorized"); change it with `better_schema_breadcrumb_category`. Pages go through their parent pages, custom post types through their archive, and terms through their parent terms.

The author Person `@id` uses a site-specific hash, so it does not reveal the user ID or login. No avatar is output, because Gravatar URLs contain a hash of the user's email address.

== Site identity ==

The site is described as an Organization (default) or a Person. With no setup it uses the site title as the name and the site icon as the logo.

Open Settings → Better Schema to choose the identity type, name, logo from the media library, social profiles, and a default schema type for each public REST-enabled post type. The first visit offers a three-question setup that can be completed or skipped. Existing configured sites open the full settings page.

Turn on "This is a local business" for an Organization to add a phone number, price range, address, map coordinates and opening hours. Opening hours without a day or both times, and a single coordinate, are not saved.

Post type defaults are stored in the `better_schema_type_defaults` option as a map from post type slug to `Article`, `BlogPosting`, `WebPage` or `none`. Missing or invalid entries use the built-in defaults. Posts with a saved `_better_schema_type` choice keep that choice when the site default changes.

The identity is stored in the `better_schema_identity` option. All keys are optional:

`wp option update better_schema_identity --format=json '{"type":"Person","name":"Ada Lovelace","logo":123,"same_as":["https://example.com/ada"]}'`

* `type`: `Organization` or `Person`.
* `name`: overrides the site title.
* `logo`: media library attachment ID. An Organization outputs it as `logo`, a Person as `image`. Overrides the site icon.
* `same_as`: list of `http(s)` URLs for the social profiles, output as `sameAs`.

Invalid values are ignored. The WebSite entity points to the identity as its `publisher`. If you remove the `identity` piece with `better_schema_pieces`, also remove that reference with `better_schema_piece_website`.

== Developer filters ==

The schema is one JSON-LD `@graph` built from small pieces. Each piece returns a list of entity arrays. The pieces, in output order, are `website`, `identity`, `webpage`, `breadcrumb`, `article`, `author`, `product`, `event`, `recipe`, `faq`, `video` and `custom`. In a post's Replace mode, only `custom` runs. In Override mode, custom entities replace generated ones after every `better_schema_piece_{id}` filter and before `better_schema_graph`.

* `better_schema_context` (array|null $context): what the current request describes. Return a different context, or null for no schema on this request.
* `better_schema_pieces` (array $pieces): map of piece id to callable. Add, remove or replace pieces. The callable receives the context array and returns a list of entities (an empty array omits the piece).
* `better_schema_piece_{id}` (array $entities, array $context): change the entities one piece returned, for example `better_schema_piece_article`. Return a list of entities.
* `better_schema_graph` (array $graph, array $context): change the full list of entities before it is output. Return a list of entities.
* `better_schema_breadcrumb_category` (WP_Term|null $category, WP_Post $post): the category used in a post's breadcrumbs. Return a WP_Term or null.
* `better_schema_custom_role_types` (array $types): map of role (`website`, `identity`, `webpage`, `breadcrumb`, `article`, `product`, `event`, `recipe`, `faq`, `video`) to the schema.org types a custom entity needs to replace the generated entity of that role in Override mode. Return a map.
* `better_schema_validation_hints` (array $hints, array $document, array $context): the hints shown in the editor. Each hint is an array with `type` (the entity `@type`), `property`, `level` (`required` or `recommended`) and `message`. Return a list of hints.

The editor preview comes from `GET` or `POST /wp-json/better-schema/v1/preview/{id}`, which needs permission to edit the post. The optional `type`, `custom` (custom JSON-LD) and `custom_mode` (`add`, `override` or `replace`) parameters preview unsaved values without saving them. The response has `document` (the JSON-LD document, or null when there is no schema) and `hints`. The preview uses the post's context directly, so the `better_schema_context` filter, which applies to frontend requests, is not run.

The context is an array with a `type` key:

* `post`: a single post, page or other singular item, with `post` (WP_Post)
* `blog`: the posts page, with `post` (WP_Post)
* `home`: a front page that lists the latest posts
* `term`: a category, tag or taxonomy archive, with `term` (WP_Term)
* `author`: an author archive, with `user` (WP_User)
* `post_type_archive`: with `post_type` (string)
* `date`: with `year`, `month` and `day` (ints, 0 when not part of the archive)
* `search`: with `query` (string)

Build contexts with the `better_schema_*_context()` functions in `includes/context.php`.

Entities refer to each other by `@id`. If you remove a piece, also remove references to it from the others. For example, without `identity`, remove `publisher` from `better_schema_piece_website` and `better_schema_piece_article`. Without `breadcrumb`, remove `breadcrumb` from `better_schema_piece_webpage`. Without `author`, remove `author` from `better_schema_piece_article`.

== Upgrade Notice ==

= 0.19.2 =
Settings now use the available page width, with responsive columns on larger screens.

= 0.19.1 =
Vimeo schema now requires explicit permission in Settings → Better Schema. Existing sites must opt in; YouTube schema is unchanged.

= 0.19.0 =
A redesigned Settings → Better Schema page and editor schema fields. Your settings are unchanged.

= 0.18.1 =
Clearer settings, editor fields and import results, with help buttons and examples. No settings change.

= 0.18.0 =
Custom JSON-LD can now override or replace the generated schema per post. Existing posts keep adding it, as before.

= 0.17.0 =
Adds a Schema meta box when using the classic editor, including Disable Gutenberg. Save schema changes with Update or Publish.

= 0.16.1 =
Fix admin API requests on headless WordPress sites with a separate frontend domain. No site URL changes are required.

= 0.16.0 =
Release preparation adds SEO schema takeover and migration, translations and uninstall cleanup. Review migration reports before switching production sites. Deleting the plugin removes its saved settings and per-post choices.

== Changelog ==

= 0.19.2 =
* Use the available settings-page width with two columns on larger screens and stacked sections on smaller screens.

= 0.19.1 =
* Require opt-in before requesting Vimeo metadata or using cached Vimeo metadata for schema.
* Show SEO takeover notices only on the plugin settings page.
* Include bundled third-party license notices in release packages.

= 0.19.0 =
* Redesign the Settings → Better Schema page with clearer fields, dropdowns, switches and help popovers.
* Use the same design for the schema fields in the block editor and the classic editor, with two-column rows where there is room and choice cards for the custom JSON-LD mode.
* Load the settings page's code only on that page, and minify the admin scripts.

= 0.18.1 =
* Add "?" help buttons that explain the site identity, frontend URL, social profiles, local business and custom JSON-LD fields.
* Show example URLs in the social profiles field and point out which lines are not full URLs while typing.
* Redesign the settings and editor forms with clearer spacing: sentence-case labels above each field, help text directly below it, titled sections with descriptions, and related fields side by side.
* Explain the Yoast SEO and Rank Math import in plain words, with a help button, and show its result under the Preview and Import buttons instead of above the section.

= 0.18.0 =
* Add a mode to custom JSON-LD: add to the generated schema (the default), override matching entities, or replace the generated schema on that post. Override keeps WebPage, article and other links pointing at your entities.
* Preview unsaved custom JSON-LD and its mode in the editor.
* Hint when custom JSON-LD duplicates the generated WebSite, organization or other entities, or uses another domain than the site.
* Add the `better_schema_custom_role_types` filter, and pass the context to `better_schema_validation_hints`.

= 0.17.0 =
* Add classic editor support with the schema type selector, Event/Recipe/FAQ fields, custom JSON-LD, validation hints and preview.
* Save classic editor schema fields with the post, preserving untouched values and inherited defaults.

= 0.16.1 =
* Fix settings, imports and editor previews on headless sites by sending admin API requests to the WordPress backend instead of the public frontend domain. Standard WordPress sites remain supported.

= 0.16.0 =
* Replace Yoast SEO and Rank Math schema with an optional takeover setting.
* Import supported saved schema choices through Settings or WP-CLI, with dry runs and limitation reports.
* Add script translation loading, translated weekdays and the translation template.
* Remove plugin-owned settings, metadata and video caches on uninstall.
* Add release packaging, directory artwork and complete plugin documentation.
* Keep the editor JSON preview and validation hints within the sidebar width.

= 0.15.0 =
* Add a custom JSON-LD field per post, under Advanced in the Schema panel. It accepts one object, a list or a document with `@graph`. Invalid JSON is not saved, and the output cannot break out of the script tag.

= 0.14.0 =
* Add the FAQ page type with questions and answers. Google only shows FAQ rich results for well-known government and health sites.

= 0.13.0 =
* Add VideoObject entities for YouTube and Vimeo videos linked or embedded in a post. Vimeo details come from oEmbed and are cached.

= 0.12.0 =
* Add the Recipe type with ingredients, steps, times, servings and calories. Recipes link to their author.

= 0.11.0 =
* Add the Event type with dates, venue, online or in-person attendance, status and a ticket link.
* Add validation hints for events, recipes, FAQ pages, local businesses and products.

= 0.10.0 =
* Add a local business option under Settings → Better Schema. For an Organization it makes the identity a LocalBusiness with address, phone number, price range, opening hours and location. It is stored in the `better_schema_business` option.

= 0.9.0 =
* Add a Product piece for WooCommerce products: name, description, image, SKU, brand, offers (AggregateOffer for variable products) and aggregate rating. Output only when WooCommerce is active.

= 0.8.0 =
* Add WPGraphQL content and site graphs, effective schema types, and frontend URL mapping for headless sites.
* Add a Next.js integration and migration guide in docs/wpgraphql.md.

= 0.7.0 =
* Add a React settings page and first-run setup for site identity, logo and social profiles.
* Add configurable default schema types for posts, pages and public REST-enabled custom post types while preserving individual post choices.

= 0.6.0 =
* The schema type picker in the editor now uses plain-language labels and explains each type.
* Add a live JSON-LD preview to the editor, from a new REST endpoint that runs the same graph builder as the frontend.
* Add hints in the editor when Google recommends a property the page is missing, and the `better_schema_validation_hints` filter.
* Add a "Test with Google" link to the Rich Results Test for published posts.

= 0.5.0 =
* Add the WebPage piece for every page: WebPage, CollectionPage, ProfilePage or SearchResultsPage, linked to the WebSite.
* Articles now link to their WebPage (`isPartOf`, `mainEntityOfPage`), the site identity (`publisher`) and their author, who is output as a Person with their own `@id`.
* Add the BreadcrumbList piece, built from categories, parent pages, post type archives, parent terms and date archives.
* Output schema on the front page, posts page, archives and search results, and none on 404 pages.
* Pages now default to WebPage instead of Article.
* Titles and names are output as plain text, without HTML entities.
* Add the `better_schema_context` and `better_schema_breadcrumb_category` filters.
* Require PHP 8.3. The code now uses strict types and typed functions.

= 0.4.0 =
* Add the site identity piece (Organization or Person) with name, logo and social profiles. Defaults to the site title and site icon.
* Add the `better_schema_identity` option, validated on save and available through the REST API.
* The WebSite entity now references the identity as its `publisher`.

= 0.3.0 =
* Add the WebSite piece: site name, URL, tagline and language, with the stable `@id` `{home}/#website`.
* Choosing "None" for a post now turns off all schema for it, including the WebSite entity.

= 0.2.0 =
* Add the graph builder: schema is now one JSON-LD `@graph` with stable `@id`s, built from pieces and extendable with filters.
* Move the article output onto the graph builder and add tests for the graph, pieces, rendering and frontend output.

= 0.1.6 =
* Ignore invalid stored schema types instead of outputting them.

= 0.1.5 =
* Add GitHub Actions CI.

= 0.1.4 =
* Add Vitest and PHPUnit test setup.

= 0.1.3 =
* Convert the editor panel to strict TypeScript.

= 0.1.2 =
* Switch to GPL-2.0-or-later and add readme.txt.

= 0.1.1 =
* Rename plugin to Better Schema Markup.
