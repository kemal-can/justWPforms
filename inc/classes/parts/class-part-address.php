<?php

class justwpforms_Part_Address extends justwpforms_Form_Part {

	public $type = 'address';

	public function __construct() {
		$this->label = __( 'Address', 'justwpforms' );
		$this->description = __( 'For geographical locations. Includes Google Maps intergration.', 'justwpforms' );

		add_filter( 'justwpforms_part_class', array( $this, 'html_part_class' ), 10, 3 );
		add_filter( 'justwpforms_part_data_attributes', array( $this, 'html_part_data_attributes' ), 10, 3 );
		add_filter( 'justwpforms_stringify_part_value', array( $this, 'stringify_value' ), 10, 3 );
		add_filter( 'justwpforms_frontend_dependencies', array( $this, 'script_dependencies' ), 10, 2 );
		add_filter( 'justwpforms_frontend_settings', array( $this, 'frontend_settings' ) );
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
			'mode' => array(
				'default' => 'simple',
				'sanitize' => array(
					'justwpforms_sanitize_choice',
					array( 'simple', 'autocomplete', 'country', 'country-city' ),
				),
			),
			'has_geolocation' => array(
				'default' => 0,
				'sanitize' => 'justwpforms_sanitize_checkbox',
			),
			'has_autocomplete' => array(
				'default' => 0,
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
		$template_path = justwpforms_get_include_folder() . '/templates/parts/customize-address.php';
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

		include( justwpforms_get_include_folder() . '/templates/parts/frontend-address.php' );
	}

	public function get_default_value( $part_data = array() ) {
		return array(
			'full' => '',
			'country' => '',
			'city' => '',
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

			switch ( $part_data['mode'] ) {
				case 'simple':
				case 'autocomplete':
					$sanitized_value['full'] = sanitize_text_field( $sanitized_value['full'] );
					break;
				case 'country':
					$sanitized_value['country'] = sanitize_text_field( $sanitized_value['country'] );
					break;
				case 'country-city':
					$sanitized_value['country'] = sanitize_text_field( $sanitized_value['country'] );
					$sanitized_value['city'] = sanitize_text_field( $sanitized_value['city'] );
					break;
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
	 * @param array $part Form part data.
	 * @param string $value Submitted value.
	 *
	 * @return string|object
	 */
	public function validate_value( $value, $part = array(), $form = array() ) {
		$validated_value = $value;

		if ( 1 === $part['required'] ) {
			$is_empty = false;

			switch ( $part['mode'] ) {
				case 'simple':
				case 'autocomplete':
					$is_empty = empty( $validated_value['full'] );
					break;
				case 'country':
					$is_empty = empty( $validated_value['country'] );
					break;
				case 'country-city':
					$is_empty = empty( $validated_value['country'] ) || empty( $validated_value['city'] );
					break;
			}

			if ( $is_empty ) {
				return new WP_Error( 'error', justwpforms_get_validation_message( 'field_empty' ) );
			}
		} else {
			if ( 'country-city' === $part['mode'] ) {
				$country = $validated_value['country'];
				$city = $validated_value['city'];

				if ( ( $country && '' === $city ) || ( $city && '' === $country ) ) {
					return new WP_Error( 'error', justwpforms_get_validation_message( 'field_empty' ) );
				}
			}
		}

		return $validated_value;
	}

	public function html_part_class( $class, $part, $form ) {
		if ( $this->type === $part['type'] ) {
			if ( justwpforms_get_part_value( $part, $form, 'full' )
				|| justwpforms_get_part_value( $part, $form, 'country' )
				|| justwpforms_get_part_value( $part, $form, 'city' ) ) {
				$class[] = 'justwpforms-part--filled';
			}

			if ( isset( $part['mode'] ) && 'country-city' === $part['mode'] ) {
				$class[] = 'justwpforms-part--address-country-city';
			}

			if ( isset( $part['mode'] ) && 'country' === $part['mode'] || 'country-city' === $part['mode'] ) {
				$class[] = 'justwpforms-part--with-autocomplete';
			}

			if ( isset( $part['has_geolocation'] ) && $part['has_geolocation'] ) {
				$class[] = 'justwpforms-part--address-has-geolocation';
			}

			if ( 'focus-reveal' === $part['description_mode'] ) {
				$class[] = 'justwpforms-part--focus-reveal-description';
			}
		}

		return $class;
	}

	public function stringify_value( $value, $part, $form ) {
		if ( $this->type === $part['type'] ) {
			extract( $value );

			switch ( $part['mode'] ) {
				case 'simple':
				case 'autocomplete':
					$value = $full;
					break;
				case 'country':
					$value = $city;
					break;
				case 'country-city':
					$value = "{$city}, {$country}";
					break;
			}
		}

		return $value;
	}

	public function html_part_data_attributes( $attributes, $part, $form ) {
		if ( $this->type !== $part['type'] ) {
			return $attributes;
		}

		if ( isset( $part['mode'] ) ) {
			$attributes['mode'] = $part['mode'];
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
			'part-address',
			justwpforms_get_plugin_url() . 'inc/assets/js/parts/part-address.js',
			$deps, justwpforms_get_version(), true
		);
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
		$contains_address = false;
		$form_controller = justwpforms_get_form_controller();

		foreach ( $forms as $form ) {
			if ( $form_controller->get_first_part_by_type( $form, $this->type ) ) {
				$contains_address = true;
				break;
			}
		}

		if ( ! justwpforms_is_preview() && ! $contains_address ) {
			return $deps;
		}

		wp_register_script(
			'justwpforms-select',
			justwpforms_get_plugin_url() . 'inc/assets/js/lib/justwpforms-select.js',
			array( 'jquery' ), justwpforms_get_version(), true
		);

		wp_register_script(
			'justwpforms-part-address',
			justwpforms_get_plugin_url() . 'inc/assets/js/frontend/address.js',
			array( 'justwpforms-select' ), justwpforms_get_version(), true
		);

		$deps[] = 'justwpforms-part-address';

		return $deps;
	}

	public function frontend_settings( $settings ) {
		$countries = justwpforms_get_countries();
		$settings['address'] = array(
			'countries' => $countries,
		);

		return $settings;
	}

}
