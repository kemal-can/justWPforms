<?php

class justwpforms_Part_Number extends justwpforms_Form_Part {

	public $type = 'number';

	public function __construct() {
		$this->label = __( 'Number', 'justwpforms' );
		$this->description = __( 'For numeric fields.', 'justwpforms' );

		add_filter( 'justwpforms_part_value', array( $this, 'get_part_value' ), 10, 3 );
		add_filter( 'justwpforms_part_class', array( $this, 'html_part_class' ), 10, 3 );
		add_filter( 'justwpforms_part_data_attributes', array( $this, 'html_part_data_attributes' ), 10, 3 );
		add_filter( 'justwpforms_frontend_dependencies', array( $this, 'script_dependencies' ), 10, 2 );
		add_filter( 'justwpforms_stringify_part_value', array( $this, 'stringify_value' ), 10, 3 );
		add_filter( 'justwpforms_validate_part', array( $this, 'validate_part' ) );
		add_filter( 'justwpforms_email_part_visible', array( $this, 'email_part_visible' ), 10, 4 );
		add_filter( 'justwpforms_the_part_value', array( $this, 'handle_confirmation_value' ), 10, 4 );
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
			'min_value' => array(
				'default' => '',
				'sanitize' => 'justwpforms_sanitize_intval_empty'
			),
			'max_value' => array(
				'default' => '',
				'sanitize' => 'justwpforms_sanitize_intval_empty'
			),
			'step' => array(
				'default' => 1,
				'sanitize' => 'floatval',
			),
			'masked' => array(
				'default' => 0,
				'sanitize' => 'intval'
			),
			'mask_numeric_thousands_delimiter' => array(
				'default' => ',',
				'sanitize' => 'sanitize_text_field'
			),
			'mask_numeric_decimal_mark' => array(
				'default' => '.',
				'sanitize' => 'sanitize_text_field'
			),
			'mask_numeric_prefix' => array(
				'default' => '',
				'sanitize' => 'sanitize_text_field'
			),
			'mask_numeric_suffix' => array(
				'default' => '',
				'sanitize' => 'sanitize_text_field'
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
		$template_path = justwpforms_get_core_folder() . '/templates/parts/customize-number.php';
		$template_path = justwpforms_get_part_customize_template_path( $template_path, $this->type );

		require_once( $template_path );
	}

	public function validate_part( $part_data ) {
		if ( $this->type !== $part_data['type'] ) {
			return $part_data;
		}

		if ( '' === $part_data['min_value'] || '' === $part_data['max_value'] ) {
			return $part_data;
		}

		$min_value = $part_data['min_value'];
		$max_value = $part_data['max_value'];

		$min_value = intval( $min_value );
		$max_value = intval( $max_value );
		$min_value = min( $min_value, $max_value );

		$part_data['min_value'] = $min_value;

		return $part_data;
	}

	public function email_part_visible( $visible, $part, $form, $response ) {
		if ( $this->type === $part['type'] ) {
			if ( ( '' === $part['mask_numeric_prefix'] ) && ( '' === $part['mask_numeric_suffix'] ) ) {
				return $visible;
			}

			$empty_value = $part['mask_numeric_prefix'] . $part['mask_numeric_suffix'];
			$value = justwpforms_get_email_part_value( $response, $part, $form );

			if ( $empty_value === $value ) {
				$visible = false;
			}
		}

		return $visible;
	}

	public function handle_confirmation_value( $value, $part, $form, $component ) {
		if ( $this->type === $part['type'] ) {
			if ( false === $component && is_array( $value ) ) {
				$value = $value[0];
			}
		}

		return $value;
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

		include( justwpforms_get_core_folder() . '/templates/parts/frontend-number.php' );
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
			$sanitized_value = sanitize_text_field( $request[$part_name] );
		}

		return $sanitized_value;
	}

	/**
	 * Validate value before submitting it. If it fails validation,
	 * return WP_Error object, showing respective error message.
	 *
	 * @since 1.0.0.
	 *
	 * @param array $part Form part data.
	 * @param string $value Submitted value.
	 *
	 * @return string|object
	 */
	public function validate_value( $value, $part = array(), $form = array() ) {
		$part_name = justwpforms_get_part_name( $part, $form );
		$validated_values = $value;

		if ( $part['required'] && '' === $validated_values ) {
			$error = new WP_Error( 'error', justwpforms_get_validation_message( 'field_empty' ) );

			return $error;
		}

		if ( '' === $validated_values ) {
			return $validated_values;
		}

		$validation_number = $validated_values;

		if ( $part['masked'] ) {
			$validation_number = str_replace( $part['mask_numeric_prefix'], '', $validation_number );
			$validation_number = trim( $validation_number );
			$validation_number = str_replace( $part['mask_numeric_thousands_delimiter'], '', $validation_number );
			$validation_number = str_replace( $part['mask_numeric_decimal_mark'], '.', $validation_number );
		}

		// Bounds check
		$validation_number = floatval( $validation_number );

		if ( '' !== $part['min_value'] ) {
			$min_value = intval( $part['min_value'] );

			if ( $validation_number < $min_value ) {
				return new WP_Error( 'error', justwpforms_get_validation_message( 'number_min_invalid' ) );
			}
		}

		if ( '' !== $part['max_value'] ) {
			$max_value = intval( $part['max_value'] );

			if ( $validation_number > $max_value ) {
				return new WP_Error( 'error', justwpforms_get_validation_message( 'number_max_invalid' ) );
			}
		}

		return $validated_values;
	}

	public function stringify_value( $value, $part, $form ) {
		if ( $this->type === $part['type'] ) {
			if ( ! empty( $part['mask_numeric_prefix'] ) ) {
				$value = "{$part['mask_numeric_prefix']}{$value}";
			}

			if ( ! empty( $part['mask_numeric_suffix'] ) ) {
				$value = "{$value}{$part['mask_numeric_suffix']}";
			}
		}

		return $value;
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
			'part-number',
			justwpforms_get_plugin_url() . 'core/assets/js/parts/part-number.js',
			$deps, justwpforms_get_version(), true
		);
	}

	public function html_part_class( $class, $part, $form ) {
		if ( $this->type === $part['type'] ) {
			if ( justwpforms_get_part_value( $part, $form, 0 )
				|| justwpforms_get_part_value( $part, $form, 1 ) ) {
				$class[] = 'justwpforms-part--filled';
			}

			if ( 'focus-reveal' === $part['description_mode'] ) {
				$class[] = 'justwpforms-part--focus-reveal-description';
			}
		}

		return $class;
	}

	public function get_part_value( $value, $part, $form ){
		if ( $this->type === $part['type'] ) {
			$value = $part['default_value'];
		}
		return $value;
	}

	public function html_part_data_attributes( $attributes, $part, $form ) {
		if ( $this->type !== $part['type'] ) {
			return $attributes;
		}

		if ( $part['masked'] ) {
			$attributes['mask'] = 'true';
			$attributes['thousands-delimiter'] = $part['mask_numeric_thousands_delimiter'];
			$attributes['decimal-mark'] = $part['mask_numeric_decimal_mark'];
			$attributes['prefix'] = $part['mask_numeric_prefix'];
		}

		return $attributes;
	}

	/**
	 * Action: enqueue additional scripts on the frontend.
	 *
	 * @since 1.3.0.
	 *
	 * @hooked action justwpforms_frontend_dependencies
	 *
	 * @param array	List of dependencies.
	 *
	 * @return array
	 */
	public function script_dependencies( $deps, $forms ) {
		$contains_number = false;
		$form_controller = justwpforms_get_form_controller();

		foreach ( $forms as $form ) {
			if ( $form_controller->get_first_part_by_type( $form, $this->type ) ) {
				$contains_number = true;
				break;
			}
		}

		if ( ! justwpforms_is_preview() && ! $contains_number ) {
			return $deps;
		}

		wp_register_script(
			'cleave',
			justwpforms_get_plugin_url() . 'core/assets/js/lib/cleave.min.js',
			array(), justwpforms_get_version()
		);

		wp_register_script(
			'justwpforms-part-number',
			justwpforms_get_plugin_url() . 'core/assets/js/frontend/number.js',
			array( 'cleave' ), justwpforms_get_version(), true
		);

		$deps[] = 'justwpforms-part-number';

		return $deps;
	}

}
