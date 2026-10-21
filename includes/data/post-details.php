<?php
/**
 * Per-post details for the Event, Recipe and FAQPage types, and the custom JSON-LD field.
 *
 * Each is a single post meta value with a REST schema. Stored meta is untrusted,
 * so the getters always return a valid value.
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Event attendance modes and statuses, keyed by the stored value.
 *
 * @return array{attendance: array<string, string>, status: array<string, string>}
 */
function better_schema_event_options(): array {
	return array(
		'attendance' => array(
			'offline' => 'https://schema.org/OfflineEventAttendanceMode',
			'online'  => 'https://schema.org/OnlineEventAttendanceMode',
			'mixed'   => 'https://schema.org/MixedEventAttendanceMode',
		),
		'status'     => array(
			'scheduled'    => 'https://schema.org/EventScheduled',
			'cancelled'    => 'https://schema.org/EventCancelled',
			'postponed'    => 'https://schema.org/EventPostponed',
			'rescheduled'  => 'https://schema.org/EventRescheduled',
			'moved_online' => 'https://schema.org/EventMovedOnline',
		),
	);
}

function better_schema_event_defaults(): array {
	return array(
		'start'            => '',
		'end'              => '',
		'attendance'       => 'offline',
		'status'           => 'scheduled',
		'location_name'    => '',
		'location_address' => '',
		'offers_url'       => '',
	);
}

/**
 * A date or date and time in ISO 8601 (`2026-12-31` or `2026-12-31T19:00` with an optional offset), or an empty string.
 *
 * @param mixed $value Submitted value.
 */
function better_schema_sanitize_datetime( mixed $value ): string {
	if ( ! is_string( $value ) ) {
		return '';
	}

	$value = trim( $value );

	if ( ! preg_match( '/^(\d{4})-(\d{2})-(\d{2})(T([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?(Z|[+-]([01]\d|2[0-3]):[0-5]\d)?)?$/', $value, $parts ) ) {
		return '';
	}

	return checkdate( (int) $parts[2], (int) $parts[3], (int) $parts[1] ) ? $value : '';
}

function better_schema_sanitize_event( mixed $value ): array {
	$defaults = better_schema_event_defaults();
	$options  = better_schema_event_options();

	if ( ! is_array( $value ) ) {
		return $defaults;
	}

	$text = static fn ( mixed $item ): string => is_string( $item ) ? sanitize_text_field( $item ) : '';

	$url = $value['offers_url'] ?? '';
	$url = is_string( $url ) && preg_match( '#^https?://#i', trim( $url ) ) ? esc_url_raw( trim( $url ), array( 'http', 'https' ) ) : '';

	return array(
		'start'            => better_schema_sanitize_datetime( $value['start'] ?? '' ),
		'end'              => better_schema_sanitize_datetime( $value['end'] ?? '' ),
		'attendance'       => in_array( $value['attendance'] ?? null, array_keys( $options['attendance'] ), true ) ? $value['attendance'] : $defaults['attendance'],
		'status'           => in_array( $value['status'] ?? null, array_keys( $options['status'] ), true ) ? $value['status'] : $defaults['status'],
		'location_name'    => $text( $value['location_name'] ?? '' ),
		'location_address' => $text( $value['location_address'] ?? '' ),
		'offers_url'       => $url,
	);
}

function better_schema_recipe_defaults(): array {
	return array(
		'ingredients'  => array(),
		'instructions' => array(),
		'prep_minutes' => 0,
		'cook_minutes' => 0,
		'yield'        => '',
		'calories'     => 0,
	);
}

/**
 * Keeps non-empty plain-text strings.
 *
 * @param mixed $items Submitted list.
 * @return string[]
 */
function better_schema_sanitize_text_list( mixed $items ): array {
	if ( ! is_array( $items ) ) {
		return array();
	}

	$clean = array();
	foreach ( $items as $item ) {
		$item = is_string( $item ) ? sanitize_textarea_field( $item ) : '';

		if ( '' !== $item ) {
			$clean[] = $item;
		}
	}

	return $clean;
}

function better_schema_sanitize_recipe( mixed $value ): array {
	$defaults = better_schema_recipe_defaults();

	if ( ! is_array( $value ) ) {
		return $defaults;
	}

	$whole = static fn ( mixed $item ): int => is_numeric( $item ) && $item > 0 ? min( (int) $item, 100000 ) : 0;

	return array(
		'ingredients'  => better_schema_sanitize_text_list( $value['ingredients'] ?? array() ),
		'instructions' => better_schema_sanitize_text_list( $value['instructions'] ?? array() ),
		'prep_minutes' => $whole( $value['prep_minutes'] ?? 0 ),
		'cook_minutes' => $whole( $value['cook_minutes'] ?? 0 ),
		'yield'        => is_string( $value['yield'] ?? null ) ? sanitize_text_field( $value['yield'] ) : '',
		'calories'     => $whole( $value['calories'] ?? 0 ),
	);
}

/**
 * Keeps question and answer pairs where both are filled in.
 *
 * @param mixed $value Submitted list.
 * @return array<int, array{question: string, answer: string}>
 */
function better_schema_sanitize_faq( mixed $value ): array {
	if ( ! is_array( $value ) ) {
		return array();
	}

	$clean = array();
	foreach ( $value as $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}

		$question = is_string( $row['question'] ?? null ) ? sanitize_text_field( $row['question'] ) : '';
		$answer   = is_string( $row['answer'] ?? null ) ? sanitize_textarea_field( $row['answer'] ) : '';

		if ( '' !== $question && '' !== $answer ) {
			$clean[] = array(
				'question' => $question,
				'answer'   => $answer,
			);
		}
	}

	return $clean;
}

/**
 * Parses custom JSON-LD into a list of entities.
 *
 * Accepts one entity, a list of entities or a document with `@graph`. Anything
 * else, including invalid JSON, gives an empty list.
 *
 * @param mixed $json Stored or submitted JSON string.
 * @return array<int, array> Entities, each with an `@type`.
 */
function better_schema_parse_custom_jsonld( mixed $json ): array {
	if ( ! is_string( $json ) || '' === trim( $json ) ) {
		return array();
	}

	$data = json_decode( $json, true );

	if ( ! is_array( $data ) ) {
		return array();
	}

	if ( isset( $data['@graph'] ) && is_array( $data['@graph'] ) ) {
		$data = $data['@graph'];
	} elseif ( isset( $data['@type'] ) ) {
		$data = array( $data );
	}

	$entities = array();
	foreach ( $data as $entity ) {
		if ( is_array( $entity ) && isset( $entity['@type'] ) ) {
			unset( $entity['@context'] );
			$entities[] = $entity;
		}
	}

	return $entities;
}

/**
 * Validates custom JSON-LD before it is saved.
 *
 * Invalid input is replaced by an empty string rather than stored. Valid input
 * is stored re-encoded, so it is normalized.
 *
 * @param mixed $json Submitted JSON string.
 */
function better_schema_sanitize_custom_jsonld( mixed $json ): string {
	$entities = better_schema_parse_custom_jsonld( $json );

	return $entities ? (string) wp_json_encode( $entities, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) : '';
}

/**
 * Reads a post's details, always valid.
 */
function better_schema_get_event( WP_Post $post ): array {
	return better_schema_sanitize_event( get_post_meta( $post->ID, '_better_schema_event', true ) );
}

function better_schema_get_recipe( WP_Post $post ): array {
	return better_schema_sanitize_recipe( get_post_meta( $post->ID, '_better_schema_recipe', true ) );
}

function better_schema_get_faq( WP_Post $post ): array {
	return better_schema_sanitize_faq( get_post_meta( $post->ID, '_better_schema_faq', true ) );
}

function better_schema_get_custom_entities( WP_Post $post ): array {
	return better_schema_parse_custom_jsonld( get_post_meta( $post->ID, '_better_schema_custom_jsonld', true ) );
}

/**
 * Returns a valid custom JSON-LD mode, falling back to `add`.
 *
 * @param mixed $mode Stored or submitted mode.
 */
function better_schema_sanitize_custom_mode( mixed $mode ): string {
	return in_array( $mode, better_schema_custom_modes(), true ) ? $mode : 'add';
}

function better_schema_get_custom_mode( WP_Post $post ): string {
	return better_schema_sanitize_custom_mode( get_post_meta( $post->ID, '_better_schema_custom_mode', true ) );
}

/**
 * The custom JSON-LD mode for a context. Only posts have custom JSON-LD.
 */
function better_schema_context_custom_mode( array $context ): string {
	$post = $context['post'] ?? null;

	return 'post' === ( $context['type'] ?? '' ) && $post instanceof WP_Post ? better_schema_get_custom_mode( $post ) : 'add';
}

/**
 * Registers the detail meta for a post type.
 */
function better_schema_register_detail_meta( string $post_type ): void {
	$options = better_schema_event_options();
	$string  = array( 'type' => 'string' );
	$integer = array( 'type' => 'integer' );
	$strings = array(
		'type'  => 'array',
		'items' => $string,
	);

	$meta = array(
		'_better_schema_event'         => array(
			'type'              => 'object',
			'default'           => better_schema_event_defaults(),
			'sanitize_callback' => 'better_schema_sanitize_event',
			'schema'            => array(
				'type'                 => 'object',
				'properties'           => array(
					'start'            => $string,
					'end'              => $string,
					'attendance'       => array( 'type' => 'string', 'enum' => array_keys( $options['attendance'] ) ),
					'status'           => array( 'type' => 'string', 'enum' => array_keys( $options['status'] ) ),
					'location_name'    => $string,
					'location_address' => $string,
					'offers_url'       => $string,
				),
				'additionalProperties' => false,
			),
		),
		'_better_schema_recipe'        => array(
			'type'              => 'object',
			'default'           => better_schema_recipe_defaults(),
			'sanitize_callback' => 'better_schema_sanitize_recipe',
			'schema'            => array(
				'type'                 => 'object',
				'properties'           => array(
					'ingredients'  => $strings,
					'instructions' => $strings,
					'prep_minutes' => $integer,
					'cook_minutes' => $integer,
					'yield'        => $string,
					'calories'     => $integer,
				),
				'additionalProperties' => false,
			),
		),
		'_better_schema_faq'           => array(
			'type'              => 'array',
			'default'           => array(),
			'sanitize_callback' => 'better_schema_sanitize_faq',
			'schema'            => array(
				'type'  => 'array',
				'items' => array(
					'type'                 => 'object',
					'properties'           => array(
						'question' => $string,
						'answer'   => $string,
					),
					'additionalProperties' => false,
				),
			),
		),
		'_better_schema_custom_jsonld' => array(
			'type'              => 'string',
			'default'           => '',
			'sanitize_callback' => 'better_schema_sanitize_custom_jsonld',
			'schema'            => $string,
		),
		'_better_schema_custom_mode'   => array(
			'type'              => 'string',
			'default'           => 'add',
			'sanitize_callback' => 'better_schema_sanitize_custom_mode',
			'schema'            => array(
				'type' => 'string',
				'enum' => better_schema_custom_modes(),
			),
		),
	);

	foreach ( $meta as $key => $args ) {
		register_post_meta(
			$post_type,
			$key,
			array(
				'type'              => $args['type'],
				'single'            => true,
				'default'           => $args['default'],
				'show_in_rest'      => array( 'schema' => $args['schema'] ),
				'sanitize_callback' => $args['sanitize_callback'],
				'auth_callback'     => fn ( $allowed, $meta_key, $post_id ): bool => current_user_can( 'edit_post', (int) $post_id ),
			)
		);
	}
}
