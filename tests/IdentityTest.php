<?php
/**
 * Tests for the identity settings: defaults, sanitizing and registration.
 */

class IdentityTest extends WP_UnitTestCase {

	public function test_defaults_to_an_organization_with_nothing_else_set() {
		$this->assertSame(
			array(
				'type'    => 'Organization',
				'name'    => '',
				'logo'    => 0,
				'same_as' => array(),
			),
			better_schema_get_identity()
		);
	}

	public function test_reads_saved_values() {
		update_option(
			'better_schema_identity',
			array(
				'type'    => 'Person',
				'name'    => 'Ada Lovelace',
				'logo'    => 12,
				'same_as' => array( 'https://example.com/ada' ),
			)
		);

		$this->assertSame(
			array(
				'type'    => 'Person',
				'name'    => 'Ada Lovelace',
				'logo'    => 12,
				'same_as' => array( 'https://example.com/ada' ),
			),
			better_schema_get_identity()
		);
	}

	public function test_fills_missing_keys_with_defaults() {
		update_option( 'better_schema_identity', array( 'name' => 'Acme' ) );

		$identity = better_schema_get_identity();

		$this->assertSame( 'Acme', $identity['name'] );
		$this->assertSame( 'Organization', $identity['type'] );
		$this->assertSame( array(), $identity['same_as'] );
	}

	public function test_falls_back_to_defaults_for_malformed_stored_values() {
		// Bypass the sanitize callback, as a direct database edit would.
		global $wpdb;
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name = %s", 'better_schema_identity' ) );
		$wpdb->insert(
			$wpdb->options,
			array(
				'option_name'  => 'better_schema_identity',
				'option_value' => 'not-an-array',
			)
		);
		wp_cache_flush();

		$this->assertSame( better_schema_identity_defaults(), better_schema_get_identity() );
	}

	public function test_rejects_unknown_types() {
		$this->assertSame( 'Organization', better_schema_sanitize_identity( array( 'type' => 'Corporation' ) )['type'] );
		$this->assertSame( 'Organization', better_schema_sanitize_identity( array( 'type' => array( 'Person' ) ) )['type'] );
	}

	public function test_cleans_the_name() {
		$identity = better_schema_sanitize_identity( array( 'name' => "  <b>Acme</b> <script>x()</script>\n" ) );

		$this->assertSame( 'Acme', $identity['name'] );
		$this->assertSame( '', better_schema_sanitize_identity( array( 'name' => array( 'Acme' ) ) )['name'] );
	}

	public function test_logo_must_be_a_non_negative_whole_number() {
		$this->assertSame( 7, better_schema_sanitize_identity( array( 'logo' => '7' ) )['logo'] );
		$this->assertSame( 7, better_schema_sanitize_identity( array( 'logo' => -7 ) )['logo'] );
		$this->assertSame( 0, better_schema_sanitize_identity( array( 'logo' => 'abc' ) )['logo'] );
		$this->assertSame( 0, better_schema_sanitize_identity( array( 'logo' => array( 1 ) ) )['logo'] );
	}

	public function test_same_as_keeps_only_unique_http_urls() {
		$identity = better_schema_sanitize_identity(
			array(
				'same_as' => array(
					'https://example.com/a',
					'  https://example.com/a  ',
					'http://example.com/b',
					'javascript:alert(1)',
					'ftp://example.com/file',
					'not a url',
					'',
					42,
					array( 'https://example.com/nested' ),
				),
			)
		);

		$this->assertSame( array( 'https://example.com/a', 'http://example.com/b' ), $identity['same_as'] );
	}

	public function test_same_as_must_be_a_list() {
		$this->assertSame( array(), better_schema_sanitize_identity( array( 'same_as' => 'https://example.com' ) )['same_as'] );
	}

	public function test_drops_unknown_keys() {
		$identity = better_schema_sanitize_identity( array( 'evil' => 'x' ) );

		$this->assertArrayNotHasKey( 'evil', $identity );
		$this->assertSame( array( 'type', 'name', 'logo', 'same_as' ), array_keys( $identity ) );
	}

	public function test_saving_the_option_sanitizes_it() {
		update_option(
			'better_schema_identity',
			array(
				'type'    => 'Hacker',
				'same_as' => array( 'javascript:alert(1)', 'https://example.com' ),
			)
		);

		$stored = get_option( 'better_schema_identity' );

		$this->assertSame( 'Organization', $stored['type'] );
		$this->assertSame( array( 'https://example.com' ), $stored['same_as'] );
	}

	public function test_the_setting_is_registered_for_the_rest_api_with_a_schema() {
		$setting = get_registered_settings()['better_schema_identity'];

		$this->assertSame( 'object', $setting['type'] );
		$this->assertSame( better_schema_identity_defaults(), $setting['default'] );
		$this->assertSame( array( 'Organization', 'Person' ), $setting['show_in_rest']['schema']['properties']['type']['enum'] );
		$this->assertFalse( $setting['show_in_rest']['schema']['additionalProperties'] );
	}

	public function test_the_identity_id_is_stable() {
		$this->assertSame( home_url( '/' ) . '#identity', better_schema_identity_id() );
	}
}
