<?php

class justwpforms_Part_Title extends justwpforms_Form_Part {

	public $type = 'title';

	public function __construct() {
		$this->label = __( 'Title', 'justwpforms' );
		$this->description = __( 'For displaying personal honorifics.', 'justwpforms' );

		add_filter( 'justwpforms_stringify_part_value', array( $this, 'stringify_value' ), 10, 3 );
		add_filter( 'justwpforms_frontend_dependencies', array( $this, 'script_dependencies' ), 10, 2 );
		add_filter( 'justwpforms_part_class', array( $this, 'html_part_class' ), 10, 3 );
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
			'required' => array(
				'default' => 1,
				'sanitize' => 'justwpforms_sanitize_checkbox',
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
		$template_path = justwpforms_get_include_folder() . '/templates/parts/customize-title.php';
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
		$part['options'] = $this->get_honorifics();
		$form = $form_data;

		include( justwpforms_get_include_folder() . '/templates/parts/frontend-title.php' );
    }

    /**
     * Get honorifics as possible options for the dropdown.
     *
     * @since 1.1.0.
     *
     * @return array List of honorifics.
     */
    public function get_honorifics() {
        $honorifics = array(
            array( 'id' => 'mr', 'label' => __( 'Mr.', 'justwpforms' ) ),
            array( 'id' => 'mrs', 'label' => __( 'Mrs.', 'justwpforms' ) ),
            array( 'id' => 'ms', 'label' => __( 'Ms.', 'justwpforms' ) ),
            array( 'id' => 'miss', 'label' => __( 'Miss', 'justwpforms' ) ),
            array( 'id' => 'prof', 'label' => __( 'Prof.', 'justwpforms' ) ),
            array( 'id' => 'dr', 'label' => __( 'Dr.', 'justwpforms' ) ),
        );

        $honorifics = apply_filters( 'justwpforms_get_honorifics', $honorifics );

        return $honorifics;
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
			'part-title',
			justwpforms_get_plugin_url() . 'inc/assets/js/parts/part-title.js',
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
			$sanitized_value = sanitize_text_field( $request[$part_name] );

			return $sanitized_value;
		}
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

		if ( 1 === $part['required'] && '' === $validated_value ) {
			return new WP_Error( 'error', justwpforms_get_validation_message( 'field_empty' ) );
		}

		if ( '' !== $validated_value ) {
			if ( ! is_numeric( $validated_value ) ) {
				return new WP_Error( 'error', justwpforms_get_validation_message( 'field_invalid' ) );
			}

			$options = range( 0, count( $this->get_honorifics() ) - 1 );

			if ( ! in_array( intval( $validated_value ), $options ) ) {
				return new WP_Error( 'error', justwpforms_get_validation_message( 'field_invalid' ) );
			}
		}

		return $validated_value;
	}

	public function stringify_value( $value, $part, $form ) {
		if ( $this->type === $part['type'] ) {
			if ( '' !== $value ) {
				$options = $this->get_honorifics();
				$value = $options[$value]['label'];
			}
		}

		return $value;
	}

	public function html_part_class( $class, $part, $form ) {
		if ( $this->type === $part['type'] ) {
			$class[] = 'justwpforms-part--select';

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
		$contains_title = false;
		$form_controller = justwpforms_get_form_controller();

		foreach ( $forms as $form ) {
			if ( $form_controller->get_first_part_by_type( $form, $this->type ) ) {
				$contains_title = true;
				break;
			}
		}

		if ( ! justwpforms_is_preview() && ! $contains_title ) {
			return $deps;
		}

		wp_register_script(
			'justwpforms-select',
			justwpforms_get_plugin_url() . 'inc/assets/js/lib/justwpforms-select.js',
			array( 'jquery' ), justwpforms_get_version(), true
		);

		wp_register_script(
			'justwpforms-title',
			justwpforms_get_plugin_url() . 'inc/assets/js/frontend/title.js',
			array( 'justwpforms-select' ), justwpforms_get_version(), true
		);

		$deps[] = 'justwpforms-title';

		return $deps;
	}

}
