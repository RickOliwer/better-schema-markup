<?php
/**
 * Local business settings: an extension of the site identity.
 *
 * When enabled for an Organization, the identity entity becomes a LocalBusiness
 * with an address, opening hours and a location. Stored in its own option so the
 * identity option keeps its shape. The option is registered in includes/data/settings.php.
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * @return string[]
 */
function better_schema_weekdays(): array {
	return array( 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday' );
}

/**
 * @return array{enabled: bool, telephone: string, price_range: string, address: array<string, string>, geo: array{lat: string, lng: string}, hours: array<int, array{days: string[], opens: string, closes: string}>}
 */
function better_schema_business_defaults(): array {
	return array(
		'enabled'     => false,
		'telephone'   => '',
		'price_range' => '',
		'address'     => array(
			'street'      => '',
			'locality'    => '',
			'region'      => '',
			'postal_code' => '',
			'country'     => '',
		),
		'geo'         => array(
			'lat' => '',
			'lng' => '',
		),
		'hours'       => array(),
	);
}

/**
 * A coordinate as a string within range, or an empty string.
 *
 * @param mixed $value Submitted value.
 * @param float $limit Largest absolute value allowed.
 */
function better_schema_sanitize_coordinate( mixed $value, float $limit ): string {
	if ( ! is_numeric( $value ) || abs( (float) $value ) > $limit ) {
		return '';
	}

	return (string) (float) $value;
}

/**
 * A time as `HH:MM`, or an empty string.
 *
 * @param mixed $value Submitted value.
 */
function better_schema_sanitize_time( mixed $value ): string {
	return is_string( $value ) && preg_match( '/^([01]\d|2[0-3]):[0-5]\d$/', $value ) ? $value : '';
}

/**
 * Makes any stored or submitted value into valid business settings.
 *
 * Opening hours with no valid day or time are dropped.
 *
 * @param mixed $value Stored or submitted value.
 * @return array See `better_schema_business_defaults()`.
 */
function better_schema_sanitize_business( mixed $value ): array {
	$defaults = better_schema_business_defaults();

	if ( ! is_array( $value ) ) {
		return $defaults;
	}

	$text    = static fn ( mixed $item ): string => is_string( $item ) ? sanitize_text_field( $item ) : '';
	$address = is_array( $value['address'] ?? null ) ? $value['address'] : array();
	$geo     = is_array( $value['geo'] ?? null ) ? $value['geo'] : array();
	$hours   = array();

	foreach ( is_array( $value['hours'] ?? null ) ? $value['hours'] : array() as $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}

		$days   = is_array( $row['days'] ?? null ) ? array_values( array_intersect( better_schema_weekdays(), $row['days'] ) ) : array();
		$opens  = better_schema_sanitize_time( $row['opens'] ?? '' );
		$closes = better_schema_sanitize_time( $row['closes'] ?? '' );

		if ( $days && '' !== $opens && '' !== $closes ) {
			$hours[] = array(
				'days'   => $days,
				'opens'  => $opens,
				'closes' => $closes,
			);
		}
	}

	$clean_address = array();
	foreach ( array_keys( $defaults['address'] ) as $key ) {
		$clean_address[ $key ] = $text( $address[ $key ] ?? '' );
	}

	$lat = better_schema_sanitize_coordinate( $geo['lat'] ?? '', 90 );
	$lng = better_schema_sanitize_coordinate( $geo['lng'] ?? '', 180 );

	return array(
		'enabled'     => true === ( $value['enabled'] ?? false ),
		'telephone'   => $text( $value['telephone'] ?? '' ),
		'price_range' => $text( $value['price_range'] ?? '' ),
		'address'     => $clean_address,
		// A single coordinate is meaningless, so keep both or neither.
		'geo'         => '' !== $lat && '' !== $lng ? array( 'lat' => $lat, 'lng' => $lng ) : $defaults['geo'],
		'hours'       => $hours,
	);
}

/**
 * Returns the saved business settings, always valid.
 */
function better_schema_get_business(): array {
	return better_schema_sanitize_business( get_option( 'better_schema_business', better_schema_business_defaults() ) );
}

/**
 * Describes the business settings as schema.org properties.
 *
 * @param array $business Settings from `better_schema_get_business()`.
 * @return array Properties to merge into the identity entity; empty values are omitted.
 */
function better_schema_business_properties( array $business ): array {
	$properties = array();

	$address = array_filter(
		array(
			'streetAddress'   => $business['address']['street'],
			'addressLocality' => $business['address']['locality'],
			'addressRegion'   => $business['address']['region'],
			'postalCode'      => $business['address']['postal_code'],
			'addressCountry'  => $business['address']['country'],
		),
		static fn ( string $part ): bool => '' !== $part
	);
	if ( $address ) {
		$properties['address'] = array( '@type' => 'PostalAddress' ) + $address;
	}

	if ( '' !== $business['geo']['lat'] ) {
		$properties['geo'] = array(
			'@type'     => 'GeoCoordinates',
			'latitude'  => (float) $business['geo']['lat'],
			'longitude' => (float) $business['geo']['lng'],
		);
	}

	if ( '' !== $business['telephone'] ) {
		$properties['telephone'] = $business['telephone'];
	}

	if ( '' !== $business['price_range'] ) {
		$properties['priceRange'] = $business['price_range'];
	}

	if ( $business['hours'] ) {
		$properties['openingHoursSpecification'] = array_map(
			static fn ( array $row ): array => array(
				'@type'     => 'OpeningHoursSpecification',
				'dayOfWeek' => $row['days'],
				'opens'     => $row['opens'],
				'closes'    => $row['closes'],
			),
			$business['hours']
		);
	}

	return $properties;
}
