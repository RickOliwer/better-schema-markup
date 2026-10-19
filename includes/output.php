<?php
/**
 * Frontend integration: resolves the current request and prints the JSON-LD.
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Resolves what the current request describes.
 *
 * This is the only place that reads request state. Everything it returns is
 * passed on explicitly. Filter `better_schema_context` receives the resolved
 * context (or null) and returns a context array or null for no schema.
 *
 * @return array|null Context, or null when the request has no schema (for example a 404).
 */
function better_schema_resolve_current_context(): ?array {
	$context = apply_filters( 'better_schema_context', better_schema_context_for_request() );

	return is_array( $context ) ? $context : null;
}

function better_schema_context_for_request(): ?array {
	if ( is_404() ) {
		return null;
	}

	$queried = get_queried_object();

	if ( is_front_page() && is_home() ) {
		return better_schema_home_context();
	}

	if ( is_home() ) {
		return $queried instanceof WP_Post ? better_schema_blog_context( $queried ) : better_schema_home_context();
	}

	if ( is_singular() ) {
		return $queried instanceof WP_Post ? better_schema_post_context( $queried ) : null;
	}

	if ( is_search() ) {
		return better_schema_search_context( (string) get_search_query( false ) );
	}

	if ( is_category() || is_tag() || is_tax() ) {
		return $queried instanceof WP_Term ? better_schema_term_context( $queried ) : null;
	}

	if ( is_author() ) {
		return $queried instanceof WP_User ? better_schema_author_context( $queried ) : null;
	}

	if ( is_post_type_archive() ) {
		$post_type = get_query_var( 'post_type' );
		$post_type = is_array( $post_type ) ? reset( $post_type ) : $post_type;

		return is_string( $post_type ) && post_type_exists( $post_type ) ? better_schema_post_type_archive_context( $post_type ) : null;
	}

	if ( is_date() ) {
		return better_schema_date_context_for_request();
	}

	return null;
}

/**
 * Date archives come from `year`/`monthnum`/`day` with pretty permalinks, or from `m` (e.g. 202610) without.
 */
function better_schema_date_context_for_request(): array {
	$year  = (int) get_query_var( 'year' );
	$month = (int) get_query_var( 'monthnum' );
	$day   = (int) get_query_var( 'day' );
	$m     = (string) preg_replace( '/\D/', '', (string) get_query_var( 'm' ) );

	if ( ! $year && strlen( $m ) >= 4 ) {
		$year  = (int) substr( $m, 0, 4 );
		$month = (int) substr( $m, 4, 2 );
		$day   = (int) substr( $m, 6, 2 );
	}

	return better_schema_date_context( $year, $month, $day );
}

function better_schema_print(): void {
	$context = better_schema_resolve_current_context();

	if ( null === $context ) {
		return;
	}

	// Already JSON-encoded with JSON_HEX_TAG in better_schema_render_script().
	echo better_schema_render_script( better_schema_build_graph( $context ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
add_action( 'wp_head', 'better_schema_print' );
