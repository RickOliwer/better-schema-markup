=== Better Schema Markup ===
Contributors: rickoliwer
Tags: schema, json-ld, seo, structured data, wpgraphql
Requires at least: 6.6
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 0.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Schema.org JSON-LD for better SEO and rich results. Replaces the schema output of Yoast SEO and Rank Math, with WPGraphQL support.

== Description ==

Better Schema Markup adds schema.org structured data (JSON-LD) to your site so search engines can show rich results.

Work in progress.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/`.
2. Activate the plugin through the Plugins screen in WordPress.

== Developer filters ==

The schema is one JSON-LD `@graph` built from small pieces. Each piece returns a list of entity arrays.

* `better_schema_pieces` (array $pieces): map of piece id to callable. Add, remove or replace pieces. The callable receives the context array and returns a list of entities (an empty array omits the piece).
* `better_schema_piece_{id}` (array $entities, array $context): change the entities one piece returned, for example `better_schema_piece_article`. Return a list of entities.
* `better_schema_graph` (array $graph, array $context): change the full list of entities before it is output. Return a list of entities.

The context is an array such as `array( 'type' => 'post', 'post' => WP_Post )`.

== Changelog ==

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
