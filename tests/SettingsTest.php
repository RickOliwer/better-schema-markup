<?php
/**
 * Tests for configurable defaults and first-run settings.
 */

class SettingsTest extends WP_UnitTestCase {

	public function set_up() {
		parent::set_up();
		better_schema_register_settings();
		better_schema_register_meta();
	}

	public function test_defaults_apply_to_existing_and_new_posts_without_saved_meta() {
		$existing = self::factory()->post->create();
		$this->assertSame( 'Article', better_schema_post_schema_type( get_post( $existing ) ) );

		update_option( 'better_schema_type_defaults', array( 'post' => 'BlogPosting' ) );
		$new = self::factory()->post->create();

		$this->assertSame( 'BlogPosting', better_schema_post_schema_type( get_post( $existing ) ) );
		$this->assertSame( 'BlogPosting', better_schema_post_schema_type( get_post( $new ) ) );
		$this->assertSame( 'BlogPosting', better_schema_piece_article( better_schema_post_context( get_post( $existing ) ) )[0]['@type'] );
	}

	public function test_explicit_post_choice_wins_over_site_default() {
		$post_id = self::factory()->post->create();
		update_post_meta( $post_id, '_better_schema_type', 'WebPage' );
		update_option( 'better_schema_type_defaults', array( 'post' => 'BlogPosting' ) );

		$this->assertSame( 'WebPage', better_schema_post_schema_type( get_post( $post_id ) ) );
		$this->assertSame( array(), better_schema_piece_article( better_schema_post_context( get_post( $post_id ) ) ) );
	}

	public function test_none_as_a_default_suppresses_the_graph() {
		update_option( 'better_schema_type_defaults', array( 'post' => 'none' ) );
		$post_id = self::factory()->post->create();

		$this->assertSame( array(), better_schema_build_graph( better_schema_post_context( get_post( $post_id ) ) ) );
	}

	public function test_invalid_defaults_are_ignored() {
		$defaults = better_schema_sanitize_type_defaults(
			array( 'post' => 'Product', 'page' => 'BlogPosting', 'other' => 'none' )
		);

		$this->assertSame( 'Article', $defaults['post'] );
		$this->assertSame( 'BlogPosting', $defaults['page'] );
		$this->assertArrayNotHasKey( 'other', $defaults );
		$this->assertSame( better_schema_type_defaults(), better_schema_sanitize_type_defaults( 'invalid' ) );
	}

	public function test_public_rest_custom_post_type_has_a_default_and_editable_meta() {
		register_post_type( 'book', array( 'public' => true, 'show_in_rest' => true ) );
		better_schema_register_settings();
		better_schema_register_meta();
		$book_id = self::factory()->post->create( array( 'post_type' => 'book' ) );

		$this->assertContains( 'book', better_schema_configurable_post_types() );
		$this->assertTrue( post_type_supports( 'book', 'custom-fields' ) );
		$this->assertSame( 'WebPage', better_schema_post_schema_type( get_post( $book_id ) ) );
		$this->assertArrayHasKey( 'book', get_registered_settings()['better_schema_type_defaults']['show_in_rest']['schema']['properties'] );

		update_option( 'better_schema_type_defaults', array( 'book' => 'Article' ) );
		$this->assertSame( 'Article', better_schema_post_schema_type( get_post( $book_id ) ) );
		update_post_meta( $book_id, '_better_schema_type', 'none' );
		$this->assertSame( 'none', better_schema_post_schema_type( get_post( $book_id ) ) );

		unregister_post_type( 'book' );
	}

	public function test_non_rest_custom_post_type_is_not_configurable() {
		register_post_type( 'private_book', array( 'public' => true, 'show_in_rest' => false ) );
		$this->assertNotContains( 'private_book', better_schema_configurable_post_types() );
		unregister_post_type( 'private_book' );
	}

	public function test_first_run_is_skipped_for_existing_identity_and_after_completion() {
		$this->assertFalse( better_schema_setup_is_complete() );
		update_option( 'better_schema_identity', array( 'type' => 'Person', 'name' => 'Ada' ) );
		$this->assertTrue( better_schema_setup_is_complete() );
		delete_option( 'better_schema_identity' );
		update_option( 'better_schema_setup_complete', true );
		$this->assertTrue( better_schema_setup_is_complete() );
	}

	public function test_rest_settings_require_administrator_permission() {
		do_action( 'rest_api_init' );
		wp_set_current_user( 0 );
		$this->assertSame( 401, rest_do_request( new WP_REST_Request( 'GET', '/wp/v2/settings' ) )->get_status() );
		$blocked = new WP_REST_Request( 'POST', '/wp/v2/settings' );
		$blocked->set_body_params( array( 'better_schema_type_defaults' => array( 'post' => 'BlogPosting' ) ) );
		$this->assertSame( 401, rest_do_request( $blocked )->get_status() );

		$admin = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin );
		$response = rest_do_request( new WP_REST_Request( 'GET', '/wp/v2/settings' ) );
		$this->assertSame( 200, $response->get_status() );
		$this->assertArrayHasKey( 'better_schema_type_defaults', $response->get_data() );

		$save = new WP_REST_Request( 'POST', '/wp/v2/settings' );
		$save->set_body_params(
			array(
				'better_schema_identity'       => array( 'type' => 'Person', 'name' => 'Ada', 'logo' => 0, 'same_as' => array() ),
				'better_schema_type_defaults' => array( 'post' => 'BlogPosting', 'page' => 'WebPage' ),
				'better_schema_setup_complete' => true,
			)
		);
		$saved = rest_do_request( $save );
		$this->assertSame( 200, $saved->get_status() );
		$this->assertSame( 'Person', better_schema_get_identity()['type'] );
		$this->assertSame( 'BlogPosting', better_schema_default_type( 'post' ) );
		$this->assertTrue( better_schema_setup_is_complete() );
	}
}
