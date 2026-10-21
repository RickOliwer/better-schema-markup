<?php
/**
 * REST endpoint for the editor's JSON-LD preview. Runs the same graph builder
 * as the frontend for the post being edited.
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

function better_schema_register_preview_route(): void {
	register_rest_route(
		'better-schema/v1',
		'/preview/(?P<id>\d+)',
		array(
			// POST carries unsaved custom JSON-LD, which can be too long for a URL.
			'methods'             => WP_REST_Server::READABLE . ', ' . WP_REST_Server::CREATABLE,
			'callback'            => 'better_schema_rest_preview',
			'permission_callback' => fn ( WP_REST_Request $request ): bool => current_user_can( 'edit_post', (int) $request['id'] ),
			'args'                => array(
				'id'          => array(
					'type'     => 'integer',
					'required' => true,
				),
				// The type picked in the editor, which may not be saved yet.
				'type'        => array(
					'type' => 'string',
					'enum' => better_schema_types(),
				),
				// Custom JSON-LD and its mode from the editor, which may not be saved yet.
				'custom'      => array(
					'type' => 'string',
				),
				'custom_mode' => array(
					'type' => 'string',
					'enum' => better_schema_custom_modes(),
				),
			),
		)
	);
}
add_action( 'rest_api_init', 'better_schema_register_preview_route' );

/**
 * Responds with `document` (the JSON-LD document, or null when there is no
 * schema) and `hints` (from `better_schema_validation_hints()`).
 */
function better_schema_rest_preview( WP_REST_Request $request ): WP_REST_Response|WP_Error {
	$post = get_post( (int) $request['id'] );

	if ( ! $post instanceof WP_Post ) {
		return new WP_Error( 'better_schema_post_not_found', __( 'Post not found.', 'better-schema-markup' ), array( 'status' => 404 ) );
	}

	$unsaved = array();

	foreach ( array( 'type' => '_better_schema_type', 'custom' => '_better_schema_custom_jsonld', 'custom_mode' => '_better_schema_custom_mode' ) as $param => $meta_key ) {
		if ( is_string( $request[ $param ] ) ) {
			$unsaved[ $meta_key ] = $request[ $param ];
		}
	}

	// Invalid custom JSON-LD is previewed as it would be saved: empty.
	if ( isset( $unsaved['_better_schema_custom_jsonld'] ) ) {
		$unsaved['_better_schema_custom_jsonld'] = better_schema_sanitize_custom_jsonld( $unsaved['_better_schema_custom_jsonld'] );
	}

	// Hints read the custom JSON-LD too, so they are built with the unsaved values as well.
	$response = better_schema_with_unsaved_meta(
		$post,
		$unsaved,
		static function () use ( $post ): array {
			$context  = better_schema_context_for_post( $post );
			$document = better_schema_build_graph( $context );

			return array(
				'document' => $document ? $document : null,
				'hints'    => better_schema_validation_hints( $document, $context ),
			);
		}
	);

	return new WP_REST_Response( $response );
}

/**
 * Builds the document for a post as the frontend would, optionally with a
 * schema type that has not been saved yet.
 *
 * @param WP_Post     $post Post.
 * @param string|null $type Schema type to use instead of the saved one, or null for the saved one.
 */
function better_schema_preview_document( WP_Post $post, ?string $type = null ): array {
	return better_schema_with_unsaved_meta(
		$post,
		null === $type ? array() : array( '_better_schema_type' => $type ),
		static fn (): array => better_schema_build_graph( better_schema_context_for_post( $post ) )
	);
}

/**
 * Runs a callback while the post's meta lookups return unsaved values.
 *
 * @param WP_Post               $post     Post the values belong to. Other posts are not affected.
 * @param array<string, string> $unsaved  Map of meta key to unsaved value.
 * @param callable              $callback Callback to run.
 * @return mixed The callback's result.
 */
function better_schema_with_unsaved_meta( WP_Post $post, array $unsaved, callable $callback ): mixed {
	if ( ! $unsaved ) {
		return $callback();
	}

	// Answers the meta lookups for this post only, then removes itself.
	$use_unsaved = static fn ( $value, $object_id, $meta_key ) => ( (int) $object_id === $post->ID && is_string( $meta_key ) && array_key_exists( $meta_key, $unsaved ) ) ? array( $unsaved[ $meta_key ] ) : $value;

	add_filter( 'get_post_metadata', $use_unsaved, 10, 3 );

	try {
		return $callback();
	} finally {
		remove_filter( 'get_post_metadata', $use_unsaved, 10 );
	}
}
