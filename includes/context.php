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

defined( 'ABSPATH' ) || exit;

/**
 * @param WP_Post $post The post being described.
 * @return array{type: string, post: WP_Post}
 */
function better_schema_post_context( WP_Post $post ) {
	return array(
		'type' => 'post',
		'post' => $post,
	);
}

/**
 * @param WP_Post $page The page set as the posts page.
 * @return array{type: string, post: WP_Post}
 */
function better_schema_blog_context( WP_Post $page ) {
	return array(
		'type' => 'blog',
		'post' => $page,
	);
}

/**
 * @return array{type: string}
 */
function better_schema_home_context() {
	return array( 'type' => 'home' );
}

/**
 * @param WP_Term $term The term whose archive is described.
 * @return array{type: string, term: WP_Term}
 */
function better_schema_term_context( WP_Term $term ) {
	return array(
		'type' => 'term',
		'term' => $term,
	);
}

/**
 * @param WP_User $user The author whose archive is described.
 * @return array{type: string, user: WP_User}
 */
function better_schema_author_context( WP_User $user ) {
	return array(
		'type' => 'author',
		'user' => $user,
	);
}

/**
 * @param string $post_type Post type name.
 * @return array{type: string, post_type: string}
 */
function better_schema_post_type_archive_context( $post_type ) {
	return array(
		'type'      => 'post_type_archive',
		'post_type' => (string) $post_type,
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
function better_schema_date_context( $year, $month = 0, $day = 0 ) {
	$year  = max( 0, (int) $year );
	$month = (int) $month;
	$day   = (int) $day;

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
 * @param string $query The search terms.
 * @return array{type: string, query: string}
 */
function better_schema_search_context( $query ) {
	return array(
		'type'  => 'search',
		'query' => is_string( $query ) ? $query : '',
	);
}

/**
 * Builds a stable entity `@id` so other entities can refer to it.
 *
 * @param string $url      Canonical URL of the page the entity belongs to.
 * @param string $fragment Entity name, e.g. `article`.
 * @return string
 */
function better_schema_entity_id( $url, $fragment ) {
	return $url . '#' . $fragment;
}

/**
 * Tells whether a post is the static front page.
 *
 * @param WP_Post $post Post.
 * @return bool
 */
function better_schema_is_front_page_post( WP_Post $post ) {
	return 'page' === get_option( 'show_on_front' ) && (int) get_option( 'page_on_front' ) === $post->ID;
}

/**
 * Tells whether the context describes the site's front page.
 *
 * @param array $context Context.
 * @return bool
 */
function better_schema_context_is_front_page( array $context ) {
	$type = $context['type'] ?? '';

	if ( 'home' === $type ) {
		return true;
	}

	$post = $context['post'] ?? null;

	return 'post' === $type && $post instanceof WP_Post && better_schema_is_front_page_post( $post );
}

/**
 * Canonical URL of what the context describes.
 *
 * @param array $context Context.
 * @return string URL, or an empty string when it cannot be determined.
 */
function better_schema_context_url( array $context ) {
	$url = '';

	switch ( $context['type'] ?? '' ) {
		case 'post':
		case 'blog':
			$post = $context['post'] ?? null;
			$url  = $post instanceof WP_Post ? get_permalink( $post ) : '';
			break;

		case 'home':
			$url = home_url( '/' );
			break;

		case 'term':
			$term = $context['term'] ?? null;
			$url  = $term instanceof WP_Term ? get_term_link( $term ) : '';
			break;

		case 'author':
			$user = $context['user'] ?? null;
			$url  = $user instanceof WP_User ? get_author_posts_url( $user->ID, $user->user_nicename ) : '';
			break;

		case 'post_type_archive':
			$url = get_post_type_archive_link( (string) ( $context['post_type'] ?? '' ) );
			break;

		case 'date':
			$url = better_schema_date_url( (int) ( $context['year'] ?? 0 ), (int) ( $context['month'] ?? 0 ), (int) ( $context['day'] ?? 0 ) );
			break;

		case 'search':
			$url = get_search_link( (string) ( $context['query'] ?? '' ) );
			break;
	}

	return is_string( $url ) ? $url : '';
}

/**
 * Plain-text name of what the context describes.
 *
 * @param array $context Context.
 * @return string Name, or an empty string when there is none.
 */
function better_schema_context_name( array $context ) {
	switch ( $context['type'] ?? '' ) {
		case 'post':
		case 'blog':
			$post = $context['post'] ?? null;
			return $post instanceof WP_Post ? better_schema_plain_text( get_the_title( $post ) ) : '';

		case 'home':
			return better_schema_plain_text( get_bloginfo( 'name' ) );

		case 'term':
			$term = $context['term'] ?? null;
			return $term instanceof WP_Term ? better_schema_plain_text( $term->name ) : '';

		case 'author':
			$user = $context['user'] ?? null;
			return $user instanceof WP_User ? better_schema_plain_text( $user->display_name ) : '';

		case 'post_type_archive':
			$object = get_post_type_object( (string) ( $context['post_type'] ?? '' ) );
			return $object ? better_schema_plain_text( $object->labels->name ) : '';

		case 'date':
			return better_schema_date_name( (int) ( $context['year'] ?? 0 ), (int) ( $context['month'] ?? 0 ), (int) ( $context['day'] ?? 0 ) );

		case 'search':
			$query = better_schema_plain_text( (string) ( $context['query'] ?? '' ) );
			/* translators: %s: the search terms. */
			return sprintf( __( 'Search results for "%s"', 'better-schema-markup' ), $query );
	}

	return '';
}

/**
 * @param int $year  Year.
 * @param int $month Month, or 0.
 * @param int $day   Day, or 0.
 * @return string
 */
function better_schema_date_url( $year, $month, $day ) {
	if ( $year < 1 ) {
		return '';
	}

	if ( $month && $day ) {
		return get_day_link( $year, $month, $day );
	}

	return $month ? get_month_link( $year, $month ) : get_year_link( $year );
}

/**
 * @param int $year  Year.
 * @param int $month Month, or 0.
 * @param int $day   Day, or 0.
 * @return string
 */
function better_schema_date_name( $year, $month, $day ) {
	if ( $year < 1 ) {
		return '';
	}

	$utc = new DateTimeZone( 'UTC' );

	if ( $month && $day ) {
		return wp_date( get_option( 'date_format' ), gmmktime( 0, 0, 0, $month, $day, $year ), $utc );
	}

	return $month ? wp_date( 'F Y', gmmktime( 0, 0, 0, $month, 1, $year ), $utc ) : (string) $year;
}

/**
 * Tells whether the author turned schema off for what the context describes.
 *
 * Applies to posts and to the posts page, which have their own schema type.
 *
 * @param array $context Context.
 * @return bool
 */
function better_schema_context_opted_out( array $context ) {
	$post = $context['post'] ?? null;

	if ( ! $post instanceof WP_Post || ! in_array( $context['type'] ?? '', array( 'post', 'blog' ), true ) ) {
		return false;
	}

	return 'none' === better_schema_post_schema_type( $post );
}
