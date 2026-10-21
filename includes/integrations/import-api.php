<?php
/**
 * Administrator and WP-CLI interfaces for the shared importer.
 */
declare( strict_types=1 );
defined( 'ABSPATH' ) || exit;

function better_schema_register_import_route(): void {
	register_rest_route( 'better-schema/v1', '/import', array(
		'methods' => 'POST',
		'permission_callback' => static fn (): bool => current_user_can( 'manage_options' ),
		'callback' => static function ( WP_REST_Request $request ): WP_REST_Response {
			return new WP_REST_Response( better_schema_import_batch( $request['source'], (int) $request['cursor'], (bool) $request['dry_run'] ) );
		},
		'args' => array(
			'source' => array( 'type' => 'string', 'required' => true, 'enum' => array( 'yoast', 'rank-math' ) ),
			'cursor' => array( 'type' => 'integer', 'default' => 0, 'minimum' => 0 ),
			'dry_run' => array( 'type' => 'boolean', 'default' => true ),
		),
	) );
}
add_action( 'rest_api_init', 'better_schema_register_import_route' );

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	/**
	 * Imports explicit saved per-post schema settings without overwriting existing choices.
	 *
	 * ## OPTIONS
	 *
	 * --source=<source>
	 * : Source plugin: yoast or rank-math.
	 *
	 * [--dry-run]
	 * : Report proposed imports without saving them.
	 */
	function better_schema_cli_import( array $args, array $assoc_args ): void {
		$source = $assoc_args['source'] ?? '';
		if ( ! in_array( $source, array( 'yoast', 'rank-math' ), true ) ) {
			WP_CLI::error( __( 'Specify --source=yoast or --source=rank-math.', 'better-schema-markup' ) );
		}
		$cursor = 0;
		$totals = array( 'imported' => 0, 'skipped' => 0, 'unsupported' => 0, 'failed' => 0 );
		do {
			$report = better_schema_import_batch( $source, $cursor, isset( $assoc_args['dry-run'] ) );
			foreach ( $totals as $key => $count ) {
				$totals[ $key ] += $report[ $key ];
			}
			foreach ( $report['details'] as $detail ) {
				WP_CLI::log( $detail['post'] . ': ' . $detail['message'] );
			}
			$cursor = $report['cursor'];
		} while ( ! $report['done'] );
		WP_CLI::log( (string) wp_json_encode( $totals ) );
		if ( $totals['failed'] ) {
			WP_CLI::error( __( 'Some posts could not be imported. Review the report before retrying.', 'better-schema-markup' ) );
		}
		WP_CLI::success( isset( $assoc_args['dry-run'] ) ? __( 'Dry run finished. Nothing was saved.', 'better-schema-markup' ) : __( 'Import finished. Review reported limitations.', 'better-schema-markup' ) );
	}
	WP_CLI::add_command( 'better-schema import', 'better_schema_cli_import' );
}
