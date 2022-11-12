<?php

class justwpforms_Part_Toggletip extends justwpforms_Form_Part {

	public $type = 'toggletip';

	public function __construct() {
		$this->label = __( 'Toggletip', 'justwpforms' );
		$this->description = __( 'For letting submitters reveal more information as they need it.', 'justwpforms' );

		add_filter( 'justwpforms_message_part_visible', array( $this, 'message_part_visible' ), 10, 2 );
		add_filter( 'justwpforms_csv_part_visible', array( $this, 'csv_part_visible' ), 10, 2 );
		add_filter( 'justwpforms_email_part_value', array( $this, 'email_part_value' ), 10, 5 );
		add_filter( 'justwpforms_email_part_label', array( $this, 'email_part_label'), 10, 3 );
	}

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
			'details' => array(
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
				'default' => 0,
				'sanitize' => 'justwpforms_sanitize_checkbox'
			)
		);

		return justwpforms_get_part_customize_fields( $fields, $this->type );
	}

	public function sanitize_value( $part_data = array(), $form_data = array(), $request = array() ) {
		$sanitized_value = $this->get_default_value( $part_data );
		$part_name = justwpforms_get_part_name( $part_data, $form_data );

		if ( isset( $request[$part_name] ) ) {
			$sanitized_value = sanitize_text_field( $request[$part_name] );
		}

		return $sanitized_value;
	}

	public function validate_value( $value, $part = array(), $form = array() ) {
		$validated_value = esc_attr( $value );

		return $validated_value;
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

	public function email_part_label( $label, $message, $part ) {
		if ( $this->type !== $part['type'] ) {
			return $label;
		}

		if ( '' === $part['label'] ) {
			$label = '';
		}

		return $label;
	}

	public function email_part_value( $value, $message, $part, $form, $context ) {
		if ( $this->type !== $part['type'] ) {
			return $value;
		}

		$value = wp_specialchars_decode( $part['details'] );

		return $value;
	}

	public function customize_templates() {
		$template_path = justwpforms_get_include_folder() . '/templates/parts/customize-toggletip.php';
		$template_path = justwpforms_get_part_customize_template_path( $template_path, $this->type );

		require_once( $template_path );
	}

	public function customize_enqueue_scripts( $deps = array() ) {
		wp_enqueue_script(
			'part-toggletip',
			justwpforms_get_plugin_url() . 'inc/assets/js/parts/part-toggletip.js',
			$deps, justwpforms_get_version(), true
		);
	}

	public function frontend_template( $part_data = array(), $form_data = array() ) {
		$part = wp_parse_args( $part_data, $this->get_customize_defaults() );
		$form = $form_data;

		include( justwpforms_get_include_folder() . '/templates/parts/frontend-toggletip.php' );
	}
}
