<?php
/**
 * Removes plugin-owned data only when WordPress uninstalls the plugin.
 */
declare( strict_types=1 );
defined( 'WP_UNINSTALL_PLUGIN' ) || exit;
require_once __DIR__ . '/includes/lifecycle/uninstall-data.php';
better_schema_delete_all_data();
