<?php

class justwpforms_Validation_Messages_Upgrade {
	private $save_action = 'justwpforms_save_validation_messages';

	public $save_nonce = 'justwpforms-validation-messages-nonce';
	public $messages_option_name = 'justwpforms-validation-messages';
	public $validation_messages_controller = '';

	/**
	 * The singleton instance.
	 *
	 * @since 1.0
	 *
	 * @var justwpforms_Form_Controller
	 */
	private static $instance;

	/**
	 * The singleton constructor.
	 *
	 * @since 1.0
	 *
	 * @return justwpforms_Form_Controller
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		self::$instance->hook();

		return self::$instance;
	}

	public function hook() {
		$this->validation_messages_controller = justwpforms_validation_messages();

		add_filter( 'justwpforms_default_validation_messages', array( $this, 'add_messages' ) );

		add_filter( 'justwpforms_messages_fields', array( $this, 'meta_messages_fields' ) );
		add_filter( 'justwpforms_messages_controls', array( $this, 'messages_controls' ) );
	}

	/**
	 * Adds messages applicable to paid version only.
	 *
	 * @hooked filter `justwpforms_default_validation_messages`
	 *
	 * @param array $messages Array of default messages.
	 *
	 * @return array Messages array with new items added.
	 */
	public function add_messages( $messages ) {
		$upgrade_messages = wp_list_pluck( $this->get_validation_fields(), 'default' );
		$messages = array_merge( $messages, $upgrade_messages );

		return $messages;
	}


	public function get_validation_fields() {
		$fields = array(
			'file_not_uploaded' => array(
				'default' => __( 'Please upload a file.', 'justwpforms' ),
				'sanitize' => 'sanitize_text_field',
			),
			'file_size_too_big' => array(
				'default' => __( 'This file size is too big.', 'justwpforms' ),
				'sanitize' => 'sanitize_text_field',
			),
			'file_invalid' => array(
				'default' => __( 'This file type isn’t allowed.', 'justwpforms' ),
				'sanitize' => 'sanitize_text_field',
			),
			'file_duplicate' => array(
				'default' => __( 'A file with this name has already been uploaded.', 'justwpforms' ),
				'sanitize' => 'sanitize_text_field',
			),
			'file_min_count' => array(
				'default' => __( 'Too few files have been uploaded.', 'justwpforms' ),
				'sanitize' => 'sanitize_text_field',
			),
		);

		return $fields;
	}

	public function messages_controls( $controls ) {
		$message_controls = array(
			4060 => array(
				'type' => 'text',
				'label' => __( "Required file isn't uploaded", 'justwpforms' ),
				'field' => 'file_not_uploaded',
			),
			4220 => array(
				'type' => 'text',
				'label' => __( "This file's size is too big", 'justwpforms' ),
				'field' => 'file_size_too_big',
			),
			4240 => array(
				'type' => 'text',
				'label' => __( "This file's type not allowed", 'justwpforms' ),
				'field' => 'file_invalid',
			),
			4241 => array(
				'type' => 'text',
				'label' => __( 'A file with this name has already been uploaded', 'justwpforms' ),
				'field' => 'file_duplicate',
			),
			4242 => array(
				'type' => 'text',
				'label' => __( 'User uploaded too few files', 'justwpforms' ),
				'field' => 'file_min_count',
			)
		);

		$controls = justwpforms_safe_array_merge( $controls, $message_controls );

		return $controls;
	}

	public function meta_messages_fields( $fields ) {
		$fields = array_merge( $fields, $this->get_validation_fields() );

		return $fields;
	}
}

if ( ! function_exists( 'justwpforms_validation_messages_upgrade' ) ):
/**
 * Get the justwpforms_Validation_Messages_Upgrade class instance.
 *
 * @since 1.0
 *
 * @return justwpforms_Validation_Messages_Upgrade
 */
function justwpforms_validation_messages_upgrade() {
	return justwpforms_Validation_Messages_Upgrade::instance();
}

endif;

/**
 * Initialize the justwpforms_Validation_Messages_Upgrade class immediately.
 */
justwpforms_validation_messages_upgrade();
