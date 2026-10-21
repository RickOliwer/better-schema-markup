<?php
/**
 * Keep this plugin's authenticated admin requests on the WordPress backend.
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

function better_schema_admin_api_url(): string {
	// rest_url() uses the public home URL, which can belong to a headless frontend.
	// The query route also works without rewrite rules and in subdirectories.
	return add_query_arg( 'rest_route', '/', site_url( 'index.php', 'admin' ) );
}

function better_schema_add_admin_api_config(): void {
	wp_add_inline_script(
		'better-schema-editor',
		'window.betterSchemaApi = ' . wp_json_encode(
			array( 'rootUrl' => better_schema_admin_api_url() ),
			JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
		) . ';',
		'before'
	);
}
