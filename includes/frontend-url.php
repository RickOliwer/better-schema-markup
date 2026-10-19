<?php
/** Frontend URL mapping shared by every graph consumer. */
declare( strict_types=1 );
defined( 'ABSPATH' ) || exit;

/** Accept an absolute HTTP(S) base URL without credentials, query or fragment. */
function better_schema_sanitize_frontend_url( mixed $value ): string {
	if ( ! is_string( $value ) || '' === trim( $value ) ) {
		return '';
	}
	$parts = wp_parse_url( trim( $value ) );
	if ( ! is_array( $parts ) || empty( $parts['host'] ) || ! in_array( $parts['scheme'] ?? '', array( 'http', 'https' ), true )
		|| isset( $parts['user'] ) || isset( $parts['pass'] ) || isset( $parts['query'] ) || isset( $parts['fragment'] ) ) {
		return '';
	}
	return untrailingslashit( esc_url_raw( trim( $value ), array( 'http', 'https' ) ) );
}

/** Rewrite URL properties, preserving text, external URLs, and path boundaries. */
function better_schema_rewrite_urls( array $graph ): array {
	$frontend = better_schema_sanitize_frontend_url( get_option( 'better_schema_frontend_url', '' ) );
	if ( '' === $frontend ) {
		return $graph;
	}
	$bases = array_unique( array( untrailingslashit( home_url() ), untrailingslashit( site_url() ) ) );
	usort( $bases, static fn( $a, $b ) => strlen( $b ) <=> strlen( $a ) );
	return better_schema_map_urls( $graph, $bases, $frontend );
}

/** Recursively map URL values, including lists and nested entity references. */
function better_schema_map_urls( array $values, array $bases, string $frontend, string $property = '' ): array {
	$url_properties = array( '@id', 'url', 'contentUrl', 'embedUrl', 'thumbnailUrl', 'image', 'logo', 'sameAs', 'item', 'mainEntityOfPage', 'isPartOf', 'publisher', 'author', 'about' );
	foreach ( $values as $key => $value ) {
		$name = is_string( $key ) ? $key : $property;
		if ( is_array( $value ) ) {
			$values[ $key ] = better_schema_map_urls( $value, $bases, $frontend, $name );
		} elseif ( is_string( $value ) && in_array( $name, $url_properties, true ) ) {
			foreach ( $bases as $base ) {
				if ( str_starts_with( $value, $base ) && ( strlen( $value ) === strlen( $base ) || in_array( substr( $value, strlen( $base ), 1 ), array( '/', '?', '#' ), true ) ) ) {
					$values[ $key ] = $frontend . substr( $value, strlen( $base ) );
					break;
				}
			}
		}
	}
	return $values;
}
