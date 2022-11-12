<?php
class justwpforms_Part_Scrollable_Terms extends justwpforms_Form_Part {

	public $type = 'scrollable_terms';

	public function __construct() {
		$this->label = __( 'Scrollable Terms', 'justwpforms' );
		$this->description = __( 'For putting text, notes and formatted messages in a scrollable box.', 'justwpforms' );

		add_filter( 'justwpforms_message_part_value', array( $this, 'message_part_value' ), 10, 4 );
		add_filter( 'justwpforms_frontend_dependencies', array( $this, 'script_dependencies' ), 10, 2 );

		add_filter( 'justwpforms_messages_fields', array( $this, 'meta_messages_fields' ) );
		add_filter( 'justwpforms_messages_controls', array( $this, 'get_message_controls' ) );
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
			'terms_text' => array(
				'default' => '',
				'sanitize' => 'esc_html'
			),
			'required' => array(
				'default' => 1,
				'sanitize' => 'justwpforms_sanitize_checkbox'
			)
		);

		return justwpforms_get_part_customize_fields( $fields, $this->type );
	}

	public function customize_templates() {
		$template_path = justwpforms_get_include_folder() . '/templates/parts/customize-scrollable-terms.php';
		$template_path = justwpforms_get_part_customize_template_path( $template_path, $this->type );

		require_once( $template_path );
	}

	public function frontend_template( $part_data = array(), $form_data = array() ) {
		$part = wp_parse_args( $part_data, $this->get_customize_defaults() );
		$form = $form_data;

		include( justwpforms_get_include_folder() . '/templates/parts/frontend-scrollable-terms.php' );
	}

	public function get_message_definitions() {
		return array();
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
		if ( ( 1 === $part['required'] ) && ( '' === $value ) ) {
			return new WP_Error( 'error', justwpforms_get_validation_message( 'terms_not_scrolled' ) );
		}

		return $value;
	}

	public function get_part_value( $value, $part, $form ) {
		if ( $this->type === $part['type'] ) {
			$value = $part['default_value'];
		}

		return $value;
	}

	public function message_part_value( $value, $original_value, $part, $destination ) {
		if ( isset( $part['type'] ) && $this->type === $part['type'] ) {
			$value = '';

			if ( 1 == $original_value ) {
				$value = html_entity_decode( $part['terms_text'] );

				if ( 'admin-column' == $destination ) {
					$value = wp_strip_all_tags( $value );
					$value = wp_trim_words( $value, 50, '…' );
				}
			}
		}

		return $value;
	}

	public function get_messages_fields() {
		$fields = array(
			'terms_not_scrolled' => array(
				'default' => __( 'Please scroll to the bottom.', 'justwpforms' ),
				'sanitize' => 'sanitize_text_field'
			)
		);

		return $fields;
	}

	public function meta_messages_fields( $fields ) {
		$fields = array_merge( $fields, $this->get_messages_fields() );

		return $fields;
	}

	public function get_message_controls( $controls ) {

		$message_controls = array(
			4105 => array(
				'type' => 'text',
				'label' => __( "Required terms haven't been scrolled", 'justwpforms' ),
				'field' => 'terms_not_scrolled'
			)
		);

		$controls = justwpforms_safe_array_merge( $controls, $message_controls );

		return $controls;
	}



	public function customize_enqueue_scripts( $deps = array() ) {
		wp_enqueue_script(
			'part-scrollabe-terms',
			justwpforms_get_plugin_url() . 'inc/assets/js/parts/part-scrollable-terms.js',
			$deps, justwpforms_get_version(), true
		);
	}

		public function script_dependencies( $deps, $forms ) {
		$contains_terms = false;
		$form_controller = justwpforms_get_form_controller();

		foreach ( $forms as $form ) {
			if ( $form_controller->get_first_part_by_type( $form, $this->type ) ) {
				$contains_terms = true;
				break;
			}
		}

		if ( ! justwpforms_is_preview() && ! $contains_terms ) {
			return $deps;
		}

		wp_register_script(
			'justwpforms-part-scrollable-terms',
			justwpforms_get_plugin_url() . 'inc/assets/js/frontend/scrollable-terms.js',
			array(), justwpforms_get_version(), true
		);

		$deps[] = 'justwpforms-part-scrollable-terms';

		return $deps;
	}
}
