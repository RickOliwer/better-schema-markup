<?php
/**
 * PHPUnit bootstrap: loads the WordPress test library and this plugin.
 */

$better_schema_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $better_schema_tests_dir ) {
	$better_schema_tests_dir = '/wordpress-phpunit';
}

// The WP test suite needs Yoast's PHPUnit Polyfills (installed with `vp run composer install`).
define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', dirname( __DIR__ ) . '/vendor/yoast/phpunit-polyfills' );

require_once $better_schema_tests_dir . '/includes/functions.php';

tests_add_filter(
	'muplugins_loaded',
	function () {
		require dirname( __DIR__ ) . '/better-schema-markup.php';
	}
);

require $better_schema_tests_dir . '/includes/bootstrap.php';

require __DIR__ . '/GraphAssertions.php';
