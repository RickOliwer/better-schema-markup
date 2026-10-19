<?php
/**
 * Post meta used by the editor panel and the JSON-LD output.
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

function better_schema_register_meta(): void {
	foreach ( array( 'post', 'page' ) as $post_type ) {
		register_post_meta(
			$post_type,
			'_better_schema_type',
			array(
				'type'              => 'string',
				'single'            => true,
				'default'           => better_schema_default_type( $post_type ),
				'show_in_rest'      => true,
				'sanitize_callback' => fn ( mixed $value ): string => better_schema_sanitize_type( $value, $post_type ),
				// WordPress may pass the post ID as a string or null, so it is cast rather than typed.
				'auth_callback'     => fn ( $allowed, $meta_key, $post_id ): bool => current_user_can( 'edit_post', (int) $post_id ),
			)
		);
	}
}
add_action( 'init', 'better_schema_register_meta' );

/**
 * @return string[]
 */
function better_schema_types(): array {
	return array( 'Article', 'BlogPosting', 'WebPage', 'none' );
}

/**
 * Default schema type for a post type: posts are articles, everything else is a plain page.
 */
function better_schema_default_type( string $post_type ): string {
	return 'post' === $post_type ? 'Article' : 'WebPage';
}

/**
 * Returns a valid schema type, falling back to the post type's default.
 *
 * @param mixed  $value     Stored or submitted value.
 * @param string $post_type Post type the value belongs to.
 */
function better_schema_sanitize_type( mixed $value, string $post_type = 'post' ): string {
	return in_array( $value, better_schema_types(), true ) ? $value : better_schema_default_type( $post_type );
}

/**
 * The schema type chosen for a post. Stored meta is untrusted, so a missing,
 * invalid or legacy value falls back to the post type's default.
 */
function better_schema_post_schema_type( WP_Post $post ): string {
	return better_schema_sanitize_type( get_post_meta( $post->ID, '_better_schema_type', true ), $post->post_type );
}
