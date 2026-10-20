<?php
/**
 * Tests for the local business extension of the site identity.
 */

class BusinessTest extends WP_UnitTestCase {

	use GraphAssertions;

	private function identity() {
		return better_schema_piece_identity( better_schema_site_context() )[0];
	}

	private function enable_business( array $overrides = array() ) {
		update_option( 'better_schema_business', array_merge( array( 'enabled' => true ), $overrides ) );
	}

	public function test_the_identity_is_unchanged_until_the_business_is_enabled() {
		update_option( 'better_schema_business', array( 'telephone' => '+46 8 123 456' ) );

		$identity = $this->identity();

		$this->assertSame( 'Organization', $identity['@type'] );
		$this->assertArrayNotHasKey( 'telephone', $identity );
	}

	public function test_an_enabled_business_makes_the_organization_a_local_business() {
		$this->enable_business(
			array(
				'telephone'   => '+46 8 123 456',
				'price_range' => '$$',
				'address'     => array( 'street' => 'Storgatan 1', 'locality' => 'Stockholm', 'postal_code' => '111 22', 'country' => 'SE' ),
				'geo'         => array( 'lat' => '59.33', 'lng' => '18.06' ),
				'hours'       => array( array( 'days' => array( 'Monday', 'Tuesday' ), 'opens' => '09:00', 'closes' => '17:30' ) ),
			)
		);

		$identity = $this->identity();

		$this->assertSame( 'LocalBusiness', $identity['@type'] );
		$this->assertSame( better_schema_identity_id(), $identity['@id'] );
		$this->assertSame( '+46 8 123 456', $identity['telephone'] );
		$this->assertSame( '$$', $identity['priceRange'] );
		$this->assertSame(
			array( '@type' => 'PostalAddress', 'streetAddress' => 'Storgatan 1', 'addressLocality' => 'Stockholm', 'postalCode' => '111 22', 'addressCountry' => 'SE' ),
			$identity['address']
		);
		$this->assertSame( array( '@type' => 'GeoCoordinates', 'latitude' => 59.33, 'longitude' => 18.06 ), $identity['geo'] );
		$this->assertSame(
			array( array( '@type' => 'OpeningHoursSpecification', 'dayOfWeek' => array( 'Monday', 'Tuesday' ), 'opens' => '09:00', 'closes' => '17:30' ) ),
			$identity['openingHoursSpecification']
		);
	}

	public function test_a_person_never_becomes_a_local_business() {
		update_option( 'better_schema_identity', array( 'type' => 'Person' ) );
		$this->enable_business( array( 'telephone' => '123' ) );

		$identity = $this->identity();

		$this->assertSame( 'Person', $identity['@type'] );
		$this->assertArrayNotHasKey( 'telephone', $identity );
	}

	public function test_empty_properties_are_omitted() {
		$this->enable_business();

		$identity = $this->identity();

		foreach ( array( 'address', 'geo', 'telephone', 'priceRange', 'openingHoursSpecification' ) as $property ) {
			$this->assertArrayNotHasKey( $property, $identity );
		}
	}

	public function test_sanitizing_drops_invalid_values() {
		$business = better_schema_sanitize_business(
			array(
				'enabled'   => 'yes',
				'telephone' => array( 'x' ),
				'geo'       => array( 'lat' => '95', 'lng' => '10' ),
				'hours'     => array(
					array( 'days' => array( 'Funday' ), 'opens' => '09:00', 'closes' => '17:00' ),
					array( 'days' => array( 'Friday' ), 'opens' => '25:00', 'closes' => '17:00' ),
					array( 'days' => array( 'Friday', 'Funday' ), 'opens' => '09:00', 'closes' => '17:00' ),
					'nope',
				),
			)
		);

		$this->assertFalse( $business['enabled'] );
		$this->assertSame( '', $business['telephone'] );
		$this->assertSame( array( 'lat' => '', 'lng' => '' ), $business['geo'] );
		$this->assertSame( array( array( 'days' => array( 'Friday' ), 'opens' => '09:00', 'closes' => '17:00' ) ), $business['hours'] );
	}

	public function test_non_array_input_gives_the_defaults() {
		$this->assertSame( better_schema_business_defaults(), better_schema_sanitize_business( 'junk' ) );
	}

	public function test_the_graph_stays_connected() {
		$this->enable_business( array( 'telephone' => '123' ) );
		$post_id = self::factory()->post->create();

		$document = better_schema_build_graph( better_schema_post_context( get_post( $post_id ) ) );

		$this->assertContains( 'LocalBusiness', $this->types( $document ) );
		$this->assertGraphIsConnected( $document );
	}

	public function test_the_setting_is_registered_for_rest() {
		$setting = get_registered_settings()['better_schema_business'];

		$this->assertSame( better_schema_business_defaults(), $setting['default'] );
		$this->assertFalse( $setting['show_in_rest']['schema']['additionalProperties'] );
	}
}
