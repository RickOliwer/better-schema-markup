<?php
/**
 * WebPage schema piece: the page being viewed.
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Stable `@id` of the page entity for a context.
 *
 * @param array $context Context.
 * @return string The `@id`, or an empty string when the context has no URL.
 */
function better_schema_webpage_id( array $context ): string {
	$url = better_schema_context_url( $context );

	return '' === $url ? '' : better_schema_entity_id( $url, 'webpage' );
}

/**
 * The schema.org page type for a context.
 *
 * @param array $context Context.
 * @return string
 */
function better_schema_webpage_type( array $context ): string {
	return match ( $context['type'] ?? '' ) {
		'author' => 'ProfilePage',
		'search' => 'SearchResultsPage',
		'home', 'blog', 'term', 'post_type_archive', 'date' => 'CollectionPage',
		default => 'WebPage',
	};
}

/**
 * Describes the page being viewed and links it to the site.
 *
 * @param array $context Context.
 * @return array List with one entity, or an empty array when the context has no URL.
 */
function better_schema_piece_webpage( array $context ): array {
	$url = better_schema_context_url( $context );

	if ( '' === $url ) {
		return array();
	}

	$entity = array(
		'@type'    => better_schema_webpage_type( $context ),
		'@id'      => better_schema_webpage_id( $context ),
		'url'      => $url,
		'isPartOf' => array( '@id' => better_schema_website_id() ),
	);

	$name = better_schema_context_name( $context );
	if ( '' !== $name ) {
		$entity['name'] = $name;
	}

	$post = $context['post'] ?? null;
	if ( 'post' === ( $context['type'] ?? '' ) && $post instanceof WP_Post ) {
		$entity['datePublished'] = get_the_date( 'c', $post );
		$entity['dateModified']  = get_the_modified_date( 'c', $post );
	}

	if ( better_schema_context_is_front_page( $context ) ) {
		$entity['about'] = array( '@id' => better_schema_identity_id() );
	}

	$user = $context['user'] ?? null;
	if ( 'author' === ( $context['type'] ?? '' ) && $user instanceof WP_User ) {
		$entity['mainEntity'] = array( '@id' => better_schema_person_id( $user ) );
	}

	if ( count( better_schema_breadcrumb_items( $context ) ) >= 2 ) {
		$entity['breadcrumb'] = array( '@id' => better_schema_breadcrumb_id( $context ) );
	}

	$language = get_bloginfo( 'language' );
	if ( $language ) {
		$entity['inLanguage'] = $language;
	}

	return array( $entity );
}
