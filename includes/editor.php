<?php
/**
 * Loads the React panel in the block editor.
 */

defined( 'ABSPATH' ) || exit;

function schema_ld_enqueue_editor_assets() {
	$asset_file = SCHEMA_LD_PATH . 'build/index.asset.php';

	if ( ! file_exists( $asset_file ) ) {
		return;
	}

	$asset = require $asset_file;

	wp_enqueue_script(
		'schema-ld-editor',
		SCHEMA_LD_URL . 'build/index.js',
		$asset['dependencies'],
		filemtime( SCHEMA_LD_PATH . 'build/index.js' ),
		true
	);
}
add_action( 'enqueue_block_editor_assets', 'schema_ld_enqueue_editor_assets' );
