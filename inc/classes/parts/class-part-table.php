<?php

class justwpforms_Part_Table extends justwpforms_Form_Part {

	public $type = 'table';

	public function __construct() {
		$this->label = __( 'Matrix', 'justwpforms' );
		$this->description = __( 'For radios and checkboxes displaying in a grid of rows and columns.', 'justwpforms' );

		add_filter( 'justwpforms_part_value', array( $this, 'get_part_value' ), 10, 3 );
		add_filter( 'justwpforms_part_class', array( $this, 'html_part_class' ), 10, 3 );
		add_filter( 'justwpforms_stringify_part_value', array( $this, 'stringify_value' ), 10, 3 );
		add_filter( 'justwpforms_message_part_value', array( $this, 'message_part_value' ), 10, 4 );
		add_filter( 'justwpforms_frontend_dependencies', array( $this, 'script_dependencies' ), 10, 2 );
		add_filter( 'justwpforms_validate_part', array( $this, 'validate_part' ) );
		add_filter( 'justwpforms_get_csv_value', array( $this, 'get_csv_value' ), 10, 4 );
		add_filter( 'justwpforms_limited_options_supported_parts', array( $this, 'add_limited_option_support' ) );
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
			'required' => array(
				'default' => 1,
				'sanitize' => 'justwpforms_sanitize_checkbox',
			),
			'allow_multiple_selection' => array(
				'default' => 0,
				'sanitize' => 'justwpforms_sanitize_checkbox'
			),
			'columns' => array(
				'default' => array(),
				'sanitize' => 'justwpforms_sanitize_array'
			),
			'rows' => array(
				'default' => array(),
				'sanitize' => 'justwpforms_sanitize_array'
			),
			'limit_choices' => array(
				'default' => 0,
				'sanitize' => 'justwpforms_sanitize_checkbox'
			),
			'limit_choices_min' => array(
				'default' => 1,
				'sanitize' => 'intval'
			),
			'limit_choices_max' => array(
				'default' => 1,
				'sanitize' => 'intval'
			)
		);

		return justwpforms_get_part_customize_fields( $fields, $this->type );
	}

	private function get_column_defaults() {
		return array(
			'is_default' => 0,
			'label' => '',
			'type' => 'column',
		);
	}

	private function get_row_defaults() {
		return array(
			'label' => '',
			'type' => 'row'
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
		$template_path = justwpforms_get_include_folder() . '/templates/parts/customize-table.php';
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

		foreach( $part['columns'] as $c => $column ) {
			$part['columns'][$c] = wp_parse_args( $column, $this->get_column_defaults() );
		}

		foreach( $part['rows'] as $r => $row ) {
			$part['rows'][$r] = wp_parse_args( $row, $this->get_row_defaults() );
		}

		include( justwpforms_get_include_folder() . '/templates/parts/frontend-table.php' );
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
		wp_register_script(
			'part-table',
			justwpforms_get_plugin_url() . 'inc/assets/js/parts/part-table.js',
			$deps, justwpforms_get_version(), true
		);

		$settings = array(
			'column' => $this->get_column_defaults(),
			'row' => $this->get_row_defaults(),
		);

		wp_localize_script( 'part-table', '_justwpformsTableSettings', $settings );
		wp_enqueue_script( 'part-table' );
	}

	public function get_default_value( $part_data = array() ) {
		$value = array();

		if ( $part_data['allow_multiple_selection'] ) {
			foreach( $part_data['rows'] as $row ) {
				$value[$row['id']] = array();
			}
		}

		return $value;
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
			$requested_data = $request[$part_name];

			if ( is_array( $requested_data ) ) {
				if ( $part_data['allow_multiple_selection'] ) {
					// Checkbox mode
					foreach( $requested_data as $row_id => $row ) {
						$sanitized_value[$row_id] = array_map( 'intval', $requested_data[$row_id] );
					}
				} else {
					// Radio mode
					$sanitized_value = array_map( 'intval', $requested_data );
				}
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

		$rows = justwpforms_get_part_options( $part['rows'], $part, $form );
		$columns = justwpforms_get_part_options( $part['columns'], $part, $form );
		$row_ids = wp_list_pluck( $rows, 'id' );

		if ( 1 === $part['required'] ) {
			$validated_rows = array_keys( $validated_value );

			if ( count( $row_ids ) !== count( array_intersect( $row_ids, $validated_rows ) ) ) {
				return new WP_Error( 'error', justwpforms_get_validation_message( 'no_selection' ) );
			}

			if ( $part['allow_multiple_selection'] ) {
				$non_empty_rows = array_filter( $validated_value );

				if ( count( $non_empty_rows ) !== count( $row_ids ) ) {
					return new WP_Error( 'error', justwpforms_get_validation_message( 'no_selection' ) );
				}
			}
		}

		$columns = array_keys( $columns );

		if ( 1 == $part['allow_multiple_selection'] ) {
			// Checkbox mode
			foreach( $validated_value as $row_id => $row ) {
				if ( 1 == $part['limit_choices'] ) {
					if ( count( $row ) < $part['limit_choices_min'] ) {
						return new WP_Error( 'error', justwpforms_get_validation_message( 'select_more_choices' ) );
					}

					if ( count( $row ) > $part['limit_choices_max'] ) {
						return new WP_Error( 'error', justwpforms_get_validation_message( 'select_less_choices' ) );
					}
				}

				$intersection = array_intersect( $columns, $row );

				if ( count( $row ) !== count( $intersection ) ) {
					return new WP_Error( 'error', justwpforms_get_validation_message( 'field_invalid' ) );
				}
			}
		} else {
			// Radio mode
			foreach( $validated_value as $value ) {
				if ( ! in_array( $value, $columns ) ) {
					return new WP_Error( 'error', justwpforms_get_validation_message( 'field_invalid' ) );
				}
			}
		}

		foreach( $validated_value as $row_id => $row ) {
			if ( ! is_array( $row ) ) {
				$row = array( $row );
			}

			foreach( $row as $value ) {
				$validated_row_value = $this->validate_option_limits( $value, $part, $form );

				if ( is_wp_error( $validated_row_value ) ) {
					return $validated_row_value;
				}
			}
		}

		return $validated_value;
	}

	private function validate_option_limits( $value, $part, $form ) {
		foreach( $part['columns'] as $c => $column ) {
			$column = wp_parse_args( $column, justwpforms_upgrade_get_option_limiter()->get_option_fields() );

			if ( '' == $column['limit_submissions_amount'] || $c !== intval( $value ) || $value !== $c ) {
				continue;
			}

			$limit = intval( $column['limit_submissions_amount'] );
			$count = justwpforms_upgrade_get_option_limiter()->count_by_option( $form['ID'], $part['id'], $column['id'] );

			if ( $count === $limit ) {
				return new WP_Error( 'error', justwpforms_get_validation_message( 'field_invalid' ) );
			}
		}

		return $value;
	}

	public function get_part_value( $value, $part, $form ) {
		if ( $this->type === $part['type'] ) {
			foreach ( $part['columns'] as $c => $column ) {
				if ( ! justwpforms_is_falsy( $column['is_default'] ) ) {
					if ( ! $part['allow_multiple_selection'] ) {
						$value = $c;
					} else {
						foreach( $value as $r => $row ) {
							$value[$r][] = $c;
						}
					}
				}
			}
		}

		return $value;
	}

	public function html_part_class( $class, $part, $form ) {
		if ( $this->type === $part['type'] ) {
			if ( $part['allow_multiple_selection'] ) {
				$class[] = 'justwpforms-selection--multiple';
			}
		}

		return $class;
	}

	public function stringify_value( $value, $part, $form ) {
		if ( $this->type === $part['type'] ) {
			$columns = justwpforms_get_part_options( $part['columns'], $part, $form );
			$rows = justwpforms_get_part_options( $part['rows'], $part, $form );
			$column_labels = wp_list_pluck( $columns, 'label' );
			$row_labels = wp_list_pluck( $rows, 'label', 'id' );
			$string_value = array();

			foreach ( $value as $row_id => $row_values ) {
				$row_label = $row_labels[$row_id];
				$row_values = $part['allow_multiple_selection'] ? $row_values : array( $row_values );

				foreach ( $row_values as $r => $row_value ) {
					$row_values[$r] = $column_labels[$row_value];
				}

				if ( count( $row_values ) ) {
					$row_values = implode( ', ', $row_values );
					$string_value[] = "{$row_label}: $row_values";
				}
			}

			$value = implode( '<br>', $string_value );
		}

		return $value;
	}

	public function message_part_value( $value, $original_value, $part, $destination ) {
		if ( isset( $part['type'] )
			&& $this->type === $part['type']
			&& 'email' === $destination ) {

			$value = "<br>{$value}";
		}

		return $value;
	}

	public function script_dependencies( $deps, $forms ) {
		$contains_table = false;
		$form_controller = justwpforms_get_form_controller();

		foreach ( $forms as $form ) {
			if ( $form_controller->get_first_part_by_type( $form, $this->type ) ) {
				$contains_table = true;
				break;
			}
		}

		if ( ! justwpforms_is_preview() && ! $contains_table ) {
			return $deps;
		}

		wp_register_script(
			'justwpforms-part-table',
			justwpforms_get_plugin_url() . 'inc/assets/js/frontend/table.js',
			array(), justwpforms_get_version(), true
		);

		$deps[] = 'justwpforms-part-table';

		return $deps;
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
		$num_choices_columns = count( $part_data['columns'] );
		$num_choices_rows = count( $part_data['rows'] );
		$num_choices = $num_choices_columns * $num_choices_rows;

		// reset limit choices option when poll has radios instead of checkboxes
		if ( 0 == $part_data['allow_multiple_selection'] && 1 == $part_data['limit_choices'] ) {
			$part_data['limit_choices'] = 0;
			$part_data['limit_choices_min'] = '';
			$part_data['limit_choices_max'] = '';
		}

		if ( 1 == $part_data['allow_multiple_selection'] ) {
			$min_choices = $this->clamp( $min_choices, $num_choices > 1 ? 2 : 1, $min_choices );
			$min_choices = $this->clamp( $min_choices, $min_choices, $num_choices );
			$max_choices = $this->clamp( $max_choices, $min_choices, $num_choices );

			$part_data['limit_choices_min'] = $min_choices;
			$part_data['limit_choices_max'] = $max_choices;
		}

		return $part_data;
	}

	public function get_csv_value( $value, $message, $part, $form ) {
		if ( $this->type === $part['type'] ) {
			$value = str_replace( '<br>', "\n", $value );
			$value = strip_tags( $value );
		}

		return $value;
	}

	public function add_limited_option_support( $parts ) {
		$parts[] = 'table';

		return $parts;
	}

}
