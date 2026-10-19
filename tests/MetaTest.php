<?php
/**
 * Tests for the per-post schema type meta.
 */

class MetaTest extends WP_UnitTestCase {

	public function set_up() {
		parent::set_up();
		// The test framework clears registered meta between tests; real requests register it on `init`.
		better_schema_register_meta();
	}

	public function test_posts_default_to_article_and_everything_else_to_webpage() {
		$this->assertSame( 'Article', better_schema_default_type( 'post' ) );
		$this->assertSame( 'WebPage', better_schema_default_type( 'page' ) );
		$this->assertSame( 'WebPage', better_schema_default_type( 'book' ) );
	}

	public function test_registered_meta_defaults_follow_the_post_type() {
		$post_id = self::factory()->post->create();
		$page_id = self::factory()->post->create( array( 'post_type' => 'page' ) );

		$this->assertSame( 'Article', get_post_meta( $post_id, '_better_schema_type', true ) );
		$this->assertSame( 'WebPage', get_post_meta( $page_id, '_better_schema_type', true ) );
	}

	public function test_sanitize_keeps_valid_types_and_falls_back_per_post_type() {
		$this->assertSame( 'BlogPosting', better_schema_sanitize_type( 'BlogPosting', 'page' ) );
		$this->assertSame( 'Article', better_schema_sanitize_type( 'Product' ) );
		$this->assertSame( 'Article', better_schema_sanitize_type( 'Product', 'post' ) );
		$this->assertSame( 'WebPage', better_schema_sanitize_type( 'Product', 'page' ) );
		$this->assertSame( 'WebPage', better_schema_sanitize_type( array( 'Article' ), 'page' ) );
	}

	public function test_saving_an_invalid_type_stores_the_post_type_default() {
		$page_id = self::factory()->post->create( array( 'post_type' => 'page' ) );
		update_post_meta( $page_id, '_better_schema_type', 'Hacker' );

		$this->assertSame( 'WebPage', get_post_meta( $page_id, '_better_schema_type', true ) );
	}

	public function test_post_schema_type_ignores_invalid_stored_values() {
		$post_id = self::factory()->post->create();
		$page_id = self::factory()->post->create( array( 'post_type' => 'page' ) );
		// Bypass the sanitize callback, as a direct database edit would.
		global $wpdb;
		foreach ( array( $post_id, $page_id ) as $id ) {
			$wpdb->insert(
				$wpdb->postmeta,
				array(
					'post_id'    => $id,
					'meta_key'   => '_better_schema_type',
					'meta_value' => 'NotARealType',
				)
			);
		}
		wp_cache_flush();

		$this->assertSame( 'Article', better_schema_post_schema_type( get_post( $post_id ) ) );
		$this->assertSame( 'WebPage', better_schema_post_schema_type( get_post( $page_id ) ) );
	}

	public function test_post_types_without_registered_meta_use_their_default() {
		register_post_type( 'book', array( 'public' => true ) );
		$book_id = self::factory()->post->create( array( 'post_type' => 'book' ) );

		$this->assertSame( 'WebPage', better_schema_post_schema_type( get_post( $book_id ) ) );

		unregister_post_type( 'book' );
	}
}
