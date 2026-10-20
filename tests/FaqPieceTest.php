<?php
/**
 * Tests for the FAQPage schema piece.
 */

class FaqPieceTest extends WP_UnitTestCase {

	use GraphAssertions;

	private function faq_post( $questions ) {
		$post_id = self::factory()->post->create( array( 'post_title' => 'Help' ) );
		update_post_meta( $post_id, '_better_schema_type', 'FAQPage' );
		update_post_meta( $post_id, '_better_schema_faq', $questions );

		return get_post( $post_id );
	}

	public function test_describes_questions_and_answers() {
		$post = $this->faq_post(
			array(
				array( 'question' => 'Is it free?', 'answer' => 'Yes.' ),
				array( 'question' => 'Half done', 'answer' => '' ),
				'junk',
			)
		);

		$faq = better_schema_piece_faq( better_schema_post_context( $post ) )[0];

		$this->assertSame( 'FAQPage', $faq['@type'] );
		$this->assertSame(
			array( array( '@type' => 'Question', 'name' => 'Is it free?', 'acceptedAnswer' => array( '@type' => 'Answer', 'text' => 'Yes.' ) ) ),
			$faq['mainEntity']
		);
	}

	public function test_invalid_stored_values_give_no_questions() {
		$faq = better_schema_piece_faq( better_schema_post_context( $this->faq_post( 'not a list' ) ) )[0];

		$this->assertArrayNotHasKey( 'mainEntity', $faq );
	}

	public function test_the_graph_stays_connected() {
		$document = better_schema_build_graph( better_schema_post_context( $this->faq_post( array( array( 'question' => 'Q', 'answer' => 'A' ) ) ) ) );

		$this->assertContains( 'FAQPage', $this->types( $document ) );
		$this->assertGraphIsConnected( $document );
	}

	public function test_nothing_is_output_for_other_types() {
		$post_id = self::factory()->post->create();

		$this->assertSame( array(), better_schema_piece_faq( better_schema_post_context( get_post( $post_id ) ) ) );
	}
}
