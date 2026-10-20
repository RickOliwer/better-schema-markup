<?php
/**
 * Plugin Name: Better Schema Markup
 * Description: Schema.org JSON-LD for better SEO and rich results. Replaces the schema output of Yoast SEO and Rank Math, with WPGraphQL support.
 * Version:     0.17.0
 * Requires at least: 6.6
 * Requires PHP: 8.3
 * Author:      Rick Centerhall
 * Author URI:  https://rickcenterhall.com/
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: better-schema-markup
 * Domain Path: /languages
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

define( 'BETTER_SCHEMA_PATH', plugin_dir_path( __FILE__ ) );
define( 'BETTER_SCHEMA_URL', plugin_dir_url( __FILE__ ) );

require_once BETTER_SCHEMA_PATH . 'includes/helpers.php';
require_once BETTER_SCHEMA_PATH . 'includes/meta.php';
require_once BETTER_SCHEMA_PATH . 'includes/post-details.php';
require_once BETTER_SCHEMA_PATH . 'includes/admin-api.php';
require_once BETTER_SCHEMA_PATH . 'includes/editor.php';
require_once BETTER_SCHEMA_PATH . 'includes/classic-editor.php';
require_once BETTER_SCHEMA_PATH . 'includes/context.php';
require_once BETTER_SCHEMA_PATH . 'includes/identity.php';
require_once BETTER_SCHEMA_PATH . 'includes/business.php';
require_once BETTER_SCHEMA_PATH . 'includes/settings.php';
require_once BETTER_SCHEMA_PATH . 'includes/settings-page.php';
require_once BETTER_SCHEMA_PATH . 'includes/pieces/website.php';
require_once BETTER_SCHEMA_PATH . 'includes/pieces/identity.php';
require_once BETTER_SCHEMA_PATH . 'includes/pieces/webpage.php';
require_once BETTER_SCHEMA_PATH . 'includes/pieces/breadcrumb.php';
require_once BETTER_SCHEMA_PATH . 'includes/pieces/article.php';
require_once BETTER_SCHEMA_PATH . 'includes/pieces/author.php';
require_once BETTER_SCHEMA_PATH . 'includes/pieces/product.php';
require_once BETTER_SCHEMA_PATH . 'includes/pieces/event.php';
require_once BETTER_SCHEMA_PATH . 'includes/pieces/recipe.php';
require_once BETTER_SCHEMA_PATH . 'includes/pieces/faq.php';
require_once BETTER_SCHEMA_PATH . 'includes/pieces/video.php';
require_once BETTER_SCHEMA_PATH . 'includes/pieces/custom.php';
require_once BETTER_SCHEMA_PATH . 'includes/graph.php';
require_once BETTER_SCHEMA_PATH . 'includes/render.php';
require_once BETTER_SCHEMA_PATH . 'includes/output.php';
require_once BETTER_SCHEMA_PATH . 'includes/validation.php';
require_once BETTER_SCHEMA_PATH . 'includes/preview.php';

require_once BETTER_SCHEMA_PATH . 'includes/frontend-url.php';
require_once BETTER_SCHEMA_PATH . 'includes/graphql.php';

require_once BETTER_SCHEMA_PATH . 'includes/compatibility.php';
require_once BETTER_SCHEMA_PATH . 'includes/import.php';
require_once BETTER_SCHEMA_PATH . 'includes/import-api.php';
