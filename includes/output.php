<?php
/**
 * Prints the JSON-LD block in the page head.
 */

defined( 'ABSPATH' ) || exit;

function better_schema_build_data( WP_Post $post ) {
	$type = get_post_meta( $post->ID, '_better_schema_type', true );

	if ( 'none' === $type ) {
		return null;
	}

	$data = array(
		'@context'      => 'https://schema.org',
		'@type'         => $type ? $type : 'Article',
		'headline'      => wp_strip_all_tags( get_the_title( $post ) ),
		'url'           => get_permalink( $post ),
		'datePublished' => get_the_date( 'c', $post ),
		'dateModified'  => get_the_modified_date( 'c', $post ),
	);

	$author = get_the_author_meta( 'display_name', $post->post_author );
	if ( $author ) {
		$data['author'] = array(
			'@type' => 'Person',
			'name'  => $author,
		);
	}

	$image = get_the_post_thumbnail_url( $post, 'full' );
	if ( $image ) {
		$data['image'] = $image;
	}

	return $data;
}

function better_schema_print() {
	if ( ! is_singular() ) {
		return;
	}

	$data = better_schema_build_data( get_queried_object() );

	if ( ! $data ) {
		return;
	}

	printf(
		"<script type=\"application/ld+json\">%s</script>\n",
		wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG )
	);
}
add_action( 'wp_head', 'better_schema_print' );
