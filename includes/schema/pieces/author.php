<?php
/**
 * Author schema piece: the Person who wrote an article, or whose archive is viewed.
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * The author of a post, if the user still exists.
 *
 * @param WP_Post $post Post.
 * @return WP_User|null
 */
function better_schema_post_author( WP_Post $post ): ?WP_User {
	$user = get_userdata( (int) $post->post_author );

	return $user instanceof WP_User ? $user : null;
}

/**
 * Stable `@id` of a user's Person entity.
 *
 * Uses a site-specific hash so the `@id` does not reveal the user ID or login.
 *
 * @param WP_User $user User.
 * @return string
 */
function better_schema_person_id( WP_User $user ): string {
	return better_schema_entity_id( home_url( '/' ), 'person-' . substr( wp_hash( 'better_schema_person_' . $user->ID ), 0, 12 ) );
}

/**
 * Describes the article's author, or the author whose archive is viewed, as a Person.
 *
 * @param array $context Context.
 * @return array List with one entity, or an empty array.
 */
function better_schema_piece_author( array $context ): array {
	$type = $context['type'] ?? '';
	$user = null;

	if ( 'author' === $type && ( $context['user'] ?? null ) instanceof WP_User ) {
		$user = $context['user'];
	} elseif ( 'post' === $type && ( $context['post'] ?? null ) instanceof WP_Post && ( better_schema_post_is_article( $context['post'] ) || 'Recipe' === better_schema_post_schema_type( $context['post'] ) ) ) {
		$user = better_schema_post_author( $context['post'] );
	}

	if ( ! $user ) {
		return array();
	}

	$entity = array(
		'@type' => 'Person',
		'@id'   => better_schema_person_id( $user ),
		'url'   => get_author_posts_url( $user->ID, $user->user_nicename ),
	);

	$name = better_schema_plain_text( $user->display_name );
	if ( '' !== $name ) {
		$entity['name'] = $name;
	}

	$description = better_schema_plain_text( get_the_author_meta( 'description', $user->ID ) );
	if ( '' !== $description ) {
		$entity['description'] = $description;
	}

	$same_as = better_schema_sanitize_urls( array( $user->user_url ) );
	if ( $same_as ) {
		$entity['sameAs'] = $same_as;
	}

	return array( $entity );
}
