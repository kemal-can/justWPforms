<?php

class justwpforms_Part_PageBreak extends justwpforms_Form_Part {

	public $type = 'page_break';

	public function __construct() {
		$this->label = __( 'Page Break', 'justwpforms' );
		$this->description = __( 'For splitting your form across multiple pages with navigation controls.', 'justwpforms' );

		add_filter( 'justwpforms_message_part_visible', array( $this, 'message_part_visible' ), 10, 2 );
		add_filter( 'justwpforms_csv_part_visible', array( $this, 'csv_part_visible' ), 10, 2 );
		add_filter( 'justwpforms_part_class', array( $this, 'html_part_class' ), 10, 3 );
		add_filter( 'justwpforms_get_form_data', array( $this, 'get_form_data' ) );
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
			'continue_button_label' => array(
				'default' => __( 'Continue', 'justwpforms' ),
				'sanitize' => 'sanitize_text_field',
			),
			'label_placement' => array(
				'default' => 'show',
				'sanitize' => 'sanitize_text_field'
			),
			'css_class' => array(
				'default' => '',
				'sanitize' => 'sanitize_text_field'
			),
			'required' => array(
				'default' => 0,
				'sanitize' => 'justwpforms_sanitize_checkbox'
			),
			'is_first' => array(
				'default' => 0,
				'sanitize' => 'justwpforms_sanitize_checkbox'
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
		$template_path = justwpforms_get_include_folder() . '/templates/parts/customize-page-break.php';
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

		include( justwpforms_get_include_folder() . '/templates/parts/frontend-page-break.php' );
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
	 * @return string
	 */
	public function sanitize_value( $part_data = array(), $form_data = array(), $request = array() ) {
		$sanitized_value = $this->get_default_value( $part_data );
		$part_name = justwpforms_get_part_name( $part_data, $form_data );

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

		return $validated_value;
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
			'part-page-break',
			justwpforms_get_plugin_url() . 'inc/assets/js/parts/part-page-break.js',
			$deps, justwpforms_get_version(), true
		);
	}

	public function message_part_visible( $visible, $part ) {
		if ( $this->type === $part['type'] ) {
			$visible = false;
		}

		return $visible;
	}

	public function csv_part_visible( $visible, $part ) {
		if ( $this->type === $part['type'] ) {
			$visible = false;
		}

		return $visible;
	}

	public function html_part_class( $class, $part, $form ) {
		if ( $this->type === $part['type'] ) {
			if ( justwpforms_is_preview() ) {
				$class[] = 'page-break-preview';
			}

			unset( $class[ $part['css_class'] ] );
		}

		return $class;
	}

	public function get_form_data( $form ) {
		if ( ! justwpforms_meta_exists( $form['ID'], 'next_button_label' ) ) {
			return $form;
		}

		$global_value = justwpforms_get_meta( $form['ID'], 'next_button_label', true );

		foreach( $form['parts'] as $p => $part ) {
			if ( $this->type !== $part['type'] ) {
				continue;
			}

			$form['parts'][$p]['continue_button_label'] = $global_value;
		}

		return $form;
	}

}
