<?php
/**
 * Imports explicit saved schema choices. Never changes source-plugin data.
 */
declare( strict_types=1 );
defined( 'ABSPATH' ) || exit;

/** Returns a supported choice and optional detail meta, plus information that could not transfer. */
function better_schema_import_choice( int $post_id, string $source ): array {
	$warnings = array();
	if ( 'yoast' === $source ) {
		$article = get_post_meta( $post_id, '_yoast_wpseo_schema_article_type', true );
		$page = get_post_meta( $post_id, '_yoast_wpseo_schema_page_type', true );
		if ( '' === $article && '' === $page ) {
			return array();
		}
		if ( 'None' === $article ) {
			$type = 'WebPage'; // Yoast disables the article, not the WebPage or site graph.
		} elseif ( in_array( $article, array( 'Article', 'BlogPosting' ), true ) ) {
			$type = $article;
		} elseif ( '' === $article && 'WebPage' === $page ) {
			$type = 'WebPage';
		} else {
			return array( 'warnings' => array( __( 'Unsupported or inherited Yoast schema choice; no choice was imported.', 'better-schema-markup' ) ) );
		}
		if ( '' !== $page && 'WebPage' !== $page ) {
			$warnings[] = __( 'The Yoast page subtype could not be transferred.', 'better-schema-markup' );
		}
		return array( 'meta' => array( '_better_schema_type' => $type ), 'warnings' => $warnings );
	}

	$schemas = array();
	foreach ( get_post_meta( $post_id ) as $key => $values ) {
		if ( str_starts_with( $key, 'rank_math_schema_' ) ) {
			foreach ( $values as $value ) {
				$schemas[] = maybe_unserialize( $value );
			}
		}
	}
	if ( ! $schemas ) {
		$legacy = get_post_meta( $post_id, 'rank_math_rich_snippet', true );
		if ( '' === $legacy ) {
			return array();
		}
		$type = array( 'off' => 'none', 'article' => 'Article', 'event' => 'Event', 'recipe' => 'Recipe' )[ is_string( $legacy ) ? $legacy : '' ] ?? null;
		if ( 'Article' === $type ) {
			$article = get_post_meta( $post_id, 'rank_math_snippet_article_type', true );
			$type = in_array( $article, array( 'Article', 'BlogPosting' ), true ) ? $article : $type;
		}
		return $type ? array(
			'meta' => array( '_better_schema_type' => $type ),
			'warnings' => array( __( 'Legacy Rank Math type imported; legacy detail fields require manual review.', 'better-schema-markup' ) ),
		) : array( 'warnings' => array( __( 'Unsupported legacy Rank Math type.', 'better-schema-markup' ) ) );
	}
	$primary = array_values( array_filter( $schemas, static fn ( mixed $schema ): bool => is_array( $schema ) && ! empty( $schema['metadata']['isPrimary'] ) ) );
	if ( count( $schemas ) > 1 && 1 !== count( $primary ) ) {
		return array( 'warnings' => array( __( 'Multiple Rank Math entities have no unique primary entity; select one manually.', 'better-schema-markup' ) ) );
	}
	$schema = $primary[0] ?? $schemas[0];
	if ( ! is_array( $schema ) || ! is_string( $schema['@type'] ?? null ) || ! in_array( $schema['@type'], array( 'Article', 'BlogPosting', 'WebPage', 'Event', 'Recipe', 'FAQPage' ), true ) ) {
		return array( 'warnings' => array( __( 'Unsupported or malformed Rank Math schema. Products continue to use WooCommerce data.', 'better-schema-markup' ) ) );
	}
	if ( count( $schemas ) > 1 ) {
		$warnings[] = __( 'Additional Rank Math entities were not imported.', 'better-schema-markup' );
	}
	// Source template expressions need the source plugin to resolve them; never save them as literal values.
	$contains_template = static function ( mixed $value ) use ( &$contains_template ): bool {
		if ( is_array( $value ) ) {
			foreach ( $value as $item ) {
				if ( $contains_template( $item ) ) {
					return true;
				}
			}
		}
		return is_string( $value ) && (bool) preg_match( '/%[^%]+%/', $value );
	};
	foreach ( $schema as $key => $value ) {
		if ( 'metadata' !== $key && $contains_template( $value ) ) {
			unset( $schema[ $key ] );
			$warnings[] = sprintf( /* translators: %s: source schema property. */ __( 'Unresolved template in %s was not imported.', 'better-schema-markup' ), $key );
		}
	}
	$type = $schema['@type'];
	$meta = array( '_better_schema_type' => $type );
	$handled = array( '@type', 'metadata' );
	if ( 'Event' === $type ) {
		$location = is_array( $schema['location'] ?? null ) ? $schema['location'] : array();
		$address = $location['address'] ?? '';
		if ( is_array( $address ) ) {
			$address = implode( ', ', array_filter( array_intersect_key( $address, array_flip( array( 'streetAddress', 'addressLocality', 'addressRegion', 'postalCode', 'addressCountry' ) ) ), 'is_string' ) );
		}
		$offers = is_array( $schema['offers'] ?? null ) ? $schema['offers'] : array();
		$options = better_schema_event_options();
		$meta['_better_schema_event'] = better_schema_sanitize_event( array(
			'start' => $schema['startDate'] ?? '', 'end' => $schema['endDate'] ?? '',
			'attendance' => array_search( $schema['eventAttendanceMode'] ?? '', $options['attendance'], true ) ?: 'offline',
			'status' => array_search( $schema['eventStatus'] ?? '', $options['status'], true ) ?: 'scheduled',
			'location_name' => $location['name'] ?? '', 'location_address' => $address,
			'offers_url' => $offers['url'] ?? ( 'VirtualLocation' === ( $location['@type'] ?? '' ) ? ( $location['url'] ?? '' ) : '' ),
		) );
		$handled = array_merge( $handled, array( 'startDate', 'endDate', 'eventAttendanceMode', 'eventStatus', 'location', 'offers' ) );
		if ( $location || $offers ) {
			$warnings[] = __( 'Event location and offers were reduced to the supported venue, address and URL fields; review nested properties manually.', 'better-schema-markup' );
		}
	} elseif ( 'Recipe' === $type ) {
		$steps = $schema['recipeInstructions'] ?? array();
		if ( is_string( $steps ) ) {
			$steps = preg_split( '/\r?\n/', $steps );
		}
		$instructions = array();
		foreach ( is_array( $steps ) ? $steps : array() as $step ) {
			if ( is_string( $step ) || ( is_array( $step ) && is_string( $step['text'] ?? null ) ) ) {
				$instructions[] = is_string( $step ) ? $step : $step['text'];
			} else {
				$warnings[] = __( 'An unsupported recipe instruction was not transferred.', 'better-schema-markup' );
			}
		}
		$ingredients = $schema['recipeIngredient'] ?? array();
		$nutrition = is_array( $schema['nutrition'] ?? null ) ? $schema['nutrition'] : array();
		$meta['_better_schema_recipe'] = better_schema_sanitize_recipe( array(
			'ingredients' => is_string( $ingredients ) ? preg_split( '/\r?\n/', $ingredients ) : $ingredients,
			'instructions' => $instructions,
			'prep_minutes' => better_schema_import_minutes( $schema['prepTime'] ?? '' ),
			'cook_minutes' => better_schema_import_minutes( $schema['cookTime'] ?? '' ),
			'yield' => $schema['recipeYield'] ?? '',
			'calories' => is_scalar( $nutrition['calories'] ?? null ) ? (float) $nutrition['calories'] : 0,
		) );
		$handled = array_merge( $handled, array( 'recipeIngredient', 'recipeInstructions', 'prepTime', 'cookTime', 'recipeYield', 'nutrition' ) );
		if ( $nutrition ) {
			$warnings[] = __( 'Only calories were transferred from recipe nutrition; review other nutrition properties manually.', 'better-schema-markup' );
		}
	} elseif ( 'FAQPage' === $type ) {
		$items = array();
		foreach ( is_array( $schema['mainEntity'] ?? null ) ? $schema['mainEntity'] : array() as $question ) {
			$items[] = array( 'question' => is_array( $question ) ? ( $question['name'] ?? '' ) : '', 'answer' => is_array( $question['acceptedAnswer'] ?? null ) ? ( $question['acceptedAnswer']['text'] ?? '' ) : '' );
		}
		$meta['_better_schema_faq'] = better_schema_sanitize_faq( $items );
		if ( count( $items ) !== count( $meta['_better_schema_faq'] ) ) {
			$warnings[] = __( 'Incomplete FAQ questions were not transferred.', 'better-schema-markup' );
		}
		$handled[] = 'mainEntity';
	}
	foreach ( array_diff( array_keys( $schema ), $handled ) as $property ) {
		$warnings[] = sprintf( /* translators: %s: source schema property. */ __( 'Property %s is generated from WordPress or is unsupported; its override was not transferred.', 'better-schema-markup' ), $property );
	}
	return array( 'meta' => $meta, 'warnings' => array_values( array_unique( $warnings ) ) );
}

function better_schema_import_minutes( mixed $duration ): int {
	if ( ! is_string( $duration ) || ! preg_match( '/^PT(?:(\d+)H)?(?:(\d+)M)?$/', $duration, $parts ) ) {
		return 0;
	}
	return (int) min( 100000, (float) ( $parts[1] ?? 0 ) * 60 + (float) ( $parts[2] ?? 0 ) );
}

/** Imports at most 100 posts; the cursor is an offset into ascending post IDs. */
function better_schema_import_batch( string $source, int $cursor = 0, bool $dry_run = false ): array {
	if ( ! in_array( $source, array( 'yoast', 'rank-math' ), true ) || $cursor < 0 ) {
		return array( 'error' => __( 'Choose Yoast or Rank Math and a valid cursor.', 'better-schema-markup' ) );
	}
	$posts = get_posts( array( 'post_type' => better_schema_configurable_post_types(), 'post_status' => array_keys( get_post_stati() ), 'posts_per_page' => 100, 'offset' => $cursor, 'orderby' => 'ID', 'order' => 'ASC' ) );
	$report = array( 'imported' => 0, 'skipped' => 0, 'unsupported' => 0, 'failed' => 0, 'details' => array(), 'cursor' => $cursor + count( $posts ), 'done' => count( $posts ) < 100 );
	foreach ( $posts as $post ) {
		if ( ! ( defined( 'WP_CLI' ) && WP_CLI ) && ! current_user_can( 'edit_post', $post->ID ) ) {
			++$report['skipped'];
			continue;
		}
		$choice = better_schema_import_choice( $post->ID, $source );
		if ( ! $choice ) {
			++$report['skipped'];
			continue;
		}
		$existing = false;
		foreach ( array( '_better_schema_type', '_better_schema_event', '_better_schema_recipe', '_better_schema_faq', '_better_schema_custom_jsonld', '_better_schema_custom_mode' ) as $key ) {
			$existing = $existing || metadata_exists( 'post', $post->ID, $key );
		}
		if ( $existing ) {
			++$report['skipped'];
			$report['details'][] = array( 'post' => $post->ID, 'message' => __( 'Existing Better Schema settings were preserved.', 'better-schema-markup' ) );
			continue;
		}
		foreach ( $choice['warnings'] ?? array() as $warning ) {
			$report['details'][] = array( 'post' => $post->ID, 'message' => $warning );
		}
		if ( ! empty( $choice['warnings'] ) ) {
			++$report['unsupported'];
		}
		if ( empty( $choice['meta'] ) ) {
			++$report['skipped'];
			continue;
		}
		$written = array();
		$failed = false;
		if ( ! $dry_run ) {
			foreach ( $choice['meta'] as $key => $value ) {
				if ( ! add_post_meta( $post->ID, $key, wp_slash( $value ), true ) ) {
					$failed = true;
					break;
				}
				$written[ $key ] = $value;
			}
			if ( $failed ) {
				foreach ( $written as $key => $value ) {
					delete_post_meta( $post->ID, $key, $value );
				}
				$report['details'][] = array( 'post' => $post->ID, 'message' => __( 'Saving failed; changes for this post were rolled back.', 'better-schema-markup' ) );
			}
		}
		++$report[ $failed ? 'failed' : 'imported' ];
	}
	return $report;
}
