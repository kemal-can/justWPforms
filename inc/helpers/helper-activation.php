<?php

if ( ! function_exists( 'justwpforms_schedule_remove_unassigned_attachments' ) ) :

function justwpforms_schedule_remove_unassigned_attachments() {
	require_once( justwpforms_get_include_folder() . '/classes/class-attachment-controller.php' );

	$controller = justwpforms_get_attachment_controller();

	if ( ! wp_next_scheduled( $controller->schedule_remove_unassigned ) ) {
		wp_schedule_event( time(), 'hourly', $controller->schedule_remove_unassigned );
	}
}

endif;

add_action( 'justwpforms_activate', 'justwpforms_schedule_remove_unassigned_attachments' );

if ( ! function_exists( 'justwpforms_reset_license' ) ) :

function justwpforms_reset_license() {
	delete_option( justwpforms()->updater->product->option );
}

endif;

add_action( 'justwpforms_deactivate', 'justwpforms_reset_license' );