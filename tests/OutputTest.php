<?php
/**
 * Tests for the JSON-LD data builder.
 */

class OutputTest extends WP_UnitTestCase {

	public function test_defaults_to_article() {
		$post_id = self::factory()->post->create( array( 'post_title' => 'Hello' ) );

		$data = better_schema_build_data( get_post( $post_id ) );

		$this->assertSame( 'https://schema.org', $data['@context'] );
		$this->assertSame( 'Article', $data['@type'] );
		$this->assertSame( 'Hello', $data['headline'] );
	}

	public function test_uses_the_type_saved_on_the_post() {
		$post_id = self::factory()->post->create();
		update_post_meta( $post_id, '_better_schema_type', 'BlogPosting' );

		$data = better_schema_build_data( get_post( $post_id ) );

		$this->assertSame( 'BlogPosting', $data['@type'] );
	}

	public function test_returns_null_when_type_is_none() {
		$post_id = self::factory()->post->create();
		update_post_meta( $post_id, '_better_schema_type', 'none' );

		$this->assertNull( better_schema_build_data( get_post( $post_id ) ) );
	}

	public function test_omits_author_when_post_has_no_author() {
		$post_id = self::factory()->post->create( array( 'post_author' => 0 ) );

		$data = better_schema_build_data( get_post( $post_id ) );

		$this->assertArrayNotHasKey( 'author', $data );
	}

	public function test_sanitize_rejects_unknown_types() {
		$this->assertSame( 'Article', better_schema_sanitize_type( 'Product' ) );
		$this->assertSame( 'WebPage', better_schema_sanitize_type( 'WebPage' ) );
	}
}
