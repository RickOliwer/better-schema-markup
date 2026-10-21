<?php
/**
 * Graph builder: the single source of truth for schema output.
 *
 * Builds a schema.org JSON-LD document from an explicit context. It does not
 * read request globals and does not render HTML.
 */

declare( strict_types=1 );

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
function better_schema_pieces(): array {
	$pieces = apply_filters(
		'better_schema_pieces',
		array(
			'website'    => 'better_schema_piece_website',
			'identity'   => 'better_schema_piece_identity',
			'webpage'    => 'better_schema_piece_webpage',
			'breadcrumb' => 'better_schema_piece_breadcrumb',
			'article'    => 'better_schema_piece_article',
			'author'     => 'better_schema_piece_author',
			'product'    => 'better_schema_piece_product',
			'event'      => 'better_schema_piece_event',
			'recipe'     => 'better_schema_piece_recipe',
			'faq'        => 'better_schema_piece_faq',
			'video'      => 'better_schema_piece_video',
			'custom'     => 'better_schema_piece_custom',
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
 * The post's custom JSON-LD mode decides how custom entities combine with the
 * generated ones (see includes/custom-merge.php). In `replace` mode only the
 * `custom` piece runs, unless it has no entities.
 *
 * @param array $context Context from `better_schema_post_context()`.
 * @return array Document with `@context` and `@graph`, or an empty array when there is nothing to describe.
 */
function better_schema_build_graph( array $context ): array {
	// "None" means no schema at all, so site-wide pieces must not be output either.
	if ( better_schema_context_opted_out( $context ) ) {
		return array();
	}

	$mode   = better_schema_context_custom_mode( $context );
	$pieces = better_schema_pieces();
	$graph  = array();

	if ( 'replace' === $mode && isset( $pieces['custom'] ) && is_callable( $pieces['custom'] ) ) {
		$graph = better_schema_run_piece( 'custom', $pieces['custom'], $context );
	}

	if ( ! $graph ) {
		$entries = better_schema_collect_piece_entries( $pieces, $context );
		$graph   = 'override' === $mode ? better_schema_merge_custom_entities( $entries ) : array_column( $entries, 1 );
	}

	$graph = apply_filters( 'better_schema_graph', $graph, $context );

	if ( ! is_array( $graph ) || empty( $graph ) ) {
		return array();
	}

	return array(
		'@context' => 'https://schema.org',
		'@graph'   => better_schema_rewrite_urls( array_values( $graph ) ),
	);
}

/**
 * Runs every piece, keeping which piece generated each entity.
 *
 * @param array<string, callable> $pieces  Pieces from `better_schema_pieces()`.
 * @param array                   $context Context.
 * @return array<int, array{0: string, 1: array}> Piece id and entity pairs, in graph order.
 */
function better_schema_collect_piece_entries( array $pieces, array $context ): array {
	$entries = array();

	foreach ( $pieces as $id => $piece ) {
		if ( ! is_callable( $piece ) ) {
			continue;
		}

		foreach ( better_schema_run_piece( (string) $id, $piece, $context ) as $entity ) {
			$entries[] = array( (string) $id, $entity );
		}
	}

	return $entries;
}

/**
 * Runs one piece through its `better_schema_piece_{id}` filter.
 *
 * @return array<int, array> Non-empty entities.
 */
function better_schema_run_piece( string $id, callable $piece, array $context ): array {
	$entities = apply_filters( "better_schema_piece_{$id}", $piece( $context ), $context );

	return is_array( $entities ) ? array_values( array_filter( $entities ) ) : array();
}
