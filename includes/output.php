<?php
/**
 * Frontend integration: resolves the current request and prints the JSON-LD.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Resolves what the current request describes.
 *
 * This is the only place that reads request state. Everything it returns is
 * passed on explicitly.
 *
 * @return array|null Context, or null when the request has no schema.
 */
function better_schema_resolve_current_context() {
	if ( ! is_singular() ) {
		return null;
	}

	$post = get_queried_object();

	if ( ! $post instanceof WP_Post ) {
		return null;
	}

	return better_schema_post_context( $post );
}

function better_schema_print() {
	$context = better_schema_resolve_current_context();

	if ( null === $context ) {
		return;
	}

	// Already JSON-encoded with JSON_HEX_TAG in better_schema_render_script().
	echo better_schema_render_script( better_schema_build_graph( $context ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
add_action( 'wp_head', 'better_schema_print' );
