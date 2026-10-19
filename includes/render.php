<?php
/**
 * JSON-LD rendering. The only place that encodes the graph as JSON.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Renders a JSON-LD document as a script element.
 *
 * @param array $document Document from `better_schema_build_graph()`.
 * @return string The script element, or an empty string when there is nothing to render.
 */
function better_schema_render_script( array $document ) {
	if ( empty( $document ) ) {
		return '';
	}

	// JSON_HEX_TAG keeps "</script>" in any value from ending the element early.
	$json = wp_json_encode( $document, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG );

	if ( false === $json ) {
		return '';
	}

	return sprintf( "<script type=\"application/ld+json\">%s</script>\n", $json );
}
