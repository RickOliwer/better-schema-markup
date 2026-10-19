<?php
/**
 * Plugin Name: Better Schema Markup
 * Description: Schema.org JSON-LD for better SEO and rich results. Replaces the schema output of Yoast SEO and Rank Math, with WPGraphQL support.
 * Version:     0.2.0
 * Requires at least: 6.6
 * Requires PHP: 7.4
 * Author:      Rick Oliwer
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: better-schema-markup
 */

defined( 'ABSPATH' ) || exit;

define( 'BETTER_SCHEMA_PATH', plugin_dir_path( __FILE__ ) );
define( 'BETTER_SCHEMA_URL', plugin_dir_url( __FILE__ ) );

require_once BETTER_SCHEMA_PATH . 'includes/meta.php';
require_once BETTER_SCHEMA_PATH . 'includes/editor.php';
require_once BETTER_SCHEMA_PATH . 'includes/context.php';
require_once BETTER_SCHEMA_PATH . 'includes/pieces/article.php';
require_once BETTER_SCHEMA_PATH . 'includes/graph.php';
require_once BETTER_SCHEMA_PATH . 'includes/render.php';
require_once BETTER_SCHEMA_PATH . 'includes/output.php';
