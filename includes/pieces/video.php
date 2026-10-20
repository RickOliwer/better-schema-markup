<?php
/**
 * VideoObject schema piece, detected from YouTube and Vimeo links in the content.
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Most videos described per post.
 */
const BETTER_SCHEMA_MAX_VIDEOS = 5;

/**
 * Finds YouTube and Vimeo videos linked or embedded in post content.
 *
 * @param string $content Post content, including block markup.
 * @return array<int, array{provider: string, id: string}> Videos in order of appearance, without duplicates.
 */
function better_schema_find_videos( string $content ): array {
	$patterns = array(
		'youtube' => '~(?:youtube\.com/(?:watch\?(?:[^"\'\s<]*?&(?:amp;|#038;)?)?v=|embed/|shorts/|live/)|youtube-nocookie\.com/embed/|youtu\.be/)([A-Za-z0-9_-]{11})(?![A-Za-z0-9_-])~i',
		'vimeo'   => '~(?:player\.)?vimeo\.com/(?:video/|(?:[A-Za-z0-9_]+/)*)(\d{5,})~i',
	);

	$found = array();
	foreach ( $patterns as $provider => $pattern ) {
		if ( ! preg_match_all( $pattern, $content, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE ) ) {
			continue;
		}

		foreach ( $matches as $match ) {
			$found[ $provider . ':' . $match[1][0] ] = array(
				'offset'   => $match[0][1],
				'provider' => $provider,
				'id'       => $match[1][0],
			);
		}
	}

	uasort( $found, static fn ( array $a, array $b ): int => $a['offset'] <=> $b['offset'] );

	return array_map(
		static fn ( array $video ): array => array(
			'provider' => $video['provider'],
			'id'       => $video['id'],
		),
		array_slice( array_values( $found ), 0, BETTER_SCHEMA_MAX_VIDEOS )
	);
}

/**
 * Looks up a Vimeo video's title and thumbnail through oEmbed.
 *
 * Results, including failures, are cached in a transient so page views do not wait on Vimeo.
 *
 * @param string $id Vimeo video ID.
 * @return array{title: string, thumbnail: string}|null Null when Vimeo gave no usable answer.
 */
function better_schema_vimeo_details( string $id ): ?array {
	$key    = 'better_schema_vimeo_' . $id;
	$cached = get_transient( $key );

	if ( is_array( $cached ) ) {
		return $cached ? $cached : null;
	}

	$response = wp_remote_get(
		add_query_arg( 'url', rawurlencode( 'https://vimeo.com/' . $id ), 'https://vimeo.com/api/oembed.json' ),
		array( 'timeout' => 3 )
	);
	$data     = 200 === wp_remote_retrieve_response_code( $response ) ? json_decode( wp_remote_retrieve_body( $response ), true ) : null;

	if ( ! is_array( $data ) || ! is_string( $data['thumbnail_url'] ?? null ) || ! preg_match( '#^https://#', $data['thumbnail_url'] ) ) {
		// Remember the failure briefly so a broken lookup is not retried on every request.
		set_transient( $key, array(), HOUR_IN_SECONDS );

		return null;
	}

	$details = array(
		'title'     => better_schema_plain_text( $data['title'] ?? '' ),
		'thumbnail' => esc_url_raw( $data['thumbnail_url'] ),
	);
	set_transient( $key, $details, WEEK_IN_SECONDS );

	return $details;
}

/**
 * Describes the YouTube and Vimeo videos in a post as VideoObjects on its WebPage.
 *
 * A video without a known thumbnail is left out, because Google requires one.
 *
 * @param array $context Context.
 * @return array List of entities, or an empty array.
 */
function better_schema_piece_video( array $context ): array {
	$post = $context['post'] ?? null;

	if ( 'post' !== ( $context['type'] ?? '' ) || ! $post instanceof WP_Post ) {
		return array();
	}

	$url = get_permalink( $post );

	if ( ! $url ) {
		return array();
	}

	$entities = array();

	foreach ( better_schema_find_videos( $post->post_content ) as $video ) {
		$name = better_schema_plain_text( get_the_title( $post ) );

		if ( 'youtube' === $video['provider'] ) {
			$embed     = 'https://www.youtube.com/embed/' . $video['id'];
			$thumbnail = 'https://img.youtube.com/vi/' . $video['id'] . '/hqdefault.jpg';
		} else {
			$details = better_schema_vimeo_details( $video['id'] );

			if ( ! $details ) {
				continue;
			}

			$embed     = 'https://player.vimeo.com/video/' . $video['id'];
			$thumbnail = $details['thumbnail'];
			$name      = '' !== $details['title'] ? $details['title'] : $name;
		}

		$entity = array(
			'@type'        => 'VideoObject',
			'@id'          => better_schema_entity_id( $url, 'video-' . ( count( $entities ) + 1 ) ),
			'isPartOf'     => array( '@id' => better_schema_webpage_id( $context ) ),
			'embedUrl'     => $embed,
			'thumbnailUrl' => $thumbnail,
			'uploadDate'   => get_the_date( 'c', $post ),
		);

		if ( '' !== $name ) {
			$entity['name'] = $name;
		}

		$entities[] = $entity;
	}

	return $entities;
}
