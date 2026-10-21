<?php
/**
 * BreadcrumbList schema piece.
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Stable `@id` of the breadcrumb entity for a context.
 *
 * @param array $context Context.
 * @return string
 */
function better_schema_breadcrumb_id( array $context ): string {
	return better_schema_entity_id( better_schema_context_url( $context ), 'breadcrumb' );
}

/**
 * The trail from the home page to what the context describes.
 *
 * The front page and search results have no trail.
 *
 * @param array $context Context.
 * @return array<int, array{name: string, url: string}> Items in order, or an empty array.
 */
function better_schema_breadcrumb_items( array $context ): array {
	if ( 'search' === ( $context['type'] ?? '' ) || better_schema_context_is_front_page( $context ) ) {
		return array();
	}

	$url  = better_schema_context_url( $context );
	$name = better_schema_context_name( $context );

	if ( '' === $url || '' === $name ) {
		return array();
	}

	$items = array(
		array(
			'name' => __( 'Home', 'better-schema-markup' ),
			'url'  => home_url( '/' ),
		),
	);

	foreach ( better_schema_breadcrumb_parents( $context ) as $parent ) {
		$items[] = $parent;
	}

	$items[] = array(
		'name' => $name,
		'url'  => $url,
	);

	return $items;
}

/**
 * Items between the home page and the current item.
 *
 * @param array $context Context.
 * @return array<int, array{name: string, url: string}>
 */
function better_schema_breadcrumb_parents( array $context ): array {
	switch ( $context['type'] ?? '' ) {
		case 'post':
			$post = $context['post'] ?? null;
			return $post instanceof WP_Post ? better_schema_post_breadcrumb_parents( $post ) : array();

		case 'term':
			$term = $context['term'] ?? null;
			return $term instanceof WP_Term ? better_schema_term_ancestor_items( $term ) : array();

		case 'date':
			$items = array();
			$year  = (int) ( $context['year'] ?? 0 );
			$month = (int) ( $context['month'] ?? 0 );
			$day   = (int) ( $context['day'] ?? 0 );

			if ( $month ) {
				$items[] = array(
					'name' => better_schema_date_name( $year, 0, 0 ),
					'url'  => better_schema_date_url( $year, 0, 0 ),
				);
			}

			if ( $day ) {
				$items[] = array(
					'name' => better_schema_date_name( $year, $month, 0 ),
					'url'  => better_schema_date_url( $year, $month, 0 ),
				);
			}

			return $items;
	}

	return array();
}

/**
 * Parents of a post: ancestor pages, the post type archive, or the post's category trail.
 *
 * @param WP_Post $post Post.
 * @return array<int, array{name: string, url: string}>
 */
function better_schema_post_breadcrumb_parents( WP_Post $post ): array {
	if ( is_post_type_hierarchical( $post->post_type ) ) {
		$items = array();

		foreach ( array_reverse( get_post_ancestors( $post ) ) as $ancestor_id ) {
			$link = get_permalink( $ancestor_id );

			if ( is_string( $link ) ) {
				$items[] = array(
					'name' => better_schema_plain_text( get_the_title( $ancestor_id ) ),
					'url'  => $link,
				);
			}
		}

		return $items;
	}

	if ( 'post' !== $post->post_type ) {
		$archive = get_post_type_archive_link( $post->post_type );
		$object  = get_post_type_object( $post->post_type );

		if ( ! $archive || ! $object ) {
			return array();
		}

		return array(
			array(
				'name' => better_schema_plain_text( $object->labels->name ),
				'url'  => $archive,
			),
		);
	}

	$category = better_schema_breadcrumb_category( $post );

	if ( ! $category ) {
		return array();
	}

	$items = better_schema_term_ancestor_items( $category );
	$link  = get_term_link( $category );

	if ( is_string( $link ) ) {
		$items[] = array(
			'name' => better_schema_plain_text( $category->name ),
			'url'  => $link,
		);
	}

	return $items;
}

/**
 * The category shown in a post's breadcrumb trail.
 *
 * Defaults to the post's first category by name, skipping the site's default
 * category ("Uncategorized"). Filter `better_schema_breadcrumb_category`
 * receives that category (or null) and the post, and returns a WP_Term or null.
 *
 * @param WP_Post $post Post.
 * @return WP_Term|null
 */
function better_schema_breadcrumb_category( WP_Post $post ): ?WP_Term {
	$default  = (int) get_option( 'default_category' );
	$category = null;

	foreach ( get_the_category( $post->ID ) as $candidate ) {
		if ( $candidate->term_id !== $default ) {
			$category = $candidate;
			break;
		}
	}

	$category = apply_filters( 'better_schema_breadcrumb_category', $category, $post );

	return $category instanceof WP_Term ? $category : null;
}

/**
 * Items for a term's ancestors, top-most first.
 *
 * @param WP_Term $term Term.
 * @return array<int, array{name: string, url: string}>
 */
function better_schema_term_ancestor_items( WP_Term $term ): array {
	$items = array();

	foreach ( array_reverse( get_ancestors( $term->term_id, $term->taxonomy, 'taxonomy' ) ) as $ancestor_id ) {
		$ancestor = get_term( $ancestor_id, $term->taxonomy );
		$link     = $ancestor instanceof WP_Term ? get_term_link( $ancestor ) : '';

		if ( is_string( $link ) && '' !== $link ) {
			$items[] = array(
				'name' => better_schema_plain_text( $ancestor->name ),
				'url'  => $link,
			);
		}
	}

	return $items;
}

/**
 * Describes the breadcrumb trail as a BreadcrumbList.
 *
 * @param array $context Context.
 * @return array List with one entity, or an empty array when there is no trail.
 */
function better_schema_piece_breadcrumb( array $context ): array {
	$items = better_schema_breadcrumb_items( $context );

	if ( count( $items ) < 2 ) {
		return array();
	}

	$elements = array();

	foreach ( array_values( $items ) as $index => $item ) {
		$elements[] = array(
			'@type'    => 'ListItem',
			'position' => $index + 1,
			'name'     => $item['name'],
			'item'     => $item['url'],
		);
	}

	return array(
		array(
			'@type'           => 'BreadcrumbList',
			'@id'             => better_schema_breadcrumb_id( $context ),
			'itemListElement' => $elements,
		),
	);
}
