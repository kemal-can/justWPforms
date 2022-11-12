<?php

class justwpforms_Part_Email extends justwpforms_Form_Part {

	public $type = 'email';

	public function __construct() {
		$this->label = __( 'Email', 'justwpforms' );
		$this->description = __( 'For formatted email addresses. The \'@\' symbol is required.', 'justwpforms' );

		add_filter( 'justwpforms_part_value', array( $this, 'get_part_value' ), 10, 3 );
		add_filter( 'justwpforms_part_class', array( $this, 'html_part_class' ), 10, 3 );
		add_filter( 'justwpforms_message_part_value', array( $this, 'message_part_value' ), 10, 4 );
		add_filter( 'justwpforms_frontend_dependencies', array( $this, 'script_dependencies' ), 10, 2 );
		add_filter( 'justwpforms_stringify_part_value', array( $this, 'stringify_value' ), 10, 3 );
		add_filter( 'justwpforms_validate_part', array( $this, 'validate_part' ) );
		add_filter( 'justwpforms_email_part_visible', array( $this, 'email_part_visible' ), 10, 4 );
		add_filter( 'justwpforms_the_part_value', array( $this, 'handle_confirmation_value' ), 10, 4 );
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
			'prefix' => array(
				'default' => '',
				'sanitize' => 'sanitize_text_field',
			),
			'suffix' => array(
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
			'default_value' => array(
				'default' => '',
				'sanitize' => 'sanitize_text_field'
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
		$template_path = justwpforms_get_core_folder() . '/templates/parts/customize-email.php';
		$template_path = justwpforms_get_part_customize_template_path( $template_path, $this->type );

		require_once( $template_path );
	}

	public function validate_part( $part_data ) {
		if ( $this->type !== $part_data['type'] ) {
			return $part_data;
		}

		return $part_data;
	}

	public function email_part_visible( $visible, $part, $form, $response ) {
		if ( $this->type === $part['type'] ) {
			if ( ( '' === $part['prefix'] ) && ( '' === $part['suffix'] ) ) {
				return $visible;
			}

			$empty_value = $part['prefix'] . $part['suffix'];
			$value = justwpforms_get_message_part_value( $response['parts'][$part['id']], $part );

			if ( $empty_value === $value ) {
				$visible = false;
			}
		}

		return $visible;
	}

	public function handle_confirmation_value( $value, $part, $form, $component ) {
		if ( $this->type === $part['type'] ) {
			if ( false === $component && is_array( $value ) ) {
				$value = $value[0];
			}
		}

		return $value;
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

		include( justwpforms_get_core_folder() . '/templates/parts/frontend-email.php' );
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
		$part_name = justwpforms_get_part_name( $part, $form );

		if ( empty( $value ) ) {
			if ( 1 == $part['required'] ) {
				$error = new WP_Error( 'error', justwpforms_get_validation_message( 'field_empty' ) );

				return $error;
			} else {
				return $value;
			}
		}

		$validation_value = $value;

		if ( ( '' !== $part['prefix'] ) ) {
			$validation_value = "{$part['prefix']}{$validation_value}";
		}

		if ( ( '' !== $part['suffix'] ) ) {
			$validation_value = "{$validation_value}{$part['suffix']}";
		}

		if ( ! justwpforms_is_email( $validation_value ) ) {
			$error = new WP_error( 'error', justwpforms_get_validation_message( 'field_invalid' ) );

			return $error;
		}


		return $value;
	}

	public function get_part_value( $value, $part, $form ){
		if ( $this->type === $part['type'] ) {
			$value = $part['default_value'];
		}
		return $value;
	}

	public function stringify_value( $value, $part, $form ) {
		if ( $this->type === $part['type'] ) {
			$value = justwpforms_get_email_encoder()->decode_email( $value );

			if ( ! empty( $part['prefix'] ) ) {
				$value = "{$part['prefix']}{$value}";
			}
			if ( ! empty( $part['suffix'] ) ) {
				$value = "{$value}{$part['suffix']}";
			}
		}

		return $value;
	}

	public function html_part_class( $class, $part, $form ) {
		if ( $this->type === $part['type'] ) {
			if ( justwpforms_get_part_value( $part, $form, 0 )
				|| justwpforms_get_part_value( $part, $form, 1 ) ) {
				$class[] = 'justwpforms-part--filled';
			}

			if ( 'focus-reveal' === $part['description_mode'] ) {
				$class[] = 'justwpforms-part--focus-reveal-description';
			}

			$class[] = 'justwpforms-part--with-autocomplete';
		}

		return $class;
	}

	public function message_part_value( $value, $original_value, $part, $destination ) {
		if ( isset( $part['type'] )
			&& $this->type === $part['type'] ) {

			switch( $destination ) {
				case 'email':
				case 'admin-column':
					$value = "<a href=\"mailto:{$value}\">{$value}</a>";
					break;
				default:
					break;
			}

		}

		return $value;
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
			'part-email',
			justwpforms_get_plugin_url() . 'core/assets/js/parts/part-email.js',
			$deps, justwpforms_get_version(), true
		);
	}

	public function script_dependencies( $deps, $forms ) {
		$contains_email = false;
		$form_controller = justwpforms_get_form_controller();

		foreach ( $forms as $form ) {
			if ( $form_controller->get_first_part_by_type( $form, $this->type ) ) {
				$contains_email = true;
				break;
			}
		}

		if ( ! justwpforms_is_preview() && ! $contains_email ) {
			return $deps;
		}

		wp_register_script(
			'justwpforms-email',
			justwpforms_get_plugin_url() . 'core/assets/js/frontend/email.js',
			array(), justwpforms_get_version(), true
		);

		$deps[] = 'justwpforms-email';

		return $deps;
	}
}
