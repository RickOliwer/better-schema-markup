<?php
/**
 * Admin screen for site identity and post type defaults.
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

function better_schema_add_settings_page(): void {
	add_options_page(
		__( 'Better Schema', 'better-schema-markup' ),
		__( 'Better Schema', 'better-schema-markup' ),
		'manage_options',
		'better-schema-markup',
		'better_schema_render_settings_page'
	);
}
add_action( 'admin_menu', 'better_schema_add_settings_page' );

function better_schema_render_settings_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	echo '<div class="wrap"><h1>' . esc_html__( 'Better Schema', 'better-schema-markup' ) . '</h1>';
	echo '<div id="better-schema-settings"></div></div>';
}

function better_schema_enqueue_settings_assets( string $hook_suffix ): void {
	if ( 'settings_page_better-schema-markup' !== $hook_suffix ) {
		return;
	}

	$asset_file = BETTER_SCHEMA_PATH . 'build/index.asset.php';
	if ( ! file_exists( $asset_file ) ) {
		return;
	}

	$asset = require $asset_file;
	wp_enqueue_media();
	wp_enqueue_style( 'wp-components' );
	wp_enqueue_script(
		'better-schema-editor',
		BETTER_SCHEMA_URL . 'build/index.js',
		$asset['dependencies'],
		filemtime( BETTER_SCHEMA_PATH . 'build/index.js' ),
		true
	);
	wp_set_script_translations( 'better-schema-editor', 'better-schema-markup', BETTER_SCHEMA_PATH . 'languages' );
	better_schema_add_admin_api_config();

	$post_types = array();
	foreach ( better_schema_configurable_post_types() as $post_type ) {
		$object = get_post_type_object( $post_type );
		if ( $object ) {
			$post_types[] = array(
				'name'  => $post_type,
				'label' => $object->labels->name,
			);
		}
	}

	$identity = better_schema_get_identity();
	$logo_url = $identity['logo'] ? wp_get_attachment_url( $identity['logo'] ) : false;
	wp_add_inline_script(
		'better-schema-editor',
		'window.betterSchemaSettings = ' . wp_json_encode(
			array(
				'postTypes'     => $post_types,
				'showSetup'     => ! better_schema_setup_is_complete(),
				'logoUrl'       => $logo_url ?: '',
				'siteName'      => get_bloginfo( 'name' ),
			),
			JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
		) . ';',
		'before'
	);
}
add_action( 'admin_enqueue_scripts', 'better_schema_enqueue_settings_assets' );
