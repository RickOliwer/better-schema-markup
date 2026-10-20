<?php
/**
 * Classic editor integration and native post-form persistence.
 */

class ClassicEditorTest extends WP_UnitTestCase {

	private $original_post;
	public function set_up() {
		parent::set_up();
		$this->original_post = $_POST;
		$_POST = array();
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		better_schema_register_meta();
	}

	public function tear_down() {
		$_POST = $this->original_post;
		parent::tear_down();
	}

	private function submit( int $post_id, array $changes ): void {
		$_POST = array(
			'better_schema_classic_nonce' => wp_create_nonce( 'better_schema_classic_' . $post_id ),
			'better_schema_classic_meta'  => wp_slash( wp_json_encode( $changes ) ),
		);
		wp_update_post( array( 'ID' => $post_id, 'post_title' => 'Saved from the classic editor' ) );
	}

	public function test_meta_box_is_registered_with_gutenberg_disabled_but_not_in_block_editor() {
		global $wp_meta_boxes;
		$post = self::factory()->post->create_and_get( array( 'post_type' => 'page' ) );
		add_filter( 'use_block_editor_for_post', '__return_false' );
		try {
			do_action( 'add_meta_boxes', 'page', $post );
			$this->assertSame( 'better_schema_render_classic_meta_box', $wp_meta_boxes['page']['normal']['default']['better-schema-classic']['callback'] );
		} finally {
			remove_filter( 'use_block_editor_for_post', '__return_false' );
			unset( $wp_meta_boxes['page']['normal']['default']['better-schema-classic'] );
		}
		add_filter( 'use_block_editor_for_post', '__return_true' );
		try {
			do_action( 'add_meta_boxes', 'page', $post );
			$this->assertArrayNotHasKey( 'better-schema-classic', $wp_meta_boxes['page']['normal']['default'] );
		} finally {
			remove_filter( 'use_block_editor_for_post', '__return_true' );
		}
	}

	public function test_native_post_save_persists_sanitized_fields_and_json_backslashes() {
		$id = self::factory()->post->create( array( 'post_type' => 'page' ) );
		$custom = '{"@type":"Thing","name":"A \\"quoted\\" path C:\\\\example"}';
		$this->submit( $id, array(
			'_better_schema_type'          => 'Event',
			'_better_schema_event'         => array( 'start' => '2026-12-31T19:00', 'location_name' => '<b>Town Hall</b>', 'offers_url' => 'javascript:alert(1)' ),
			'_better_schema_recipe'        => array( 'ingredients' => array( 'Flour', 'Water' ), 'instructions' => array( 'Mix', 'Bake' ), 'prep_minutes' => 15, 'cook_minutes' => 25, 'yield' => '4 servings' ),
			'_better_schema_faq'           => array( array( 'question' => 'Where?', 'answer' => 'C:\\example' ), array( 'question' => 'Incomplete', 'answer' => '' ) ),
			'_better_schema_custom_jsonld' => $custom,
		) );
		$this->assertSame( 'Event', better_schema_post_schema_type( get_post( $id ) ) );
		$this->assertSame( '2026-12-31T19:00', better_schema_get_event( get_post( $id ) )['start'] );
		$this->assertSame( 'Town Hall', better_schema_get_event( get_post( $id ) )['location_name'] );
		$this->assertSame( '', better_schema_get_event( get_post( $id ) )['offers_url'] );
		$this->assertSame( array( 'Flour', 'Water' ), better_schema_get_recipe( get_post( $id ) )['ingredients'] );
		$this->assertSame( 25, better_schema_get_recipe( get_post( $id ) )['cook_minutes'] );
		$this->assertSame( array( array( 'question' => 'Where?', 'answer' => 'C:\\example' ) ), better_schema_get_faq( get_post( $id ) ) );
		$this->assertSame( json_decode( $custom, true ), better_schema_get_custom_entities( get_post( $id ) )[0] );
	}

	public function test_untouched_fields_and_inherited_type_are_preserved() {
		$id = self::factory()->post->create();
		update_post_meta( $id, '_better_schema_faq', array( array( 'question' => 'Q', 'answer' => 'A' ) ) );
		$this->submit( $id, array() );
		$this->assertFalse( metadata_exists( 'post', $id, '_better_schema_type' ) );
		$this->submit( $id, array( '_better_schema_recipe' => array( 'yield' => '2 servings' ) ) );
		$this->assertFalse( metadata_exists( 'post', $id, '_better_schema_type' ) );
		$this->assertSame( array( array( 'question' => 'Q', 'answer' => 'A' ) ), better_schema_get_faq( get_post( $id ) ) );
		update_option( 'better_schema_type_defaults', array( 'post' => 'BlogPosting' ) );
		$this->assertSame( 'BlogPosting', better_schema_post_schema_type( get_post( $id ) ) );
		$this->submit( $id, array( '_better_schema_type' => 'none' ) );
		$this->assertSame( 'none', better_schema_post_schema_type( get_post( $id ) ) );
		$this->assertSame( '2 servings', better_schema_get_recipe( get_post( $id ) )['yield'] );
		$this->submit( $id, array( '_better_schema_faq' => array() ) );
		$this->assertSame( array(), better_schema_get_faq( get_post( $id ) ) );
	}

	public function test_missing_invalid_or_other_post_nonce_cannot_change_meta() {
		$id = self::factory()->post->create();
		$other = self::factory()->post->create();
		foreach ( array( null, 'invalid', wp_create_nonce( 'better_schema_classic_' . $other ) ) as $nonce ) {
			$_POST = array( 'better_schema_classic_nonce' => $nonce, 'better_schema_classic_meta' => '{"_better_schema_type":"none"}' );
			wp_update_post( array( 'ID' => $id, 'post_title' => 'Unrelated save' ) );
			$this->assertFalse( metadata_exists( 'post', $id, '_better_schema_type' ) );
		}
	}

	public function test_user_without_post_permission_cannot_save_schema() {
		$id = self::factory()->post->create();
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );
		$this->submit( $id, array( '_better_schema_type' => 'none' ) );
		$this->assertFalse( metadata_exists( 'post', $id, '_better_schema_type' ) );
	}

	public function test_revisions_and_autosaves_do_not_change_parent_meta() {
		$id = self::factory()->post->create();
		$_POST = array( 'better_schema_classic_nonce' => wp_create_nonce( 'better_schema_classic_' . $id ), 'better_schema_classic_meta' => '{"_better_schema_type":"none"}' );
		foreach ( array( $id . '-revision-v1', $id . '-autosave-v1' ) as $name ) {
			$revision = wp_insert_post( array( 'post_type' => 'revision', 'post_parent' => $id, 'post_status' => 'inherit', 'post_name' => $name ) );
			better_schema_save_classic_meta( $revision, get_post( $revision ) );
			$this->assertFalse( metadata_exists( 'post', $id, '_better_schema_type' ) );
		}
	}

	public function test_malformed_payloads_and_unknown_fields_preserve_saved_values() {
		$id = self::factory()->post->create();
		$this->submit( $id, array( '_better_schema_type' => 'FAQPage', '_better_schema_custom_jsonld' => '{"@type":"Thing","name":"Original"}' ) );
		$this->submit( $id, array( '_better_schema_type' => 'Invalid', '_better_schema_faq' => 'bad', '_better_schema_custom_jsonld' => '{bad', '_yoast_wpseo_title' => 'Overwrite', '_better_schema_unknown' => 'Ignore' ) );
		$this->assertSame( 'FAQPage', better_schema_post_schema_type( get_post( $id ) ) );
		$this->assertSame( 'Original', better_schema_get_custom_entities( get_post( $id ) )[0]['name'] );
		$this->assertFalse( metadata_exists( 'post', $id, '_yoast_wpseo_title' ) );
		$this->assertFalse( metadata_exists( 'post', $id, '_better_schema_unknown' ) );
		foreach ( array( '{bad', 'null', '"string"', array() ) as $payload ) {
			$_POST['better_schema_classic_meta'] = $payload;
			wp_update_post( array( 'ID' => $id, 'post_title' => 'Unrelated save' ) );
			$this->assertSame( 'FAQPage', better_schema_post_schema_type( get_post( $id ) ) );
		}
	}
}
