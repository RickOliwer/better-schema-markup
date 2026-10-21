<?php
/**
 * Plugin Name: Better Schema Markup
 * Description: Schema.org JSON-LD for better SEO and rich results. Replaces the schema output of Yoast SEO and Rank Math, with WPGraphQL support.
 * Version:     0.19.3
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

// Shared helpers and post data.
require_once BETTER_SCHEMA_PATH . 'includes/support/helpers.php';
require_once BETTER_SCHEMA_PATH . 'includes/data/meta.php';
require_once BETTER_SCHEMA_PATH . 'includes/data/post-details.php';
// Editor assets and classic editor saving.
require_once BETTER_SCHEMA_PATH . 'includes/admin/admin-api.php';
require_once BETTER_SCHEMA_PATH . 'includes/admin/block-editor.php';
require_once BETTER_SCHEMA_PATH . 'includes/admin/classic-editor.php';
// Schema context and site settings.
require_once BETTER_SCHEMA_PATH . 'includes/schema/context.php';
require_once BETTER_SCHEMA_PATH . 'includes/data/identity.php';
require_once BETTER_SCHEMA_PATH . 'includes/data/business.php';
require_once BETTER_SCHEMA_PATH . 'includes/data/settings.php';
require_once BETTER_SCHEMA_PATH . 'includes/admin/settings-page.php';
// Schema entities and graph assembly.
require_once BETTER_SCHEMA_PATH . 'includes/schema/pieces/website.php';
require_once BETTER_SCHEMA_PATH . 'includes/schema/pieces/identity.php';
require_once BETTER_SCHEMA_PATH . 'includes/schema/pieces/webpage.php';
require_once BETTER_SCHEMA_PATH . 'includes/schema/pieces/breadcrumb.php';
require_once BETTER_SCHEMA_PATH . 'includes/schema/pieces/article.php';
require_once BETTER_SCHEMA_PATH . 'includes/schema/pieces/author.php';
require_once BETTER_SCHEMA_PATH . 'includes/schema/pieces/product.php';
require_once BETTER_SCHEMA_PATH . 'includes/schema/pieces/event.php';
require_once BETTER_SCHEMA_PATH . 'includes/schema/pieces/recipe.php';
require_once BETTER_SCHEMA_PATH . 'includes/schema/pieces/faq.php';
require_once BETTER_SCHEMA_PATH . 'includes/schema/pieces/video.php';
require_once BETTER_SCHEMA_PATH . 'includes/schema/pieces/custom.php';
require_once BETTER_SCHEMA_PATH . 'includes/schema/custom-merge.php';
require_once BETTER_SCHEMA_PATH . 'includes/schema/graph.php';
require_once BETTER_SCHEMA_PATH . 'includes/schema/render.php';
// Frontend output and editor preview.
require_once BETTER_SCHEMA_PATH . 'includes/integrations/output.php';
require_once BETTER_SCHEMA_PATH . 'includes/schema/validation.php';
require_once BETTER_SCHEMA_PATH . 'includes/admin/preview.php';

// Headless output and third-party SEO integrations.
require_once BETTER_SCHEMA_PATH . 'includes/integrations/frontend-url.php';
require_once BETTER_SCHEMA_PATH . 'includes/integrations/graphql.php';

require_once BETTER_SCHEMA_PATH . 'includes/integrations/compatibility.php';
require_once BETTER_SCHEMA_PATH . 'includes/integrations/import.php';
require_once BETTER_SCHEMA_PATH . 'includes/integrations/import-api.php';
