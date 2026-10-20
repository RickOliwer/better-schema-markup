<?php
/**
 * Admin requests must use the installation URL, independently of the frontend.
 */

class AdminApiTest extends WP_UnitTestCase {

	public function test_api_url_uses_backend_with_both_permalink_configurations() {
		$original_https = $_SERVER['HTTPS'] ?? null;
		try {
			foreach ( array( 'http', 'https' ) as $scheme ) {
				$_SERVER['HTTPS'] = 'https' === $scheme ? 'on' : 'off';
				foreach ( array( '', '/%postname%/' ) as $permalinks ) {
					update_option( 'permalink_structure', $permalinks );
					foreach ( array( 'example.org', 'cloud.example.org', 'cloud.example.org/wordpress' ) as $host_path ) {
						$backend = $scheme . '://' . $host_path;
						// wp-env defines WP_HOME/WP_SITEURL; override before their option filters.
						$home_filter = static fn() => $scheme . '://example.org';
						$site_filter = static fn() => $backend;
						add_filter( 'pre_option_home', $home_filter );
						add_filter( 'pre_option_siteurl', $site_filter );
						try {
							$url = better_schema_admin_api_url();
							$this->assertSame( $backend . '/index.php', strtok( $url, '?' ) );
							parse_str( wp_parse_url( $url, PHP_URL_QUERY ), $query );
							$this->assertSame( '/', $query['rest_route'] );
						} finally {
							remove_filter( 'pre_option_home', $home_filter );
							remove_filter( 'pre_option_siteurl', $site_filter );
						}
					}
				}
			}
		} finally {
			if ( null === $original_https ) {
				unset( $_SERVER['HTTPS'] );
			} else {
				$_SERVER['HTTPS'] = $original_https;
			}
		}
	}

	public function test_configuration_is_available_before_settings_and_editor_scripts() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		foreach ( array( 'settings', 'editor' ) as $screen ) {
			wp_scripts()->remove( 'better-schema-editor' );
			if ( 'settings' === $screen ) {
				better_schema_enqueue_settings_assets( 'settings_page_better-schema-markup' );
			} else {
				better_schema_enqueue_editor_assets();
			}
			$scripts = wp_scripts();
			$before = implode( "\n", $scripts->get_data( 'better-schema-editor', 'before' ) );
			$this->assertStringContainsString( 'window.betterSchemaApi = ', $before );
			$this->assertStringContainsString( wp_json_encode( better_schema_admin_api_url() ), $before );
			$this->assertContains( 'wp-api-fetch', $scripts->registered['better-schema-editor']->deps );
		}
	}
}
