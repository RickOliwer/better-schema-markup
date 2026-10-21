<?php
/**
 * Small shared helpers for building schema values.
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Turns a WordPress display string into plain text for JSON-LD.
 *
 * Titles and site options can contain HTML and entities (for example `&amp;` or
 * the curly quotes that `wptexturize()` adds), which JSON-LD should not.
 *
 * @param mixed $text Text that may contain HTML or entities.
 * @return string Plain text, or an empty string for non-strings.
 */
function better_schema_plain_text( mixed $text ): string {
	if ( ! is_string( $text ) ) {
		return '';
	}

	return trim( html_entity_decode( wp_strip_all_tags( $text ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
}
