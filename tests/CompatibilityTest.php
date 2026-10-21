<?php
/** Schema takeover contracts, independent of optional SEO plugin installation. */
class CompatibilityTest extends WP_UnitTestCase {
	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_notice_is_limited_to_settings_and_administrators() {
		if ( ! defined( 'WPSEO_VERSION' ) ) {
			define( 'WPSEO_VERSION', 'test' );
		}
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		foreach ( array( 'dashboard', 'edit-post', 'plugins' ) as $screen ) {
			set_current_screen( $screen );
			ob_start();
			better_schema_takeover_notice();
			$this->assertSame( '', ob_get_clean() );
		}
		set_current_screen( 'settings_page_better-schema-markup' );
		ob_start();
		better_schema_takeover_notice();
		$this->assertStringContainsString( 'now handles your schema', ob_get_clean() );
		update_option( 'better_schema_takeover', false );
		ob_start();
		better_schema_takeover_notice();
		$this->assertStringContainsString( 'Your SEO plugin handles frontend schema', ob_get_clean() );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );
		ob_start();
		better_schema_takeover_notice();
		$this->assertSame( '', ob_get_clean() );
		set_current_screen( 'front' );
	}

	public function test_takeover_only_changes_schema_filters() {
		$schema = array( 'WebSite' => array( '@type' => 'WebSite' ) );
		$this->assertSame( array(), apply_filters( 'wpseo_json_ld_output', $schema ) );
		$this->assertSame( array(), apply_filters( 'rank_math/json_ld', $schema ) );
		$this->assertSame( 'A title', apply_filters( 'wpseo_title', 'A title' ) );
		$this->assertSame( 'A canonical', apply_filters( 'rank_math/frontend/canonical', 'A canonical' ) );
		update_option( 'better_schema_takeover', false );
		$this->assertSame( $schema, apply_filters( 'wpseo_json_ld_output', $schema ) );
		$this->assertSame( $schema, apply_filters( 'rank_math/json_ld', $schema ) );
	}

	public function test_opt_out_without_another_plugin_still_outputs_schema() {
		update_option( 'better_schema_takeover', false );
		$post = self::factory()->post->create_and_get();
		$this->go_to( get_permalink( $post ) );
		ob_start();
		better_schema_print();
		$this->assertStringContainsString( 'application/ld+json', ob_get_clean() );
		$this->assertNotEmpty( better_schema_build_graph( better_schema_post_context( $post ) ) );
	}

	public function test_takeover_is_registered_and_requires_admin_to_save() {
		better_schema_register_takeover();
		do_action( 'rest_api_init' );
		$request = new WP_REST_Request( 'POST', '/wp/v2/settings' );
		$request->set_body_params( array( 'better_schema_takeover' => false ) );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );
		$this->assertSame( 403, rest_do_request( $request )->get_status() );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$this->assertSame( 200, rest_do_request( $request )->get_status() );
		$this->assertFalse( better_schema_takeover_enabled() );
	}
}
