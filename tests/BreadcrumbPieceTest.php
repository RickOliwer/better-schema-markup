<?php
/**
 * Tests for the BreadcrumbList schema piece.
 */

class BreadcrumbPieceTest extends WP_UnitTestCase {

	public function tear_down() {
		unregister_post_type( 'book' );
		parent::tear_down();
	}

	/**
	 * @return array<int, array{0: string, 1: string}> Name and URL of each item.
	 */
	private function trail( array $context ) {
		$entities = better_schema_piece_breadcrumb( $context );

		if ( ! $entities ) {
			return array();
		}

		$trail = array();
		foreach ( $entities[0]['itemListElement'] as $index => $item ) {
			$this->assertSame( 'ListItem', $item['@type'] );
			$this->assertSame( $index + 1, $item['position'] );
			$trail[] = array( $item['name'], $item['item'] );
		}

		return $trail;
	}

	private function home() {
		return array( 'Home', home_url( '/' ) );
	}

	public function test_a_post_without_a_real_category_is_home_then_the_post() {
		$post_id = self::factory()->post->create( array( 'post_title' => 'Hello' ) );

		$this->assertSame(
			array( $this->home(), array( 'Hello', get_permalink( $post_id ) ) ),
			$this->trail( better_schema_post_context( get_post( $post_id ) ) )
		);
	}

	public function test_a_post_goes_through_its_category_and_its_parents() {
		$parent  = self::factory()->category->create( array( 'name' => 'News' ) );
		$child   = self::factory()->category->create(
			array(
				'name'   => 'Local',
				'parent' => $parent,
			)
		);
		$post_id = self::factory()->post->create( array( 'post_title' => 'Hello' ) );
		wp_set_post_categories( $post_id, array( $child ) );

		$this->assertSame(
			array(
				$this->home(),
				array( 'News', get_term_link( $parent ) ),
				array( 'Local', get_term_link( $child ) ),
				array( 'Hello', get_permalink( $post_id ) ),
			),
			$this->trail( better_schema_post_context( get_post( $post_id ) ) )
		);
	}

	public function test_skips_the_default_category() {
		$news    = self::factory()->category->create( array( 'name' => 'News' ) );
		$post_id = self::factory()->post->create();
		wp_set_post_categories( $post_id, array( (int) get_option( 'default_category' ), $news ) );

		$trail = $this->trail( better_schema_post_context( get_post( $post_id ) ) );

		$this->assertSame( 'News', $trail[1][0] );
		$this->assertCount( 3, $trail );
	}

	public function test_the_category_can_be_chosen_with_a_filter() {
		$news    = self::factory()->category->create( array( 'name' => 'News' ) );
		$sport   = self::factory()->category->create( array( 'name' => 'Sport' ) );
		$post_id = self::factory()->post->create();
		wp_set_post_categories( $post_id, array( $news, $sport ) );
		add_filter(
			'better_schema_breadcrumb_category',
			function () use ( $sport ) {
				return get_term( $sport );
			}
		);

		$this->assertSame( 'Sport', $this->trail( better_schema_post_context( get_post( $post_id ) ) )[1][0] );
	}

	public function test_a_category_filter_returning_something_else_is_ignored() {
		$post_id = self::factory()->post->create();
		add_filter(
			'better_schema_breadcrumb_category',
			function () {
				return 'Sport';
			}
		);

		$this->assertCount( 2, $this->trail( better_schema_post_context( get_post( $post_id ) ) ) );
	}

	public function test_a_page_goes_through_its_parent_pages() {
		$grandparent = self::factory()->post->create(
			array(
				'post_type'  => 'page',
				'post_title' => 'About',
			)
		);
		$parent      = self::factory()->post->create(
			array(
				'post_type'   => 'page',
				'post_title'  => 'Team',
				'post_parent' => $grandparent,
			)
		);
		$page        = self::factory()->post->create(
			array(
				'post_type'   => 'page',
				'post_title'  => 'Ada',
				'post_parent' => $parent,
			)
		);

		$this->assertSame(
			array(
				$this->home(),
				array( 'About', get_permalink( $grandparent ) ),
				array( 'Team', get_permalink( $parent ) ),
				array( 'Ada', get_permalink( $page ) ),
			),
			$this->trail( better_schema_post_context( get_post( $page ) ) )
		);
	}

	public function test_a_custom_post_goes_through_its_archive() {
		register_post_type(
			'book',
			array(
				'public'      => true,
				'has_archive' => true,
				'label'       => 'Books',
			)
		);
		$book = self::factory()->post->create(
			array(
				'post_type'  => 'book',
				'post_title' => 'Dune',
			)
		);

		$this->assertSame(
			array(
				$this->home(),
				array( 'Books', get_post_type_archive_link( 'book' ) ),
				array( 'Dune', get_permalink( $book ) ),
			),
			$this->trail( better_schema_post_context( get_post( $book ) ) )
		);
	}

	public function test_a_term_archive_goes_through_its_parent_terms() {
		$parent = self::factory()->category->create( array( 'name' => 'News' ) );
		$child  = self::factory()->category->create(
			array(
				'name'   => 'Local',
				'parent' => $parent,
			)
		);

		$this->assertSame(
			array(
				$this->home(),
				array( 'News', get_term_link( $parent ) ),
				array( 'Local', get_term_link( $child ) ),
			),
			$this->trail( better_schema_term_context( get_term( $child ) ) )
		);
	}

	public function test_a_day_archive_goes_through_its_year_and_month() {
		$trail = $this->trail( better_schema_date_context( 2026, 10, 19 ) );

		$this->assertSame( array( '2026', get_year_link( 2026 ) ), $trail[1] );
		$this->assertSame( array( 'October 2026', get_month_link( 2026, 10 ) ), $trail[2] );
		$this->assertSame( get_day_link( 2026, 10, 19 ), $trail[3][1] );
		$this->assertCount( 4, $trail );
	}

	public function test_an_author_archive_is_home_then_the_author() {
		$user = get_userdata( self::factory()->user->create( array( 'display_name' => 'Ada' ) ) );

		$this->assertSame(
			array( $this->home(), array( 'Ada', get_author_posts_url( $user->ID ) ) ),
			$this->trail( better_schema_author_context( $user ) )
		);
	}

	public function test_no_trail_on_the_front_page_or_search_results() {
		$front_id = self::factory()->post->create( array( 'post_type' => 'page' ) );
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $front_id );

		$this->assertSame( array(), better_schema_piece_breadcrumb( better_schema_post_context( get_post( $front_id ) ) ) );
		$this->assertSame( array(), better_schema_piece_breadcrumb( better_schema_home_context() ) );
		$this->assertSame( array(), better_schema_piece_breadcrumb( better_schema_search_context( 'shoes' ) ) );
	}

	public function test_has_a_stable_id_based_on_the_page_url() {
		$post_id = self::factory()->post->create();

		$entities = better_schema_piece_breadcrumb( better_schema_post_context( get_post( $post_id ) ) );

		$this->assertSame( 'BreadcrumbList', $entities[0]['@type'] );
		$this->assertSame( get_permalink( $post_id ) . '#breadcrumb', $entities[0]['@id'] );
	}
}
