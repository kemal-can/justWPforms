<?php

class justwpforms_Part_Poll extends justwpforms_Form_Part {

	public $type = 'poll';

	public function __construct() {
		$this->label = __( 'Poll', 'justwpforms' );
		$this->description = __( 'For collecting opinions and showing published results in a bar chart.', 'justwpforms' );

		add_filter( 'justwpforms_stringify_part_value', array( $this, 'stringify_value' ), 10, 3 );
		add_filter( 'justwpforms_part_class', array( $this, 'html_part_class' ), 10, 3 );
		add_filter( 'justwpforms_frontend_dependencies', array( $this, 'script_dependencies' ), 10, 2 );
		add_filter( 'justwpforms_style_dependencies', array( $this, 'style_dependencies' ), 10, 2 );
		add_filter( 'justwpforms_part_input_after', array( $this, 'append_other_input' ), 10, 2 );
		add_filter( 'justwpforms_validate_part', array( $this, 'validate_part' ) );

		add_action( 'justwpforms_response_created', array( $this, 'handle_poll_actions' ), 10, 3 );

		$this->part_library = justwpforms_get_part_library();
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
			'css_class' => array(
				'default' => '',
				'sanitize' => 'sanitize_text_field'
			),
			'required' => array(
				'default' => 1,
				'sanitize' => 'justwpforms_sanitize_checkbox',
			),
			'options' => array(
				'default' => array(),
				'sanitize' => 'justwpforms_sanitize_array'
			),
			'other_option' => array(
				'default' => 0,
				'sanitize' => 'justwpforms_sanitize_checkbox'
			),
			'other_option_label' => array(
				'default' => __( 'Other', 'justwpforms' ),
				'sanitize' => 'sanitize_text_field'
			),
			'other_option_placeholder' => array(
				'default' => '',
				'sanitize' => 'sanitize_text_field'
			),
			'allow_multiple' => array(
				'default' => 0,
				'sanitize' => 'justwpforms_sanitize_checkbox'
			),
			'show_results_before_voting' => array(
				'default' => 1,
				'sanitize' => 'justwpforms_sanitize_checkbox'
			),
			'limit_choices' => array(
				'default' => 0,
				'sanitize' => 'justwpforms_sanitize_checkbox'
			),
			'limit_choices_min' => array(
				'default' => 1,
				'sanitize' => 'intval'
			),
			'limit_choices_max' => array(
				'default' => 1,
				'sanitize' => 'intval'
			)
		);

		return justwpforms_get_part_customize_fields( $fields, $this->type );
	}

	/**
	 * Get part option (sub-part) defaults.
	 *
	 * @since 1.0.0.
	 *
	 * @return array
	 */
	private function get_option_defaults() {
		return array(
			'label' => '',
			'description' => ''
		);
	}

	/**
	 * Get template for part item in customize pane.
	 *
	 * @since 1.0.0.
	 *
	 * @return string
	 */
	public function customize_templates() {
		$template_path = justwpforms_get_include_folder() . '/templates/parts/customize-poll.php';
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

		foreach( $part['options'] as $o => $option ) {
			$part['options'][$o] = wp_parse_args( $option, $this->get_option_defaults() );
		}

		include( justwpforms_get_include_folder() . '/templates/parts/frontend-poll.php' );
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
			'part-poll',
			justwpforms_get_plugin_url() . 'inc/assets/js/parts/part-poll.js',
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

		$radio_object = $this->part_library->get_part( 'radio' );
		$checkbox_object = $this->part_library->get_part( 'checkbox' );

		if ( isset( $request[$part_name] ) ) {
			$requested_data = $request[$part_name];

			if ( 1 == intval( $part_data['allow_multiple'] ) ) {
				$sanitized_value = $checkbox_object->sanitize_value( $part_data, $form_data, $request );
			} else {
				$sanitized_value = $radio_object->sanitize_value( $part_data, $form_data, $request );
			}
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

		$radio_object = $this->part_library->get_part( 'radio' );
		$checkbox_object = $this->part_library->get_part( 'checkbox' );

		if ( 1 == intval( $part['allow_multiple'] ) ) {
			$validated_value = $checkbox_object->validate_value( $value, $part, $form );
		} else {
			$validated_value = $radio_object->validate_value( $value, $part, $form );
		}

		return $validated_value;
	}

	public function stringify_value( $value, $part, $form ) {
		if ( $this->type === $part['type'] ) {
			$radio_object = $this->part_library->get_part( 'radio' );
			$checkbox_object = $this->part_library->get_part( 'checkbox' );

			if ( 1 == intval( $part['allow_multiple'] ) ) {
				$value = $checkbox_object->stringify_value( $value, $part, $form, true );
			} else {
				$value = $radio_object->stringify_value( $value, $part, $form, true );
			}
		}

		return $value;
	}

	public function script_dependencies( $deps, $forms ) {
		$contains_poll = false;
		$form_controller = justwpforms_get_form_controller();

		foreach ( $forms as $form ) {
			if ( $form_controller->get_first_part_by_type( $form, $this->type ) ) {
				$contains_poll = true;
				break;
			}
		}

		if ( ! justwpforms_is_preview() && ! $contains_poll ) {
			return $deps;
		}

		wp_register_script(
			'justwpforms-poll',
			justwpforms_get_plugin_url() . 'inc/assets/js/frontend/poll.js',
			array(), justwpforms_get_version(), true
		);

		$deps[] = 'justwpforms-poll';

		return $deps;
	}

	public function style_dependencies( $deps, $forms ) {
		$contains_poll = false;
		$form_controller = justwpforms_get_form_controller();

		foreach ( $forms as $form ) {
			if ( $form_controller->get_first_part_by_type( $form, $this->type ) ) {
				$contains_poll = true;
				break;
			}
		}

		if ( ! justwpforms_is_preview() && ! $contains_poll ) {
			return $deps;
		}

		wp_register_style(
			'justwpforms-poll',
			justwpforms_get_plugin_url() . 'inc/assets/css/frontend/poll.css',
			array(), justwpforms_get_version()
		);

		$deps[] = 'justwpforms-poll';

		return $deps;
	}

	public function html_part_class( $class, $part, $form ) {
		if ( $this->type === $part['type'] ) {
			$class[] = 'justwpforms-part--choice';
			$class[] = 'display-type--block';

			$session = justwpforms_get_session();

			if ( in_array( 'results', $session->get_states( justwpforms_get_part_name( $part, $form ) ) ) ) {
				$class[] = 'show-results';
			}

			if ( 1 == $part['allow_multiple'] ) {
				$class[] = 'justwpforms-poll--allow-multiple';
			}
		}

		return $class;
	}

	public function append_other_input( $part, $form ) {
		if ( $this->type !== $part['type'] ) {
			return;
		}

		if ( 1 == $part['other_option'] ) {
			if ( 1 == $part['allow_multiple'] ) {
				require( justwpforms_get_core_folder() . '/templates/parts/frontend-checkbox-other-option.php' );
			} else {
				require( justwpforms_get_core_folder() . '/templates/parts/frontend-radio-other-option.php' );
			}
		}
	}

	public function handle_poll_actions( $message_id, $form ) {
		$message_controller = justwpforms_get_message_controller();
		$message = $message_controller->get( $message_id );
		$submission = $message['parts'];

		$form_controller = justwpforms_get_form_controller();
		$session = justwpforms_get_session();
		$polls = $form_controller->get_parts_by_type( $form, 'poll' );

		if ( ! empty( $polls ) ) {
			foreach ( $polls as $poll ) {
				// add results state
				$session->add_state( justwpforms_get_part_name( $poll, $form ), 'results' );

				// save to database
				justwpforms_get_polls_controller()->save_poll_entry( $poll, $form, $submission );
			}
		}
	}

	private function clamp( $v, $min, $max ) {
		return min( max( $v, $min ), $max );
	}

	public function validate_part( $part_data ) {
		if ( $this->type !== $part_data['type'] ) {
			return $part_data;
		}

		$min_choices = intval( $part_data['limit_choices_min'] );
		$max_choices = intval( $part_data['limit_choices_max'] );
		$num_choices = count( $part_data['options'] );

		// reset limit choices option when poll has radios instead of checkboxes
		if ( 0 == $part_data['allow_multiple'] && 1 == $part_data['limit_choices'] ) {
			$part_data['limit_choices'] = 0;
			$part_data['limit_choices_min'] = '';
			$part_data['limit_choices_max'] = '';
		}

		if ( 1 == $part_data['allow_multiple'] ) {
			$min_choices = $this->clamp( $min_choices, $num_choices > 1 ? 2 : 1, $min_choices );
			$min_choices = $this->clamp( $min_choices, $min_choices, $num_choices );
			$max_choices = $this->clamp( $max_choices, $min_choices, $num_choices );

			$part_data['limit_choices_min'] = $min_choices;
			$part_data['limit_choices_max'] = $max_choices;
		}

		return $part_data;
	}

}
