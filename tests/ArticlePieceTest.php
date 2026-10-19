<?php
/**
 * Tests for the Article schema piece.
 */

class ArticlePieceTest extends WP_UnitTestCase {

	private function article_for( $post_id ) {
		$entities = better_schema_piece_article( better_schema_post_context( get_post( $post_id ) ) );

		return $entities[0] ?? null;
	}

	public function test_defaults_to_article() {
		$post_id = self::factory()->post->create( array( 'post_title' => 'Hello' ) );

		$article = $this->article_for( $post_id );

		$this->assertSame( 'Article', $article['@type'] );
		$this->assertSame( 'Hello', $article['headline'] );
		$this->assertSame( get_permalink( $post_id ), $article['url'] );
	}

	public function test_uses_the_type_saved_on_the_post() {
		$post_id = self::factory()->post->create();
		update_post_meta( $post_id, '_better_schema_type', 'BlogPosting' );

		$this->assertSame( 'BlogPosting', $this->article_for( $post_id )['@type'] );
	}

	public function test_returns_no_entity_when_type_is_none() {
		$post_id = self::factory()->post->create();
		update_post_meta( $post_id, '_better_schema_type', 'none' );

		$this->assertSame( array(), better_schema_piece_article( better_schema_post_context( get_post( $post_id ) ) ) );
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

		$this->assertSame( 'Article', $this->article_for( $post_id )['@type'] );
	}

	public function test_describes_pages_too() {
		$page_id = self::factory()->post->create(
			array(
				'post_type'  => 'page',
				'post_title' => 'About',
			)
		);
		update_post_meta( $page_id, '_better_schema_type', 'WebPage' );

		$article = $this->article_for( $page_id );

		$this->assertSame( 'WebPage', $article['@type'] );
		$this->assertSame( 'About', $article['headline'] );
	}

	public function test_has_a_stable_id_based_on_the_permalink() {
		$post_id = self::factory()->post->create();

		$first  = $this->article_for( $post_id )['@id'];
		$second = $this->article_for( $post_id )['@id'];

		$this->assertSame( get_permalink( $post_id ) . '#article', $first );
		$this->assertSame( $first, $second );
	}

	public function test_omits_author_when_post_has_no_author() {
		$post_id = self::factory()->post->create( array( 'post_author' => 0 ) );

		$this->assertArrayNotHasKey( 'author', $this->article_for( $post_id ) );
	}

	public function test_includes_author_when_post_has_one() {
		$user_id = self::factory()->user->create( array( 'display_name' => 'Ada' ) );
		$post_id = self::factory()->post->create( array( 'post_author' => $user_id ) );

		$this->assertSame(
			array(
				'@type' => 'Person',
				'name'  => 'Ada',
			),
			$this->article_for( $post_id )['author']
		);
	}

	public function test_includes_featured_image_when_set() {
		$post_id       = self::factory()->post->create();
		$attachment_id = self::factory()->attachment->create_object(
			'photo.jpg',
			$post_id,
			array( 'post_mime_type' => 'image/jpeg' )
		);
		set_post_thumbnail( $post_id, $attachment_id );

		$this->assertStringEndsWith( 'photo.jpg', $this->article_for( $post_id )['image'] );
	}

	public function test_omits_image_without_featured_image() {
		$post_id = self::factory()->post->create();

		$this->assertArrayNotHasKey( 'image', $this->article_for( $post_id ) );
	}

	public function test_returns_no_entity_without_a_post_in_the_context() {
		$this->assertSame( array(), better_schema_piece_article( array( 'type' => 'post' ) ) );
	}

	public function test_sanitize_rejects_unknown_types() {
		$this->assertSame( 'Article', better_schema_sanitize_type( 'Product' ) );
		$this->assertSame( 'WebPage', better_schema_sanitize_type( 'WebPage' ) );
	}
}
