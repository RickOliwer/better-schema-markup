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
				'default'           => 'Article',
				'show_in_rest'      => true,
				'sanitize_callback' => 'better_schema_sanitize_type',
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

function better_schema_sanitize_type( $value ) {
	return in_array( $value, better_schema_types(), true ) ? $value : 'Article';
}
