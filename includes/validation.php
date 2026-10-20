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
 * Google lists no required properties for Article, Organization, LocalBusiness
 * or Product (without a review or offer), only recommended ones. Events,
 * recipes and FAQ pages have required properties.
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

	$rule = static fn ( string $level, string $message ): array => array(
		'level'   => $level,
		'message' => $message,
	);

	return array(
		'Event'         => array(
			'name'      => $rule( 'required', __( 'Add a title. Google requires a name for events.', 'better-schema-markup' ) ),
			'startDate' => $rule( 'required', __( 'Set a start date. Google requires one for events.', 'better-schema-markup' ) ),
			'location'  => $rule( 'required', __( 'Add a location. Google requires one for events.', 'better-schema-markup' ) ),
			'image'     => $rule( 'recommended', __( 'Add a featured image. Google recommends an image for events.', 'better-schema-markup' ) ),
		),
		'Recipe'        => array(
			'name'               => $rule( 'required', __( 'Add a title. Google requires a name for recipes.', 'better-schema-markup' ) ),
			'image'              => $rule( 'required', __( 'Add a featured image. Google requires an image for recipes.', 'better-schema-markup' ) ),
			'recipeIngredient'   => $rule( 'recommended', __( 'List the ingredients. Google recommends them for recipes.', 'better-schema-markup' ) ),
			'recipeInstructions' => $rule( 'recommended', __( 'Add the steps. Google recommends instructions for recipes.', 'better-schema-markup' ) ),
		),
		'FAQPage'       => array(
			'mainEntity' => $rule( 'required', __( 'Add at least one question with an answer. Google requires them for FAQ pages.', 'better-schema-markup' ) ),
		),
		'LocalBusiness' => array(
			'address' => $rule( 'recommended', __( 'Add an address in Settings → Better Schema. Google recommends one for local businesses.', 'better-schema-markup' ) ),
		),
		'Product'       => array(
			'image'  => $rule( 'recommended', __( 'Add a product image. Google recommends an image for products.', 'better-schema-markup' ) ),
			'offers' => $rule( 'recommended', __( 'Set a price. Google recommends an offer for products.', 'better-schema-markup' ) ),
		),
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
