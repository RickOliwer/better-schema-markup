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
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => 'better_schema_rest_preview',
			'permission_callback' => fn ( WP_REST_Request $request ): bool => current_user_can( 'edit_post', (int) $request['id'] ),
			'args'                => array(
				'id'   => array(
					'type'     => 'integer',
					'required' => true,
				),
				// The type picked in the editor, which may not be saved yet.
				'type' => array(
					'type' => 'string',
					'enum' => better_schema_types(),
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

	$type     = $request['type'];
	$document = better_schema_preview_document( $post, is_string( $type ) ? $type : null );

	return new WP_REST_Response(
		array(
			'document' => $document ? $document : null,
			'hints'    => better_schema_validation_hints( $document ),
		)
	);
}

/**
 * Builds the document for a post as the frontend would, optionally with a
 * schema type that has not been saved yet.
 *
 * @param WP_Post     $post Post.
 * @param string|null $type Schema type to use instead of the saved one, or null for the saved one.
 */
function better_schema_preview_document( WP_Post $post, ?string $type = null ): array {
	$context = better_schema_context_for_post( $post );

	if ( null === $type ) {
		return better_schema_build_graph( $context );
	}

	// Answers the meta lookup with the unsaved type for this post only, then removes itself.
	$use_type = static fn ( $value, $object_id, $meta_key ) => ( (int) $object_id === $post->ID && '_better_schema_type' === $meta_key ) ? array( $type ) : $value;

	add_filter( 'get_post_metadata', $use_type, 10, 3 );

	try {
		return better_schema_build_graph( $context );
	} finally {
		remove_filter( 'get_post_metadata', $use_type, 10 );
	}
}
