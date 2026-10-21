<?php
/**
 * Registers the plugin's site-wide settings.
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

function better_schema_register_settings(): void {
	register_setting( 'better_schema', 'better_schema_vimeo_enabled', array(
		'type' => 'boolean',
		'default' => false,
		'sanitize_callback' => 'rest_sanitize_boolean',
		'show_in_rest' => true,
	) );
	register_setting( 'better_schema', 'better_schema_frontend_url', array(
		'type' => 'string',
		'default' => '',
		'sanitize_callback' => 'better_schema_sanitize_frontend_url',
		'show_in_rest' => true,
	) );
	register_setting(
		'better_schema',
		'better_schema_identity',
		array(
			'type'              => 'object',
			'default'           => better_schema_identity_defaults(),
			'sanitize_callback' => 'better_schema_sanitize_identity',
			'show_in_rest'      => array(
				'schema' => array(
					'type'                 => 'object',
					'properties'           => array(
						'type'    => array(
							'type' => 'string',
							'enum' => better_schema_identity_types(),
						),
						'name'    => array( 'type' => 'string' ),
						'logo'    => array( 'type' => 'integer' ),
						'same_as' => array(
							'type'  => 'array',
							'items' => array(
								'type'   => 'string',
								'format' => 'uri',
							),
						),
					),
					'additionalProperties' => false,
				),
			),
		)
	);

	$business_text = array( 'type' => 'string' );
	register_setting(
		'better_schema',
		'better_schema_business',
		array(
			'type'              => 'object',
			'default'           => better_schema_business_defaults(),
			'sanitize_callback' => 'better_schema_sanitize_business',
			'show_in_rest'      => array(
				'schema' => array(
					'type'                 => 'object',
					'properties'           => array(
						'enabled'     => array( 'type' => 'boolean' ),
						'telephone'   => $business_text,
						'price_range' => $business_text,
						'address'     => array(
							'type'                 => 'object',
							'properties'           => array(
								'street'      => $business_text,
								'locality'    => $business_text,
								'region'      => $business_text,
								'postal_code' => $business_text,
								'country'     => $business_text,
							),
							'additionalProperties' => false,
						),
						'geo'         => array(
							'type'                 => 'object',
							'properties'           => array(
								'lat' => $business_text,
								'lng' => $business_text,
							),
							'additionalProperties' => false,
						),
						'hours'       => array(
							'type'  => 'array',
							'items' => array(
								'type'                 => 'object',
								'properties'           => array(
									'days'   => array(
										'type'  => 'array',
										'items' => array(
											'type' => 'string',
											'enum' => better_schema_weekdays(),
										),
									),
									'opens'  => $business_text,
									'closes' => $business_text,
								),
								'additionalProperties' => false,
							),
						),
					),
					'additionalProperties' => false,
				),
			),
		)
	);

	$properties = array();
	foreach ( better_schema_configurable_post_types() as $post_type ) {
		$properties[ $post_type ] = array(
			'type' => 'string',
			'enum' => better_schema_types(),
		);
	}

	register_setting(
		'better_schema',
		'better_schema_type_defaults',
		array(
			'type'              => 'object',
			'default'           => better_schema_type_defaults(),
			'sanitize_callback' => 'better_schema_sanitize_type_defaults',
			'show_in_rest'      => array(
				'schema' => array(
					'type'                 => 'object',
					'properties'           => $properties,
					'additionalProperties' => false,
				),
			),
		)
	);

	register_setting(
		'better_schema',
		'better_schema_setup_complete',
		array(
			'type'              => 'boolean',
			'default'           => false,
			'sanitize_callback' => 'rest_sanitize_boolean',
			'show_in_rest'      => true,
		)
	);
}
add_action( 'init', 'better_schema_register_settings', 100 );

/**
 * Already configured sites can go straight to the full settings form.
 */
function better_schema_setup_is_complete(): bool {
	return (bool) get_option( 'better_schema_setup_complete', false )
		|| better_schema_get_identity() !== better_schema_identity_defaults();
}
