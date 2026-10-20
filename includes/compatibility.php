<?php
/**
 * Replaces schema output only; other SEO plugin features remain enabled.
 */
declare( strict_types=1 );
defined( 'ABSPATH' ) || exit;

function better_schema_other_seo_active(): bool {
	return defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' );
}

function better_schema_takeover_enabled(): bool {
	return (bool) get_option( 'better_schema_takeover', true );
}

function better_schema_filter_seo_schema( mixed $data ): mixed {
	return better_schema_takeover_enabled() ? array() : $data;
}
// Run after the SEO plugins have assembled their graphs, without changing their other filters.
add_filter( 'wpseo_json_ld_output', 'better_schema_filter_seo_schema', PHP_INT_MAX );
add_filter( 'rank_math/json_ld', 'better_schema_filter_seo_schema', PHP_INT_MAX );

function better_schema_register_takeover(): void {
	register_setting( 'better_schema', 'better_schema_takeover', array(
		'type' => 'boolean',
		'default' => true,
		'sanitize_callback' => 'rest_sanitize_boolean',
		'show_in_rest' => true,
	) );
}
add_action( 'init', 'better_schema_register_takeover', 100 );

function better_schema_takeover_notice(): void {
	if ( ! current_user_can( 'manage_options' ) || ! better_schema_other_seo_active() ) {
		return;
	}
	$message = better_schema_takeover_enabled()
		? __( 'Better Schema Markup now handles your schema. Your SEO plugin continues to handle its other SEO features.', 'better-schema-markup' )
		: __( 'Your SEO plugin handles frontend schema. Better Schema Markup previews and WPGraphQL remain available.', 'better-schema-markup' );
	echo '<div class="notice notice-info"><p>' . esc_html( $message ) . ' <a href="' . esc_url( admin_url( 'options-general.php?page=better-schema-markup' ) ) . '">' . esc_html__( 'Schema settings', 'better-schema-markup' ) . '</a></p></div>';
}
add_action( 'admin_notices', 'better_schema_takeover_notice' );
