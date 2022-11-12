<?php
/**
 *
 * Core activation/deactivation hooks
 *
 */
if ( ! function_exists( 'justwpforms_first_run' ) ):

function justwpforms_activate() {
	do_action( 'justwpforms_activate' );
}

endif;

if ( ! function_exists( 'justwpforms_deactivate' ) ):

function justwpforms_deactivate() {
	do_action( 'justwpforms_deactivate' );
}

endif;

register_activation_hook( justwpforms_plugin_file(), 'justwpforms_activate' );
register_deactivation_hook( justwpforms_plugin_file(), 'justwpforms_deactivate' );

/**
 *
 * Hooked activation/deactivation/remove routines
 *
 */
if ( ! function_exists( 'justwpforms_create_samples' ) ):

function justwpforms_create_samples() {
	require_once( justwpforms_get_core_folder() . '/classes/class-cache.php' );
	require_once( justwpforms_get_core_folder() . '/classes/class-tracking.php' );
	require_once( justwpforms_get_core_folder() . '/helpers/helper-misc.php' );

	$tracking = justwpforms_get_tracking();
	$status = $tracking->get_status();

	if ( 0 < intval( $status['status'] ) ) {
		return;
	}

	require_once( justwpforms_get_core_folder() . '/classes/class-form-controller.php' );
	require_once( justwpforms_get_core_folder() . '/classes/class-form-part-library.php' );
	require_once( justwpforms_get_core_folder() . '/classes/class-form-setup.php' );
	require_once( justwpforms_get_core_folder() . '/classes/class-form-styles.php' );
	require_once( justwpforms_get_core_folder() . '/classes/class-validation-messages.php' );
	require_once( justwpforms_get_core_folder() . '/classes/class-form-messages.php' );
	require_once( justwpforms_get_core_folder() . '/classes/class-session.php' );
	require_once( justwpforms_get_core_folder() . '/helpers/helper-form-templates.php' );
	require_once( justwpforms_get_core_folder() . '/helpers/helper-validation.php' );

	$part_library = justwpforms_get_part_library();
	$form_controller = justwpforms_get_form_controller();

	// Create a new form
	$form = $form_controller->create();

	// Get the new form default data
	$form_data = $form_controller->get( $form->ID );

	$form_data['post_title'] = __( 'Sample Form', 'justwpforms' );
	$form_data['submit_button_label'] = __( 'Send my question', 'justwpforms' );

	$form_parts = array(
		array(
			'type' => 'single_line_text',
			'label' => __( 'First name', 'justwpforms' ),
			'width' => 'half',
		),
		array(
			'type' => 'single_line_text',
			'label' => __( 'Last name', 'justwpforms' ),
			'width' => 'half',
		),
		array(
			'type' => 'email',
			'label' => __( 'Email address', 'justwpforms' ),
		),
		array(
			'type' => 'multi_line_text',
			'label' => __( 'How can we help?', 'justwpforms' ),
			'rows' => 5,
		),
		array(
			'type' => 'checkbox',
			'label' => __( 'Agreement', 'justwpforms' ),
			'label_placement' => 'hidden',
			'options' => array(
				array(
					'label' => __( 'I agree to allow this site to store and process the personal information submitted.', 'justwpforms' ),
				),
			),
		),
	);

	foreach( $form_parts as $part_id => $part_data ) {
		$part_type = $part_data['type'];
		$part_complete_id = "{$part_type}_$part_id";
		$part_data['id'] = $part_complete_id;
		$part = $part_library->get_part( $part_type );
		$part_defaults = $part->get_customize_defaults();
		$part_data = wp_parse_args( $part_data, $part_defaults );

		if ( isset( $part_data['options'] ) ) {
			foreach( $part_data['options'] as $option_id => $option_data ) {
				$option_data['id'] = "{$part_complete_id}_{$option_id}";
				$part_data['options'][$option_id] = $option_data;
			}
		}

		$form_data['parts'][] = $part_data;
	}

	// Update the new form with default parts
	$form_data = $form_controller->update( $form_data );

	// Store an option to avoid creating new forms on reactivation
	$tracking->update_status( 1 );

	// Force a permalinks refresh
	flush_rewrite_rules();
}

endif;

add_action( 'justwpforms_activate', 'justwpforms_create_samples' );
