<?php
/**
 * Tests for the frontend output integration: request resolution and printing.
 */

class OutputTest extends WP_UnitTestCase {

	use GraphAssertions;

	public function tear_down() {
		unregister_post_type( 'book' );
		parent::tear_down();
	}

	private function printed() {
		ob_start();
		better_schema_print();

		return ob_get_clean();
	}

	private function printed_document() {
		$html = $this->printed();
		$this->assertSame( 1, substr_count( $html, '<script type="application/ld+json">' ) );
		preg_match( '#<script type="application/ld\+json">(.*)</script>#s', $html, $matches );

		return json_decode( $matches[1], true );
	}

	public function test_prints_one_json_ld_script_on_a_post() {
		$post_id = self::factory()->post->create( array( 'post_title' => 'Hello' ) );
		$this->go_to( get_permalink( $post_id ) );

		$document = $this->printed_document();

		$this->assertSame( 'https://schema.org', $document['@context'] );
		$this->assertSame( 'Hello', $this->entity( $document, 'Article' )['headline'] );
	}

	public function test_a_front_page_listing_posts_resolves_to_home() {
		self::factory()->post->create();
		$this->go_to( home_url( '/' ) );

		$this->assertSame( array( 'type' => 'home' ), better_schema_resolve_current_context() );
		$this->assertNotNull( $this->entity( $this->printed_document(), 'CollectionPage' ) );
	}

	public function test_a_static_front_page_resolves_to_its_page() {
		$front = self::factory()->post->create( array( 'post_type' => 'page' ) );
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $front );
		$this->go_to( home_url( '/' ) );

		$context = better_schema_resolve_current_context();

		$this->assertSame( 'post', $context['type'] );
		$this->assertSame( $front, $context['post']->ID );
	}

	public function test_the_posts_page_resolves_to_blog() {
		$front = self::factory()->post->create( array( 'post_type' => 'page' ) );
		$blog  = self::factory()->post->create( array( 'post_type' => 'page' ) );
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $front );
		update_option( 'page_for_posts', $blog );
		$this->go_to( get_permalink( $blog ) );

		$context = better_schema_resolve_current_context();

		$this->assertSame( 'blog', $context['type'] );
		$this->assertSame( $blog, $context['post']->ID );
	}

	public function test_category_and_tag_archives_resolve_to_terms() {
		$category = self::factory()->category->create();
		$tag      = self::factory()->tag->create();
		$post_id  = self::factory()->post->create();
		wp_set_post_categories( $post_id, array( $category ) );
		wp_set_post_tags( $post_id, array( $tag ) );

		$this->go_to( get_term_link( $category ) );
		$this->assertSame( $category, better_schema_resolve_current_context()['term']->term_id );

		$this->go_to( get_term_link( $tag ) );
		$this->assertSame( $tag, better_schema_resolve_current_context()['term']->term_id );
	}

	public function test_an_author_archive_resolves_to_the_author() {
		$user_id = self::factory()->user->create();
		self::factory()->post->create( array( 'post_author' => $user_id ) );
		$this->go_to( get_author_posts_url( $user_id ) );

		$context = better_schema_resolve_current_context();

		$this->assertSame( 'author', $context['type'] );
		$this->assertSame( $user_id, $context['user']->ID );
	}

	public function test_a_post_type_archive_resolves_to_the_post_type() {
		register_post_type(
			'book',
			array(
				'public'      => true,
				'has_archive' => true,
			)
		);
		self::factory()->post->create( array( 'post_type' => 'book' ) );
		$this->go_to( get_post_type_archive_link( 'book' ) );

		$this->assertSame( better_schema_post_type_archive_context( 'book' ), better_schema_resolve_current_context() );
	}

	public function test_date_archives_resolve_with_plain_permalinks() {
		self::factory()->post->create( array( 'post_date' => '2026-10-19 10:00:00' ) );

		$this->go_to( get_month_link( 2026, 10 ) );
		$this->assertSame( better_schema_date_context( 2026, 10 ), better_schema_resolve_current_context() );

		$this->go_to( get_day_link( 2026, 10, 19 ) );
		$this->assertSame( better_schema_date_context( 2026, 10, 19 ), better_schema_resolve_current_context() );
	}

	public function test_date_archives_resolve_with_pretty_permalinks() {
		$this->set_permalink_structure( '/%year%/%monthnum%/%postname%/' );
		self::factory()->post->create( array( 'post_date' => '2026-10-19 10:00:00' ) );

		$this->go_to( get_year_link( 2026 ) );

		$this->assertSame( better_schema_date_context( 2026 ), better_schema_resolve_current_context() );
	}

	public function test_search_resolves_to_the_search_terms() {
		$this->go_to( home_url( '/?s=shoes' ) );

		$this->assertSame( better_schema_search_context( 'shoes' ), better_schema_resolve_current_context() );
		$this->assertNotNull( $this->entity( $this->printed_document(), 'SearchResultsPage' ) );
	}

	public function test_a_404_has_no_schema() {
		$this->go_to( home_url( '/?p=999999' ) );
		$this->assertTrue( is_404() );

		$this->assertNull( better_schema_resolve_current_context() );
		$this->assertSame( '', $this->printed() );
	}

	public function test_a_forced_404_on_a_post_has_no_schema() {
		$post_id = self::factory()->post->create();
		$this->go_to( get_permalink( $post_id ) );
		// Some plugins flag a 404 without resetting the other query flags.
		$GLOBALS['wp_query']->is_404 = true;

		$this->assertTrue( is_singular() );
		$this->assertNull( better_schema_resolve_current_context() );
	}

	public function test_prints_nothing_when_the_post_opts_out() {
		$post_id = self::factory()->post->create();
		update_post_meta( $post_id, '_better_schema_type', 'none' );
		$this->go_to( get_permalink( $post_id ) );

		$this->assertSame( '', $this->printed() );
	}

	public function test_the_context_filter_can_turn_schema_off() {
		$post_id = self::factory()->post->create();
		$this->go_to( get_permalink( $post_id ) );
		add_filter( 'better_schema_context', '__return_null' );

		$this->assertNull( better_schema_resolve_current_context() );
		$this->assertSame( '', $this->printed() );
	}

	public function test_the_context_filter_can_replace_the_context() {
		$post_id = self::factory()->post->create();
		$this->go_to( home_url( '/?s=shoes' ) );
		add_filter(
			'better_schema_context',
			function () use ( $post_id ) {
				return better_schema_post_context( get_post( $post_id ) );
			}
		);

		$this->assertNotNull( $this->entity( $this->printed_document(), 'Article' ) );
	}

	public function test_output_comes_from_the_graph_builder() {
		$post_id = self::factory()->post->create();
		$this->go_to( get_permalink( $post_id ) );
		add_filter(
			'better_schema_graph',
			function ( $graph ) {
				$graph[] = array( '@type' => 'Thing' );
				return $graph;
			}
		);

		$this->assertStringContainsString( '"@type":"Thing"', $this->printed() );
	}
}
