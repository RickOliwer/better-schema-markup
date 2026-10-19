<?php
/**
 * Tests for the frontend output integration.
 */

class OutputTest extends WP_UnitTestCase {

	private function printed() {
		ob_start();
		better_schema_print();

		return ob_get_clean();
	}

	public function test_prints_one_json_ld_script_on_a_singular_post() {
		$post_id = self::factory()->post->create( array( 'post_title' => 'Hello' ) );
		$this->go_to( get_permalink( $post_id ) );

		$html = $this->printed();

		$this->assertSame( 1, substr_count( $html, '<script type="application/ld+json">' ) );
		preg_match( '#<script type="application/ld\+json">(.*)</script>#s', $html, $matches );
		$document = json_decode( $matches[1], true );
		$this->assertSame( 'https://schema.org', $document['@context'] );
		$this->assertSame( 'Hello', $document['@graph'][0]['headline'] );
	}

	public function test_prints_nothing_outside_singular_requests() {
		self::factory()->post->create();
		$this->go_to( home_url( '/' ) );

		$this->assertSame( '', $this->printed() );
	}

	public function test_prints_nothing_when_the_post_opts_out() {
		$post_id = self::factory()->post->create();
		update_post_meta( $post_id, '_better_schema_type', 'none' );
		$this->go_to( get_permalink( $post_id ) );

		$this->assertSame( '', $this->printed() );
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

	public function test_resolves_a_post_context_for_singular_requests_only() {
		$post_id = self::factory()->post->create();

		$this->go_to( home_url( '/' ) );
		$this->assertNull( better_schema_resolve_current_context() );

		$this->go_to( get_permalink( $post_id ) );
		$context = better_schema_resolve_current_context();
		$this->assertSame( 'post', $context['type'] );
		$this->assertSame( $post_id, $context['post']->ID );
	}
}
