<?php
/**
 * Tests for how custom JSON-LD combines with the generated graph.
 */

class CustomJsonldModeTest extends WP_UnitTestCase {

	use GraphAssertions;

	public function set_up() {
		parent::set_up();
		// The test framework clears registered meta between tests; real requests register it on `init`.
		better_schema_register_meta();
	}

	public function tear_down() {
		delete_option( 'better_schema_frontend_url' );
		delete_option( 'better_schema_identity' );
		parent::tear_down();
	}

	private function graph_for( int $post_id, $custom, string $mode = '' ): array {
		update_post_meta( $post_id, '_better_schema_custom_jsonld', wp_slash( is_string( $custom ) ? $custom : wp_json_encode( $custom ) ) );

		if ( '' !== $mode ) {
			update_post_meta( $post_id, '_better_schema_custom_mode', $mode );
		}

		return better_schema_build_graph( better_schema_post_context( get_post( $post_id ) ) );
	}

	private function of_type( array $document, string $type ): array {
		return array_values( array_filter( $document['@graph'], fn ( $entity ) => ( $entity['@type'] ?? '' ) === $type ) );
	}

	/**
	 * The SEO team's own site-level schema, with ids on their own domain.
	 */
	private function clinic_schema(): array {
		return array(
			array(
				'@type'     => 'WebSite',
				'@id'       => 'https://clinic.example/#website',
				'url'       => 'https://clinic.example/',
				'name'      => 'Clinic',
				'publisher' => array( '@id' => 'https://clinic.example/#clinic' ),
			),
			array(
				'@type'     => 'MedicalClinic',
				'@id'       => 'https://clinic.example/#clinic',
				'name'      => 'Clinic',
				'telephone' => '+46101401020',
			),
		);
	}

	public function test_add_is_the_default_and_keeps_generated_entities() {
		$post_id  = self::factory()->post->create();
		$document = $this->graph_for( $post_id, $this->clinic_schema() );

		$this->assertSame( 'add', better_schema_get_custom_mode( get_post( $post_id ) ) );
		$this->assertCount( 2, $this->of_type( $document, 'WebSite' ) );
		$this->assertContains( 'Organization', $this->types( $document ) );
		$this->assertContains( 'MedicalClinic', $this->types( $document ) );
	}

	public function test_override_replaces_the_site_and_identity_and_keeps_links() {
		$post_id  = self::factory()->post->create();
		$document = $this->graph_for( $post_id, $this->clinic_schema(), 'override' );

		$this->assertSame( array( 'WebSite', 'MedicalClinic', 'WebPage' ), array_slice( $this->types( $document ), 0, 3 ) );
		$this->assertCount( 1, $this->of_type( $document, 'WebSite' ) );
		$this->assertNotContains( 'Organization', $this->types( $document ) );
		$this->assertSame( 'https://clinic.example/#website', $this->entity( $document, 'WebPage' )['isPartOf']['@id'] );
		$this->assertSame( 'https://clinic.example/#clinic', $this->entity( $document, 'Article' )['publisher']['@id'] );
		$this->assertGraphIsConnected( $document );
	}

	public function test_override_replaces_an_entity_with_the_same_id_in_place() {
		$post_id  = self::factory()->post->create();
		$document = $this->graph_for(
			$post_id,
			array(
				'@type' => 'WebPage',
				'@id'   => better_schema_identity_id(),
				'name'  => 'Not really a page',
			),
			'override'
		);

		$this->assertSame( array( 'WebSite', 'WebPage', 'WebPage' ), array_slice( $this->types( $document ), 0, 3 ) );
		$this->assertSame( 'Not really a page', $document['@graph'][1]['name'] );
		$this->assertGraphIsConnected( $document );
	}

	public function test_override_matches_ids_written_with_the_frontend_url() {
		update_option( 'better_schema_frontend_url', 'https://public.example' );
		$post_id  = self::factory()->post->create();
		$document = $this->graph_for(
			$post_id,
			array(
				'@type' => 'WebSite',
				'@id'   => 'https://public.example/#website',
				'name'  => 'Custom',
			),
			'override'
		);

		$this->assertCount( 1, $this->of_type( $document, 'WebSite' ) );
		$this->assertSame( 'Custom', $this->entity( $document, 'WebSite' )['name'] );
		$this->assertGraphIsConnected( $document );
	}

	public function test_an_override_without_an_id_keeps_the_generated_id() {
		$post_id  = self::factory()->post->create();
		$document = $this->graph_for(
			$post_id,
			array(
				'@type' => 'NewsArticle',
				'name'  => 'News',
			),
			'override'
		);

		$this->assertNotContains( 'Article', $this->types( $document ) );
		$this->assertSame( get_permalink( $post_id ) . '#article', $this->entity( $document, 'NewsArticle' )['@id'] );
		$this->assertGraphIsConnected( $document );
	}

	public function test_a_person_only_replaces_a_person_identity() {
		$post_id = self::factory()->post->create();
		$person  = array(
			'@type' => 'Person',
			'@id'   => 'https://clinic.example/#doctor',
			'name'  => 'Doctor',
		);

		$document = $this->graph_for( $post_id, $person, 'override' );
		$this->assertContains( 'Organization', $this->types( $document ) );

		update_option( 'better_schema_identity', array( 'type' => 'Person' ) );
		$document = better_schema_build_graph( better_schema_post_context( get_post( $post_id ) ) );

		$this->assertNull( $this->entity( $document, 'Organization' ) );
		$this->assertSame( 'https://clinic.example/#doctor', $this->entity( $document, 'WebSite' )['publisher']['@id'] );
		$this->assertGraphIsConnected( $document );
	}

	public function test_unmatched_and_extra_entities_are_appended() {
		$post_id  = self::factory()->post->create();
		$document = $this->graph_for(
			$post_id,
			array(
				array(
					'@type' => 'WebSite',
					'@id'   => 'https://clinic.example/#website',
				),
				array(
					'@type' => 'WebSite',
					'@id'   => 'https://clinic.example/#other-website',
				),
				array(
					'@type' => 'Course',
					'name'  => 'Intro',
				),
			),
			'override'
		);

		$this->assertSame( 'https://clinic.example/#website', $document['@graph'][0]['@id'] );
		$this->assertSame( array( 'WebSite', 'Course' ), array_slice( $this->types( $document ), -2 ) );
		$this->assertSame( 'https://clinic.example/#other-website', $document['@graph'][ count( $document['@graph'] ) - 2 ]['@id'] );
	}

	public function test_replace_outputs_only_the_custom_entities() {
		$post_id  = self::factory()->post->create();
		$document = $this->graph_for( $post_id, $this->clinic_schema(), 'replace' );

		$this->assertSame( array( 'WebSite', 'MedicalClinic' ), $this->types( $document ) );
		$this->assertGraphIsConnected( $document );
	}

	public function test_replace_without_custom_entities_outputs_the_generated_graph() {
		$post_id = self::factory()->post->create();

		$this->assertSame(
			better_schema_build_graph( better_schema_post_context( get_post( $post_id ) ) ),
			$this->graph_for( $post_id, '', 'replace' )
		);
		$this->assertContains( 'Article', $this->types( $this->graph_for( $post_id, '', 'replace' ) ) );
	}

	public function test_none_turns_off_replaced_schema_too() {
		$post_id = self::factory()->post->create();
		update_post_meta( $post_id, '_better_schema_type', 'none' );

		$this->assertSame( array(), $this->graph_for( $post_id, $this->clinic_schema(), 'replace' ) );
	}

	public function test_invalid_modes_fall_back_to_add() {
		$this->assertSame( 'add', better_schema_sanitize_custom_mode( 'merge' ) );
		$this->assertSame( 'add', better_schema_sanitize_custom_mode( null ) );
		$this->assertSame( 'override', better_schema_sanitize_custom_mode( 'override' ) );

		$post_id = self::factory()->post->create();
		update_post_meta( $post_id, '_better_schema_custom_mode', 'merge' );
		$this->assertSame( 'add', better_schema_get_custom_mode( get_post( $post_id ) ) );
	}

	public function test_the_mode_is_saved_through_rest() {
		$user_id = self::factory()->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $user_id );
		do_action( 'rest_api_init' );
		$post_id = self::factory()->post->create( array( 'post_author' => $user_id ) );

		$request = new WP_REST_Request( 'POST', "/wp/v2/posts/{$post_id}" );
		$request->set_body_params( array( 'meta' => array( '_better_schema_custom_mode' => 'override' ) ) );
		rest_get_server()->dispatch( $request );
		$this->assertSame( 'override', better_schema_get_custom_mode( get_post( $post_id ) ) );

		$request->set_body_params( array( 'meta' => array( '_better_schema_custom_mode' => 'merge' ) ) );
		$this->assertSame( 400, rest_get_server()->dispatch( $request )->get_status() );
		$this->assertSame( 'override', better_schema_get_custom_mode( get_post( $post_id ) ) );
	}
}
