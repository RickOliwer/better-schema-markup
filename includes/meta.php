<?php
/**
 * Post meta used by the editor panel and the JSON-LD output.
 */

defined( 'ABSPATH' ) || exit;

function better_schema_register_meta() {
	foreach ( array( 'post', 'page' ) as $post_type ) {
		register_post_meta(
			$post_type,
			'_better_schema_type',
			array(
				'type'              => 'string',
				'single'            => true,
				'default'           => better_schema_default_type( $post_type ),
				'show_in_rest'      => true,
				'sanitize_callback' => function ( $value ) use ( $post_type ) {
					return better_schema_sanitize_type( $value, $post_type );
				},
				'auth_callback'     => function ( $allowed, $meta_key, $post_id ) {
					return current_user_can( 'edit_post', $post_id );
				},
			)
		);
	}
}
add_action( 'init', 'better_schema_register_meta' );

function better_schema_types() {
	return array( 'Article', 'BlogPosting', 'WebPage', 'none' );
}

/**
 * Default schema type for a post type: posts are articles, everything else is a plain page.
 *
 * @param string $post_type Post type name.
 * @return string
 */
function better_schema_default_type( $post_type ) {
	return 'post' === $post_type ? 'Article' : 'WebPage';
}

/**
 * Returns a valid schema type, falling back to the post type's default.
 *
 * @param mixed  $value     Stored or submitted value.
 * @param string $post_type Post type the value belongs to.
 * @return string
 */
function better_schema_sanitize_type( $value, $post_type = 'post' ) {
	return in_array( $value, better_schema_types(), true ) ? $value : better_schema_default_type( $post_type );
}

/**
 * The schema type chosen for a post. Stored meta is untrusted, so a missing,
 * invalid or legacy value falls back to the post type's default.
 *
 * @param WP_Post $post Post.
 * @return string
 */
function better_schema_post_schema_type( WP_Post $post ) {
	return better_schema_sanitize_type( get_post_meta( $post->ID, '_better_schema_type', true ), $post->post_type );
}
