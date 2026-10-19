<?php
/**
 * JSON-LD rendering. The only place that encodes the graph as JSON.
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Renders a JSON-LD document as a script element.
 *
 * @param array $document Document from `better_schema_build_graph()`.
 * @return string The script element, or an empty string when there is nothing to render.
 */
function better_schema_render_script( array $document ): string {
	$json = better_schema_encode_graph( $document );
	return '' === $json ? '' : sprintf( "<script type=\"application/ld+json\">%s</script>\n", $json );
}

/** Encode once for HTML output and GraphQL, safely embeddable in a script. */
function better_schema_encode_graph( array $document ): string {
	if ( empty( $document ) ) {
		return '';
	}
	$json = wp_json_encode( $document, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG );
	return false === $json ? '' : $json;
}
