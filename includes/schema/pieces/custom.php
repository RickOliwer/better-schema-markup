<?php
/**
 * Custom JSON-LD piece: entities an advanced user wrote for a single post.
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Adds the post's custom JSON-LD entities to the graph.
 *
 * The JSON was validated when saved and is re-validated here, because stored
 * meta is untrusted. It is encoded with the rest of the graph, so it cannot
 * break out of the script tag.
 *
 * @param array $context Context.
 * @return array List of entities, or an empty array.
 */
function better_schema_piece_custom( array $context ): array {
	$post = $context['post'] ?? null;

	if ( 'post' !== ( $context['type'] ?? '' ) || ! $post instanceof WP_Post ) {
		return array();
	}

	return better_schema_get_custom_entities( $post );
}
