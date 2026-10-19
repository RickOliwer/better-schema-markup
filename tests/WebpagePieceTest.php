<?php
/**
 * Tests for the WebPage schema piece.
 */

class WebpagePieceTest extends WP_UnitTestCase {

	public function tear_down() {
		unregister_post_type( 'book' );
		parent::tear_down();
	}

	private function webpage( array $context ) {
		$entities = better_schema_piece_webpage( $context );

		return $entities[0] ?? null;
	}

	public function test_describes_a_post_as_a_webpage_on_the_site() {
		$post_id = self::factory()->post->create( array( 'post_title' => 'Hello' ) );
		$url     = get_permalink( $post_id );

		$webpage = $this->webpage( better_schema_post_context( get_post( $post_id ) ) );

		$this->assertSame( 'WebPage', $webpage['@type'] );
		$this->assertSame( $url . '#webpage', $webpage['@id'] );
		$this->assertSame( $url, $webpage['url'] );
		$this->assertSame( 'Hello', $webpage['name'] );
		$this->assertSame( array( '@id' => home_url( '/' ) . '#website' ), $webpage['isPartOf'] );
		$this->assertSame( get_the_date( 'c', $post_id ), $webpage['datePublished'] );
		$this->assertSame( get_bloginfo( 'language' ), $webpage['inLanguage'] );
	}

	public function test_refers_to_its_breadcrumb() {
		$post_id = self::factory()->post->create();

		$webpage = $this->webpage( better_schema_post_context( get_post( $post_id ) ) );

		$this->assertSame( array( '@id' => get_permalink( $post_id ) . '#breadcrumb' ), $webpage['breadcrumb'] );
	}

	public function test_the_static_front_page_is_about_the_identity_and_has_no_breadcrumb() {
		$front_id = self::factory()->post->create( array( 'post_type' => 'page' ) );
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $front_id );

		$webpage = $this->webpage( better_schema_post_context( get_post( $front_id ) ) );

		$this->assertSame( 'WebPage', $webpage['@type'] );
		$this->assertSame( array( '@id' => home_url( '/' ) . '#identity' ), $webpage['about'] );
		$this->assertArrayNotHasKey( 'breadcrumb', $webpage );
	}

	public function test_other_pages_are_not_about_the_identity() {
		$post_id = self::factory()->post->create();

		$this->assertArrayNotHasKey( 'about', $this->webpage( better_schema_post_context( get_post( $post_id ) ) ) );
	}

	public function test_a_front_page_listing_posts_is_a_collection_about_the_identity() {
		$webpage = $this->webpage( better_schema_home_context() );

		$this->assertSame( 'CollectionPage', $webpage['@type'] );
		$this->assertSame( home_url( '/' ), $webpage['url'] );
		$this->assertSame( array( '@id' => home_url( '/' ) . '#identity' ), $webpage['about'] );
		$this->assertArrayNotHasKey( 'breadcrumb', $webpage );
	}

	public function test_listing_pages_are_collections() {
		$page_id = self::factory()->post->create( array( 'post_type' => 'page' ) );
		$term_id = self::factory()->category->create();
		register_post_type(
			'book',
			array(
				'public'      => true,
				'has_archive' => true,
			)
		);

		$contexts = array(
			better_schema_blog_context( get_post( $page_id ) ),
			better_schema_term_context( get_term( $term_id ) ),
			better_schema_post_type_archive_context( 'book' ),
			better_schema_date_context( 2026, 10 ),
		);

		foreach ( $contexts as $context ) {
			$webpage = $this->webpage( $context );
			$this->assertSame( 'CollectionPage', $webpage['@type'], $context['type'] );
			$this->assertArrayNotHasKey( 'datePublished', $webpage, $context['type'] );
		}
	}

	public function test_an_author_archive_is_a_profile_page_about_the_person() {
		$user = get_userdata( self::factory()->user->create() );

		$webpage = $this->webpage( better_schema_author_context( $user ) );

		$this->assertSame( 'ProfilePage', $webpage['@type'] );
		$this->assertSame( array( '@id' => better_schema_person_id( $user ) ), $webpage['mainEntity'] );
	}

	public function test_search_results_have_no_breadcrumb() {
		$webpage = $this->webpage( better_schema_search_context( 'shoes' ) );

		$this->assertSame( 'SearchResultsPage', $webpage['@type'] );
		$this->assertArrayNotHasKey( 'breadcrumb', $webpage );
	}

	public function test_returns_nothing_without_a_url() {
		register_post_type( 'book', array( 'public' => true ) );

		$this->assertSame( array(), better_schema_piece_webpage( better_schema_post_type_archive_context( 'book' ) ) );
		$this->assertSame( array(), better_schema_piece_webpage( array( 'type' => 'unknown' ) ) );
	}
}
