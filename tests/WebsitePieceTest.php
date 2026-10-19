<?php
/**
 * Tests for the WebSite schema piece.
 */

class WebsitePieceTest extends WP_UnitTestCase {

	private function website() {
		$post_id  = self::factory()->post->create();
		$entities = better_schema_piece_website( better_schema_post_context( get_post( $post_id ) ) );

		return $entities[0];
	}

	public function test_describes_the_site_as_a_website() {
		update_option( 'blogname', 'My Site' );

		$website = $this->website();

		$this->assertSame( 'WebSite', $website['@type'] );
		$this->assertSame( 'My Site', $website['name'] );
		$this->assertSame( home_url( '/' ), $website['url'] );
	}

	public function test_has_a_stable_id_based_on_the_home_url() {
		$this->assertSame( home_url( '/' ) . '#website', $this->website()['@id'] );
		$this->assertSame( $this->website()['@id'], $this->website()['@id'] );
	}

	public function test_is_the_same_for_every_context() {
		$first  = self::factory()->post->create();
		$second = self::factory()->post->create( array( 'post_type' => 'page' ) );

		$this->assertSame(
			better_schema_piece_website( better_schema_post_context( get_post( $first ) ) ),
			better_schema_piece_website( better_schema_post_context( get_post( $second ) ) )
		);
	}

	public function test_includes_the_tagline_when_set() {
		update_option( 'blogdescription', 'Just another test' );

		$this->assertSame( 'Just another test', $this->website()['description'] );
	}

	public function test_omits_the_tagline_when_empty() {
		update_option( 'blogdescription', '   ' );

		$this->assertArrayNotHasKey( 'description', $this->website() );
	}

	public function test_omits_the_name_when_the_site_has_none() {
		update_option( 'blogname', '' );

		$website = $this->website();

		$this->assertArrayNotHasKey( 'name', $website );
		$this->assertSame( 'WebSite', $website['@type'] );
	}

	public function test_decodes_html_entities_stored_in_site_options() {
		update_option( 'blogname', 'Tom &amp; Jerry&#039;s' );

		$this->assertSame( "Tom & Jerry's", $this->website()['name'] );
	}

	public function test_includes_the_site_language() {
		$this->assertSame( get_bloginfo( 'language' ), $this->website()['inLanguage'] );
		$this->assertNotSame( '', $this->website()['inLanguage'] );
	}
}
