<?php
/**
 * Article schema piece.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Describes the post as an Article, BlogPosting or WebPage.
 *
 * @param array $context Context from `better_schema_post_context()`.
 * @return array List with one entity, or an empty array when the post opts out.
 */
function better_schema_piece_article( array $context ) {
	$post = $context['post'] ?? null;

	if ( ! $post instanceof WP_Post ) {
		return array();
	}

	// Stored meta is untrusted: a bad or legacy value falls back to the default type.
	$type = better_schema_sanitize_type( get_post_meta( $post->ID, '_better_schema_type', true ) );

	if ( 'none' === $type ) {
		return array();
	}

	$url = get_permalink( $post );

	$entity = array(
		'@type'         => $type,
		'@id'           => better_schema_entity_id( $url, 'article' ),
		'headline'      => wp_strip_all_tags( get_the_title( $post ) ),
		'url'           => $url,
		'datePublished' => get_the_date( 'c', $post ),
		'dateModified'  => get_the_modified_date( 'c', $post ),
	);

	$author = get_the_author_meta( 'display_name', $post->post_author );
	if ( $author ) {
		$entity['author'] = array(
			'@type' => 'Person',
			'name'  => $author,
		);
	}

	$image = get_the_post_thumbnail_url( $post, 'full' );
	if ( $image ) {
		$entity['image'] = $image;
	}

	return array( $entity );
}
