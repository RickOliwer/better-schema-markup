<?php
/**
 * Validation hints: properties Google requires or recommends for rich results
 * that are missing from a built graph.
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Properties to check per entity type.
 *
 * Google lists no required properties for Article or Organization, only
 * recommended ones, so every rule here is `recommended`.
 *
 * @return array<string, array<string, array{level: string, message: string}>> Map of `@type` to property to rule.
 */
function better_schema_hint_rules(): array {
	$article = array(
		'headline' => array(
			'level'   => 'recommended',
			'message' => __( 'Add a title. Google recommends a headline for articles.', 'better-schema-markup' ),
		),
		'image'    => array(
			'level'   => 'recommended',
			'message' => __( 'Add a featured image. Google recommends an image for articles.', 'better-schema-markup' ),
		),
		'author'   => array(
			'level'   => 'recommended',
			'message' => __( 'Set an author. Google recommends an author for articles.', 'better-schema-markup' ),
		),
	);

	return array(
		'Article'      => $article,
		'BlogPosting'  => $article,
		'Organization' => array(
			'logo' => array(
				'level'   => 'recommended',
				'message' => __( 'Add a site icon or logo. Google recommends a logo for organizations.', 'better-schema-markup' ),
			),
		),
	);
}

/**
 * Lists the properties Google requires or recommends that the document is missing.
 *
 * Filter `better_schema_validation_hints` receives the list of hints and the
 * document, and returns a list of hints.
 *
 * @param array $document Document from `better_schema_build_graph()`.
 * @return array<int, array{type: string, property: string, level: string, message: string}>
 */
function better_schema_validation_hints( array $document ): array {
	$rules = better_schema_hint_rules();
	$hints = array();

	foreach ( $document['@graph'] ?? array() as $entity ) {
		$type = is_array( $entity ) ? ( $entity['@type'] ?? null ) : null;

		if ( ! is_string( $type ) || ! isset( $rules[ $type ] ) ) {
			continue;
		}

		foreach ( $rules[ $type ] as $property => $rule ) {
			if ( empty( $entity[ $property ] ) ) {
				$hints[] = array(
					'type'     => $type,
					'property' => $property,
					'level'    => $rule['level'],
					'message'  => $rule['message'],
				);
			}
		}
	}

	$hints = apply_filters( 'better_schema_validation_hints', $hints, $document );

	return is_array( $hints ) ? array_values( $hints ) : array();
}
