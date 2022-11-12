<?php

class justwpforms_Part_Narrative extends justwpforms_Form_Part {

	public $type = 'narrative';

	public function __construct() {
		$this->label = __( 'Blanks', 'justwpforms' );
		$this->description = __( 'For adding fill-in-the-blank style inputs to a paragraph of text.', 'justwpforms' );

		add_filter( 'justwpforms_stringify_part_value', array( $this, 'stringify_value' ), 10, 3 );
		add_filter( 'justwpforms_frontend_dependencies', array( $this, 'script_dependencies' ), 10, 2 );
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
			'format' => array(
				'default' => '',
				'sanitize' => 'esc_html'
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
		$template_path = justwpforms_get_include_folder() . '/templates/parts/customize-narrative.php';
		$template_path = justwpforms_get_part_customize_template_path( $template_path, $this->type );

		require_once( $template_path );
	}

	/**
	 * Get front end part template with parsed data.
	 *
	 * @since 1.0.0.
	 *
	 * @param array $part_data  Form part data.
	 * @param array $form_data  Form (post) data.
	 *
	 * @return string   Markup for the form part.
	 */
	public function frontend_template( $part_data = array(), $form_data = array() ) {
		$part = wp_parse_args( $part_data, $this->get_customize_defaults() );
		$form = $form_data;

		include( justwpforms_get_include_folder() . '/templates/parts/frontend-narrative.php' );
	}

	public function get_default_value( $part_data = array() ) {
		$tokens = justwpforms_get_narrative_tokens( $part_data['format'] );

		return $tokens;
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
			$sanitized_value = array_map( 'sanitize_text_field', $request[$part_name] );
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
		$tokens = justwpforms_get_narrative_tokens( $part['format'] );

		if ( 1 === $part['required'] ) {
			if ( count( $value ) < count( $tokens ) ) {
				return new WP_Error( 'error', justwpforms_get_validation_message( 'field_empty' ) );
			}

			foreach( $value as $component ) {
				if ( empty( $component ) ) {
					return new WP_Error( 'error', justwpforms_get_validation_message( 'field_empty' ) );
				}
			}
		}

		return $value;
	}

	public function stringify_value( $value, $part, $form ) {
		if ( $this->type === $part['type'] ) {
			$tokens = justwpforms_get_narrative_tokens( $part['format'] );
			$format = justwpforms_get_narrative_format( $part['format'] );
			$value = vsprintf( html_entity_decode( stripslashes( $format ) ), $value );
			$value = sanitize_text_field( $value );
		}

		return $value;
	}

	/**
	 * Enqueue scripts in customizer area.
	 *
	 * @since 1.0.0.
	 *
	 * @param array List of dependencies.
	 *
	 * @return void
	 */
	public function customize_enqueue_scripts( $deps = array() ) {
		wp_enqueue_script(
			'part-narrative',
			justwpforms_get_plugin_url() . 'inc/assets/js/parts/part-narrative.js',
			$deps, justwpforms_get_version(), true
		);

		$narrative_settings = array(
			'blankTooltip' => __( 'Insert blank', 'justwpforms' ),
		);

		wp_localize_script( 'part-narrative', '_justwpformsNarrativeSettings', $narrative_settings );
	}

	public function script_dependencies( $deps, $forms ) {
		$contains_narrative = false;
		$form_controller = justwpforms_get_form_controller();

		foreach ( $forms as $form ) {
			if ( $form_controller->get_first_part_by_type( $form, $this->type ) ) {
				$contains_narrative = true;
				break;
			}
		}

		if ( ! justwpforms_is_preview() && ! $contains_narrative ) {
			return $deps;
		}

		wp_register_script(
			'justwpforms-part-narrative',
			justwpforms_get_plugin_url() . 'inc/assets/js/frontend/narrative.js',
			array(), justwpforms_get_version(), true
		);

		$deps[] = 'justwpforms-part-narrative';

		return $deps;
	}

}
