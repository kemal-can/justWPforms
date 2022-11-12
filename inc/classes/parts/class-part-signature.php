<?php

class justwpforms_Part_Signature extends justwpforms_Form_Part {

	public $type = 'signature';
	public $template_id = 'justwpforms-signature-template';

	public function __construct() {
		$this->label = __( 'Signature', 'justwpforms' );
		$this->description = __( 'For requiring a signature before accepting submission.', 'justwpforms' );

		add_filter( 'justwpforms_messages_fields', array( $this, 'get_messages_fields' ) );
		add_filter( 'justwpforms_messages_controls', array( $this, 'get_messages_controls' ) );
		add_filter( 'justwpforms_message_part_value', array( $this, 'message_part_value' ), 10, 4 );
		add_filter( 'justwpforms_email_part_value', array( $this, 'email_part_value' ), 10, 5 );
		add_filter( 'justwpforms_part_class', array( $this, 'html_part_class' ), 10, 3 );
		add_filter( 'justwpforms_part_data_attributes', array( $this, 'html_part_data_attributes' ), 10, 2 );
		add_filter( 'justwpforms_get_pdf_part_value', array( $this, 'get_pdf_part_value' ), 10, 3 );
		add_filter( 'justwpforms_style_dependencies', array( $this, 'style_dependencies' ), 10, 2 );
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
				'default' => __( 'Legal name', 'justwpforms' ),
				'sanitize' => 'sanitize_text_field'
			),
			'intent_text' => array(
				'default' => __( '', 'justwpforms' ),
				'sanitize' => 'esc_html'
			),
			'signature_type' => array(
				'default' => 'draw',
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
		);

		return justwpforms_get_part_customize_fields( $fields, $this->type );
	}

	public function get_messages_fields( $fields ) {
		$messages_fields = array(
			'field_signature_start_drawing_button_label' => array(
				'default' => __( 'Start drawing', 'justwpforms' ),
				'sanitize' => 'sanitize_text_field',
			),
			'field_signature_start_over_button_label' => array(
				'default' => __( 'Start over', 'justwpforms' ),
				'sanitize' => 'sanitize_text_field',
			),
			'field_signature_clear_button_label' => array(
				'default' => __( 'Clear', 'justwpforms' ),
				'sanitize' => 'sanitize_text_field',
			),
			'field_signature_done_button_label' => array(
				'default' => __( 'Done', 'justwpforms' ),
				'sanitize' => 'sanitize_text_field',
			),
		);

		$fields = array_merge( $fields, $messages_fields );

		return $fields;
	}

	public function get_messages_controls( $controls ) {
		$message_controls = array(
			2250 => array(
				'type' => 'text',
				'label' => __( 'Start drawing signature', 'justwpforms' ),
				'field' => 'field_signature_start_drawing_button_label',
			),
			2251 => array(
				'type' => 'text',
				'label' => __( 'Start over drawing signature', 'justwpforms' ),
				'field' => 'field_signature_start_over_button_label',
			),
			2252 => array(
				'type' => 'text',
				'label' => __( 'Clear drawn signature', 'justwpforms' ),
				'field' => 'field_signature_clear_button_label',
			),
			2253 => array(
				'type' => 'text',
				'label' => __( 'Done drawing signature', 'justwpforms' ),
				'field' => 'field_signature_done_button_label',
			),
		);

		$controls = justwpforms_safe_array_merge( $controls, $message_controls );

		return $controls;
	}

	public function get_default_value( $part_data = array() ) {
		return array(
			'intent' => '',
			'signature' => '',
			'signature_path_data' => '',
			'signature_viewbox' => '0 0 0 0',
			'signature_raster_data' => '',
			'signature_hash_id' => '',
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
		$template_path = justwpforms_get_include_folder() . '/templates/parts/customize-signature.php';
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

		include( justwpforms_get_include_folder() . '/templates/parts/frontend-signature.php' );
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
			$sanitized_value = wp_parse_args( $request[$part_name], $sanitized_value );
			$sanitized_value = array_map( 'sanitize_text_field', $sanitized_value );
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
		$signature_string = $validated_value['signature'];
		$signature_path_data = $validated_value['signature_path_data'];
		$signature_raster_data = $validated_value['signature_raster_data'];
		$signature_is_empty = (
			'type' === $part['signature_type'] ?
			empty( $signature_string ) :
			empty( $signature_path_data ) || empty( $signature_raster_data )
		);

		if ( 1 === $part['required'] && $signature_is_empty ) {
			$validated_value = new WP_Error( 'error', justwpforms_get_validation_message( 'field_empty' ) );
		}

		return $validated_value;
	}

	public function message_part_value( $value, $original_value, $part, $destination ) {
		if ( isset( $part['type'] ) && $this->type === $part['type'] ) {
			$original_value = maybe_unserialize( $original_value );

			if ( '' === $original_value['signature_hash_id'] ) {
				$value = $original_value['signature'];

				if ( $destination === 'admin-column' && '' !== $part['intent_text'] ) {
					$value = esc_html( $part['intent_text'] ) . '<br />' . $value;
				}
			} else {
				if ( 'preview' === $destination ) {
					$value = sprintf(
						'<svg preserveAspectRatio="xMidYMid meet" viewBox="%1$s"><path d="%2$s"></path></svg>',
						$original_value['signature_viewbox'], $original_value['signature_path_data']
					);
				} else {
					$attachment_controller = justwpforms_get_attachment_controller();
					$hash_id = (
						isset( $original_value['signature_hash_id'] ) ?
						$original_value['signature_hash_id'] : ''
					);

					if ( empty( $hash_id ) ) {
						$value = '';

						return $value;
					}

					if ( 'email' === $destination ) {
						$value = __( '1 attachment', 'justwpforms' );
					} else if ( 'csv' === $destination ) {
						$attachments = $attachment_controller->get( array(
							'hash_id' => $hash_id,
						) );

						$attachment_ids = wp_list_pluck( $attachments, 'ID' );
						$links = array_map( 'wp_get_attachment_url', $attachment_ids );
						$value = implode( ', ', $links );
					} else if ( 'pdf' === $destination ) {
						$attachments = $attachment_controller->get( array(
							'hash_id' => $hash_id,
						) );

						$attachment_ids = wp_list_pluck( $attachments, 'ID' );
						$paths = array_map( 'get_attached_file', $attachment_ids );
						$value = @file_exists( $paths[0] ) ? $paths[0] : '';
					} else {
						$attachments = $attachment_controller->get( array(
							'hash_id' => $hash_id,
						) );

						$links = array();

						foreach ( $attachments as $attachment ) {
							$attachment_id = $attachment['ID'];
							$title = $attachment['post_title'];
							$url = wp_get_attachment_url( $attachment_id );
							$file = get_attached_file( $attachment_id );
							$html_link_string = '';
							$file_size = '';

							if ( file_exists( $file ) ) {
								$file_size = size_format( filesize( $file ), 2 );
								$file_size = " ({$file_size})";
								$file_extension = wp_check_filetype( $file );
								$title .= '.' . $file_extension['ext'];
							}

							$links[] = "<a href=\"{$url}\" target=\"_blank\" download>{$title}</a>{$file_size}";
						}

						$deleted_attachments = justwpforms_get_meta( get_the_ID(), 'deleted_attachments', true );
						$deleted_attachments = $deleted_attachments ? $deleted_attachments : array();

						foreach ( $deleted_attachments as $attachment ) {
							$file_name = $attachment['file_name'];
							$file_extension = $attachment['file_extension'];
							$file_size = $attachment['file_size'];
							$links[] = "<span class=\"justwpforms-deleted-attachment\">{$file_name}.{$file_extension} ({$file_size})</span>";
						}

						$value = implode( ', ', $links );

						if ( $destination === 'admin-column' && '' !== $part['intent_text'] ) {
							$value = esc_html( $part['intent_text'] ) . '<br />' . $value;
						}
					}
				}
			}
		}

		return $value;
	}

	public function email_part_value( $value, $message, $part, $form, $context ) {
		if ( $this->type === $part['type'] ) {
			if ( 'type' === $part['signature_type'] ) {
				$part_name = justwpforms_get_part_name( $part, $form );
				$original_value = $message['request'][$part_name];

				$value = ( '' !== $part['intent_text'] ) ? $part['intent_text'] . "<br><br>" . $value : '';
			} elseif ( 'draw' === $part['signature_type'] ) {
				if ( 'admin-email' !== $context ) {
					return $value;
				}

				$parts = $message['parts'];
				$part_id = $part['id'];
				$value = justwpforms_get_message_part_value( $parts[$part_id], $part, '' );
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
			'part-signature',
			justwpforms_get_plugin_url() . 'inc/assets/js/parts/part-signature.js',
			$deps, justwpforms_get_version(), true
		);
	}

	public function html_part_class( $class, $part, $form ) {
		if ( $part['type'] === $this->type ) {
			if ( 'above_signature' === $part['description_mode'] ) {
				$class[] = 'justwpforms-part--description-above-signature';
			}
		}

		return $class;
	}

	public function html_part_data_attributes( $attrs, $part ) {
		if ( $this->type !== $part['type'] ) {
			return $attrs;
		}

		$attrs['justwpforms-signature-type'] = $part['signature_type'];

		return $attrs;
	}

	public function get_pdf_part_value( $value, $part, $form ) {
		if ( $part['type'] === $this->type && 'draw' === $part['signature_type'] ) {

		}

		return $value;
	}

	public function style_dependencies( $deps, $forms ) {
		$contains_hand_drawn_signature = false;
		$form_controller = justwpforms_get_form_controller();

		foreach ( $forms as $form ) {
			$parts = $form_controller->get_parts_by_type( $form, $this->type );

			if ( count( $parts ) > 0 ) {
				foreach( $parts as $part ) {
					if ( 'draw' === $part['signature_type'] ) {
						$contains_hand_drawn_signature = true;
						break;
					}
				}
			}
		}

		if ( ! justwpforms_is_preview() && ! $contains_hand_drawn_signature ) {
			return $deps;
		}

		wp_register_style(
			'justwpforms-signature',
			justwpforms_get_plugin_url() . 'inc/assets/css/frontend/signature.css',
			array(), justwpforms_get_version()
		);

		$deps[] = 'justwpforms-signature';

		return $deps;
	}

	public function script_dependencies( $deps, $forms ) {
		$contains_signature = false;
		$contains_hand_drawn_signature = false;
		$form_controller = justwpforms_get_form_controller();

		foreach ( $forms as $form ) {
			$parts = $form_controller->get_parts_by_type( $form, $this->type );

			if ( count( $parts ) > 0 ) {
				$contains_signature = true;

				foreach( $parts as $part ) {
					if ( 'draw' === $part['signature_type'] ) {
						$contains_hand_drawn_signature = true;
						break 2;
					}
				}
			}
		}

		if ( ! justwpforms_is_preview() && ! $contains_signature ) {
			return $deps;
		}

		$signature_deps = array();

		if ( justwpforms_is_preview() || $contains_hand_drawn_signature ) {
			wp_register_script(
				'justwpforms-perfect-freehand',
				justwpforms_get_plugin_url() . 'inc/assets/js/lib/perfect-freehand.js'
			);

			$signature_deps = array( 'justwpforms-perfect-freehand' );
		}

		wp_register_script(
			'justwpforms-part-signature',
			justwpforms_get_plugin_url() . 'inc/assets/js/frontend/signature.js',
			$signature_deps, justwpforms_get_version(), true
		);

		$deps[] = 'justwpforms-part-signature';

		return $deps;
	}

}
