<?php
/**
 * Schema context: what is being described.
 *
 * Contexts are plain arrays passed explicitly to the graph builder and the
 * schema pieces. They never read request globals.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Builds the context for describing a single post or page.
 *
 * @param WP_Post $post The post being described.
 * @return array{type: string, post: WP_Post}
 */
function better_schema_post_context( WP_Post $post ) {
	return array(
		'type' => 'post',
		'post' => $post,
	);
}

/**
 * Builds a stable entity `@id` so other entities can refer to it.
 *
 * @param string $url      Canonical URL of the page the entity belongs to.
 * @param string $fragment Entity name, e.g. `article`.
 * @return string
 */
function better_schema_entity_id( $url, $fragment ) {
	return $url . '#' . $fragment;
}

/**
 * Tells whether the author turned schema off for what the context describes.
 *
 * Stored meta is untrusted, so an invalid value is treated as "not opted out".
 *
 * @param array $context Context from `better_schema_post_context()`.
 * @return bool
 */
function better_schema_context_opted_out( array $context ) {
	$post = $context['post'] ?? null;

	if ( ! $post instanceof WP_Post ) {
		return false;
	}

	return 'none' === better_schema_sanitize_type( get_post_meta( $post->ID, '_better_schema_type', true ) );
}
