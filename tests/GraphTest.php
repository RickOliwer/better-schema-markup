<?php
/**
 * Tests for the graph builder.
 */

class GraphTest extends WP_UnitTestCase {

	private function context_for( $post_id ) {
		return better_schema_post_context( get_post( $post_id ) );
	}

	public function test_builds_a_document_with_context_and_graph() {
		$post_id = self::factory()->post->create( array( 'post_title' => 'Hello' ) );

		$document = better_schema_build_graph( $this->context_for( $post_id ) );

		$this->assertSame( 'https://schema.org', $document['@context'] );
		$this->assertCount( 1, $document['@graph'] );
		$this->assertSame( 'Article', $document['@graph'][0]['@type'] );
		$this->assertSame( 'Hello', $document['@graph'][0]['headline'] );
	}

	public function test_returns_an_empty_array_when_nothing_describes_the_post() {
		$post_id = self::factory()->post->create();
		update_post_meta( $post_id, '_better_schema_type', 'none' );

		$this->assertSame( array(), better_schema_build_graph( $this->context_for( $post_id ) ) );
	}

	public function test_does_not_read_the_current_request() {
		$post_id = self::factory()->post->create( array( 'post_title' => 'Elsewhere' ) );
		// The request is the home page, not this post.
		$this->go_to( home_url( '/' ) );
		$this->assertFalse( is_singular() );

		$document = better_schema_build_graph( $this->context_for( $post_id ) );

		$this->assertSame( 'Elsewhere', $document['@graph'][0]['headline'] );
	}

	public function test_entity_ids_are_unique_and_stable() {
		$post_id = self::factory()->post->create();

		$first  = better_schema_build_graph( $this->context_for( $post_id ) );
		$second = better_schema_build_graph( $this->context_for( $post_id ) );
		$ids    = wp_list_pluck( $first['@graph'], '@id' );

		$this->assertSame( $ids, array_values( array_unique( $ids ) ) );
		$this->assertSame( $ids, wp_list_pluck( $second['@graph'], '@id' ) );
	}

	public function test_pieces_filter_can_remove_a_piece() {
		$post_id = self::factory()->post->create();
		add_filter(
			'better_schema_pieces',
			function ( $pieces ) {
				unset( $pieces['article'] );
				return $pieces;
			}
		);

		$this->assertSame( array(), better_schema_build_graph( $this->context_for( $post_id ) ) );
	}

	public function test_pieces_filter_can_add_a_piece() {
		$post_id = self::factory()->post->create();
		add_filter(
			'better_schema_pieces',
			function ( $pieces ) {
				$pieces['extra'] = function ( $context ) {
					return array(
						array(
							'@type' => 'Thing',
							'@id'   => better_schema_entity_id( get_permalink( $context['post'] ), 'extra' ),
						),
					);
				};
				return $pieces;
			}
		);

		$document = better_schema_build_graph( $this->context_for( $post_id ) );

		$this->assertSame( array( 'Article', 'Thing' ), wp_list_pluck( $document['@graph'], '@type' ) );
	}

	public function test_ignores_pieces_that_are_not_callable() {
		$post_id = self::factory()->post->create();
		add_filter(
			'better_schema_pieces',
			function ( $pieces ) {
				$pieces['broken'] = 'better_schema_no_such_function';
				return $pieces;
			}
		);

		$document = better_schema_build_graph( $this->context_for( $post_id ) );

		$this->assertCount( 1, $document['@graph'] );
	}

	public function test_piece_filter_can_change_one_piece() {
		$post_id = self::factory()->post->create();
		add_filter(
			'better_schema_piece_article',
			function ( $entities ) {
				$entities[0]['headline'] = 'Changed';
				return $entities;
			}
		);

		$document = better_schema_build_graph( $this->context_for( $post_id ) );

		$this->assertSame( 'Changed', $document['@graph'][0]['headline'] );
	}

	public function test_piece_filter_returning_a_non_array_drops_the_piece() {
		$post_id = self::factory()->post->create();
		add_filter( 'better_schema_piece_article', '__return_false' );

		$this->assertSame( array(), better_schema_build_graph( $this->context_for( $post_id ) ) );
	}

	public function test_graph_filter_receives_the_context_and_can_change_the_graph() {
		$post_id  = self::factory()->post->create();
		$received = null;
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

		$document = better_schema_build_graph( $this->context_for( $post_id ) );

		$this->assertSame( 'post', $received['type'] );
		$this->assertSame( $post_id, $received['post']->ID );
		$this->assertCount( 2, $document['@graph'] );
	}
}
