<?php
/**
 * Loads the React panel in the block editor.
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

function better_schema_enqueue_editor_assets(): void {
	$asset_file = BETTER_SCHEMA_PATH . 'build/index.asset.php';

	if ( ! file_exists( $asset_file ) ) {
		return;
	}

	$asset = require $asset_file;

	// Loaded in the editor's outer document, where the schema panel is, not in the content iframe.
	wp_enqueue_style(
		'better-schema-editor',
		BETTER_SCHEMA_URL . 'build/index.css',
		array(),
		filemtime( BETTER_SCHEMA_PATH . 'build/index.css' )
	);
	wp_enqueue_script(
		'better-schema-editor',
		BETTER_SCHEMA_URL . 'build/index.js',
		$asset['dependencies'],
		filemtime( BETTER_SCHEMA_PATH . 'build/index.js' ),
		true
	);
	wp_set_script_translations( 'better-schema-editor', 'better-schema-markup', BETTER_SCHEMA_PATH . 'languages' );
	better_schema_add_admin_api_config();
}
add_action( 'enqueue_block_editor_assets', 'better_schema_enqueue_editor_assets' );
