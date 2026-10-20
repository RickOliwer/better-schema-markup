<?php
/** Migration must preserve choices and make losses visible. */
class ImportTest extends WP_UnitTestCase {
	public function set_up() {
		parent::set_up();
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
	}

	public function test_yoast_dry_run_import_and_repeat_preserve_source_and_existing_choice() {
		$id = self::factory()->post->create();
		update_post_meta( $id, '_yoast_wpseo_schema_article_type', 'BlogPosting' );
		$dry = better_schema_import_batch( 'yoast', 0, true );
		$this->assertSame( 1, $dry['imported'] );
		$this->assertFalse( metadata_exists( 'post', $id, '_better_schema_type' ) );
		$this->assertSame( 1, better_schema_import_batch( 'yoast' )['imported'] );
		$this->assertSame( 'BlogPosting', get_post_meta( $id, '_better_schema_type', true ) );
		$this->assertSame( 'BlogPosting', get_post_meta( $id, '_yoast_wpseo_schema_article_type', true ) );
		$this->assertSame( 0, better_schema_import_batch( 'yoast' )['imported'] );
		update_post_meta( $id, '_better_schema_type', 'none' );
		better_schema_import_batch( 'yoast' );
		$this->assertSame( 'none', get_post_meta( $id, '_better_schema_type', true ) );
	}

	public function test_yoast_none_only_disables_article_and_unsupported_types_are_reported() {
		$id = self::factory()->post->create();
		update_post_meta( $id, '_yoast_wpseo_schema_article_type', 'None' );
		$this->assertSame( 'WebPage', better_schema_import_choice( $id, 'yoast' )['meta']['_better_schema_type'] );
		update_post_meta( $id, '_yoast_wpseo_schema_article_type', 'NewsArticle' );
		$choice = better_schema_import_choice( $id, 'yoast' );
		$this->assertArrayNotHasKey( 'meta', $choice );
		$this->assertNotEmpty( $choice['warnings'] );
	}

	public function test_rank_math_recipe_fields_are_sanitized_and_templates_and_overrides_reported() {
		$id = self::factory()->post->create();
		update_post_meta( $id, 'rank_math_schema_Recipe', array(
			'@type' => 'Recipe', 'name' => '%title%', 'recipeIngredient' => array( 'Flour', 'Water' ),
			'recipeInstructions' => array( array( '@type' => 'HowToStep', 'text' => 'Mix' ) ),
			'prepTime' => 'PT1H15M', 'cookTime' => 'PT30M', 'recipeYield' => '4 servings',
			'nutrition' => array( 'calories' => '250 calories' ), 'description' => 'Custom copy',
		) );
		$choice = better_schema_import_choice( $id, 'rank-math' );
		$this->assertSame( array( 'Flour', 'Water' ), $choice['meta']['_better_schema_recipe']['ingredients'] );
		$this->assertSame( array( 'Mix' ), $choice['meta']['_better_schema_recipe']['instructions'] );
		$this->assertSame( 75, $choice['meta']['_better_schema_recipe']['prep_minutes'] );
		$this->assertSame( 250, $choice['meta']['_better_schema_recipe']['calories'] );
		$this->assertStringContainsString( 'template', implode( ' ', $choice['warnings'] ) );
		$this->assertStringContainsString( 'description', implode( ' ', $choice['warnings'] ) );
	}

	public function test_rank_math_event_and_faq_mapping() {
		$id = self::factory()->post->create();
		update_post_meta( $id, 'rank_math_schema_Event', array( '@type' => 'Event', 'startDate' => '2026-12-24T10:00', 'location' => array( '@type' => 'Place', 'name' => 'Hall', 'address' => 'Main Street' ), 'offers' => array( 'url' => 'https://example.org/tickets' ), 'eventStatus' => 'https://schema.org/EventPostponed' ) );
		$event = better_schema_import_choice( $id, 'rank-math' )['meta']['_better_schema_event'];
		$this->assertSame( '2026-12-24T10:00', $event['start'] );
		$this->assertSame( 'Hall', $event['location_name'] );
		$this->assertSame( 'postponed', $event['status'] );
		delete_post_meta( $id, 'rank_math_schema_Event' );
		update_post_meta( $id, 'rank_math_schema_FAQPage', array( '@type' => 'FAQPage', 'mainEntity' => array( array( '@type' => 'Question', 'name' => 'Question?', 'acceptedAnswer' => array( '@type' => 'Answer', 'text' => 'Answer.' ) ) ) ) );
		$this->assertSame( array( array( 'question' => 'Question?', 'answer' => 'Answer.' ) ), better_schema_import_choice( $id, 'rank-math' )['meta']['_better_schema_faq'] );
	}

	public function test_malformed_multiple_entities_and_existing_detail_meta_are_not_overwritten() {
		$id = self::factory()->post->create();
		update_post_meta( $id, 'rank_math_schema_Event', 'invalid' );
		$this->assertArrayNotHasKey( 'meta', better_schema_import_choice( $id, 'rank-math' ) );
		update_post_meta( $id, 'rank_math_schema_Event', array( '@type' => 'Event' ) );
		update_post_meta( $id, 'rank_math_schema_Recipe', array( '@type' => 'Recipe' ) );
		$this->assertArrayNotHasKey( 'meta', better_schema_import_choice( $id, 'rank-math' ) );
		delete_post_meta( $id, 'rank_math_schema_Recipe' );
		update_post_meta( $id, '_better_schema_event', array( 'start' => '2026-12-31' ) );
		$this->assertSame( 0, better_schema_import_batch( 'rank-math' )['imported'] );
		$this->assertFalse( metadata_exists( 'post', $id, '_better_schema_type' ) );
	}

	public function test_duration_conversion_bounds_untrusted_values() {
		$this->assertSame( 75, better_schema_import_minutes( 'PT1H15M' ) );
		$this->assertSame( 100000, better_schema_import_minutes( 'PT999999999999999999999999H' ) );
		$this->assertSame( 0, better_schema_import_minutes( array() ) );
	}

	public function test_import_route_requires_admin_and_valid_source() {
		do_action( 'rest_api_init' );
		$request = new WP_REST_Request( 'POST', '/better-schema/v1/import' );
		$request->set_body_params( array( 'source' => 'yoast', 'dry_run' => true ) );
		wp_set_current_user( 0 );
		$this->assertSame( 401, rest_do_request( $request )->get_status() );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );
		$this->assertSame( 403, rest_do_request( $request )->get_status() );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$this->assertSame( 200, rest_do_request( $request )->get_status() );
		$request->set_param( 'source', 'invalid' );
		$this->assertSame( 400, rest_do_request( $request )->get_status() );
	}

	public function test_bounded_batches_visit_each_post_once() {
		$ids = self::factory()->post->create_many( 101 );
		foreach ( $ids as $id ) {
			update_post_meta( $id, '_yoast_wpseo_schema_article_type', 'Article' );
		}
		$first = better_schema_import_batch( 'yoast', 0, true );
		$second = better_schema_import_batch( 'yoast', $first['cursor'], true );
		$this->assertFalse( $first['done'] );
		$this->assertTrue( $second['done'] );
		$this->assertSame( 101, $first['imported'] + $second['imported'] );
	}
}
