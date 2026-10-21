<?php
/**
 * Recipe schema piece.
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Formats minutes as an ISO 8601 duration such as `PT1H30M`.
 *
 * @param int $minutes Minutes; zero or less gives an empty string.
 */
function better_schema_duration( int $minutes ): string {
	if ( $minutes <= 0 ) {
		return '';
	}

	$hours = intdiv( $minutes, 60 );
	$rest  = $minutes % 60;

	return 'PT' . ( $hours ? $hours . 'H' : '' ) . ( $rest ? $rest . 'M' : '' );
}

/**
 * Describes a post whose schema type is Recipe.
 *
 * @param array $context Context.
 * @return array List with one entity, or an empty array.
 */
function better_schema_piece_recipe( array $context ): array {
	$post = $context['post'] ?? null;

	if ( 'post' !== ( $context['type'] ?? '' ) || ! $post instanceof WP_Post || 'Recipe' !== better_schema_post_schema_type( $post ) ) {
		return array();
	}

	$url = get_permalink( $post );

	if ( ! $url ) {
		return array();
	}

	$details = better_schema_get_recipe( $post );
	$webpage = array( '@id' => better_schema_webpage_id( $context ) );

	$entity = array(
		'@type'            => 'Recipe',
		'@id'              => better_schema_entity_id( $url, 'recipe' ),
		'mainEntityOfPage' => $webpage,
		'url'              => $url,
		'datePublished'    => get_the_date( 'c', $post ),
	);

	$name = better_schema_plain_text( get_the_title( $post ) );
	if ( '' !== $name ) {
		$entity['name'] = $name;
	}

	$description = better_schema_plain_text( get_the_excerpt( $post ) );
	if ( '' !== $description ) {
		$entity['description'] = $description;
	}

	$image = get_the_post_thumbnail_url( $post, 'full' );
	if ( $image ) {
		$entity['image'] = $image;
	}

	$author = better_schema_post_author( $post );
	if ( $author ) {
		$entity['author'] = array( '@id' => better_schema_person_id( $author ) );
	}

	if ( $details['ingredients'] ) {
		$entity['recipeIngredient'] = $details['ingredients'];
	}

	if ( $details['instructions'] ) {
		$entity['recipeInstructions'] = array_map(
			static fn ( string $step ): array => array(
				'@type' => 'HowToStep',
				'text'  => $step,
			),
			$details['instructions']
		);
	}

	$durations = array(
		'prepTime'  => $details['prep_minutes'],
		'cookTime'  => $details['cook_minutes'],
		'totalTime' => $details['prep_minutes'] + $details['cook_minutes'],
	);
	foreach ( $durations as $property => $minutes ) {
		if ( $minutes > 0 ) {
			$entity[ $property ] = better_schema_duration( $minutes );
		}
	}

	if ( '' !== $details['yield'] ) {
		$entity['recipeYield'] = $details['yield'];
	}

	if ( $details['calories'] > 0 ) {
		$entity['nutrition'] = array(
			'@type'    => 'NutritionInformation',
			'calories' => $details['calories'] . ' calories',
		);
	}

	return array( $entity );
}
