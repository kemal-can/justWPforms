<?php

class justwpforms_Part_Select extends justwpforms_Form_Part {

	public $type = 'select';

	public static $parent;

	public function __construct() {
		$this->label = __( 'Dropdown', 'justwpforms' );
		$this->description = __( 'For selecting one option from a long list. Default value adjustable.', 'justwpforms' );

		$this->hook();
	}

	public function hook() {
		self::$parent = $this;

		add_filter( 'justwpforms_part_input_after', array( $this, 'append_input' ), 10, 2 );
		add_filter( 'justwpforms_stringify_part_value', array( $this, 'stringify_value' ), 10, 3 );
		add_filter( 'justwpforms_frontend_dependencies', array( $this, 'script_dependencies' ), 10, 2 );
		add_filter( 'justwpforms_part_class', array( $this, 'html_part_class' ), 10, 3 );
		add_filter( 'justwpforms_part_value', array( $this, 'get_part_value' ), 10, 3 );
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
			'required' => array(
				'default' => 1,
				'sanitize' => 'justwpforms_sanitize_checkbox',
			),
			'options' => array(
				'default' => array(),
				'sanitize' => 'justwpforms_sanitize_array'
			),
			'other_option' => array(
				'default' => 0,
				'sanitize' => 'justwpforms_sanitize_checkbox'
			),
			'other_option_label' => array(
				'default' => __( 'Other', 'justwpforms' ),
				'sanitize' => 'sanitize_text_field'
			),
			'other_option_placeholder' => array(
				'default' => '',
				'sanitize' => 'sanitize_text_field'
			),
		);

		return justwpforms_get_part_customize_fields( $fields, $this->type );
	}

	/**
	 * Get part option (sub-part) defaults.
	 *
	 * @since 1.0.0.
	 *
	 * @return array
	 */
	protected function get_option_defaults() {
		return array(
			'is_default' => 0,
			'label' => '',
			'is_heading' => 0
		);
	}

	/**
	 * Get template for part item in customize pane.
	 *
	 * @since 1.0.0.
	 *
	 * @return string
	 */
	public function customize_templates() {
		$template_path = justwpforms_get_core_folder() . '/templates/parts/customize-select.php';
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

		foreach( $part['options'] as $o => $option ) {
			$part['options'][$o] = wp_parse_args( $option, $this->get_option_defaults() );
		}

		$template_path = justwpforms_get_core_folder() . '/templates/parts/frontend-select.php';
		$template_path = justwpforms_get_part_frontend_template_path( $template_path, $this->type );

		include( $template_path );
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
			'part-select',
			justwpforms_get_plugin_url() . 'core/assets/js/parts/part-select.js',
			$deps, justwpforms_get_version(), true
		);
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
			$value_array = json_decode( stripslashes( $request[$part_name] ) );

			if ( ! is_array( $value_array ) ) {
				if ( isset( $request[$part_name] ) ) {
					$sanitized_value = sanitize_text_field( $request[$part_name] );
				}
				return $sanitized_value;
			}

			$sanitized_value = array();
			$sanitized_value[0] = intval( $value_array[0] );
			$sanitized_value[1] = sanitize_text_field( $value_array[1] );
		}

		return $sanitized_value;
	}

	/**
	 * Validate value before submitting it. If it fails validation, return WP_Error object, showing respective error message.
	 *
	 * @since 1.0.0.
	 *
	 * @param array $part Form part data.
	 * @param string $value Submitted value.
	 *
	 * @return string|object
	 */
	public function validate_value( $value, $part = array(), $form = array() ) {
		$validated_value = $value;

		if ( ! is_array( $validated_value ) ) {
			if ( 1 === $part['required'] && '' === $validated_value ) {
				return new WP_Error( 'error', justwpforms_get_validation_message( 'no_selection' ) );
			}

			if ( '' !== $validated_value ) {
				if ( ! is_numeric( $validated_value ) ) {
					return new WP_Error( 'error', justwpforms_get_validation_message( 'field_invalid' ) );
				}

				if ( ! in_array( intval( $validated_value ), array_keys( $part['options'] ) ) ) {
					return new WP_Error( 'error', justwpforms_get_validation_message( 'field_invalid' ) );
				}
			}

			if ( is_wp_error( $validated_value ) ) {
				return $validated_value;
			}

			return $this->validate_option_limits( $validated_value, $part, $form );
		}

		if ( 1 === $part['required'] && empty( $validated_value[0] ) ) {
			return new WP_Error( 'error', justwpforms_get_validation_message( 'no_selection' ) );
		}

		if ( ! empty( $validated_value[0] ) ) {
			if ( ! is_numeric( $validated_value[0] ) ) {
				return new WP_Error( 'error', justwpforms_get_validation_message( 'field_invalid' ) );
			}
		}

		if ( '' === $validated_value[1] ) {
			return new WP_Error( 'error', justwpforms_get_validation_message( 'field_invalid' ) );
		}

		return $validated_value;
	}

	public function stringify_value( $value, $part, $form ) {
		if ( $this->type === $part['type'] ) {
			$original_value = $value;

			if ( ! is_array( $value ) ) { // standard options
				if ( '' !== $value ) {
					$options = justwpforms_get_part_options( $part['options'], $part, $form );
					$value = $options[$value]['label'];
				}
			} else { // other option
				$value = ( ! empty( $value[1] ) ) ? $value[1] : '';
			}
		}

		return $value;
	}

	private function validate_option_limits( $value, $part, $form ) {
		foreach( $part['options'] as $o => $option ) {
			$option = wp_parse_args( $option, justwpforms_upgrade_get_option_limiter()->get_option_fields() );

			if ( '' == $option['limit_submissions_amount'] || $o !== intval( $value ) ) {
				continue;
			}

			$limit = intval( $option['limit_submissions_amount'] );
			$count = justwpforms_upgrade_get_option_limiter()->count_by_option( $form['ID'], $part['id'], $option['id'] );

			if ( $count >= $limit ) {
				return new WP_Error( 'error', justwpforms_get_validation_message( 'field_invalid' ) );
			}
		}

		return $value;
	}

	public function get_part_value( $value, $part, $form ) {
		if ( $this->type === $part['type'] ) {
			$options = $part['options'];

			foreach( $options as $option_value => $option ) {
				if ( 1 == $option['is_default'] ) {
					$value = $option_value;
				}
			}
		}

		return $value;
	}

	public function get_part_options( $options, $part, $form ) {
		if ( is_customize_preview() ) {
			return $options;
		}

		if ( $this->type !== $part['type'] ) {
			return $options;
		}

		$options = array_filter( $options, function( $option ) use( $part, $form ) {
			$option = wp_parse_args( $option, $this->get_option_defaults() );

			if ( '' == $option['limit_submissions_amount'] ) {
				return true;
			}

			$limit = intval( $option['limit_submissions_amount'] );
			$count = justwpforms_upgrade_get_option_limiter()->count_by_option( $form['ID'], $part['id'], $option['id'] );

			return $limit > $count;
		} );

		return $options;
	}

	public function append_input( $part, $form ) {
		if ( $this->type !== $part['type'] ) {
			return;
		}

		if ( 1 == $part['other_option'] ) {
			require( justwpforms_get_core_folder() . '/templates/parts/frontend-select-other-option.php' );
		}
	}

	public function html_part_class( $class, $part, $form ) {
		if ( $this->type === $part['type'] ) {
			if ( justwpforms_get_part_value( $part, $form ) ) {
				$class[] = 'justwpforms-part--filled';
			}

			if ( 1 === intval( $part['required'] ) ) {
				$class[] = 'justwpforms-part-select--required';
			}
		}

		return $class;
	}

	public function script_dependencies( $deps, $forms ) {
		$contains_select = false;
		$form_controller = justwpforms_get_form_controller();

		foreach ( $forms as $form ) {
			if ( $form_controller->get_first_part_by_type( $form, $this->type ) ) {
				$contains_select = true;
				break;
			}
		}

		if ( ! justwpforms_is_preview() && ! $contains_select ) {
			return $deps;
		}

		wp_register_script(
			'justwpforms-dropdown',
			justwpforms_get_plugin_url() . 'core/assets/js/frontend/select.js',
			array(), justwpforms_get_version(), true
		);

		$deps[] = 'justwpforms-dropdown';

		return $deps;
	}
}
