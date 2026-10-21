<?php
/**
 * Tests for the VideoObject schema piece.
 */

class VideoPieceTest extends WP_UnitTestCase {

	use GraphAssertions;

	public function test_vimeo_requires_consent_even_with_cached_details() {
		$calls = 0;
		add_filter( 'pre_http_request', function () use ( &$calls ) {
			++$calls;
			return new WP_Error( 'unexpected', 'No request should be made.' );
		} );
		$post = self::factory()->post->create_and_get( array( 'post_content' => 'https://vimeo.com/123456789' ) );
		$context = better_schema_post_context( $post );
		$this->assertSame( array(), better_schema_piece_video( $context ) );
		set_transient( 'better_schema_vimeo_123456789', array( 'title' => 'Cached', 'thumbnail' => 'https://example.com/video.jpg' ), 60 );
		$this->assertNull( better_schema_vimeo_details( '123456789' ) );
		update_option( 'better_schema_vimeo_enabled', true );
		$this->assertCount( 1, better_schema_piece_video( $context ) );
		update_option( 'better_schema_vimeo_enabled', false );
		$this->assertSame( array(), better_schema_piece_video( $context ) );
		foreach ( array( 'yes', 'false', array( true ), 2 ) as $invalid ) {
			// Simulate malformed legacy data without the registered sanitizer normalizing it.
			$filter = static fn () => $invalid;
			add_filter( 'pre_option_better_schema_vimeo_enabled', $filter );
			$this->assertNull( better_schema_vimeo_details( '123456789' ) );
			remove_filter( 'pre_option_better_schema_vimeo_enabled', $filter );
		}
		$this->assertSame( 0, $calls );
	}

	public function test_finds_youtube_urls_in_every_common_form() {
		$content = implode(
			"\n",
			array(
				'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
				'<iframe src="https://www.youtube.com/embed/aaaaaaaaaaa"></iframe>',
				'https://youtu.be/bbbbbbbbbbb?t=3',
				'https://www.youtube-nocookie.com/embed/ccccccccccc',
				'https://www.youtube.com/watch?feature=share&amp;v=ddddddddddd',
			)
		);

		$ids = wp_list_pluck( better_schema_find_videos( $content ), 'id' );

		$this->assertSame( array( 'dQw4w9WgXcQ', 'aaaaaaaaaaa', 'bbbbbbbbbbb', 'ccccccccccc', 'ddddddddddd' ), $ids );
	}

	public function test_finds_vimeo_urls_and_ignores_duplicates_and_other_links() {
		$content = 'https://vimeo.com/123456789 and <iframe src="https://player.vimeo.com/video/123456789"></iframe> https://vimeo.com/channels/staffpicks/987654321 https://example.com/watch?v=dQw4w9WgXcQ';

		$this->assertSame(
			array(
				array( 'provider' => 'vimeo', 'id' => '123456789' ),
				array( 'provider' => 'vimeo', 'id' => '987654321' ),
			),
			better_schema_find_videos( $content )
		);
	}

	public function test_limits_the_number_of_videos() {
		$content = '';
		foreach ( range( 1, 8 ) as $i ) {
			$content .= ' https://youtu.be/' . str_repeat( (string) $i, 11 );
		}

		$this->assertCount( BETTER_SCHEMA_MAX_VIDEOS, better_schema_find_videos( $content ) );
	}

	public function test_describes_a_youtube_video_on_the_webpage() {
		$post_id = self::factory()->post->create( array( 'post_title' => 'Watch this', 'post_content' => '<!-- wp:embed -->https://youtu.be/dQw4w9WgXcQ<!-- /wp:embed -->' ) );
		$context = better_schema_post_context( get_post( $post_id ) );

		$video = better_schema_piece_video( $context )[0];

		$this->assertSame( 'VideoObject', $video['@type'] );
		$this->assertSame( 'Watch this', $video['name'] );
		$this->assertSame( 'https://www.youtube.com/embed/dQw4w9WgXcQ', $video['embedUrl'] );
		$this->assertSame( 'https://img.youtube.com/vi/dQw4w9WgXcQ/hqdefault.jpg', $video['thumbnailUrl'] );
		$this->assertSame( get_the_date( 'c', $post_id ), $video['uploadDate'] );
		$this->assertSame( better_schema_webpage_id( $context ), $video['isPartOf']['@id'] );
		$this->assertGraphIsConnected( better_schema_build_graph( $context ) );
	}

	public function test_vimeo_details_come_from_oembed_and_are_cached() {
		update_option( 'better_schema_vimeo_enabled', true );
		$calls = 0;
		add_filter(
			'pre_http_request',
			function () use ( &$calls ) {
				++$calls;

				return array(
					'response' => array( 'code' => 200, 'message' => 'OK' ),
					'body'     => wp_json_encode( array( 'title' => 'Vimeo clip', 'thumbnail_url' => 'https://i.vimeocdn.com/video/1.jpg' ) ),
				);
			}
		);
		$post_id = self::factory()->post->create( array( 'post_content' => 'https://vimeo.com/123456789' ) );
		$context = better_schema_post_context( get_post( $post_id ) );

		$video = better_schema_piece_video( $context )[0];
		better_schema_piece_video( $context );

		$this->assertSame( 'Vimeo clip', $video['name'] );
		$this->assertSame( 'https://player.vimeo.com/video/123456789', $video['embedUrl'] );
		$this->assertSame( 'https://i.vimeocdn.com/video/1.jpg', $video['thumbnailUrl'] );
		$this->assertSame( 1, $calls );
	}

	public function test_a_vimeo_video_without_a_thumbnail_is_left_out() {
		update_option( 'better_schema_vimeo_enabled', true );
		add_filter( 'pre_http_request', fn () => new WP_Error( 'down', 'Offline' ) );
		$post_id = self::factory()->post->create( array( 'post_content' => 'https://vimeo.com/123456789' ) );

		$this->assertSame( array(), better_schema_piece_video( better_schema_post_context( get_post( $post_id ) ) ) );
	}

	public function test_posts_without_videos_get_no_entity() {
		$post_id = self::factory()->post->create( array( 'post_content' => 'Just text.' ) );

		$this->assertSame( array(), better_schema_piece_video( better_schema_post_context( get_post( $post_id ) ) ) );
	}
}
