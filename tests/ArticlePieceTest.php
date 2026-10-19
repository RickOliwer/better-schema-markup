<?php
/**
 * Tests for the Article schema piece.
 */

class ArticlePieceTest extends WP_UnitTestCase {

	private function article_for( $post_id ) {
		$entities = better_schema_piece_article( better_schema_post_context( get_post( $post_id ) ) );

		return $entities[0] ?? null;
	}

	public function test_posts_are_articles_by_default() {
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

	public function test_pages_are_not_articles_by_default() {
		$page_id = self::factory()->post->create( array( 'post_type' => 'page' ) );

		$this->assertNull( $this->article_for( $page_id ) );
	}

	public function test_a_page_can_be_marked_as_an_article() {
		$page_id = self::factory()->post->create( array( 'post_type' => 'page' ) );
		update_post_meta( $page_id, '_better_schema_type', 'Article' );

		$this->assertSame( 'Article', $this->article_for( $page_id )['@type'] );
	}

	public function test_webpage_and_none_produce_no_article() {
		$webpage = self::factory()->post->create();
		$none    = self::factory()->post->create();
		update_post_meta( $webpage, '_better_schema_type', 'WebPage' );
		update_post_meta( $none, '_better_schema_type', 'none' );

		$this->assertNull( $this->article_for( $webpage ) );
		$this->assertNull( $this->article_for( $none ) );
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

	public function test_is_the_main_entity_of_its_webpage() {
		$post_id = self::factory()->post->create();
		$webpage = array( '@id' => get_permalink( $post_id ) . '#webpage' );

		$article = $this->article_for( $post_id );

		$this->assertSame( $webpage, $article['isPartOf'] );
		$this->assertSame( $webpage, $article['mainEntityOfPage'] );
	}

	public function test_is_published_by_the_site_identity() {
		$post_id = self::factory()->post->create();

		$this->assertSame( array( '@id' => home_url( '/' ) . '#identity' ), $this->article_for( $post_id )['publisher'] );
	}

	public function test_refers_to_its_author_by_id() {
		$user_id = self::factory()->user->create();
		$post_id = self::factory()->post->create( array( 'post_author' => $user_id ) );

		$this->assertSame(
			array( '@id' => better_schema_person_id( get_userdata( $user_id ) ) ),
			$this->article_for( $post_id )['author']
		);
	}

	public function test_omits_author_when_post_has_no_author() {
		$post_id = self::factory()->post->create( array( 'post_author' => 0 ) );

		$this->assertArrayNotHasKey( 'author', $this->article_for( $post_id ) );
	}

	public function test_has_a_stable_id_based_on_the_permalink() {
		$post_id = self::factory()->post->create();

		$this->assertSame( get_permalink( $post_id ) . '#article', $this->article_for( $post_id )['@id'] );
		$this->assertSame( $this->article_for( $post_id )['@id'], $this->article_for( $post_id )['@id'] );
	}

	public function test_headline_is_plain_text() {
		$post_id = self::factory()->post->create( array( 'post_title' => 'It\'s <em>new</em> & "fresh"' ) );

		$headline = $this->article_for( $post_id )['headline'];

		$this->assertStringNotContainsString( '&#', $headline );
		$this->assertStringNotContainsString( '<em>', $headline );
		$this->assertStringContainsString( ' & ', $headline );
	}

	public function test_omits_an_empty_headline() {
		$post_id = self::factory()->post->create( array( 'post_title' => '' ) );

		$this->assertArrayNotHasKey( 'headline', $this->article_for( $post_id ) );
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

	public function test_only_describes_post_contexts() {
		$post_id = self::factory()->post->create();
		$term_id = self::factory()->category->create();

		$this->assertSame( array(), better_schema_piece_article( better_schema_blog_context( get_post( $post_id ) ) ) );
		$this->assertSame( array(), better_schema_piece_article( better_schema_term_context( get_term( $term_id ) ) ) );
		$this->assertSame( array(), better_schema_piece_article( array( 'type' => 'post' ) ) );
	}
}
