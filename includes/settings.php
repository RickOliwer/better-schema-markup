<?php
/**
 * Registers the plugin's site-wide settings.
 */

defined( 'ABSPATH' ) || exit;

function better_schema_register_settings() {
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
}
add_action( 'init', 'better_schema_register_settings' );
