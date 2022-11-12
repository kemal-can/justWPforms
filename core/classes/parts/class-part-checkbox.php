<?php

class justwpforms_Part_Checkbox extends justwpforms_Form_Part {

	public $type = 'checkbox';

	public static $parent;

	public function __construct() {
		$this->label = __( 'Checkbox', 'justwpforms' );
		$this->description = __( 'For checkboxes allowing multiple selections.', 'justwpforms' );

		$this->hook();
	}

	public function hook() {
		self::$parent = $this;

		add_filter( 'justwpforms_part_input_after', array( $this, 'append_input' ), 10, 2 );
		add_filter( 'justwpforms_part_value', array( $this, 'get_part_value' ), 10, 3 );
		add_filter( 'justwpforms_part_class', array( $this, 'html_part_class' ), 10, 3 );
		add_filter( 'justwpforms_stringify_part_value', array( $this, 'stringify_value' ), 10, 3 );
		add_filter( 'justwpforms_frontend_dependencies', array( $this, 'script_dependencies' ), 10, 2 );
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
			'css_class' => array(
				'default' => '',
				'sanitize' => 'sanitize_text_field'
			),
			'display_type' => array(
				'default' => 'block',
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
				'sanitize' => 'justwpforms_sanitize_checkbox',
			),
			'other_option_label' => array(
				'default' => __( 'Other', 'justwpforms' ),
				'sanitize' => 'sanitize_text_field',
			),
			'other_option_placeholder' => array(
				'default' => '',
				'sanitize' => 'sanitize_text_field',
			),
			'limit_choices' => array(
				'default' => 0,
				'sanitize' => 'justwpforms_sanitize_checkbox',
			),
			'limit_choices_min' => array(
				'default' => 1,
				'sanitize' => 'intval',
			),
			'limit_choices_max' => array(
				'default' => 1,
				'sanitize' => 'intval',
			)
		);

		return justwpforms_get_part_customize_fields( $fields, $this->type );
	}

	public function append_input( $part, $form ) {
		if ( $this->type !== $part['type'] ) {
			return;
		}

		if ( 1 == $part['other_option'] ) {
			require( justwpforms_get_core_folder() . '/templates/parts/frontend-checkbox-other-option.php' );
		}
	}

	protected function get_option_defaults() {
		return array(
			'is_default' => 0,
			'label' => '',
			'description' => '',
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
		$template_path = justwpforms_get_core_folder() . '/templates/parts/customize-checkbox.php';
		$template_path = justwpforms_get_part_customize_template_path( $template_path, $this->type );

		require_once( $template_path );
	}

	/**
	 * Get front end part template with parsed data.
	 *
	 * @since 1.0.0.
	 *
	 * @param array	$part_data Form part data.
	 * @param array	$form_data Form (post) data.
	 *
	 * @return string Markup for the form part.
	 */
	public function frontend_template( $part_data = array(), $form_data = array() ) {
		$part = wp_parse_args( $part_data, $this->get_customize_defaults() );
		$form = $form_data;

		foreach( $part['options'] as $o => $option ) {
			$part['options'][$o] = wp_parse_args( $option, $this->get_option_defaults() );
		}

		$template_path = justwpforms_get_core_folder() . '/templates/parts/frontend-checkbox.php';
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
			'part-checkbox',
			justwpforms_get_plugin_url() . 'core/assets/js/parts/part-checkbox.js',
			$deps, justwpforms_get_version(), true
		);
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
	 * @return array
	 */
	public function sanitize_value( $part_data = array(), $form_data = array(), $request = array() ) {
		$sanitized_value = $this->get_default_value( $part_data );
		$part_name = justwpforms_get_part_name( $part_data, $form_data );

		if ( isset( $request[$part_name] ) ) {
			$requested_data = $request[$part_name];

			$filtered_request = array_map( 'json_decode', array_map( 'stripslashes', $requested_data ) );
			$contains_array = array_map( 'is_array', $filtered_request );

			if ( ! in_array( true, $contains_array ) ) {
				if ( isset( $request[$part_name] ) ) {
					$requested_data = $request[$part_name];

					if ( is_array( $requested_data ) ) {
						$sanitized_value = array_map( 'intval', $requested_data );
					}
				}
			} else {
				foreach ( $filtered_request as $index => $request ) {
					if ( is_array( $request ) ) {
						$filtered_request[$index][0] = intval( $filtered_request[$index][0] );
						$filtered_request[$index][1] = sanitize_text_field( $filtered_request[$index][1] );
					} else {
						$filtered_request[$index] = intval( $filtered_request[$index] );
					}
				}

				$sanitized_value = $filtered_request;
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

		if ( 1 === $part['required'] && empty( $validated_value ) ) {
			$validated_value = new WP_Error( 'error', justwpforms_get_validation_message( 'no_selection' ) );
			return $validated_value;
		}

		if ( ! empty( $validated_value ) && 1 === $part['limit_choices'] ) {
			if ( count( $validated_value ) < $part['limit_choices_min'] ) {
				return new WP_Error( 'error', justwpforms_get_validation_message( 'select_more_choices' ) );
			}

			if ( count( $validated_value ) > $part['limit_choices_max'] ) {
				return new WP_Error( 'error', justwpforms_get_validation_message( 'select_less_choices' ) );
			}
		}

		if ( ! is_array( $validated_value ) && 1 !== $part['required'] ) {
			return $validated_value;
		}

		$contains_array = array_map( 'is_array', $validated_value );

		if ( ! in_array( true, $contains_array ) ) {
			if ( 1 === $part['required'] && empty( $validated_value ) ) {
				$validated_value = new WP_Error( 'error', justwpforms_get_validation_message( 'no_selection' ) );
				return $validated_value;
			}

			$options = array_keys( $part['options'] );
			$intersection = array_intersect( $options, $validated_value );

			if ( count( $validated_value ) !== count( $intersection ) ) {
				return new WP_Error( 'error', justwpforms_get_validation_message( 'field_invalid' ) );
			}

			if ( is_wp_error( $validated_value ) ) {
				return $validated_value;
			}

			foreach ( $validated_value as $value ) {
				$value = $this->validate_option_limits( $value, $part, $form );

				if ( is_wp_error( $value ) ) {
					return $value;
				}
			}
		}

		$numeric_values = array_filter( $validated_value, 'is_int' );
		$array_values = array_filter( $validated_value, 'is_array' );
		$options = array_keys( $part['options'] );
		$intersection = array_intersect( $options, $numeric_values );

		if ( count( $numeric_values ) !== count( $intersection ) ) {
			return new WP_Error( 'error', justwpforms_get_validation_message( 'field_invalid' ) );
		}

		foreach ( $numeric_values as $numeric_value ) {
			$validated_numeric_value = $this->validate_option_limits( $numeric_value, $part, $form );

			if ( is_wp_error( $validated_numeric_value ) ) {
				return $validated_numeric_value;
			}
		}

		foreach ( $array_values as $array_value ) {
			if ( 1 === $part['required'] && ! isset( $array_value[1] ) ) {
				$validated_value = new WP_Error( 'error', justwpforms_get_validation_message( 'field_empty' ) );
				return $validated_value;
			}
		}

		foreach ( $validated_value as $key => $value ) {
			if ( is_array( $validated_value[ $key ] ) ) {
				foreach ( $validated_value[$key] as $opt_key  => $opt_val ) {
					if ( '' == $validated_value[$key][$opt_key] ) {
						return new WP_Error( 'error', justwpforms_get_validation_message( 'field_invalid' ) );
					}
				}
			}
		}

		return $validated_value;
	}

	public function get_part_value( $value, $part, $form ) {
		if ( $this->type === $part['type'] ) {
			foreach ( $part['options'] as $o => $option ) {
				if ( ! justwpforms_is_falsy( $option['is_default'] ) ) {
					$value[] = $o;
				}
			}
		}

		return $value;
	}

	public function stringify_value( $value, $part, $form, $force = false ) {
		if ( $this->type === $part['type'] || $force ) {
			if ( empty( $value ) ) {
				return $value;
			}
			$original_value = $value;
			$options = justwpforms_get_part_options( $part['options'], $part, $form );
			$labels = wp_list_pluck( $options, 'label' );
			$contains_array = array_map( 'is_array', $value );

			if ( ! in_array( true, $contains_array ) ) {
				foreach ( $value as $i => $index ) {
					$value[$i] = $labels[$index];
				}

				$value = implode( ', ', $value );
			} else {
				foreach ( $value as $i => $index ) {
					$label = '';
					if ( is_array( $index ) && ! empty( $index[1] ) ) { // other option
						$value[$i] = $index[1];
					} else { // standard option
						$value[$i] = $options[$index]['label'];
					}
				}
			}
		}

		return $value;
	}

	private function clamp( $v, $min, $max ) {
		return min( max( $v, $min ), $max );
	}

	public function validate_part( $part_data ) {
		if ( $this->type !== $part_data['type'] ) {
			return $part_data;
		}

		$min_choices = intval( $part_data['limit_choices_min'] );
		$max_choices = intval( $part_data['limit_choices_max'] );
		$num_choices = count( $part_data['options'] );

		$min_choices = $this->clamp( $min_choices, $num_choices > 1 ? 2 : 1, $min_choices );
		$min_choices = $this->clamp( $min_choices, $min_choices, $num_choices );
		$max_choices = $this->clamp( $max_choices, $min_choices, $num_choices );

		$part_data['limit_choices_min'] = $min_choices;
		$part_data['limit_choices_max'] = $max_choices;

		return $part_data;
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

	public function html_part_class( $class, $part, $form ) {
		if ( $this->type === $part['type'] ) {
			$class[] = 'justwpforms-part--choice';

			if ( 'block' === $part['display_type'] ) {
				$class[] = 'display-type--block';
			}
		}

		return $class;
	}

	public function script_dependencies( $deps, $forms ) {
		$contains_checkbox = false;
		$form_controller = justwpforms_get_form_controller();

		foreach ( $forms as $form ) {
			if ( $form_controller->get_first_part_by_type( $form, $this->type ) ) {
				$contains_checkbox = true;
				break;
			}
		}

		if ( ! justwpforms_is_preview() && ! $contains_checkbox ) {
			return $deps;
		}

		wp_register_script(
			'justwpforms-checkbox',
			justwpforms_get_plugin_url() . 'core/assets/js/frontend/checkbox.js',
			array(), justwpforms_get_version(), true
		);

		$deps[] = 'justwpforms-checkbox';

		return $deps;
	}

}
