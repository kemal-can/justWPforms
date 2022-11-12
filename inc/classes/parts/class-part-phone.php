<?php

class justwpforms_Part_Phone extends justwpforms_Form_Part {

	public $type = 'phone';

	public function __construct() {
		$this->label = __( 'Phone', 'justwpforms' );
		$this->description = __( 'For collecting a local or international phone number.', 'justwpforms' );

		add_filter( 'justwpforms_part_value', array( $this, 'get_part_value' ), 10, 3 );
		add_filter( 'justwpforms_message_part_value', array( $this, 'message_part_value' ), 10, 4 );
		add_filter( 'justwpforms_part_class', array( $this, 'html_part_class' ), 10, 3 );
		add_filter( 'justwpforms_part_data_attributes', array( $this, 'html_part_data_attributes' ), 10, 3 );
		add_filter( 'justwpforms_frontend_dependencies', array( $this, 'script_dependencies' ), 10, 2 );
		add_filter( 'justwpforms_messages_fields', array( $this, 'meta_messages_fields' ) );
		add_filter( 'justwpforms_messages_controls', array( $this, 'messages_controls' ) );
	}

	/**
	 * Get all part meta fields defaults.
	 *
	 * @since 1.0.0.
	 *
	 * @return array
	 */
	public function get_customize_fields() {
		$fields = array(
			'type' => array(
				'default' => $this->type,
				'sanitize' => 'sanitize_text_field',
			),
			'label' => array(
				'default' => __( '', 'justwpforms' ),
				'sanitize' => 'sanitize_text_field',
			),
			'label_placement' => array(
				'default' => 'show',
				'sanitize' => 'sanitize_text_field'
			),
			'description' => array(
				'default' => '',
				'sanitize' => 'sanitize_text_field'
			),
			'description_mode' => array(
				'default' => '',
				'sanitize' => 'sanitize_text_field'
			),
			'placeholder' => array(
				'default' => '',
				'sanitize' => 'sanitize_text_field',
			),
			'width' => array(
				'default' => 'full',
				'sanitize' => 'sanitize_key'
			),
			'css_class' => array(
				'default' => '',
				'sanitize' => 'sanitize_text_field'
			),
			'masked' => array(
				'default' => 0,
				'sanitize' => 'intval'
			),
			'required' => array(
				'default' => 1,
				'sanitize' => 'justwpforms_sanitize_checkbox',
			),
			'default_value' => array(
				'default' => '',
				'sanitize' => 'sanitize_text_field'
			)
		);

		return justwpforms_get_part_customize_fields( $fields, $this->type );
	}

	/**
	 * Get template for part item in customize pane.
	 *
	 * @since 1.0.0.
	 *
	 * @return string
	 */
	public function customize_templates() {
		$template_path = justwpforms_get_include_folder() . '/templates/parts/customize-phone.php';
		$template_path = justwpforms_get_part_customize_template_path( $template_path, $this->type );

		require_once( $template_path );
	}

	/**
	 * Get front end part template with parsed data.
	 *
	 * @since 1.0.0.
	 *
	 * @param array	$part_data 	Form part data.
	 * @param array	$form_data	Form (post) data.
	 *
	 * @return string	Markup for the form part.
	 */
	public function frontend_template( $part_data = array(), $form_data = array() ) {
		$part = wp_parse_args( $part_data, $this->get_customize_defaults() );
		$form = $form_data;

		include( justwpforms_get_include_folder() . '/templates/parts/frontend-phone.php' );
	}

	public function get_default_value( $part_data = array() ) {
		return array();
	}

	/**
	 * Sanitize submitted value before storing it.
	 *
	 * @since 1.0.0.
	 *
	 * @param array $part_data Form part data.
	 *
	 * @return string
	 */
	public function sanitize_value( $part_data = array(), $form_data = array(), $request = array() ) {
		$sanitized_value = $this->get_default_value( $part_data );
		$part_name = justwpforms_get_part_name( $part_data, $form_data );

		if ( isset( $request[$part_name] ) ) {
			$sanitized_value = wp_parse_args( $request[$part_name], $sanitized_value );

			if ( '' !== implode( '', array_values( $sanitized_value ) ) ) {
				foreach( $sanitized_value as $component => $value ) {
					if ( 'country' !== $component ) {
						$sanitized_value[$component] = preg_replace( '/[^0-9]/', '', $value );
					} else {
						$sanitized_value[$component] = sanitize_text_field( $value );
					}
				}
			}
		}

		return $sanitized_value;
	}

	/**
	 * Validate value before submitting it. If it fails validation,
	 * return WP_Error object, showing respective error message.
	 *
	 * @since 1.0.0.
	 *
	 * @param array $part_data Form part data.
	 * @param string $value Submitted value.
	 *
	 * @return string|object
	 */
	public function validate_value( $value, $part = array(), $form = array() ) {
		$part_name = justwpforms_get_part_name( $part, $form );
		$validated_values = $value;

		if ( $part['required'] && empty( $validated_values['number'] ) ) {
			$error = new WP_Error( 'error', justwpforms_get_validation_message( 'field_empty' ) );

			return $error;
		}

		$phone_string = $validated_values['number'];

		if ( $this->is_masked( $part ) ) {
			$filled_inputs = array_filter( $validated_values );
			$input_incomplete = 1 === count( $filled_inputs ) ;

			if ( $input_incomplete ) {
				$error = new WP_Error( 'error', justwpforms_get_validation_message( 'field_invalid' ) );

				return $error;
			}

			$phone_string = '+' . $validated_values['code'] . ' ' . $validated_values['number'];
		}

		return $phone_string;
	}

	public function message_part_value( $value, $original_value, $part, $destination ) {
		if ( $this->type === $part['type'] ) {
			switch( $destination ) {
				case 'email':
				case 'admin-column':
					$value = '<a href="tel:' . $value . '">' . $value . '</a>';
					break;
				default:
					break;
			}
		}

		return $value;
	}

	public function is_masked( $part ) {
		// back compatibility with "Mask this input" option
		if ( 1 == $part['masked'] ) {
			return true;
		}

		return false;
	}

	public function validate_part( $part_data ) {
		if ( $this->type !== $part_data['type'] ) {
			return $part_data;
		}

		return $part_data;
	}

	public function html_part_class( $class, $part, $form ) {
		if ( $this->type === $part['type'] ) {
			if ( $this->is_masked( $part )
				|| justwpforms_get_part_value( $part, $form, 0 )
				|| justwpforms_get_part_value( $part, $form, 1 ) ) {
				$class[] = 'justwpforms-part--filled';
			}

			if ( $this->is_masked( $part ) ) {
				$class[] = 'justwpforms-phone-international';
			}

			if ( ! $this->is_masked( $part ) ) {
				$class[] = 'justwpforms-phone--plain';
			}

			if ( 'focus-reveal' === $part['description_mode'] ) {
				$class[] = 'justwpforms-part--focus-reveal-description';
			}
		}

		return $class;
	}

	public function get_part_value( $value, $part, $form ) {
		if ( $this->type === $part['type'] ) {
			$value['number'] = $part['default_value'];
		}

		return $value;
	}

	public function html_part_data_attributes( $attributes, $part, $form ) {
		if ( $this->type !== $part['type'] ) {
			return $attributes;
		}

		if ( $this->is_masked( $part ) ) {
			$attributes['mask'] = 'true';
		}

		return $attributes;
	}

	/**
	 * Enqueue scripts in customizer area.
	 *
	 * @since 1.0.0.
	 *
	 * @param array	List of dependencies.
	 *
	 * @return void
	 */
	public function customize_enqueue_scripts( $deps = array() ) {
		wp_enqueue_script(
			'part-phone',
			justwpforms_get_plugin_url() . 'inc/assets/js/parts/part-phone.js',
			$deps, justwpforms_get_version(), true
		);
	}

	/**
	 * Action: enqueue additional scripts on the frontend.
	 *
	 * @since 1.0.0.
	 *
	 * @hooked action justwpforms_frontend_dependencies
	 *
	 * @param array	List of dependencies.
	 *
	 * @return array
	 */
	public function script_dependencies( $deps, $forms ) {
		$contains_phone = false;
		$form_controller = justwpforms_get_form_controller();

		foreach ( $forms as $form ) {
			if ( $form_controller->get_first_part_by_type( $form, $this->type ) ) {
				$contains_phone = true;
				break;
			}
		}

		if ( ! justwpforms_is_preview() && ! $contains_phone ) {
			return $deps;
		}

		wp_register_script(
			'justwpforms-part-phone',
			justwpforms_get_plugin_url() . 'inc/assets/js/frontend/phone.js',
			array(), justwpforms_get_version(), true
		);

		$deps[] = 'justwpforms-part-phone';

		return $deps;
	}

	public function meta_messages_fields( $fields ) {
		$messages_fields = array(
			'phone_label_country_code' => array(
				'default' => __( 'Country code', 'justwpforms' ),
				'sanitize' => 'sanitize_text_field',
			),
			'phone_label_number' => array(
				'default' => __( 'Phone number', 'justwpforms' ),
				'sanitize' => 'sanitize_text_field',
			),
		);

		$fields = array_merge( $fields, $messages_fields );

		return $fields;
	}

	public function messages_controls( $controls ) {
		$message_controls = array(
			6105 => array(
				'type' => 'text',
				'label' => __( 'Phone field country code label', 'justwpforms' ),
				'field' => 'phone_label_country_code',
			),
			6106 => array(
				'type' => 'text',
				'label' => __( 'Phone field number label', 'justwpforms' ),
				'field' => 'phone_label_number',
			),
		);

		$controls = justwpforms_safe_array_merge( $controls, $message_controls );

		return $controls;
	}

}
