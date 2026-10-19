<?php
/**
 * Site identity settings: who is behind the site (an organization or a person).
 *
 * Domain logic only. The option is registered in includes/settings.php.
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Identity types a site can have.
 *
 * @return string[]
 */
function better_schema_identity_types(): array {
	return array( 'Organization', 'Person' );
}

/**
 * Default identity settings.
 *
 * An empty name falls back to the site title and an empty logo to the site icon
 * when the identity is described, so a fresh install still outputs useful schema.
 *
 * @return array{type: string, name: string, logo: int, same_as: string[]}
 */
function better_schema_identity_defaults(): array {
	return array(
		'type'    => 'Organization',
		'name'    => '',
		'logo'    => 0,
		'same_as' => array(),
	);
}

/**
 * Makes any stored or submitted value into a valid identity settings array.
 *
 * Missing, malformed or unknown values are replaced by defaults or dropped.
 *
 * @param mixed $value Stored or submitted value.
 * @return array{type: string, name: string, logo: int, same_as: string[]}
 */
function better_schema_sanitize_identity( mixed $value ): array {
	$defaults = better_schema_identity_defaults();

	if ( ! is_array( $value ) ) {
		return $defaults;
	}

	$type = $value['type'] ?? '';
	$name = $value['name'] ?? '';
	$logo = $value['logo'] ?? 0;

	return array(
		'type'    => in_array( $type, better_schema_identity_types(), true ) ? $type : $defaults['type'],
		'name'    => is_string( $name ) ? sanitize_text_field( $name ) : $defaults['name'],
		'logo'    => is_numeric( $logo ) ? absint( $logo ) : $defaults['logo'],
		'same_as' => better_schema_sanitize_urls( $value['same_as'] ?? array() ),
	);
}

/**
 * Keeps only valid http(s) URLs, without duplicates.
 *
 * @param mixed $urls List of URLs.
 * @return string[]
 */
function better_schema_sanitize_urls( mixed $urls ): array {
	if ( ! is_array( $urls ) ) {
		return array();
	}

	$clean = array();

	foreach ( $urls as $url ) {
		if ( ! is_string( $url ) ) {
			continue;
		}

		$url = trim( $url );

		// esc_url_raw() would turn "not a url" into "http://not%20a%20url", so require a scheme first.
		if ( ! preg_match( '#^https?://#i', $url ) ) {
			continue;
		}

		$url = esc_url_raw( $url, array( 'http', 'https' ) );

		if ( '' !== $url && wp_parse_url( $url, PHP_URL_HOST ) ) {
			$clean[] = $url;
		}
	}

	return array_values( array_unique( $clean ) );
}

/**
 * Returns the saved identity settings, always valid.
 *
 * @return array{type: string, name: string, logo: int, same_as: string[]}
 */
function better_schema_get_identity(): array {
	return better_schema_sanitize_identity( get_option( 'better_schema_identity', better_schema_identity_defaults() ) );
}

/**
 * Stable `@id` of the identity entity, so other entities can refer to it.
 *
 * @return string
 */
function better_schema_identity_id(): string {
	return better_schema_entity_id( home_url( '/' ), 'identity' );
}
