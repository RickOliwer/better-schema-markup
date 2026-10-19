<?php
/**
 * Tests for the editor's JSON-LD preview endpoint.
 */

class PreviewTest extends WP_UnitTestCase {

	use GraphAssertions;

	private static $editor_id;
	private static $author_id;

	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		self::$editor_id = $factory->user->create( array( 'role' => 'editor' ) );
		self::$author_id = $factory->user->create( array( 'role' => 'author' ) );
	}

	public function set_up() {
		parent::set_up();
		do_action( 'rest_api_init' );
	}

	private function request( $post_id, array $params = array() ) {
		$request = new WP_REST_Request( 'GET', "/better-schema/v1/preview/{$post_id}" );
		$request->set_query_params( $params );

		return rest_get_server()->dispatch( $request );
	}

	public function test_returns_the_same_document_as_the_frontend() {
		wp_set_current_user( self::$editor_id );
		$post_id = self::factory()->post->create( array( 'post_title' => 'Hello' ) );

		$response = $this->request( $post_id );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame(
			better_schema_build_graph( better_schema_post_context( get_post( $post_id ) ) ),
			$response->get_data()['document']
		);
	}

	public function test_includes_validation_hints() {
		wp_set_current_user( self::$editor_id );
		$post_id = self::factory()->post->create();

		$hints = $this->request( $post_id )->get_data()['hints'];

		$this->assertContains( 'image', wp_list_pluck( $hints, 'property' ) );
	}

	public function test_previews_an_unsaved_type() {
		wp_set_current_user( self::$editor_id );
		$post_id = self::factory()->post->create();

		$document = $this->request( $post_id, array( 'type' => 'BlogPosting' ) )->get_data()['document'];

		$this->assertContains( 'BlogPosting', $this->types( $document ) );
		$this->assertNotContains( 'Article', $this->types( $document ) );
		$this->assertGraphIsConnected( $document );
		$this->assertSame( '', get_post_meta( $post_id, '_better_schema_type', true ), 'The preview must not save the type.' );
		$this->assertSame( 'Article', better_schema_post_schema_type( get_post( $post_id ) ), 'The override must not outlive the request.' );
	}

	public function test_the_unsaved_type_only_applies_to_the_previewed_post() {
		$other_id = self::factory()->post->create();
		$post_id  = self::factory()->post->create();
		$seen     = null;

		add_filter(
			'better_schema_graph',
			function ( $graph ) use ( $other_id, &$seen ) {
				$seen = better_schema_post_schema_type( get_post( $other_id ) );
				return $graph;
			}
		);

		better_schema_preview_document( get_post( $post_id ), 'none' );
		$this->assertNull( $seen, 'None turns off the graph before any filter runs.' );

		better_schema_preview_document( get_post( $post_id ), 'WebPage' );
		$this->assertSame( 'Article', $seen );
	}

	public function test_none_returns_no_document() {
		wp_set_current_user( self::$editor_id );
		$post_id = self::factory()->post->create();

		$data = $this->request( $post_id, array( 'type' => 'none' ) )->get_data();

		$this->assertNull( $data['document'] );
		$this->assertSame( array(), $data['hints'] );
	}

	public function test_previews_the_posts_page_as_the_blog() {
		wp_set_current_user( self::$editor_id );
		$page_id = self::factory()->post->create( array( 'post_type' => 'page' ) );
		update_option( 'show_on_front', 'page' );
		update_option( 'page_for_posts', $page_id );

		$document = $this->request( $page_id )->get_data()['document'];

		$this->assertContains( 'CollectionPage', $this->types( $document ) );
	}

	public function test_rejects_an_invalid_type() {
		wp_set_current_user( self::$editor_id );
		$post_id = self::factory()->post->create();

		$this->assertSame( 400, $this->request( $post_id, array( 'type' => 'Product' ) )->get_status() );
	}

	public function test_requires_permission_to_edit_the_post() {
		$post_id = self::factory()->post->create( array( 'post_author' => self::$editor_id ) );

		wp_set_current_user( 0 );
		$this->assertSame( 401, $this->request( $post_id )->get_status() );

		wp_set_current_user( self::$author_id );
		$this->assertSame( 403, $this->request( $post_id )->get_status() );
	}

	public function test_an_author_can_preview_their_own_draft() {
		wp_set_current_user( self::$author_id );
		$post_id = self::factory()->post->create(
			array(
				'post_author' => self::$author_id,
				'post_status' => 'draft',
			)
		);

		$this->assertSame( 200, $this->request( $post_id )->get_status() );
	}

	public function test_a_missing_post_is_not_found_or_forbidden() {
		wp_set_current_user( self::$editor_id );

		$this->assertContains( $this->request( 999999 )->get_status(), array( 403, 404 ) );
	}
}
