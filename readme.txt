=== Better Schema Markup ===
Contributors: rickoliwer
Tags: schema, json-ld, seo, structured data, wpgraphql
Requires at least: 6.6
Tested up to: 7.1
Requires PHP: 8.3
Stable tag: 0.6.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Schema.org JSON-LD for better SEO and rich results. Replaces the schema output of Yoast SEO and Rank Math, with WPGraphQL support.

== Description ==

Better Schema Markup adds schema.org structured data (JSON-LD) to your site so search engines can show rich results.

Work in progress.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/`.
2. Activate the plugin through the Plugins screen in WordPress.

== What is output where ==

Every page gets one JSON-LD `@graph`. Entities refer to each other by `@id`.

* Post: WebSite, Organization/Person, WebPage, BreadcrumbList, Article, Person (author)
* Page: WebSite, Organization/Person, WebPage, BreadcrumbList
* Static front page: WebSite, Organization/Person, WebPage (`about` the identity, no breadcrumbs)
* Front page listing posts: WebSite, Organization/Person, CollectionPage (`about` the identity)
* Posts page, category, tag, custom taxonomy, post type and date archives: WebSite, Organization/Person, CollectionPage, BreadcrumbList
* Author archive: WebSite, Organization/Person, ProfilePage, BreadcrumbList, Person
* Search results: WebSite, Organization/Person, SearchResultsPage
* 404: nothing

Each post and page has a schema type in the editor sidebar: Article, Blog post (BlogPosting), Regular page (WebPage) or No schema. Posts default to Article and everything else to WebPage. WebPage adds no Article. No schema turns off all schema for that post, including the site-wide entities.

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

To change it before the settings page exists, set the `better_schema_identity` option. All keys are optional:

`wp option update better_schema_identity --format=json '{"type":"Person","name":"Ada Lovelace","logo":123,"same_as":["https://example.com/ada"]}'`

* `type`: `Organization` or `Person`.
* `name`: overrides the site title.
* `logo`: media library attachment ID. An Organization outputs it as `logo`, a Person as `image`. Overrides the site icon.
* `same_as`: list of `http(s)` URLs for the social profiles, output as `sameAs`.

Invalid values are ignored. The WebSite entity points to the identity as its `publisher`. If you remove the `identity` piece with `better_schema_pieces`, also remove that reference with `better_schema_piece_website`.

== Developer filters ==

The schema is one JSON-LD `@graph` built from small pieces. Each piece returns a list of entity arrays. The pieces, in output order, are `website`, `identity`, `webpage`, `breadcrumb`, `article` and `author`.

* `better_schema_context` (array|null $context): what the current request describes. Return a different context, or null for no schema on this request.
* `better_schema_pieces` (array $pieces): map of piece id to callable. Add, remove or replace pieces. The callable receives the context array and returns a list of entities (an empty array omits the piece).
* `better_schema_piece_{id}` (array $entities, array $context): change the entities one piece returned, for example `better_schema_piece_article`. Return a list of entities.
* `better_schema_graph` (array $graph, array $context): change the full list of entities before it is output. Return a list of entities.
* `better_schema_breadcrumb_category` (WP_Term|null $category, WP_Post $post): the category used in a post's breadcrumbs. Return a WP_Term or null.
* `better_schema_validation_hints` (array $hints, array $document): the hints shown in the editor. Each hint is an array with `type` (the entity `@type`), `property`, `level` (`required` or `recommended`) and `message`. Return a list of hints.

The editor preview comes from `GET /wp-json/better-schema/v1/preview/{id}`, which needs permission to edit the post. The optional `type` parameter previews another schema type without saving it. The response has `document` (the JSON-LD document, or null when there is no schema) and `hints`. The preview uses the post's context directly, so the `better_schema_context` filter, which applies to frontend requests, is not run.

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

== Changelog ==

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
