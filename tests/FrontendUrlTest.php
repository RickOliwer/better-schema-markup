<?php
class FrontendUrlTest extends WP_UnitTestCase {
	public function test_mapping_respects_boundaries_and_preserves_text() {
		// wp-env defines WP_HOME/WP_SITEURL, so use the public URL filters.
		$home = static fn() => 'https://cms.example/wp';
		$site = static fn() => 'https://cms.example/wp/core';
		add_filter( 'home_url', $home );
		add_filter( 'site_url', $site );
		update_option( 'better_schema_frontend_url', 'https://public.example/blog/' );
		$graph = array( array(
			'@id' => 'https://cms.example/wp/post/#article',
			'url' => 'https://cms.example/wp?x=1',
			'name' => 'https://cms.example/wp',
			'image' => array( 'url' => 'https://cms.example/wp/core/uploads/a.jpg' ),
			'sameAs' => array( 'https://elsewhere.example/a', 'https://cms.example/wpress', 'https://cms.example.evil/wp' ),
			'isPartOf' => array( '@id' => 'https://cms.example/wp/#website' ),
		) );
		$mapped = better_schema_rewrite_urls( $graph )[0];
		$this->assertSame( 'https://public.example/blog/post/#article', $mapped['@id'] );
		$this->assertSame( 'https://public.example/blog?x=1', $mapped['url'] );
		$this->assertSame( $graph[0]['name'], $mapped['name'] );
		$this->assertSame( 'https://public.example/blog/uploads/a.jpg', $mapped['image']['url'] );
		$this->assertSame( $graph[0]['sameAs'], $mapped['sameAs'] );
		$this->assertSame( 'https://public.example/blog/#website', $mapped['isPartOf']['@id'] );
		delete_option( 'better_schema_frontend_url' );
		$this->assertSame( $graph, better_schema_rewrite_urls( $graph ) );
		remove_filter( 'home_url', $home );
		remove_filter( 'site_url', $site );
	}

	public function test_invalid_bases_are_rejected() {
		foreach ( array( array(), 'javascript:alert(1)', '/relative', 'https://user:pass@example.com', 'https://example.com?q=1', 'https://example.com#x' ) as $url ) {
			$this->assertSame( '', better_schema_sanitize_frontend_url( $url ) );
		}
		$this->assertSame( 'https://example.com/blog', better_schema_sanitize_frontend_url( ' https://example.com/blog/ ' ) );
	}

	public function test_frontend_option_is_saved_through_rest() {
		do_action( 'rest_api_init' );
		$request = new WP_REST_Request( 'POST', '/wp/v2/settings' );
		$request->set_body_params( array( 'better_schema_frontend_url' => 'https://public.example/' ) );
		wp_set_current_user( 0 );
		$this->assertSame( 401, rest_do_request( $request )->get_status() );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$this->assertSame( 200, rest_do_request( $request )->get_status() );
		$this->assertSame( 'https://public.example', get_option( 'better_schema_frontend_url' ) );
	}
}
