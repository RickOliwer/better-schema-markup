<?php
/**
 * Plugin Name: Better Schema Markup
 * Description: Schema.org JSON-LD output for WordPress.
 * Version:     0.1.1
 * License:     GPL-2.0-or-later
 * Text Domain: better-schema-markup
 */

defined( 'ABSPATH' ) || exit;

define( 'BETTER_SCHEMA_PATH', plugin_dir_path( __FILE__ ) );
define( 'BETTER_SCHEMA_URL', plugin_dir_url( __FILE__ ) );

require_once BETTER_SCHEMA_PATH . 'includes/meta.php';
require_once BETTER_SCHEMA_PATH . 'includes/editor.php';
require_once BETTER_SCHEMA_PATH . 'includes/output.php';
