<?php

class justwpforms_Part_MultiLineText extends justwpforms_Form_Part {

	public $type = 'multi_line_text';

	public function __construct() {
		$this->label = __( 'Long Text', 'justwpforms' );
		$this->description = __( 'For paragraph text fields.', 'justwpforms' );

		add_filter( 'justwpforms_part_value', array( $this, 'get_part_value' ), 10, 3 );
		add_filter( 'justwpforms_the_part_value', array( $this, 'output_part_value' ), 10, 3 );
		add_filter( 'justwpforms_email_part_value', array( $this, 'email_part_value' ), 10, 4 );
		add_filter( 'justwpforms_message_part_value', array( $this, 'message_part_value' ), 10, 4 );
		add_filter( 'justwpforms_part_attributes', array( $this, 'html_part_attributes' ), 10, 2 );
		add_action( 'justwpforms_part_input_after', array( $this, 'part_input_after' ), 10, 2 );
		add_filter( 'justwpforms_part_class', array( $this, 'html_part_class' ), 10, 3 );
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
			'placeholder' => array(
				'default' => '',
				'sanitize' => 'sanitize_text_field',
			),
			'limit_input' => array(
				'default' => 0,
				'sanitize' => 'justwpforms_sanitize_checkbox',
			),
			'character_limit' => array(
				'default' => 250,
				'sanitize' => 'intval',
			),
			'character_limit_mode' => array(
				'default' => 'word_max',
				'sanitize' => array(
					'justwpforms_sanitize_choice',
					array( 'character_max', 'character_min', 'word_max', 'word_min' ),
				),
			),
			'characters_label' => array(
				'default' => __( 'Min characters', 'justwpforms' ),
				'sanitize' => 'sanitize_text_field',
			),
			'words_label' => array(
				'default' => __( 'Max words', 'justwpforms' ),
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
		$template_path = justwpforms_get_core_folder() . '/templates/parts/customize-multi-line-text.php';
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
		$template_path = justwpforms_get_core_folder() . '/templates/parts/frontend-multi-line-text.php';

		include( $template_path );
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
			'part-multi-line-text',
			justwpforms_get_plugin_url() . 'core/assets/js/parts/part-multi-line-text.js',
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
			$sanitized_value = sanitize_textarea_field( $request[$part_name] );
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

		if ( 1 === $part['required'] && '' === $validated_value ) {
			$validated_value = new WP_Error( 'error', justwpforms_get_validation_message( 'field_empty' ) );
			return $validated_value;
		} else if ( false == $part['required'] && '' === $validated_value ) {
			return $validated_value;
		}

		$limit_input = intval( $part['limit_input'] );
		$character_limit = intval( $part['character_limit'] );
		$character_limit = $limit_input ? $character_limit : 0;

		if ( $character_limit > 0 ) {
			$character_limit_mode = $part['character_limit_mode'];
			$character_limit_input_value = trim( preg_replace('/\s+/', ' ', str_replace( '&nbsp;', ' ', strip_tags( $validated_value ) )  ) );
			$character_count = strlen( $character_limit_input_value );
			$word_count = str_word_count( $character_limit_input_value );

			if ( 'character_max' === $character_limit_mode && $character_count > $character_limit ) {
				return new WP_Error( 'error', justwpforms_get_validation_message( 'message_too_long' ) );
			} else if ( 'character_min' === $character_limit_mode && $character_count < $character_limit ) {
				return new WP_Error( 'error', justwpforms_get_validation_message( 'message_too_short' ) );
			} else if ( 'word_max' === $character_limit_mode && $word_count > $character_limit ) {
				return new WP_Error( 'error', justwpforms_get_validation_message( 'message_too_long' ) );
			} else if ( 'word_min' === $character_limit_mode && $word_count < $character_limit ) {
				return new WP_Error( 'error', justwpforms_get_validation_message( 'message_too_short' ) );
			}
		}

		return $validated_value;
	}

	public function get_part_value( $value, $part, $form ){
		if ( $this->type === $part['type'] ) {
			$value = $part['default_value'];
		}
		return $value;
	}

	public function output_part_value( $value, $part, $form ) {
		if ( $this->type === $part['type'] ) {
			$value = stripslashes( $value );
		}

		return $value;
	}

	public function html_part_class( $class, $part, $form ) {
		if ( $this->type === $part['type'] ) {
			if ( justwpforms_get_part_value( $part, $form ) ) {
				$class[] = 'justwpforms-part--filled';
			}

			if ( 'focus-reveal' === $part['description_mode'] ) {
				$class[] = 'justwpforms-part--focus-reveal-description';
			}
		}

		return $class;
	}

	private function get_limit_mode( $part ) {
		$mode = 'character';

		if ( 0 === strpos( $part['character_limit_mode'], 'word' ) ) {
			$mode = 'word';
		}

		return $mode;
	}

	private function get_limit_label( $part, $form ) {
		$min_or_max = '';
		$type = '';

		switch( $part['character_limit_mode'] ) {
			case 'character_min':
				$type = $form['characters_label_min'];
				break;
			case 'character_max':
				$type = $form['characters_label_max'];
				break;
			case 'word_min':
				$type = $form['words_label_min'];
				break;
			case 'word_max':
				$type = $form['words_label_max'];
				break;
		}

		$label = sprintf(
			'<span class="counter-label">%s</span>',
			$type,
			$min_or_max
		);

		return $label;
	}

	public function html_part_attributes( $attrs, $part ) {
		if ( $this->type !== $part['type'] ) {
			return $attrs;
		}

		$limit_input = intval( $part['limit_input'] );
		$character_limit = intval( $part['character_limit'] );
		$character_limit = $limit_input ? $character_limit : 0;

		if ( $character_limit || justwpforms_is_preview() ) {
			$mode = $this->get_limit_mode( $part );
			$attrs[] = "data-length=\"{$character_limit}\"";
			$attrs[] = "data-length-mode=\"{$mode}\"";
		}

		return $attrs;
	}

	public function email_part_value( $value, $message, $part, $form ) {
		if ( $this->type === $part['type'] ) {
			$value = str_replace( "\n", '<br />', $value );
		}

		return $value;
	}

	public function message_part_value( $value, $original_value, $part, $destination ) {
		if ( $this->type === $part['type'] ) {
			$value = str_replace( "\n", '<br />', $value );

			if ( 'admin-column' === $destination ) {
				$value = make_clickable( $value );
			}
		}

		return $value;
	}

	public function script_dependencies( $deps, $forms ) {
		$contains_long_text = false;
		$form_controller = justwpforms_get_form_controller();
		
		foreach ( $forms as $form ) {
			$parts = $form_controller->get_parts_by_type( $form, $this->type );

			if ( ! empty( $parts ) ) {
				$contains_long_text = true;
				break;
			}
		}

		if ( ! justwpforms_is_preview() && ! $contains_long_text ) {
			return $deps;
		}

		wp_register_script(
			'justwpforms-part-long-text',
			justwpforms_get_plugin_url() . 'core/assets/js/frontend/long-text.js', array(), justwpforms_get_version(), true
		);

		$deps[] = 'justwpforms-part-long-text';

		return $deps;
	}

	public function part_input_after( $part, $form ) {
		if ( $this->type !== $part['type'] ) {
			return;
		}

		$limit_input = intval( $part['limit_input'] );
		$character_limit = intval( $part['character_limit'] );
		$character_limit = $limit_input ? $character_limit : 0;

		if ( $character_limit ) {
			$label = $this->get_limit_label( $part, $form );
			?>
			<div class="justwpforms-part__char-counter <?php echo $part['character_limit_mode']; ?>">
				<span class="counter">0</span>/<span class="counter-limit"><?php echo $character_limit; ?></span> <?php echo $label; ?>
			</div>
			<?php
		}
	}

}
