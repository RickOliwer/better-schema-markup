<?php
/**
 * Schema context: what is being described.
 *
 * Contexts are plain arrays passed explicitly to the graph builder and the
 * schema pieces. They never read request globals. The resolver in
 * includes/output.php builds one for the current request.
 *
 * Context types:
 * - `post`              a single post, page or other singular item (`post`)
 * - `blog`              the page set as the posts page (`post`)
 * - `home`              a front page that lists the latest posts
 * - `term`              a category, tag or other taxonomy archive (`term`)
 * - `author`            an author archive (`user`)
 * - `post_type_archive` a post type archive (`post_type`)
 * - `date`              a year, month or day archive (`year`, `month`, `day`)
 * - `search`            search results (`query`)
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * @return array{type: string, post: WP_Post}
 */
function better_schema_post_context( WP_Post $post ): array {
	return array(
		'type' => 'post',
		'post' => $post,
	);
}

/**
 * @param WP_Post $page The page set as the posts page.
 * @return array{type: string, post: WP_Post}
 */
function better_schema_blog_context( WP_Post $page ): array {
	return array(
		'type' => 'blog',
		'post' => $page,
	);
}

/**
 * @return array{type: string}
 */
function better_schema_home_context(): array {
	return array( 'type' => 'home' );
}

/**
 * @return array{type: string, term: WP_Term}
 */
function better_schema_term_context( WP_Term $term ): array {
	return array(
		'type' => 'term',
		'term' => $term,
	);
}

/**
 * @return array{type: string, user: WP_User}
 */
function better_schema_author_context( WP_User $user ): array {
	return array(
		'type' => 'author',
		'user' => $user,
	);
}

/**
 * @return array{type: string, post_type: string}
 */
function better_schema_post_type_archive_context( string $post_type ): array {
	return array(
		'type'      => 'post_type_archive',
		'post_type' => $post_type,
	);
}

/**
 * Invalid months and days are dropped, so the context describes the widest valid period.
 *
 * @param int $year  Year.
 * @param int $month Month (1–12), or 0 for a year archive.
 * @param int $day   Day of the month, or 0 for a year or month archive.
 * @return array{type: string, year: int, month: int, day: int}
 */
function better_schema_date_context( int $year, int $month = 0, int $day = 0 ): array {
	$year = max( 0, $year );

	if ( $month < 1 || $month > 12 ) {
		$month = 0;
		$day   = 0;
	}

	if ( $day && ! checkdate( $month, $day, max( 1, $year ) ) ) {
		$day = 0;
	}

	return array(
		'type'  => 'date',
		'year'  => $year,
		'month' => $month,
		'day'   => $day,
	);
}

/**
 * @return array{type: string, query: string}
 */
function better_schema_search_context( string $query ): array {
	return array(
		'type'  => 'search',
		'query' => $query,
	);
}

/**
 * Builds a stable entity `@id` so other entities can refer to it.
 *
 * @param string $url      Canonical URL of the page the entity belongs to.
 * @param string $fragment Entity name, e.g. `article`.
 */
function better_schema_entity_id( string $url, string $fragment ): string {
	return $url . '#' . $fragment;
}

/**
 * Tells whether a post is the static front page.
 */
function better_schema_is_front_page_post( WP_Post $post ): bool {
	return 'page' === get_option( 'show_on_front' ) && (int) get_option( 'page_on_front' ) === $post->ID;
}

/**
 * Tells whether the context describes the site's front page.
 */
function better_schema_context_is_front_page( array $context ): bool {
	$type = $context['type'] ?? '';
	$post = $context['post'] ?? null;

	return 'home' === $type
		|| ( 'post' === $type && $post instanceof WP_Post && better_schema_is_front_page_post( $post ) );
}

/**
 * Canonical URL of what the context describes.
 *
 * @return string URL, or an empty string when it cannot be determined.
 */
function better_schema_context_url( array $context ): string {
	$post = $context['post'] ?? null;
	$term = $context['term'] ?? null;
	$user = $context['user'] ?? null;

	$url = match ( $context['type'] ?? '' ) {
		'post', 'blog'      => $post instanceof WP_Post ? get_permalink( $post ) : '',
		'home'              => home_url( '/' ),
		'term'              => $term instanceof WP_Term ? get_term_link( $term ) : '',
		'author'            => $user instanceof WP_User ? get_author_posts_url( $user->ID, $user->user_nicename ) : '',
		'post_type_archive' => get_post_type_archive_link( (string) ( $context['post_type'] ?? '' ) ),
		'date'              => better_schema_date_url( (int) ( $context['year'] ?? 0 ), (int) ( $context['month'] ?? 0 ), (int) ( $context['day'] ?? 0 ) ),
		'search'            => get_search_link( (string) ( $context['query'] ?? '' ) ),
		default             => '',
	};

	// get_permalink() and friends return false or a WP_Error when there is no URL.
	return is_string( $url ) ? $url : '';
}

/**
 * Plain-text name of what the context describes.
 *
 * @return string Name, or an empty string when there is none.
 */
function better_schema_context_name( array $context ): string {
	$post = $context['post'] ?? null;
	$term = $context['term'] ?? null;
	$user = $context['user'] ?? null;

	return match ( $context['type'] ?? '' ) {
		'post', 'blog'      => $post instanceof WP_Post ? better_schema_plain_text( get_the_title( $post ) ) : '',
		'home'              => better_schema_plain_text( get_bloginfo( 'name' ) ),
		'term'              => $term instanceof WP_Term ? better_schema_plain_text( $term->name ) : '',
		'author'            => $user instanceof WP_User ? better_schema_plain_text( $user->display_name ) : '',
		'post_type_archive' => better_schema_plain_text( get_post_type_object( (string) ( $context['post_type'] ?? '' ) )?->labels->name ),
		'date'              => better_schema_date_name( (int) ( $context['year'] ?? 0 ), (int) ( $context['month'] ?? 0 ), (int) ( $context['day'] ?? 0 ) ),
		/* translators: %s: the search terms. */
		'search'            => sprintf( __( 'Search results for "%s"', 'better-schema-markup' ), better_schema_plain_text( (string) ( $context['query'] ?? '' ) ) ),
		default             => '',
	};
}

/**
 * @param int $year  Year.
 * @param int $month Month, or 0.
 * @param int $day   Day, or 0.
 */
function better_schema_date_url( int $year, int $month, int $day ): string {
	return match ( true ) {
		$year < 1         => '',
		$month && $day    => get_day_link( $year, $month, $day ),
		(bool) $month     => get_month_link( $year, $month ),
		default           => get_year_link( $year ),
	};
}

/**
 * @param int $year  Year.
 * @param int $month Month, or 0.
 * @param int $day   Day, or 0.
 */
function better_schema_date_name( int $year, int $month, int $day ): string {
	$utc = new DateTimeZone( 'UTC' );

	$name = match ( true ) {
		$year < 1      => '',
		$month && $day => wp_date( (string) get_option( 'date_format' ), gmmktime( 0, 0, 0, $month, $day, $year ), $utc ),
		(bool) $month  => wp_date( 'F Y', gmmktime( 0, 0, 0, $month, 1, $year ), $utc ),
		default        => (string) $year,
	};

	return is_string( $name ) ? $name : '';
}

/**
 * Tells whether the author turned schema off for what the context describes.
 *
 * Applies to posts and to the posts page, which have their own schema type.
 */
function better_schema_context_opted_out( array $context ): bool {
	$post = $context['post'] ?? null;

	if ( ! $post instanceof WP_Post || ! in_array( $context['type'] ?? '', array( 'post', 'blog' ), true ) ) {
		return false;
	}

	return 'none' === better_schema_post_schema_type( $post );
}
