<?php
/** Uninstall deletes only owned data and preserves content. */
require_once dirname( __DIR__ ) . '/includes/uninstall-data.php';
class UninstallTest extends WP_UnitTestCase {
	public function test_network_cleanup_restores_the_current_blog() {
		$original = get_current_blog_id();
		if ( is_multisite() ) {
			$other = self::factory()->blog->create();
			switch_to_blog( $other );
			update_option( 'better_schema_setup_complete', true );
			update_option( 'unrelated_option', 'keep' );
			restore_current_blog();
			better_schema_delete_all_data();
			switch_to_blog( $other );
			$this->assertSame( 'absent', get_option( 'better_schema_setup_complete', 'absent' ) );
			$this->assertSame( 'keep', get_option( 'unrelated_option' ) );
			restore_current_blog();
		} else {
			better_schema_delete_all_data();
		}
		$this->assertSame( $original, get_current_blog_id() );
	}

	public function test_cleanup_removes_all_owned_data_and_preserves_posts_and_other_settings() {
		$id = self::factory()->post->create();
		foreach ( array( 'better_schema_identity', 'better_schema_business', 'better_schema_frontend_url', 'better_schema_type_defaults', 'better_schema_setup_complete', 'better_schema_takeover' ) as $key ) {
			update_option( $key, 'test' );
		}
		foreach ( array( '_better_schema_type', '_better_schema_event', '_better_schema_recipe', '_better_schema_faq', '_better_schema_custom_jsonld' ) as $key ) {
			add_post_meta( $id, $key, 'test' );
		}
		update_option( 'unrelated_option', 'keep' );
		update_post_meta( $id, '_yoast_wpseo_schema_article_type', 'BlogPosting' );
		set_transient( 'better_schema_vimeo_12345', array( 'title' => 'Video' ), 60 );
		set_transient( 'another_transient', 'keep', 60 );
		better_schema_delete_all_data();
		$this->assertNotNull( get_post( $id ) );
		$this->assertSame( 'keep', get_option( 'unrelated_option' ) );
		$this->assertSame( 'keep', get_transient( 'another_transient' ) );
		$this->assertSame( 'BlogPosting', get_post_meta( $id, '_yoast_wpseo_schema_article_type', true ) );
		foreach ( array_keys( get_post_meta( $id ) ) as $key ) {
			$this->assertStringStartsNotWith( '_better_schema_', $key );
		}
		$this->assertFalse( get_transient( 'better_schema_vimeo_12345' ) );
		foreach ( array( 'better_schema_identity', 'better_schema_business', 'better_schema_frontend_url', 'better_schema_type_defaults', 'better_schema_setup_complete', 'better_schema_takeover' ) as $key ) {
			$this->assertSame( 'absent', get_option( $key, 'absent' ) );
		}
	}
}
