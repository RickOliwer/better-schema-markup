<?php
/**
 * Event schema piece.
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Describes a post whose schema type is Event.
 *
 * A missing start date is left out, not guessed. The validation hints flag it.
 *
 * @param array $context Context.
 * @return array List with one entity, or an empty array.
 */
function better_schema_piece_event( array $context ): array {
	$post = $context['post'] ?? null;

	if ( 'post' !== ( $context['type'] ?? '' ) || ! $post instanceof WP_Post || 'Event' !== better_schema_post_schema_type( $post ) ) {
		return array();
	}

	$url = get_permalink( $post );

	if ( ! $url ) {
		return array();
	}

	$details = better_schema_get_event( $post );
	$options = better_schema_event_options();
	$webpage = array( '@id' => better_schema_webpage_id( $context ) );

	$entity = array(
		'@type'                => 'Event',
		'@id'                  => better_schema_entity_id( $url, 'event' ),
		'mainEntityOfPage'     => $webpage,
		'url'                  => $url,
		'eventAttendanceMode'  => $options['attendance'][ $details['attendance'] ],
		'eventStatus'          => $options['status'][ $details['status'] ],
		'organizer'            => array( '@id' => better_schema_identity_id() ),
	);

	$name = better_schema_plain_text( get_the_title( $post ) );
	if ( '' !== $name ) {
		$entity['name'] = $name;
	}

	$description = better_schema_plain_text( get_the_excerpt( $post ) );
	if ( '' !== $description ) {
		$entity['description'] = $description;
	}

	foreach ( array( 'start' => 'startDate', 'end' => 'endDate' ) as $key => $property ) {
		if ( '' !== $details[ $key ] ) {
			$entity[ $property ] = $details[ $key ];
		}
	}

	$image = get_the_post_thumbnail_url( $post, 'full' );
	if ( $image ) {
		$entity['image'] = $image;
	}

	$locations = array();
	if ( 'offline' !== $details['attendance'] ) {
		$locations[] = array(
			'@type' => 'VirtualLocation',
			'url'   => $url,
		);
	}
	if ( 'online' !== $details['attendance'] && ( '' !== $details['location_name'] || '' !== $details['location_address'] ) ) {
		$place = array( '@type' => 'Place' );

		if ( '' !== $details['location_name'] ) {
			$place['name'] = $details['location_name'];
		}
		if ( '' !== $details['location_address'] ) {
			$place['address'] = $details['location_address'];
		}

		$locations[] = $place;
	}
	if ( $locations ) {
		$entity['location'] = 1 === count( $locations ) ? $locations[0] : $locations;
	}

	if ( '' !== $details['offers_url'] ) {
		$entity['offers'] = array(
			'@type' => 'Offer',
			'url'   => $details['offers_url'],
		);
	}

	return array( $entity );
}
