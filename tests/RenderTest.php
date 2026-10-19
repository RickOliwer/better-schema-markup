<?php
/**
 * Tests for JSON-LD rendering.
 */

class RenderTest extends WP_UnitTestCase {

	private function decode( $html ) {
		preg_match( '#^<script type="application/ld\+json">(.*)</script>\n$#s', $html, $matches );

		return json_decode( $matches[1] ?? '', true );
	}

	public function test_renders_nothing_for_an_empty_document() {
		$this->assertSame( '', better_schema_render_script( array() ) );
	}

	public function test_renders_one_script_element_that_decodes_to_the_document() {
		$document = array(
			'@context' => 'https://schema.org',
			'@graph'   => array(
				array(
					'@type'    => 'Article',
					'headline' => 'Hello',
				),
			),
		);

		$html = better_schema_render_script( $document );

		$this->assertSame( 1, substr_count( $html, '<script' ) );
		$this->assertSame( $document, $this->decode( $html ) );
	}

	public function test_a_closing_script_tag_in_a_value_cannot_end_the_element() {
		$document = array(
			'@context' => 'https://schema.org',
			'@graph'   => array( array( 'headline' => '</script><script>alert(1)</script>' ) ),
		);

		$html = better_schema_render_script( $document );

		$this->assertSame( 1, substr_count( $html, '</script>' ) );
		$this->assertSame( $document, $this->decode( $html ) );
	}

	public function test_keeps_slashes_and_unicode_readable() {
		$document = array(
			'@context' => 'https://schema.org',
			'@graph'   => array(
				array(
					'url'      => 'https://example.com/a/b/',
					'headline' => 'Hej världen',
				),
			),
		);

		$html = better_schema_render_script( $document );

		$this->assertStringContainsString( 'https://example.com/a/b/', $html );
		$this->assertStringContainsString( 'Hej världen', $html );
	}
}
