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
 * With a context, also checks how the post's custom JSON-LD fits the
 * generated schema (see `better_schema_custom_jsonld_hints()`).
 *
 * Filter `better_schema_validation_hints` receives the list of hints, the
 * document and the context, and returns a list of hints.
 *
 * @param array $document Document from `better_schema_build_graph()`.
 * @param array $context  Context the document was built from, if known.
 * @return array<int, array{type: string, property: string, level: string, message: string}>
 */
function better_schema_validation_hints( array $document, array $context = array() ): array {
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

	if ( $context ) {
		$hints = array_merge( $hints, better_schema_custom_jsonld_hints( $document, $context ) );
	}

	$hints = apply_filters( 'better_schema_validation_hints', $hints, $document, $context );

	return is_array( $hints ) ? array_values( $hints ) : array();
}

/**
 * Hints about a post's custom JSON-LD:
 *
 * - in `add` mode, a custom entity that duplicates a generated one, such as a
 *   second WebSite or a second publisher, which `override` mode would replace; and
 * - a custom `@id` or `url` on another domain than the site's.
 *
 * @param array $document Document from `better_schema_build_graph()`.
 * @param array $context  Context the document was built from.
 * @return array<int, array{type: string, property: string, level: string, message: string}>
 */
function better_schema_custom_jsonld_hints( array $document, array $context ): array {
	$post = $context['post'] ?? null;

	if ( 'post' !== ( $context['type'] ?? '' ) || ! $post instanceof WP_Post ) {
		return array();
	}

	$custom = better_schema_get_custom_entities( $post );

	if ( ! $custom ) {
		return array();
	}

	$hints = array();

	if ( 'add' === better_schema_get_custom_mode( $post ) ) {
		foreach ( better_schema_duplicated_custom_types( $document, $custom ) as $type ) {
			$hints[] = array(
				'type'     => $type,
				'property' => '@type',
				'level'    => 'recommended',
				/* translators: %s: schema.org type, such as WebSite. */
				'message'  => sprintf( __( 'Your custom JSON-LD adds a second %s. Choose “Override matching schema” to replace the generated one.', 'better-schema-markup' ), $type ),
			);
		}
	}

	$site_host = wp_parse_url( better_schema_public_url( home_url( '/' ) ), PHP_URL_HOST );

	foreach ( $custom as $entity ) {
		foreach ( array( '@id', 'url' ) as $property ) {
			$host = is_string( $entity[ $property ] ?? null ) ? wp_parse_url( $entity[ $property ], PHP_URL_HOST ) : null;

			if ( is_string( $host ) && is_string( $site_host ) && strtolower( $host ) !== strtolower( $site_host ) ) {
				$hints[] = array(
					'type'     => 'custom',
					'property' => '@id',
					'level'    => 'recommended',
					/* translators: 1: domain in the custom JSON-LD, 2: the site's domain. */
					'message'  => sprintf( __( 'Your custom JSON-LD uses %1$s, but this site is %2$s. Use the site’s own URLs for @id and url.', 'better-schema-markup' ), $host, $site_host ),
				);

				return $hints;
			}
		}
	}

	return $hints;
}

/**
 * Types of custom entities that share an `@id` or a role with another entity in the document.
 *
 * @param array             $document Document from `better_schema_build_graph()`.
 * @param array<int, array> $custom   The post's custom entities.
 * @return string[] Types of the duplicating custom entities, without repeats.
 */
function better_schema_duplicated_custom_types( array $document, array $custom ): array {
	$role_types = better_schema_custom_role_types();
	$id_counts  = array();
	$role_count = array();

	foreach ( $document['@graph'] ?? array() as $entity ) {
		if ( ! is_array( $entity ) ) {
			continue;
		}

		if ( is_string( $entity['@id'] ?? null ) ) {
			$id_counts[ $entity['@id'] ] = ( $id_counts[ $entity['@id'] ] ?? 0 ) + 1;
		}

		$role = better_schema_custom_entity_role( $entity, $role_types );
		if ( null !== $role ) {
			$role_count[ $role ] = ( $role_count[ $role ] ?? 0 ) + 1;
		}
	}

	$types = array();

	foreach ( $custom as $entity ) {
		$id   = $entity['@id'] ?? null;
		$role = better_schema_custom_entity_role( $entity, $role_types );
		$type = is_array( $entity['@type'] ) ? reset( $entity['@type'] ) : $entity['@type'];

		if ( ! is_string( $type ) ) {
			continue;
		}

		if ( ( is_string( $id ) && ( $id_counts[ better_schema_public_url( $id ) ] ?? $id_counts[ $id ] ?? 0 ) > 1 ) || ( null !== $role && $role_count[ $role ] > 1 ) ) {
			$types[ $type ] = $type;
		}
	}

	return array_values( $types );
}
