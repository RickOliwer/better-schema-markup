<?php
/**
 * Article schema piece.
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Tells whether a post is described as an Article or BlogPosting.
 *
 * @param WP_Post $post Post.
 * @return bool
 */
function better_schema_post_is_article( WP_Post $post ): bool {
	return in_array( better_schema_post_schema_type( $post ), array( 'Article', 'BlogPosting' ), true );
}

/**
 * Describes a post as an Article or BlogPosting on its WebPage.
 *
 * Posts whose schema type is WebPage are described by the WebPage piece only.
 *
 * @param array $context Context.
 * @return array List with one entity, or an empty array.
 */
function better_schema_piece_article( array $context ): array {
	$post = $context['post'] ?? null;

	if ( 'post' !== ( $context['type'] ?? '' ) || ! $post instanceof WP_Post || ! better_schema_post_is_article( $post ) ) {
		return array();
	}

	$url = get_permalink( $post );

	if ( ! $url ) {
		return array();
	}

	$webpage = array( '@id' => better_schema_webpage_id( $context ) );

	$entity = array(
		'@type'            => better_schema_post_schema_type( $post ),
		'@id'              => better_schema_entity_id( $url, 'article' ),
		'isPartOf'         => $webpage,
		'mainEntityOfPage' => $webpage,
		'url'              => $url,
		'datePublished'    => get_the_date( 'c', $post ),
		'dateModified'     => get_the_modified_date( 'c', $post ),
		'publisher'        => array( '@id' => better_schema_identity_id() ),
	);

	$headline = better_schema_plain_text( get_the_title( $post ) );
	if ( '' !== $headline ) {
		$entity['headline'] = $headline;
	}

	$author = better_schema_post_author( $post );
	if ( $author ) {
		$entity['author'] = array( '@id' => better_schema_person_id( $author ) );
	}

	$image = get_the_post_thumbnail_url( $post, 'full' );
	if ( $image ) {
		$entity['image'] = $image;
	}

	$language = get_bloginfo( 'language' );
	if ( $language ) {
		$entity['inLanguage'] = $language;
	}

	return array( $entity );
}
