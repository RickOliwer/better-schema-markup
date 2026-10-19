<?php
/**
 * Graph builder: the single source of truth for schema output.
 *
 * Builds a schema.org JSON-LD document from an explicit context. It does not
 * read request globals and does not render HTML.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Returns the registered schema pieces.
 *
 * Filter `better_schema_pieces` lets developers add, remove or replace pieces.
 *
 * @param array<string, callable> $pieces Map of piece id to callback. Each callback receives the
 *                                        context array and returns a list of entity arrays
 *                                        (an empty array omits the piece).
 * @return array<string, callable>
 */
function better_schema_pieces() {
	$pieces = apply_filters(
		'better_schema_pieces',
		array(
			'article' => 'better_schema_piece_article',
		)
	);

	return is_array( $pieces ) ? $pieces : array();
}

/**
 * Builds the JSON-LD document for a context.
 *
 * Filter `better_schema_piece_{id}` receives a piece's list of entities and the
 * context, and returns a list of entities.
 *
 * Filter `better_schema_graph` receives the full list of entities and the
 * context, and returns a list of entities.
 *
 * @param array $context Context from `better_schema_post_context()`.
 * @return array Document with `@context` and `@graph`, or an empty array when there is nothing to describe.
 */
function better_schema_build_graph( array $context ) {
	$graph = array();

	foreach ( better_schema_pieces() as $id => $piece ) {
		if ( ! is_callable( $piece ) ) {
			continue;
		}

		$entities = apply_filters( "better_schema_piece_{$id}", $piece( $context ), $context );

		if ( ! is_array( $entities ) ) {
			continue;
		}

		foreach ( array_filter( $entities ) as $entity ) {
			$graph[] = $entity;
		}
	}

	$graph = apply_filters( 'better_schema_graph', $graph, $context );

	if ( ! is_array( $graph ) || empty( $graph ) ) {
		return array();
	}

	return array(
		'@context' => 'https://schema.org',
		'@graph'   => array_values( $graph ),
	);
}
