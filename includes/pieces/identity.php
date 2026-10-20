<?php
/**
 * Site identity schema piece (Organization or Person).
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Finds the first usable logo image: the saved logo, then the site icon.
 *
 * @param array $identity Identity settings from `better_schema_get_identity()`.
 * @return array|null ImageObject entity, or null when no image is available.
 */
function better_schema_identity_logo( array $identity ): ?array {
	$candidates = array_filter( array( $identity['logo'], (int) get_option( 'site_icon' ) ) );

	foreach ( $candidates as $attachment_id ) {
		if ( ! wp_attachment_is_image( $attachment_id ) ) {
			continue;
		}

		$image = wp_get_attachment_image_src( $attachment_id, 'full' );

		if ( ! $image ) {
			continue;
		}

		$logo = array(
			'@type'      => 'ImageObject',
			'url'        => $image[0],
			'contentUrl' => $image[0],
		);

		if ( $image[1] && $image[2] ) {
			$logo['width']  = (int) $image[1];
			$logo['height'] = (int) $image[2];
		}

		return $logo;
	}

	return null;
}

/**
 * Describes who is behind the site as an Organization or a Person.
 *
 * The identity is the same whatever the context is, so the context is not used.
 * Other pieces refer to this entity through `better_schema_identity_id()`.
 *
 * @param array $context Context from `better_schema_post_context()`.
 * @return array List with one entity.
 */
function better_schema_piece_identity( array $context ): array {
	$identity = better_schema_get_identity();

	$entity = array(
		'@type' => $identity['type'],
		'@id'   => better_schema_identity_id(),
		'url'   => home_url( '/' ),
	);

	$name = better_schema_plain_text( '' !== $identity['name'] ? $identity['name'] : get_bloginfo( 'name' ) );
	if ( '' !== $name ) {
		$entity['name'] = $name;
	}

	$logo = better_schema_identity_logo( $identity );
	if ( $logo ) {
		// Organization has `logo`; Person only has `image`.
		$entity['Organization' === $identity['type'] ? 'logo' : 'image'] = $logo;
	}

	if ( $identity['same_as'] ) {
		$entity['sameAs'] = $identity['same_as'];
	}

	// A person cannot be a LocalBusiness, so the business settings only apply to organizations.
	$business = better_schema_get_business();
	if ( 'Organization' === $identity['type'] && $business['enabled'] ) {
		$entity['@type'] = 'LocalBusiness';
		$entity          = array_merge( $entity, better_schema_business_properties( $business ) );
	}

	return array( $entity );
}
