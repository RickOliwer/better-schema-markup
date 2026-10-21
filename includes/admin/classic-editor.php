<?php
/**
 * Schema controls for the classic editor, saved with the native post form.
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

function better_schema_add_classic_meta_box( string $post_type, WP_Post $post ): void {
	if ( ! in_array( $post_type, better_schema_configurable_post_types(), true ) || use_block_editor_for_post( $post ) || ! current_user_can( 'edit_post', $post->ID ) ) {
		return;
	}

	add_meta_box( 'better-schema-classic', __( 'Schema', 'better-schema-markup' ), 'better_schema_render_classic_meta_box', $post_type, 'normal', 'default' );
}
add_action( 'add_meta_boxes', 'better_schema_add_classic_meta_box', 10, 2 );

function better_schema_render_classic_meta_box( WP_Post $post ): void {
	wp_nonce_field( 'better_schema_classic_' . $post->ID, 'better_schema_classic_nonce', false );
	echo '<div id="better-schema-classic-editor"><p>' . esc_html__( 'Loading schema controls…', 'better-schema-markup' ) . '</p></div>';
	echo '<noscript><p>' . esc_html__( 'Enable JavaScript to edit schema settings. Existing settings are preserved when you save.', 'better-schema-markup' ) . '</p></noscript>';
}

function better_schema_enqueue_classic_assets( string $hook_suffix ): void {
	global $post;

	if ( ! in_array( $hook_suffix, array( 'post.php', 'post-new.php' ), true ) || ! $post instanceof WP_Post || ! in_array( $post->post_type, better_schema_configurable_post_types(), true ) || use_block_editor_for_post( $post ) || ! current_user_can( 'edit_post', $post->ID ) ) {
		return;
	}

	better_schema_enqueue_editor_assets();
	wp_add_inline_script(
		'better-schema-editor',
		'window.betterSchemaClassic = ' . wp_json_encode(
			array(
				'postId'      => $post->ID,
				'savedAt'     => $post->post_modified_gmt,
				'testableUrl' => 'publish' === $post->post_status && '' === $post->post_password ? get_permalink( $post ) : null,
				'meta'        => array(
					'_better_schema_type'          => better_schema_post_schema_type( $post ),
					'_better_schema_event'         => better_schema_get_event( $post ),
					'_better_schema_recipe'        => better_schema_get_recipe( $post ),
					'_better_schema_faq'           => better_schema_get_faq( $post ),
					'_better_schema_custom_jsonld' => better_schema_sanitize_custom_jsonld( get_post_meta( $post->ID, '_better_schema_custom_jsonld', true ) ),
					'_better_schema_custom_mode'   => better_schema_get_custom_mode( $post ),
				),
			),
			JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
		) . ';',
		'before'
	);
}
add_action( 'admin_enqueue_scripts', 'better_schema_enqueue_classic_assets' );

function better_schema_save_classic_meta( int $post_id, WP_Post $post ): void {
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) || ! in_array( $post->post_type, better_schema_configurable_post_types(), true ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$nonce = isset( $_POST['better_schema_classic_nonce'] ) && is_string( $_POST['better_schema_classic_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['better_schema_classic_nonce'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'better_schema_classic_' . $post_id ) || ! isset( $_POST['better_schema_classic_meta'] ) || ! is_string( $_POST['better_schema_classic_meta'] ) ) {
		return;
	}

	// Decode and sanitize each allowed field before persisting the form submission.
	$changes = filter_var(
		wp_unslash( $_POST['better_schema_classic_meta'] ),
		FILTER_CALLBACK,
		array( 'options' => 'better_schema_sanitize_classic_changes' )
	);
	if ( ! is_array( $changes ) ) {
		return;
	}
	foreach ( $changes as $key => $value ) {
		// update_post_meta() unslashes values; protect JSON and text backslashes.
		update_post_meta( $post_id, $key, wp_slash( $value ) );
	}
}
add_action( 'save_post', 'better_schema_save_classic_meta', 10, 2 );

/**
 * Decode the form payload and sanitize only supported fields, leaving all others untouched.
 */
function better_schema_sanitize_classic_changes( mixed $payload ): array {
	$changes = is_string( $payload ) ? json_decode( $payload, true ) : null;
	if ( ! is_array( $changes ) ) {
		return array();
	}

	$clean      = array();
	$sanitizers = array(
		'_better_schema_event'         => 'better_schema_sanitize_event',
		'_better_schema_recipe'        => 'better_schema_sanitize_recipe',
		'_better_schema_faq'           => 'better_schema_sanitize_faq',
		'_better_schema_custom_jsonld' => 'better_schema_sanitize_custom_jsonld',
	);
	foreach ( $changes as $key => $value ) {
		if ( '_better_schema_type' === $key ) {
			if ( in_array( $value, better_schema_types(), true ) ) {
				$clean[ $key ] = $value;
			}
			continue;
		}
		if ( '_better_schema_custom_mode' === $key ) {
			if ( in_array( $value, better_schema_custom_modes(), true ) ) {
				$clean[ $key ] = $value;
			}
			continue;
		}
		if ( ! isset( $sanitizers[ $key ] ) ) {
			continue;
		}
		if ( '_better_schema_custom_jsonld' === $key ) {
			if ( ! is_string( $value ) || ( '' !== trim( $value ) && ! better_schema_parse_custom_jsonld( $value ) ) ) {
				continue;
			}
		} elseif ( ! is_array( $value ) ) {
			continue;
		}

		$clean[ $key ] = $sanitizers[ $key ]( $value );
	}

	return $clean;
}
