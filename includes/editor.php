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

	wp_enqueue_script(
		'better-schema-editor',
		BETTER_SCHEMA_URL . 'build/index.js',
		$asset['dependencies'],
		filemtime( BETTER_SCHEMA_PATH . 'build/index.js' ),
		true
	);
	wp_set_script_translations( 'better-schema-editor', 'better-schema-markup', BETTER_SCHEMA_PATH . 'languages' );
}
add_action( 'enqueue_block_editor_assets', 'better_schema_enqueue_editor_assets' );
