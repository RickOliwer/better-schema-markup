<?php
/**
 * Tests for the Recipe schema piece.
 */

class RecipePieceTest extends WP_UnitTestCase {

	use GraphAssertions;

	private function recipe_post( array $details ) {
		$post_id = self::factory()->post->create( array( 'post_title' => 'Pancakes', 'post_author' => self::factory()->user->create() ) );
		update_post_meta( $post_id, '_better_schema_type', 'Recipe' );
		update_post_meta( $post_id, '_better_schema_recipe', $details );

		return get_post( $post_id );
	}

	private function recipe_for( WP_Post $post ) {
		return better_schema_piece_recipe( better_schema_post_context( $post ) )[0] ?? null;
	}

	public function test_describes_a_recipe() {
		$recipe = $this->recipe_for(
			$this->recipe_post(
				array(
					'ingredients'  => array( '2 eggs', '', '3 dl milk' ),
					'instructions' => array( 'Whisk.', 'Fry.' ),
					'prep_minutes' => 10,
					'cook_minutes' => 80,
					'yield'        => '4 servings',
					'calories'     => 250,
				)
			)
		);

		$this->assertSame( 'Recipe', $recipe['@type'] );
		$this->assertSame( 'Pancakes', $recipe['name'] );
		$this->assertSame( array( '2 eggs', '3 dl milk' ), $recipe['recipeIngredient'] );
		$this->assertSame( array( '@type' => 'HowToStep', 'text' => 'Fry.' ), $recipe['recipeInstructions'][1] );
		$this->assertSame( 'PT10M', $recipe['prepTime'] );
		$this->assertSame( 'PT1H20M', $recipe['cookTime'] );
		$this->assertSame( 'PT1H30M', $recipe['totalTime'] );
		$this->assertSame( '4 servings', $recipe['recipeYield'] );
		$this->assertSame( '250 calories', $recipe['nutrition']['calories'] );
	}

	public function test_links_to_the_author_and_webpage() {
		$post    = $this->recipe_post( array() );
		$context = better_schema_post_context( $post );
		$recipe  = better_schema_piece_recipe( $context )[0];

		$this->assertSame( better_schema_webpage_id( $context ), $recipe['mainEntityOfPage']['@id'] );
		$this->assertArrayHasKey( 'author', $recipe );
		$this->assertGraphIsConnected( better_schema_build_graph( $context ) );
	}

	public function test_empty_details_are_omitted() {
		$recipe = $this->recipe_for( $this->recipe_post( array( 'prep_minutes' => 'lots', 'calories' => -5 ) ) );

		foreach ( array( 'recipeIngredient', 'recipeInstructions', 'prepTime', 'cookTime', 'totalTime', 'recipeYield', 'nutrition' ) as $property ) {
			$this->assertArrayNotHasKey( $property, $recipe );
		}
	}

	public function test_durations_are_iso_8601() {
		$this->assertSame( '', better_schema_duration( 0 ) );
		$this->assertSame( 'PT45M', better_schema_duration( 45 ) );
		$this->assertSame( 'PT2H', better_schema_duration( 120 ) );
	}

	public function test_nothing_is_output_for_other_types() {
		$post_id = self::factory()->post->create();

		$this->assertNull( $this->recipe_for( get_post( $post_id ) ) );
	}
}
