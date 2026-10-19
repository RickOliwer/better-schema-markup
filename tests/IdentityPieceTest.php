<?php
/**
 * Tests for the identity (Organization or Person) schema piece.
 */

class IdentityPieceTest extends WP_UnitTestCase {

	private function identity() {
		$post_id  = self::factory()->post->create();
		$entities = better_schema_piece_identity( better_schema_post_context( get_post( $post_id ) ) );

		return $entities[0];
	}

	private function create_image( $file = 'logo.png', $width = 512, $height = 512 ) {
		$id = self::factory()->attachment->create_object( $file, 0, array( 'post_mime_type' => 'image/png' ) );
		wp_update_attachment_metadata(
			$id,
			array(
				'file'   => $file,
				'width'  => $width,
				'height' => $height,
			)
		);

		return $id;
	}

	public function test_is_an_organization_named_after_the_site_by_default() {
		update_option( 'blogname', 'My Site' );

		$identity = $this->identity();

		$this->assertSame( 'Organization', $identity['@type'] );
		$this->assertSame( 'My Site', $identity['name'] );
		$this->assertSame( home_url( '/' ), $identity['url'] );
	}

	public function test_has_a_stable_id() {
		$this->assertSame( home_url( '/' ) . '#identity', $this->identity()['@id'] );
		$this->assertSame( $this->identity()['@id'], $this->identity()['@id'] );
	}

	public function test_the_saved_name_wins_over_the_site_title() {
		update_option( 'blogname', 'My Site' );
		update_option( 'better_schema_identity', array( 'name' => 'Acme Inc' ) );

		$this->assertSame( 'Acme Inc', $this->identity()['name'] );
	}

	public function test_decodes_html_entities_in_the_site_title() {
		update_option( 'blogname', 'Tom &amp; Jerry' );

		$this->assertSame( 'Tom & Jerry', $this->identity()['name'] );
	}

	public function test_omits_the_name_when_there_is_none() {
		update_option( 'blogname', '' );

		$this->assertArrayNotHasKey( 'name', $this->identity() );
	}

	public function test_can_be_a_person() {
		update_option( 'better_schema_identity', array( 'type' => 'Person', 'name' => 'Ada' ) );

		$identity = $this->identity();

		$this->assertSame( 'Person', $identity['@type'] );
		$this->assertSame( 'Ada', $identity['name'] );
	}

	public function test_an_organization_gets_the_site_icon_as_its_logo() {
		$icon_id = $this->create_image( 'icon.png', 512, 512 );
		update_option( 'site_icon', $icon_id );

		$logo = $this->identity()['logo'];

		$this->assertSame( 'ImageObject', $logo['@type'] );
		$this->assertSame( wp_get_attachment_url( $icon_id ), $logo['url'] );
		$this->assertSame( $logo['url'], $logo['contentUrl'] );
		$this->assertSame( 512, $logo['width'] );
		$this->assertSame( 512, $logo['height'] );
	}

	public function test_the_saved_logo_wins_over_the_site_icon() {
		update_option( 'site_icon', $this->create_image( 'icon.png' ) );
		$logo_id = $this->create_image( 'brand.png', 600, 200 );
		update_option( 'better_schema_identity', array( 'logo' => $logo_id ) );

		$logo = $this->identity()['logo'];

		$this->assertSame( wp_get_attachment_url( $logo_id ), $logo['url'] );
		$this->assertSame( 600, $logo['width'] );
	}

	public function test_falls_back_to_the_site_icon_when_the_saved_logo_is_not_an_image() {
		$icon_id = $this->create_image( 'icon.png' );
		update_option( 'site_icon', $icon_id );
		$not_image = self::factory()->attachment->create_object( 'doc.pdf', 0, array( 'post_mime_type' => 'application/pdf' ) );
		update_option( 'better_schema_identity', array( 'logo' => $not_image ) );

		$this->assertSame( wp_get_attachment_url( $icon_id ), $this->identity()['logo']['url'] );
	}

	public function test_ignores_a_logo_id_that_does_not_exist() {
		update_option( 'better_schema_identity', array( 'logo' => 999999 ) );

		$this->assertArrayNotHasKey( 'logo', $this->identity() );
	}

	public function test_omits_the_logo_when_there_is_no_image_at_all() {
		$this->assertArrayNotHasKey( 'logo', $this->identity() );
	}

	public function test_omits_dimensions_that_are_not_known() {
		$id = self::factory()->attachment->create_object( 'nosize.png', 0, array( 'post_mime_type' => 'image/png' ) );
		update_option( 'better_schema_identity', array( 'logo' => $id ) );

		$logo = $this->identity()['logo'];

		$this->assertArrayNotHasKey( 'width', $logo );
		$this->assertArrayNotHasKey( 'height', $logo );
	}

	public function test_a_person_gets_the_image_property_not_logo() {
		update_option( 'better_schema_identity', array( 'type' => 'Person', 'logo' => $this->create_image() ) );

		$identity = $this->identity();

		$this->assertArrayNotHasKey( 'logo', $identity );
		$this->assertSame( 'ImageObject', $identity['image']['@type'] );
	}

	public function test_includes_same_as_when_set() {
		update_option( 'better_schema_identity', array( 'same_as' => array( 'https://example.com/acme', 'https://example.org/acme' ) ) );

		$this->assertSame( array( 'https://example.com/acme', 'https://example.org/acme' ), $this->identity()['sameAs'] );
	}

	public function test_omits_same_as_when_empty() {
		$this->assertArrayNotHasKey( 'sameAs', $this->identity() );
	}

	public function test_is_the_same_for_every_context() {
		$first  = self::factory()->post->create();
		$second = self::factory()->post->create( array( 'post_type' => 'page' ) );

		$this->assertSame(
			better_schema_piece_identity( better_schema_post_context( get_post( $first ) ) ),
			better_schema_piece_identity( better_schema_post_context( get_post( $second ) ) )
		);
	}
}
