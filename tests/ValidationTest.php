<?php
/**
 * Tests for the validation hints shown in the editor.
 */

class ValidationTest extends WP_UnitTestCase {

	public function tear_down() {
		remove_all_filters( 'better_schema_validation_hints' );
		parent::tear_down();
	}

	private function document( array ...$entities ) {
		return array(
			'@context' => 'https://schema.org',
			'@graph'   => $entities,
		);
	}

	private function hinted( array $document ) {
		$hints = better_schema_validation_hints( $document );

		return array_map( fn ( $hint ) => $hint['type'] . '.' . $hint['property'], $hints );
	}

	public function test_a_complete_article_has_no_hints() {
		$article = array(
			'@type'    => 'Article',
			'headline' => 'Hello',
			'image'    => 'https://example.com/a.jpg',
			'author'   => array( '@id' => 'https://example.com/#person' ),
		);

		$this->assertSame( array(), better_schema_validation_hints( $this->document( $article ) ) );
	}

	public function test_an_article_without_recommended_properties_gets_hints() {
		$this->assertSame(
			array( 'Article.headline', 'Article.image', 'Article.author' ),
			$this->hinted( $this->document( array( '@type' => 'Article' ) ) )
		);
	}

	public function test_blog_postings_follow_the_article_rules() {
		$this->assertSame(
			array( 'BlogPosting.image' ),
			$this->hinted(
				$this->document(
					array(
						'@type'    => 'BlogPosting',
						'headline' => 'Hello',
						'author'   => array( '@id' => '#a' ),
					)
				)
			)
		);
	}

	public function test_an_organization_without_a_logo_gets_a_hint() {
		$this->assertSame( array( 'Organization.logo' ), $this->hinted( $this->document( array( '@type' => 'Organization' ) ) ) );
	}

	public function test_a_person_identity_needs_no_logo() {
		$this->assertSame( array(), $this->hinted( $this->document( array( '@type' => 'Person' ) ) ) );
	}

	public function test_hints_have_a_level_and_a_message() {
		$hint = better_schema_validation_hints( $this->document( array( '@type' => 'Organization' ) ) )[0];

		$this->assertSame( 'recommended', $hint['level'] );
		$this->assertNotSame( '', $hint['message'] );
	}

	public function test_an_empty_or_malformed_document_has_no_hints() {
		$this->assertSame( array(), better_schema_validation_hints( array() ) );
		$this->assertSame( array(), better_schema_validation_hints( $this->document( array( '@type' => array( 'Article', 'NewsArticle' ) ) ) ) );
		$this->assertSame( array(), better_schema_validation_hints( array( '@graph' => array( 'Article', null ) ) ) );
	}

	public function test_a_post_without_a_featured_image_is_hinted() {
		$post_id = self::factory()->post->create( array( 'post_title' => 'Hello' ) );

		$hinted = $this->hinted( better_schema_build_graph( better_schema_post_context( get_post( $post_id ) ) ) );

		$this->assertContains( 'Article.image', $hinted );
		$this->assertNotContains( 'Article.headline', $hinted );
	}

	public function test_hints_can_be_filtered() {
		add_filter( 'better_schema_validation_hints', fn () => array() );
		$this->assertSame( array(), better_schema_validation_hints( $this->document( array( '@type' => 'Article' ) ) ) );

		add_filter( 'better_schema_validation_hints', fn () => 'not a list', 20 );
		$this->assertSame( array(), better_schema_validation_hints( $this->document( array( '@type' => 'Article' ) ) ) );
	}

	private function custom_hints( string $custom, string $mode = 'add' ) {
		better_schema_register_meta();
		$post_id = self::factory()->post->create();
		update_post_meta( $post_id, '_better_schema_custom_jsonld', $custom );
		update_post_meta( $post_id, '_better_schema_custom_mode', $mode );
		$context = better_schema_post_context( get_post( $post_id ) );

		return better_schema_validation_hints( better_schema_build_graph( $context ), $context );
	}

	public function test_custom_jsonld_that_duplicates_generated_schema_is_hinted_in_add_mode() {
		$custom = '[{"@type":"WebSite","name":"Second"},{"@type":"MedicalClinic","name":"Clinic"},{"@type":"Course"}]';

		$hints = $this->custom_hints( $custom );
		$this->assertSame( array( 'WebSite', 'MedicalClinic' ), array_values( wp_list_pluck( wp_list_filter( $hints, array( 'property' => '@type' ) ), 'type' ) ) );
		$this->assertSame( 'recommended', $hints[ array_search( 'WebSite', wp_list_pluck( $hints, 'type' ), true ) ]['level'] );

		$this->assertSame( array(), wp_list_filter( $this->custom_hints( $custom, 'override' ), array( 'property' => '@type' ) ) );
	}

	public function test_custom_jsonld_on_another_domain_is_hinted_once() {
		$hints = wp_list_filter( $this->custom_hints( '[{"@type":"Course","@id":"https://other.example/#a"},{"@type":"Course","url":"https://other.example/b"}]' ), array( 'type' => 'custom' ) );
		$this->assertCount( 1, $hints );
		$this->assertStringContainsString( 'other.example', reset( $hints )['message'] );

		$this->assertSame( array(), wp_list_filter( $this->custom_hints( '{"@type":"Course","@id":"' . home_url( '/#course' ) . '"}' ), array( 'type' => 'custom' ) ) );
	}

	public function test_the_hint_filter_receives_the_context() {
		$seen = null;
		add_filter(
			'better_schema_validation_hints',
			function ( $hints, $document, $context ) use ( &$seen ) {
				$seen = $context;
				return $hints;
			},
			10,
			3
		);

		better_schema_validation_hints( $this->document( array( '@type' => 'Article' ) ), array( 'type' => 'home' ) );

		$this->assertSame( array( 'type' => 'home' ), $seen );
	}
}
