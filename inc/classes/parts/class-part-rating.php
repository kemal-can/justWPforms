<?php

class justwpforms_Part_Rating extends justwpforms_Form_Part {

	public $type = 'rating';
	public $template_id = 'justwpforms-rating-template';

	public function __construct() {
		$this->label = __( 'Rate', 'justwpforms' );
		$this->description = __( 'For collecting opinions using stars.', 'justwpforms' );

		add_filter( 'justwpforms_part_class', array( $this, 'html_part_class' ), 10, 3 );
		add_filter( 'justwpforms_frontend_dependencies', array( $this, 'script_dependencies' ), 10, 2 );
		add_filter( 'justwpforms_stringify_part_value', array( $this, 'stringify_value' ), 10, 3 );
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
			'rating_type' => array(
				'default' => 'scale',
				'sanitize' => 'sanitize_text_field'
			),
			'rating_visuals' => array(
				'default' => 'stars',
				'sanitize' => 'sanitize_text_field'
			),
			'rating_labels_yesno' => array(
				'default' => array(
					__( 'Disappointed', 'justwpforms' ),
					__( 'JustWP', 'justwpforms' )
				),
				'sanitize' => 'justwpforms_sanitize_array'
			),
			'rating_labels_scale' => array(
				'default' => array(
					__( 'Disliked', 'justwpforms' ),
					__( 'Disappointed', 'justwpforms' ),
					__( 'Okay', 'justwpforms' ),
					__( 'JustWP', 'justwpforms' ),
					__( 'Loved it', 'justwpforms' )
				),
				'sanitize' => 'justwpforms_sanitize_array'
			),
			'stars_num' => array(
				'default' => 5,
				'sanitize' => 'intval'
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
		$template_path = justwpforms_get_include_folder() . '/templates/parts/customize-rating.php';
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

		include( justwpforms_get_include_folder() . '/templates/parts/frontend-rating.php' );
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
			$sanitized_value = intval( $request[$part_name] );
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

		if ( 'scale' === $part['rating_type'] ) {
			if ( $validated_value > 0 && $validated_value < 1 || $validated_value > 5 ) {
				$validated_value = new WP_Error( 'error', justwpforms_get_validation_message( 'field_invalid' ) );
				return $validated_value;
			}
		}

		return $validated_value;
	}

	public function stringify_value( $value, $part, $form ) {
		if ( $this->type === $part['type'] ) {
			switch( $part['rating_type'] ) {
				case 'scale':
					$rating_labels = ( 'stars' === $part['rating_visuals'] ) ? array() : $part['rating_labels_scale'];

					if ( ! empty( $rating_labels ) && ! empty( $rating_labels[$value-1] ) ) {
						$value = $rating_labels[$value-1];
					}
					break;
				case 'yesno':
					$rating_labels = $part['rating_labels_yesno'];

					if ( ! empty( $rating_labels ) && ! empty( $rating_labels[$value-1] ) ) {
						$value = $rating_labels[$value-1];
					} else if ( 1 === $value ) {
						$value = __( 'No', 'justwpforms' );
					} else if ( 2 === $value ) {
						$value = __( 'Yes', 'justwpforms' );
					}
					break;
			}

			if ( 0 === $value ) {
				$value = '';
			}
		}

		return $value;
	}

	public function html_part_class( $class, $part, $form ) {
		if ( $this->type === $part['type'] ) {
			$class[] = 'justwpforms-rating--' . $part['rating_type'];
			$class[] = 'justwpforms-rating--' . $part['rating_visuals'];
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
			'part-rating',
			justwpforms_get_plugin_url() . 'inc/assets/js/parts/part-rating.js',
			$deps, justwpforms_get_version(), true
		);
	}

	public function script_dependencies( $deps, $forms ) {
		$contains_rating = false;
		$form_controller = justwpforms_get_form_controller();

		foreach ( $forms as $form ) {
			if ( $form_controller->get_first_part_by_type( $form, $this->type ) ) {
				$contains_rating = true;
				break;
			}
		}

		if ( ! justwpforms_is_preview() && ! $contains_rating ) {
			return $deps;
		}

		wp_register_script(
			'justwpforms-part-rating',
			justwpforms_get_plugin_url() . 'inc/assets/js/frontend/rating.js',
			array(), justwpforms_get_version(), true
		);

		$deps[] = 'justwpforms-part-rating';

		return $deps;
	}

}
