<?php
/**
 * WebSite schema piece.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Stable `@id` of the WebSite entity, so other entities can refer to it.
 *
 * @return string
 */
function better_schema_website_id() {
	return better_schema_entity_id( home_url( '/' ), 'website' );
}

/**
 * Describes the site as a WebSite.
 *
 * The site is the same whatever the context is, so the context is not used.
 *
 * @param array $context Context.
 * @return array List with one entity.
 */
function better_schema_piece_website( array $context ) {
	$entity = array(
		'@type'     => 'WebSite',
		'@id'       => better_schema_website_id(),
		'url'       => home_url( '/' ),
		'publisher' => array( '@id' => better_schema_identity_id() ),
	);

	$name = better_schema_plain_text( get_bloginfo( 'name' ) );
	if ( '' !== $name ) {
		$entity['name'] = $name;
	}

	$description = better_schema_plain_text( get_bloginfo( 'description' ) );
	if ( '' !== $description ) {
		$entity['description'] = $description;
	}

	$language = get_bloginfo( 'language' );
	if ( $language ) {
		$entity['inLanguage'] = $language;
	}

	return array( $entity );
}
