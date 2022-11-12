<?php

class justwpforms_Part_Legal extends justwpforms_Form_Part {

	public $type = 'legal';
	public $template_id = 'justwpforms-legal-template';

	public function __construct() {
		$this->label = __( 'Consent Choice', 'justwpforms' );
		$this->description = __( 'For requiring permission before accepting submission.', 'justwpforms' );

		add_filter( 'justwpforms_part_class', array( $this, 'html_part_class' ), 10, 3 );
		add_filter( 'justwpforms_part_value', array( $this, 'get_part_value' ), 10, 4 );
		add_filter( 'justwpforms_message_part_value', array( $this, 'message_part_value' ), 10, 4 );
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
			'legal_text' => array(
				'default' => __( 'I agree to the terms and conditions', 'justwpforms' ),
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
		$template_path = justwpforms_get_include_folder() . '/templates/parts/customize-legal.php';
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

		include( justwpforms_get_include_folder() . '/templates/parts/frontend-legal.php' );
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

		if ( empty( $validated_value ) || 'yes' !== $validated_value ) {
			$validated_value = new WP_Error( 'error', justwpforms_get_validation_message( 'no_selection' ) );
			return $validated_value;
		}

		return $validated_value;
	}

	public function get_part_value( $value, $part, $form ) {
		if ( $this->type === $part['type']
			&& ( 'review' !== justwpforms_get_current_step( $form ) ) ) {

			$value = '';
		}

		return $value;
	}

	public function message_part_value( $value, $original_value, $part, $destination ) {
		if ( isset( $part['type'] ) && $this->type === $part['type'] ) {
			$value = 'yes' === $value ? htmlspecialchars_decode( $part['legal_text'] ) : '';
		}

		return $value;
	}

	public function html_part_class( $class, $part, $form ) {
		if ( $this->type === $part['type'] ) {
			if ( justwpforms_get_previous_part( $part, $form ) ) {
				$previous_part = justwpforms_get_previous_part( $part, $form );
				if ( isset( $previous_part['label_placement'] ) && isset( $previous_part['width'] ) ) {
					if ( 'left' === $previous_part['label_placement'] && 'full' === $previous_part['width'] ) {
						$class[] = 'justwpforms-part-legal--align-right';
					}
				}
			}

			if ( justwpforms_get_next_part( $part, $form ) ) {
				$next_part = justwpforms_get_next_part( $part, $form );

				if ( isset( $next_part['label_placement'] ) && isset( $next_part['width'] ) ) {
					if ( 'left' === $next_part['label_placement'] && 'full' === $next_part['width'] ) {
						$class[] = 'justwpforms-part-legal--align-right';
					}
				}
			}
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
			'part-legal',
			justwpforms_get_plugin_url() . 'inc/assets/js/parts/part-legal.js',
			$deps, justwpforms_get_version(), true
		);
	}

}
