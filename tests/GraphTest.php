<?php
/**
 * Tests for the graph builder.
 */

class GraphTest extends WP_UnitTestCase {

	use GraphAssertions;

	public function tear_down() {
		unregister_post_type( 'book' );
		parent::tear_down();
	}

	private function post_graph( array $args = array() ) {
		$post_id = self::factory()->post->create( $args );

		return better_schema_build_graph( better_schema_post_context( get_post( $post_id ) ) );
	}

	public function test_a_post_is_described_by_every_piece_in_order() {
		$user_id  = self::factory()->user->create();
		$document = $this->post_graph(
			array(
				'post_title'  => 'Hello',
				'post_author' => $user_id,
			)
		);

		$this->assertSame( 'https://schema.org', $document['@context'] );
		$this->assertSame( array( 'WebSite', 'Organization', 'WebPage', 'BreadcrumbList', 'Article', 'Person' ), $this->types( $document ) );
		$this->assertSame( 'Hello', $this->entity( $document, 'Article' )['headline'] );
		$this->assertGraphIsConnected( $document );
	}

	public function test_a_page_has_no_article_or_author() {
		$document = $this->post_graph(
			array(
				'post_type'   => 'page',
				'post_author' => self::factory()->user->create(),
			)
		);

		$this->assertSame( array( 'WebSite', 'Organization', 'WebPage', 'BreadcrumbList' ), $this->types( $document ) );
	}

	public function test_every_kind_of_page_produces_a_connected_graph() {
		$user    = get_userdata( self::factory()->user->create() );
		$post_id = self::factory()->post->create( array( 'post_author' => $user->ID ) );
		$page_id = self::factory()->post->create( array( 'post_type' => 'page' ) );
		$front   = self::factory()->post->create( array( 'post_type' => 'page' ) );
		$blog    = self::factory()->post->create( array( 'post_type' => 'page' ) );
		$term_id = self::factory()->category->create();
		register_post_type(
			'book',
			array(
				'public'      => true,
				'has_archive' => true,
			)
		);
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $front );

		$contexts = array(
			'post'              => array( better_schema_post_context( get_post( $post_id ) ), 'WebPage' ),
			'page'              => array( better_schema_post_context( get_post( $page_id ) ), 'WebPage' ),
			'front page'        => array( better_schema_post_context( get_post( $front ) ), 'WebPage' ),
			'home'              => array( better_schema_home_context(), 'CollectionPage' ),
			'blog'              => array( better_schema_blog_context( get_post( $blog ) ), 'CollectionPage' ),
			'term'              => array( better_schema_term_context( get_term( $term_id ) ), 'CollectionPage' ),
			'author'            => array( better_schema_author_context( $user ), 'ProfilePage' ),
			'post type archive' => array( better_schema_post_type_archive_context( 'book' ), 'CollectionPage' ),
			'date'              => array( better_schema_date_context( 2026, 10, 19 ), 'CollectionPage' ),
			'search'            => array( better_schema_search_context( 'shoes' ), 'SearchResultsPage' ),
		);

		foreach ( $contexts as $label => list( $context, $page_type ) ) {
			$document = better_schema_build_graph( $context );

			$this->assertSame( array( 'WebSite', 'Organization' ), array_slice( $this->types( $document ), 0, 2 ), $label );
			$this->assertNotNull( $this->entity( $document, $page_type ), "{$label} has a {$page_type}" );
			$this->assertGraphIsConnected( $document );
		}
	}

	public function test_returns_an_empty_array_when_the_post_opts_out() {
		$post_id = self::factory()->post->create();
		update_post_meta( $post_id, '_better_schema_type', 'none' );

		// Site-wide pieces such as WebSite must not be output either.
		$this->assertSame( array(), better_schema_build_graph( better_schema_post_context( get_post( $post_id ) ) ) );
	}

	public function test_returns_an_empty_array_when_the_posts_page_opts_out() {
		$blog = self::factory()->post->create( array( 'post_type' => 'page' ) );
		update_post_meta( $blog, '_better_schema_type', 'none' );

		$this->assertSame( array(), better_schema_build_graph( better_schema_blog_context( get_post( $blog ) ) ) );
	}

	public function test_returns_an_empty_array_when_every_piece_is_removed() {
		add_filter( 'better_schema_pieces', '__return_empty_array' );

		$this->assertSame( array(), $this->post_graph() );
	}

	public function test_does_not_read_the_current_request() {
		$post_id = self::factory()->post->create( array( 'post_title' => 'Elsewhere' ) );
		// The request is the home page, not this post.
		$this->go_to( home_url( '/' ) );
		$this->assertFalse( is_singular() );

		$document = better_schema_build_graph( better_schema_post_context( get_post( $post_id ) ) );

		$this->assertSame( 'Elsewhere', $this->entity( $document, 'Article' )['headline'] );
	}

	public function test_entity_ids_are_stable() {
		$post_id = self::factory()->post->create();
		$context = better_schema_post_context( get_post( $post_id ) );

		$this->assertSame(
			wp_list_pluck( better_schema_build_graph( $context )['@graph'], '@id' ),
			wp_list_pluck( better_schema_build_graph( $context )['@graph'], '@id' )
		);
	}

	public function test_pieces_filter_can_remove_a_piece() {
		add_filter(
			'better_schema_pieces',
			function ( $pieces ) {
				unset( $pieces['article'] );
				return $pieces;
			}
		);

		$this->assertNull( $this->entity( $this->post_graph(), 'Article' ) );
	}

	public function test_pieces_filter_can_add_a_piece() {
		add_filter(
			'better_schema_pieces',
			function ( $pieces ) {
				$pieces['extra'] = function ( $context ) {
					return array(
						array(
							'@type' => 'Thing',
							'@id'   => better_schema_entity_id( better_schema_context_url( $context ), 'extra' ),
						),
					);
				};
				return $pieces;
			}
		);

		$types = $this->types( $this->post_graph() );

		$this->assertSame( 'Thing', end( $types ) );
	}

	public function test_ignores_pieces_that_are_not_callable() {
		$expected = count( $this->post_graph()['@graph'] );
		add_filter(
			'better_schema_pieces',
			function ( $pieces ) {
				$pieces['broken'] = 'better_schema_no_such_function';
				return $pieces;
			}
		);

		$this->assertCount( $expected, $this->post_graph()['@graph'] );
	}

	public function test_piece_filter_can_change_one_piece() {
		add_filter(
			'better_schema_piece_article',
			function ( $entities ) {
				$entities[0]['headline'] = 'Changed';
				return $entities;
			}
		);

		$this->assertSame( 'Changed', $this->entity( $this->post_graph(), 'Article' )['headline'] );
	}

	public function test_piece_filter_returning_a_non_array_drops_the_piece() {
		add_filter( 'better_schema_piece_article', '__return_false' );

		$this->assertNull( $this->entity( $this->post_graph(), 'Article' ) );
	}

	public function test_graph_filter_receives_the_context_and_can_change_the_graph() {
		$post_id  = self::factory()->post->create();
		$received = null;
		$expected = count( better_schema_build_graph( better_schema_post_context( get_post( $post_id ) ) )['@graph'] );
		add_filter(
			'better_schema_graph',
			function ( $graph, $context ) use ( &$received ) {
				$received = $context;
				$graph[]  = array( '@type' => 'Thing' );
				return $graph;
			},
			10,
			2
		);

		$document = better_schema_build_graph( better_schema_post_context( get_post( $post_id ) ) );

		$this->assertSame( 'post', $received['type'] );
		$this->assertSame( $post_id, $received['post']->ID );
		$this->assertCount( $expected + 1, $document['@graph'] );
	}
}
