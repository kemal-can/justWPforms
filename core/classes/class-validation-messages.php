<?php

class justwpforms_Validation_Messages {

	/**
	 * The singleton instance.
	 *
	 * @since 1.0
	 *
	 * @var justwpforms_Validation_Messages
	 */
	private static $instance;

	private $form = null;

	/**
	 * The singleton constructor.
	 *
	 * @return justwpforms_Validation_Messages
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		self::$instance->hook();

		return self::$instance;
	}

	public function hook() {

		add_filter( 'justwpforms_get_form_data', array( $this, 'set_form'), 99 );
		add_filter( 'justwpforms_messages_fields', array( $this, 'meta_messages_fields' ) );
		add_filter( 'justwpforms_messages_controls', array( $this, 'messages_controls' ) );
		add_filter( 'justwpforms_part_attributes', array( $this, 'add_accessibility_attributes' ), 10, 4 );
	}

	/**
	 * Adds HTML attributes for better accessibility in case of form returns some errors.
	 *
	 * @hooked filter `justwpforms_part_attributes`
	 *
	 * @param array  $attributes Array of attributes.
	 * @param array  $part       Part data.
	 * @param array  $form       Form data.
	 * @param string $component  Component if available.
	 *
	 * @return array Array of attributes.
	 */
	public function add_accessibility_attributes( $attributes, $part, $form, $component ) {
		$part_name = justwpforms_get_part_name( $part, $form );
		$errors = justwpforms_get_session()->get_messages( $part_name );

		if ( empty( $errors ) ) {
			return $attributes;
		}

		$error_id = "justwpforms-error-{$part_name}";
		$error_id = ( $component ) ? "{$error_id}_{$component}" : $error_id;

		$attributes[] = 'aria-invalid="true"';
		$attributes[] = 'aria-describedby="'. $error_id .'"';

		return $attributes;
	}

	public function messages_controls( $controls ) {
		$message_controls = array(
			4010 => array(
				'type' => 'text',
				'label' => __( 'Field is answered incorrectly', 'justwpforms' ),
				'field' => 'field_invalid',
			),
			4020 => array(
				'type' => 'text',
				'label' => __( "Required field isn't answered", 'justwpforms' ),
				'field' => 'field_empty',
			),
			4040 => array(
				'type' => 'text',
				'label' => __( "Required choice isn't selected", 'justwpforms' ),
				'field' => 'no_selection',
			),
			4080 => array(
				'type' => 'text',
				'label' => __( "Too many words/characters typed", 'justwpforms' ),
				'field' => 'message_too_long',
			),
			4100 => array(
				'type' => 'text',
				'label' => __( 'Not enough words/characters typed', 'justwpforms' ),
				'field' => 'message_too_short',
			),
		);

		$controls = justwpforms_safe_array_merge( $controls, $message_controls );

		return $controls;
	}

	public function get_validation_fields() {

		$fields = array(
			'field_invalid' => array(
				'default' =>  __( "Looks like there's a mistake here.", 'justwpforms' ),
				'sanitize' => 'sanitize_text_field',
			),
			'field_empty' => array(
				'default' => __( 'Please answer this question.', 'justwpforms' ),
				'sanitize' => 'sanitize_text_field',
			),
			'no_selection' => array(
				'default' => __( 'Please make a selection.', 'justwpforms' ),
				'sanitize' => 'sanitize_text_field',
			),
			'message_too_long' => array(
				'default' => __( 'This answer is too long.', 'justwpforms' ),
				'sanitize' => 'sanitize_text_field',
			),
			'message_too_short' => array(
				'default' => __( "This answer isn't long enough.", 'justwpforms' ),
				'sanitize' => 'sanitize_text_field',
			),
			// TODO remove this field once deprecated global messages is fully removed
			'per_form_validation_msg' => array(
				'default' => 0,
				'sanitize' => 'sanitize_text_field',
			),
		);

		return $fields;
	}

	public function meta_messages_fields( $fields ) {
		$fields = array_merge( $fields, $this->get_validation_fields() );

		return $fields;
	}

	/**
	 *
	 * TODO Remove this method (set_form) and all other references of per_form_validation_msg
	 * once the deprecated global messages has been fully retired.
	 *
	 */
	public function set_form( $form ) {

		if ( $form['ID'] != 0 && $form['per_form_validation_msg'] == 0 ) {
			$deprecated_messages = get_option( 'justwpforms-validation-messages', array() );
			$messages = $this->get_default_messages();

			foreach ( $deprecated_messages as $key => $value ) {
				if ( ! empty( $deprecated_messages[ $key ] ) ) {
					$form[ $key ] = $deprecated_messages[ $key ];
				} else {
					$form[ $key ] = $messages[ $key ];
				}
			}
		} else if ( $form['ID'] == 0 ) {
			$form['per_form_validation_msg'] = 1;
		}

		// since validation messages are now in form meta, we need the form variable.
		$this->form = $form;

		return $form;
	}

	public function get_default_messages() {
		$messages = wp_list_pluck( $this->get_validation_fields(), 'default' );

		return apply_filters( 'justwpforms_default_validation_messages', $messages );
	}

	/**
	 * Gets validation message from messages array key. Runs message through the filter which
	 * allows altering the message through code.
	 *
	 * @param string $message_key Array key of the message.
	 *
	 * @return string Empty string on failure (if array key is not found), message ran through the
	 * `justwpforms_validation_message filter on success.
	 */
	public function get_message( $message_key ) {
		$message = $this->form[ $message_key ];

		if ( ! empty( $message ) ) {
			return $message;
		}

		$default_messages = $this->get_default_messages();

		if ( ! isset( $default_messages[$message_key] ) ) {
			return $message;
		}

		$message = $default_messages[$message_key];

		return $message;
	}

}

if ( ! function_exists( 'justwpforms_validation_messages' ) ):
/**
 * Get the justwpforms_Validation_Messages class instance.
 *
 * @since 1.0
 *
 * @return justwpforms_Validation_Messages
 */
function justwpforms_validation_messages() {
	return justwpforms_Validation_Messages::instance();
}

endif;

/**
 * Initialize the justwpforms_Validation_Messages class immediately.
 */
justwpforms_validation_messages();
