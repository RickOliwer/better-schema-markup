<?php
/** Optional WPGraphQL integration using the shared graph builder. */
declare( strict_types=1 );
defined( 'ABSPATH' ) || exit;

/** Resolve only posts the current viewer can read, even for restricted GraphQL models. */
function better_schema_graphql_post( mixed $source ): ?WP_Post {
	$id = is_object( $source ) ? ( $source->databaseId ?? null ) : null;
	if ( ! is_numeric( $id ) || (int) $id < 1 ) {
		return null;
	}
	$post = get_post( (int) $id );
	if ( ! $post instanceof WP_Post ) {
		return null;
	}
	if ( ( ! is_post_publicly_viewable( $post ) || '' !== $post->post_password ) && ! current_user_can( 'edit_post', $post->ID ) ) {
		return null;
	}
	return $post;
}

function better_schema_register_graphql(): void {
	register_graphql_object_type( 'BetterSchema', array(
		'description' => __( 'Schema.org JSON-LD from Better Schema Markup.', 'better-schema-markup' ),
		'fields' => array( 'raw' => array( 'type' => 'String' ) ),
	) );
	register_graphql_field( 'ContentNode', 'betterSchema', array(
		'type' => 'BetterSchema',
		'description' => __( 'The complete JSON-LD graph for this content.', 'better-schema-markup' ),
		'resolve' => static function ( $source ) {
			$post = better_schema_graphql_post( $source );
			return $post ? better_schema_graphql_document( better_schema_context_for_post( $post ) ) : null;
		},
	) );
	register_graphql_field( 'ContentNode', 'betterSchemaType', array(
		'type' => 'String',
		'description' => __( 'Effective schema type, including post type defaults; none disables schema.', 'better-schema-markup' ),
		'resolve' => static function ( $source ) {
			$post = better_schema_graphql_post( $source );
			return $post ? better_schema_post_schema_type( $post ) : null;
		},
	) );
	register_graphql_field( 'RootQuery', 'betterSchema', array(
		'type' => 'BetterSchema',
		'description' => __( 'Site-level WebSite and publisher identity graph.', 'better-schema-markup' ),
		'resolve' => static fn() => better_schema_graphql_document( better_schema_site_context() ),
	) );
}
add_action( 'graphql_register_types', 'better_schema_register_graphql' );

/** An opted-out graph has no raw document. */
function better_schema_graphql_document( array $context ): ?array {
	$json = better_schema_encode_graph( better_schema_build_graph( $context ) );
	return '' === $json ? null : array( 'raw' => $json );
}
