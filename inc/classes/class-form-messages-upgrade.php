<?php

class justwpforms_Form_Messages_Upgrade {

	private static $instance;

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		self::$instance->hook();

		return self::$instance;
	}

	public function hook() {
		add_filter( 'justwpforms_messages_fields', array( $this, 'get_fields' ) );
		add_filter( 'justwpforms_messages_controls', array( $this, 'get_controls' ) );
	}

	public function get_fields( $fields ) {
		$messages_fields = array(
			'show_results_label' => array(
				'default' => __( 'Show results', 'justwpforms' ),
				'sanitize' => 'sanitize_text_field'
			),
			'back_to_poll_label' => array(
				'default' => __( 'Back to poll', 'justwpforms' ),
				'sanitize' => 'sanitize_text_field'
			),
			'max_files_uploaded_label' => array(
				'default' => __( 'Files uploaded', 'justwpforms' ),
				'sanitize' => 'sanitize_text_field',
			),
			'file_upload_browse_label' => array(
				'default' => __( 'Browse', 'justwpforms' ),
				'sanitize' => 'sanitize_text_field',
			),
			'file_upload_delete_label' => array(
				'default' => __( 'Delete', 'justwpforms' ),
				'sanitize' => 'sanitize_text_field',
			),
		);

		$fields = array_merge( $fields, $messages_fields );

		return $fields;
	}

	public function get_controls( $controls ) {
		$message_controls = array(
			2140 => array(
				'type' => 'text',
				'label' => __( 'Upload files', 'justwpforms' ),
				'field' => 'file_upload_browse_label',
			),
			2160 => array(
				'type' => 'text',
				'label' => __( 'Remove uploaded file', 'justwpforms' ),
				'field' => 'file_upload_delete_label',
			),
			2220 => array(
				'type' => 'text',
				'label' => __( 'See poll results', 'justwpforms' ),
				'field' => 'show_results_label',
			),
			2240 => array(
				'type' => 'text',
				'label' => __( 'Return to poll choices', 'justwpforms' ),
				'field' => 'back_to_poll_label',
			),
			6120 => array(
				'type' => 'text',
				'label' => __( 'Total files uploaded', 'justwpforms' ),
				'field' => 'max_files_uploaded_label',
			)
		);

		$controls = justwpforms_safe_array_merge( $controls, $message_controls );

		return $controls;
	}

}

if ( ! function_exists( 'justwpforms_get_messages_upgrade' ) ):

function justwpforms_get_messages_upgrade() {
	return justwpforms_Form_Messages_Upgrade::instance();
}

endif;

justwpforms_get_messages_upgrade();
