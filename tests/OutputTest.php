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

	public function test_falls_back_to_article_when_stored_type_is_invalid() {
		$post_id = self::factory()->post->create();
		// Bypass the registered sanitize callback, as a direct database edit would.
		global $wpdb;
		$wpdb->insert(
			$wpdb->postmeta,
			array(
				'post_id'    => $post_id,
				'meta_key'   => '_better_schema_type',
				'meta_value' => 'NotARealType',
			)
		);
		wp_cache_flush();

		$data = better_schema_build_data( get_post( $post_id ) );

		$this->assertSame( 'Article', $data['@type'] );
	}

	public function test_describes_pages_too() {
		$page_id = self::factory()->post->create(
			array(
				'post_type'  => 'page',
				'post_title' => 'About',
			)
		);
		update_post_meta( $page_id, '_better_schema_type', 'WebPage' );

		$data = better_schema_build_data( get_post( $page_id ) );

		$this->assertSame( 'WebPage', $data['@type'] );
		$this->assertSame( 'About', $data['headline'] );
	}

	public function test_includes_featured_image_when_set() {
		$post_id       = self::factory()->post->create();
		$attachment_id = self::factory()->attachment->create_object(
			'photo.jpg',
			$post_id,
			array( 'post_mime_type' => 'image/jpeg' )
		);
		set_post_thumbnail( $post_id, $attachment_id );

		$data = better_schema_build_data( get_post( $post_id ) );

		$this->assertStringEndsWith( 'photo.jpg', $data['image'] );
	}

	public function test_omits_image_without_featured_image() {
		$post_id = self::factory()->post->create();

		$data = better_schema_build_data( get_post( $post_id ) );

		$this->assertArrayNotHasKey( 'image', $data );
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
