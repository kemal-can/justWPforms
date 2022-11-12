<?php

class justwpforms_Part_Scale extends justwpforms_Form_Part {

	public $type = 'scale';

	public function __construct() {
		$this->label = __( 'Slider', 'justwpforms' );
		$this->description = __( 'For collecting opinions using a horizontal slider.', 'justwpforms' );

		add_filter( 'justwpforms_frontend_dependencies', array( $this, 'script_dependencies' ), 10, 2 );
		add_filter( 'justwpforms_part_value', array( $this, 'get_part_value' ), 10, 2 );
		add_filter( 'justwpforms_the_part_value', array( $this, 'output_part_value' ), 10, 3 );
		add_filter( 'justwpforms_part_class', array( $this, 'html_part_class' ), 10, 3 );
		add_filter( 'justwpforms_validate_part', array( $this, 'validate_part' ) );
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
			'width' => array(
				'default' => 'full',
				'sanitize' => 'sanitize_key'
			),
			'min_label' => array(
				'default' => '',
				'sanitize' => 'sanitize_text_field'
			),
			'max_label' => array(
				'default' => '',
				'sanitize' => 'sanitize_text_field'
			),
			'min_value' => array(
				'default' => 0,
				'sanitize' => 'intval'
			),
			'max_value' => array(
				'default' => 100,
				'sanitize' => 'intval'
			),
			'multiple' => array(
				'default' => 0,
				'sanitize' => 'justwpforms_sanitize_checkbox'
			),
			'default_range_from' => array(
				'default' => 0,
				'sanitize' => 'intval'
			),
			'default_range_to' => array(
				'default' => 50,
				'sanitize' => 'intval'
			),
			'step' => array(
				'default' => 1,
				'sanitize' => 'floatval',
			),
			'default_value' => array(
				'default' => 50,
				'sanitize' => 'intval'
			),
			'css_class' => array(
				'default' => '',
				'sanitize' => 'sanitize_text_field'
			),
			'required' => array(
				'default' => 1,
				'sanitize' => 'justwpforms_sanitize_checkbox',
			),
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
		$template_path = justwpforms_get_include_folder() . '/templates/parts/customize-scale.php';
		$template_path = justwpforms_get_part_customize_template_path( $template_path, $this->type );

		require_once( $template_path );
	}

	public function validate_part( $part_data ) {
		if ( $this->type !== $part_data['type'] ) {
			return $part_data;
		}

		$min_value = $part_data['min_value'];
		$max_value = $part_data['max_value'];
		$multiple = $part_data['multiple'];
		$default_range_from = $part_data['default_range_from'];
		$default_range_to = $part_data['default_range_to'];
		$default_value = $part_data['default_value'];
		$step = $part_data['step'];

		$min_value = intval( $min_value );
		$max_value = intval( $max_value );
		$multiple = intval( $multiple );
		$default_range_from = intval( $default_range_from );
		$default_range_to = intval( $default_range_to );
		$default_value = intval( $default_value );
		$step = floatval( $step );

		$min_value = min( $min_value, $max_value );

		if ( $multiple ) {
			$default_range_from = min( $default_range_from, $default_range_to );
		}

		$step = ( 0 === $step ) ? 1 : $step;
		$default_value = floor( $default_value / $step ) * $step;
		$max_value = floor( $max_value / $step ) * $step;
		$max_value = max( $max_value, $default_value );

		$part_data['min_value'] = $min_value;
		$part_data['max_value'] = $max_value;
		$part_data['multiple'] = $multiple;
		$part_data['default_range_from'] = $default_range_from;
		$part_data['default_range_to'] = $default_range_to;
		$part_data['default_value'] = $default_value;
		$part_data['step'] = $step;

		return $part_data;
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

		include( justwpforms_get_include_folder() . '/templates/parts/frontend-scale.php' );
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
			if ( is_array( $request[$part_name] ) && isset( $request[$part_name][0] ) ) {
				$array_value = explode( ',', $request[$part_name][0] );
				$sanitized_value = array_map( 'intval', $array_value );
			} else {
				$sanitized_value = intval( $request[$part_name] );
			}
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

		if ( 1 === $part['required'] && ( '' === $validated_value ) ) {
			return new WP_Error( 'error', justwpforms_get_validation_message( 'no_selection' ) );
		}

		// handle multiple range
		if ( is_array( $validated_value ) && count( $validated_value ) !== count( array_filter( $validated_value, 'is_numeric' ) ) ) {
			$validated_value = new WP_Error( 'error', justwpforms_get_validation_message( 'field_invalid' ) );
		}

		if ( ! is_array( $validated_value ) && ! is_numeric( $validated_value ) ) {
			$validated_value = new WP_Error( 'error', justwpforms_get_validation_message( 'field_invalid' ) );
		}

		return $validated_value;
	}

	public function get_part_value( $value, $part )  {
		if ( $this->type === $part['type'] ) {
			if ( ! empty( $value ) ) {
				if ( is_array( $value ) ) {
					$value = implode( ',', $value );
				}
			} else {
				if ( 1 === intval( $part['multiple'] ) ) {
					$value = $part['default_range_from'] . ',' . $part['default_range_to'];
				} else {
					$value = $part['default_value'];
				}
			}
		}

		return $value;
	}

	public function output_part_value( $value, $part, $form ) {
		if ( $this->type === $part['type'] ) {
			if ( ! empty( $value ) && is_array( $value ) ) {
				$value = implode( ',', $value );
			}
		}

		return $value;
	}

	public function html_part_class( $class, $part, $form ) {
		if ( $this->type === $part['type'] ) {
			if ( 1 === intval( $part['multiple'] ) ) {
				$class[] = 'justwpforms-part--scale-multiple';
			}
		}

		return $class;
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
			'part-scale',
			justwpforms_get_plugin_url() . 'inc/assets/js/parts/part-scale.js',
			$deps, justwpforms_get_version(), true
		);
	}

	public function script_dependencies( $deps, $forms ) {
		$contains_scale = false;
		$form_controller = justwpforms_get_form_controller();

		foreach ( $forms as $form ) {
			if ( $form_controller->get_first_part_by_type( $form, $this->type ) ) {
				$contains_scale = true;
				break;
			}
		}

		if ( ! justwpforms_is_preview() && ! $contains_scale ) {
			return $deps;
		}

		wp_register_script(
			'multirange-polyfill',
			justwpforms_get_plugin_url() . 'inc/assets/js/lib/multirange.js',
			'',
			false,
			true
		);

		wp_register_script(
			'justwpforms-part-scale',
			justwpforms_get_plugin_url() . 'inc/assets/js/frontend/scale.js',
			array( 'multirange-polyfill' ), justwpforms_get_version(), true
		);

		$deps[] = 'justwpforms-part-scale';

		return $deps;
	}

}
