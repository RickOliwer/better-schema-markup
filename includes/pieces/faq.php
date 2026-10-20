<?php
/**
 * FAQPage schema piece.
 *
 * Google only shows FAQ rich results for well-known government and health
 * sites, but the markup is still valid for everyone else.
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Describes a post whose schema type is FAQPage.
 *
 * @param array $context Context.
 * @return array List with one entity, or an empty array.
 */
function better_schema_piece_faq( array $context ): array {
	$post = $context['post'] ?? null;

	if ( 'post' !== ( $context['type'] ?? '' ) || ! $post instanceof WP_Post || 'FAQPage' !== better_schema_post_schema_type( $post ) ) {
		return array();
	}

	$url = get_permalink( $post );

	if ( ! $url ) {
		return array();
	}

	$entity = array(
		'@type'            => 'FAQPage',
		'@id'              => better_schema_entity_id( $url, 'faq' ),
		'mainEntityOfPage' => array( '@id' => better_schema_webpage_id( $context ) ),
		'url'              => $url,
	);

	$name = better_schema_plain_text( get_the_title( $post ) );
	if ( '' !== $name ) {
		$entity['name'] = $name;
	}

	$questions = array_map(
		static fn ( array $row ): array => array(
			'@type'          => 'Question',
			'name'           => $row['question'],
			'acceptedAnswer' => array(
				'@type' => 'Answer',
				'text'  => $row['answer'],
			),
		),
		better_schema_get_faq( $post )
	);
	if ( $questions ) {
		$entity['mainEntity'] = $questions;
	}

	return array( $entity );
}
