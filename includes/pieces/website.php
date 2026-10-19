<?php
/**
 * WebSite schema piece.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Describes the site as a WebSite.
 *
 * The site is the same whatever the context is, so the context is not used.
 * Other pieces refer to this entity through its `@id`.
 *
 * @param array $context Context from `better_schema_post_context()`.
 * @return array List with one entity.
 */
function better_schema_piece_website( array $context ) {
	$home = home_url( '/' );

	$entity = array(
		'@type' => 'WebSite',
		'@id'   => better_schema_entity_id( $home, 'website' ),
		'url'   => $home,
	);

	// Site options are stored HTML-escaped; JSON-LD wants plain text.
	$name = trim( wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) );
	if ( '' !== $name ) {
		$entity['name'] = $name;
	}

	$description = trim( wp_specialchars_decode( get_bloginfo( 'description' ), ENT_QUOTES ) );
	if ( '' !== $description ) {
		$entity['description'] = $description;
	}

	$language = get_bloginfo( 'language' );
	if ( $language ) {
		$entity['inLanguage'] = $language;
	}

	return array( $entity );
}
