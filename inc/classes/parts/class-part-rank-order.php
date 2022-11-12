<?php

class justwpforms_Part_Rank_Order extends justwpforms_Form_Part {

	public $type = 'rank_order';

	public static $parent;

	private $frontend_styles = false;

	public function __construct() {
		$this->label = __( 'Rank', 'justwpforms' );
		$this->description = __( 'For collecting preferences between choices in numeric order.', 'justwpforms' );

		add_filter( 'justwpforms_frontend_dependencies', array( $this, 'script_dependencies' ), 10, 2 );
		add_filter( 'justwpforms_style_dependencies', array( $this, 'style_dependencies' ), 10, 2 );
		add_filter( 'justwpforms_part_class', array( $this, 'html_part_class' ), 10, 3 );
		add_filter( 'justwpforms_stringify_part_value', array( $this, 'stringify_value' ), 10, 3 );

	}

	/**
	 * Get all part meta fields defaults.
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
				'default' => __( 'Choose', 'justwpforms' ),
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
			)
		);

		return justwpforms_get_part_customize_fields( $fields, $this->type );
	}

	/**
	 * Get part option (sub-part) defaults.
	 *
	 * @return array
	 */
	protected function get_option_defaults() {
		return array(
			'is_default' => 0,
			'label' => '',
			'description' => ''
		);
	}

	/**
	 * Get template for part item in customize pane.
	 *
	 * @return string
	 */
	public function customize_templates() {
		$template_path = justwpforms_get_include_folder() . '/templates/parts/customize-rank-order.php';
		$template_path = justwpforms_get_part_customize_template_path( $template_path, $this->type );

		require_once( $template_path );
	}

	/**
	 * Get front end part template with parsed data.
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

		$template_path = justwpforms_get_include_folder() . '/templates/parts/frontend-rank-order.php';
		$template_path = justwpforms_get_part_frontend_template_path( $template_path, $this->type );

		include( $template_path );
	}

	public function style_dependencies( $deps, $forms ) {
		$contains_rank_order = false;
		$form_controller = justwpforms_get_form_controller();

		foreach ( $forms as $form ) {
			if ( $form_controller->get_first_part_by_type( $form, $this->type ) ) {
				$contains_rank_order = true;
				break;
			}
		}

		if ( ! justwpforms_is_preview() && ! $contains_rank_order ) {
			return $deps;
		}

		wp_register_style(
			'justwpforms-rank-order',
			justwpforms_get_plugin_url() . 'inc/assets/css/frontend/rank-order.css',
			array(), justwpforms_get_version()
		);

		$deps[] = 'justwpforms-rank-order';

		return $deps;
	}

	/**
	 * Enqueue scripts in customizer area.
	 *
	 * @param array	List of dependencies.
	 *
	 * @return void
	 */
	public function customize_enqueue_scripts( $deps = array() ) {
		wp_enqueue_script(
			'part-rank-order',
			justwpforms_get_plugin_url() . 'inc/assets/js/parts/part-rank-order.js',
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
			$sanitized_value = wp_parse_args( $request[$part_name], $sanitized_value );
			$sanitized_value = array_map( 'intval', $sanitized_value );
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
		if ( 1 === $part['required'] ) {
			$has_empty_field = false;

			if( is_array( $value ) ) {
				foreach( $value as $key => $rank_value ) {
					if ( $rank_value == 0 ) {
						$has_empty_field = true;
						break;
					}
				}
			}

			if( $has_empty_field ) {
					$error = new WP_Error( 'error', justwpforms_get_validation_message( 'no_selection' ) );
					return $error;
			}
		}

		return $value;
	}

	public function stringify_value( $value, $part, $form) {
		if ( $this->type === $part['type'] ) {

			if( is_array( $value ) ) {
				$filled_components = array_filter( $value );
				$value_is_empty = ( 0 === count( $filled_components ) );

				if ( $value_is_empty ) {
					return '';
				}

				$rank_order_string_value = '';
				asort( $value );
				$option_labels = wp_list_pluck( $part['options'], 'label', 'id' );

				foreach ( $value as $option_id => $rank_value ) {
					if ( ! ( $rank_value == '' || $rank_value == 0 ) ) {
						$rank_order_string_value .= empty( $rank_order_string_value ) ? '' : ', ';
						$rank_order_string_value .= '(' . $rank_value . ') ' . $option_labels[$option_id];
					}
				}
				$value = $rank_order_string_value;
			}
		}

		return $value;
	}

	public function html_part_class( $class, $part, $form ) {
		if ( $this->type === $part['type'] ) {
			$class[] = 'justwpforms-part--choice';

			if ( isset( $part['display_type'] ) && 'block' === $part['display_type'] ) {
				$class[] = 'display-type--block';
			}
		}

		return $class;
	}

	public function script_dependencies( $deps, $forms ) {
		$contains_rank = false;
		$form_controller = justwpforms_get_form_controller();

		foreach ( $forms as $form ) {
			if ( $form_controller->get_first_part_by_type( $form, $this->type ) ) {
				$contains_rank = true;
				break;
			}
		}


		if ( ! justwpforms_is_preview() && ! $contains_rank ) {
			return $deps;
		}

		wp_register_script(
			'justwpforms-part-rank-order',
			justwpforms_get_plugin_url() . 'inc/assets/js/frontend/rank-order.js',
			array(), justwpforms_get_version(), true
		);

		$deps[] = 'justwpforms-part-rank-order';

		return $deps;
	}

}
