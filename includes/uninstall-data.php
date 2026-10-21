<?php
/**
 * Site-local cleanup shared by the guarded uninstall entrypoint and its tests.
 */
declare( strict_types=1 );
defined( 'ABSPATH' ) || exit;

function better_schema_delete_site_data(): void {
	global $wpdb;
	foreach ( array( 'better_schema_identity', 'better_schema_business', 'better_schema_frontend_url', 'better_schema_type_defaults', 'better_schema_setup_complete', 'better_schema_takeover' ) as $option ) {
		delete_option( $option );
	}
	foreach ( array( '_better_schema_type', '_better_schema_event', '_better_schema_recipe', '_better_schema_faq', '_better_schema_custom_jsonld', '_better_schema_custom_mode' ) as $key ) {
		delete_post_meta_by_key( $key );
	}
	// Transient names are dynamic. Read bounded batches, then use the API to invalidate caches too.
	do {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Uninstall must include expired transient rows.
		$names = $wpdb->get_col( $wpdb->prepare( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s LIMIT 100", $wpdb->esc_like( '_transient_better_schema_vimeo_' ) . '%', $wpdb->esc_like( '_transient_timeout_better_schema_vimeo_' ) . '%' ) );
		foreach ( $names as $name ) {
			$key = str_starts_with( $name, '_transient_timeout_' ) ? substr( $name, 19 ) : substr( $name, 11 );
			delete_transient( $key );
			// Persistent object caches can leave old database transients behind.
			delete_option( '_transient_' . $key );
			delete_option( '_transient_timeout_' . $key );
		}
	} while ( 100 === count( $names ) );
}

function better_schema_delete_all_data(): void {
	if ( ! is_multisite() ) {
		better_schema_delete_site_data();
		return;
	}
	$offset = 0;
	do {
		$ids = get_sites( array( 'fields' => 'ids', 'number' => 100, 'offset' => $offset, 'orderby' => 'id', 'order' => 'ASC' ) );
		foreach ( $ids as $id ) {
			switch_to_blog( (int) $id );
			try {
				better_schema_delete_site_data();
			} finally {
				restore_current_blog();
			}
		}
		$offset += count( $ids );
	} while ( 100 === count( $ids ) );
}
