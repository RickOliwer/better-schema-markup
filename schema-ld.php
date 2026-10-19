<?php
/**
 * Plugin Name: Schema LD
 * Description: Schema.org JSON-LD output for WordPress.
 * Version:     0.1.0
 * License:     GPL-2.0-or-later
 * Text Domain: schema-ld
 */

defined( 'ABSPATH' ) || exit;

define( 'SCHEMA_LD_PATH', plugin_dir_path( __FILE__ ) );
define( 'SCHEMA_LD_URL', plugin_dir_url( __FILE__ ) );

require_once SCHEMA_LD_PATH . 'includes/meta.php';
require_once SCHEMA_LD_PATH . 'includes/editor.php';
require_once SCHEMA_LD_PATH . 'includes/output.php';
