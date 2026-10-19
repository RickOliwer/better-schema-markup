<?php
/**
 * Post meta used by the editor panel and the JSON-LD output.
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

function better_schema_register_meta(): void {
	foreach ( better_schema_configurable_post_types() as $post_type ) {
		// REST only exposes registered meta for post types supporting custom fields.
		if ( ! post_type_supports( $post_type, 'custom-fields' ) ) {
			add_post_type_support( $post_type, 'custom-fields' );
		}

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
add_action( 'init', 'better_schema_register_meta', 100 );

/**
 * Public post types available through the REST API and block editor.
 *
 * @return string[]
 */
function better_schema_configurable_post_types(): array {
	$types = get_post_types( array( 'public' => true, 'show_in_rest' => true ), 'objects' );
	$names = array( 'post', 'page' );

	foreach ( $types as $type ) {
		if ( ! $type->_builtin && ! in_array( $type->name, $names, true ) ) {
			$names[] = $type->name;
		}
	}

	return $names;
}

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
	$defaults = better_schema_get_type_defaults();

	return $defaults[ $post_type ] ?? better_schema_builtin_default_type( $post_type );
}

/**
 * Defaults before any site setting is saved.
 */
function better_schema_builtin_default_type( string $post_type ): string {
	return 'post' === $post_type ? 'Article' : 'WebPage';
}

/**
 * @return array<string, string>
 */
function better_schema_type_defaults(): array {
	$defaults = array();

	foreach ( better_schema_configurable_post_types() as $post_type ) {
		$defaults[ $post_type ] = better_schema_builtin_default_type( $post_type );
	}

	return $defaults;
}

/**
 * @param mixed $value Stored or submitted defaults.
 * @return array<string, string>
 */
function better_schema_sanitize_type_defaults( mixed $value ): array {
	$defaults = better_schema_type_defaults();

	if ( ! is_array( $value ) ) {
		return $defaults;
	}

	foreach ( $defaults as $post_type => $default ) {
		if ( isset( $value[ $post_type ] ) && in_array( $value[ $post_type ], better_schema_types(), true ) ) {
			$defaults[ $post_type ] = $value[ $post_type ];
		}
	}

	return $defaults;
}

/**
 * @return array<string, string>
 */
function better_schema_get_type_defaults(): array {
	return better_schema_sanitize_type_defaults( get_option( 'better_schema_type_defaults', array() ) );
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
	if ( ! metadata_exists( 'post', $post->ID, '_better_schema_type' ) ) {
		return better_schema_default_type( $post->post_type );
	}

	return better_schema_sanitize_type( get_post_meta( $post->ID, '_better_schema_type', true ), $post->post_type );
}
