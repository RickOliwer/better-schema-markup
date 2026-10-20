<?php
/**
 * Tests for the custom JSON-LD field.
 */

class CustomJsonldTest extends WP_UnitTestCase {

	use GraphAssertions;

	public function set_up() {
		parent::set_up();
		// The test framework clears registered meta between tests; real requests register it on `init`.
		better_schema_register_meta();
	}



	public function test_a_single_entity_is_added_to_the_graph() {
		$post_id = self::factory()->post->create();
		update_post_meta( $post_id, '_better_schema_custom_jsonld', '{"@context":"https://schema.org","@type":"Course","name":"Intro"}' );

		$document = better_schema_build_graph( better_schema_post_context( get_post( $post_id ) ) );

		$this->assertSame( 'Intro', $this->entity( $document, 'Course' )['name'] );
		$this->assertArrayNotHasKey( '@context', $this->entity( $document, 'Course' ) );
		$this->assertContains( 'Article', $this->types( $document ) );
	}

	public function test_a_list_and_a_graph_document_are_accepted() {
		$this->assertCount( 2, better_schema_parse_custom_jsonld( '[{"@type":"A"},{"@type":"B"}]' ) );
		$this->assertCount( 1, better_schema_parse_custom_jsonld( '{"@context":"https://schema.org","@graph":[{"@type":"A"}]}' ) );
	}

	public function test_invalid_json_gives_nothing() {
		foreach ( array( '{not json', '"just a string"', '42', '{"name":"no type"}', '[]', '', null, array() ) as $value ) {
			$this->assertSame( array(), better_schema_parse_custom_jsonld( $value ) );
			$this->assertSame( '', better_schema_sanitize_custom_jsonld( $value ) );
		}
	}

	public function test_valid_json_is_stored_normalized() {
		$this->assertSame(
			'[{"@type":"Course","name":"Intro"}]',
			better_schema_sanitize_custom_jsonld( "{\n \"@context\": \"https://schema.org\",\n \"@type\": \"Course\", \"name\": \"Intro\" }" )
		);
	}

	public function test_invalid_json_is_not_saved_through_rest() {
		$user_id = self::factory()->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $user_id );
		do_action( 'rest_api_init' );
		$post_id = self::factory()->post->create( array( 'post_author' => $user_id ) );

		$request = new WP_REST_Request( 'POST', "/wp/v2/posts/{$post_id}" );
		$request->set_body_params( array( 'meta' => array( '_better_schema_custom_jsonld' => '{broken' ) ) );
		rest_get_server()->dispatch( $request );

		$this->assertSame( '', get_post_meta( $post_id, '_better_schema_custom_jsonld', true ) );

		$request->set_body_params( array( 'meta' => array( '_better_schema_custom_jsonld' => '{"@type":"Course","name":"A \"quoted\" name"}' ) ) );
		rest_get_server()->dispatch( $request );

		$this->assertSame( 'A "quoted" name', better_schema_get_custom_entities( get_post( $post_id ) )[0]['name'] );
	}

	public function test_script_tags_cannot_break_out_of_the_output() {
		$post_id = self::factory()->post->create();
		update_post_meta( $post_id, '_better_schema_custom_jsonld', wp_json_encode( array( '@type' => 'Thing', 'name' => '</script><script>alert(1)</script>' ) ) );

		$html = better_schema_render_script( better_schema_build_graph( better_schema_post_context( get_post( $post_id ) ) ) );

		$this->assertSame( 1, substr_count( $html, '</script>' ) );
	}

	public function test_none_turns_custom_schema_off_too() {
		$post_id = self::factory()->post->create();
		update_post_meta( $post_id, '_better_schema_type', 'none' );
		update_post_meta( $post_id, '_better_schema_custom_jsonld', '{"@type":"Course"}' );

		$this->assertSame( array(), better_schema_build_graph( better_schema_post_context( get_post( $post_id ) ) ) );
	}
}
