<?php

class justwpforms_Part_Likert_Scale extends justwpforms_Form_Part {

	public $type = 'likert_scale';

	public function __construct() {
		$this->label = __( 'Scale', 'justwpforms' );
		$this->description = __( 'For collecting ratings using a fixed numeric scale.', 'justwpforms' );

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
				'default' => 1,
				'sanitize' => 'intval'
			),
			'max_value' => array(
				'default' => 10,
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
		$template_path = justwpforms_get_include_folder() . '/templates/parts/customize-likert-scale.php';
		$template_path = justwpforms_get_part_customize_template_path( $template_path, $this->type );

		require_once( $template_path );
	}

	public function validate_part( $part_data ) {
		if ( $this->type !== $part_data['type'] ) {
			return $part_data;
		}

		$min_value = $part_data['min_value'];
		$max_value = $part_data['max_value'];

		$min_value = intval( $min_value );
		$max_value = intval( $max_value );

		$min_value = min( $min_value, $max_value );
		$max_value = max( $min_value, $max_value );

		$part_data['min_value'] = $min_value;
		$part_data['max_value'] = $max_value;

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

		include( justwpforms_get_include_folder() . '/templates/parts/frontend-likert-scale.php' );
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

		if ( 1 === $part['required'] && ! is_numeric( $validated_value ) ) {
			return new WP_Error( 'error', justwpforms_get_validation_message( 'no_selection' ) );
		}

		if ( ! is_numeric( $validated_value ) ) {
			$validated_value = new WP_Error( 'error', justwpforms_get_validation_message( 'field_invalid' ) );
		}

		$int_value = intval( $validated_value );

		if ( $int_value < $part['min_value'] || $int_value > $part['max_value'] ) {
			$validated_value = new WP_Error( 'error', justwpforms_get_validation_message( 'field_invalid' ) );
		}

		return $validated_value;
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
			'part-likert-scale',
			justwpforms_get_plugin_url() . 'inc/assets/js/parts/part-likert-scale.js',
			$deps, justwpforms_get_version(), true
		);
	}

}
