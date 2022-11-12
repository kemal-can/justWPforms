<?php

class justwpforms_Part_Attachment extends justwpforms_Form_Part {

	private $controller;

	public $type = 'attachment';

	public function __construct() {
		$this->label = __( 'File Upload', 'justwpforms' );
		$this->description = __( 'For allowing files to be uploaded with specific requirements.', 'justwpforms' );
		$this->controller = justwpforms_get_attachment_controller();

		add_filter( 'justwpforms_part_class', array( $this, 'html_part_class' ), 10, 3 );
		add_filter( 'justwpforms_part_data_attributes', array( $this, 'html_part_data_attributes' ), 10, 3 );
		add_filter( 'justwpforms_frontend_dependencies', array( $this, 'script_dependencies' ), 10, 2 );
		add_filter( 'justwpforms_style_dependencies', array( $this, 'style_dependencies' ), 10, 2 );
		add_filter( 'justwpforms_stringify_part_value', array( $this, 'stringify_value' ), 10, 3 );
		add_filter( 'justwpforms_message_part_value', array( $this, 'message_part_value' ), 10, 4 );
		add_filter( 'justwpforms_email_part_value', array( $this, 'email_part_value' ), 10, 5 );

		add_filter( 'justwpforms_get_form_data', array( $this, 'transition_allowed_file_extensions' ), 99 );
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
			'placeholder' => array(
				'default' => __( 'Choose file', 'justwpforms' ),
				'sanitize' => 'sanitize_text_field',
			),
			'allowed_file_types' => array(
				'default' => 'deprecated',
				'sanitize' => 'sanitize_text_field'
			),
			'allowed_file_extensions' => array(
				'default' => 'csv,txt,pdf,zip,jpg,jpeg,gif,png,bmp,mp3,mp4',
				'sanitize' => array(
					'justwpforms_sanitize_list',
					justwpforms_allowed_file_extensions(),
				),
			),
			'max_file_size' => array(
				'default' => justwpforms_get_max_upload_size(),
				'sanitize' => 'justwpforms_sanitize_float'
			),
			'min_file_count' => array(
				'default' => 0,
				'sanitize' => 'intval'
			),
			'max_file_count' => array(
				'default' => 0,
				'sanitize' => 'intval'
			),
			'required' => array(
				'default' => 1,
				'sanitize' => 'justwpforms_sanitize_checkbox',
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
		$template_path = justwpforms_get_include_folder() . '/templates/parts/customize-attachment.php';
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

		include( justwpforms_get_include_folder() . '/templates/parts/frontend-attachment.php' );
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

		$default_file_value = array(
			'id' => '',
			'name' => '',
			'size' => '',
		);

		if ( isset( $request[$part_name] ) ) {
			if ( ! is_array( $request[$part_name] ) ) {
				$request[$part_name] = array();
			}

			foreach( $request[$part_name] as $f => $file ) {
				if ( ! is_array( $file ) ) {
					$file = array();
				}

				$file = wp_parse_args( $file, $default_file_value );

				foreach ( $file as $field => $value ) {
					$file[$field] = sanitize_text_field( $value );
				}

				$sanitized_value[$f] = $file;
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
		$file_ids = wp_list_pluck( $validated_value, 'id' );
		$file_ids = array_filter( $file_ids );

		// Check if not empty
		if ( 1 === $part['required'] ) {
			if ( empty( $file_ids ) ) {
				return new WP_Error( 'error', justwpforms_get_validation_message( 'file_not_uploaded' ) );
			}
		}

		$part_id = $part['id'];
		$attachments = $this->controller->get( array(
			'hash_id' => $file_ids,
			'part_id' => $part_id
		) );

		$attachments = array_filter( $attachments, function( $attachment ) use( $file_ids ) {
			return in_array( $attachment['hash_id'], $file_ids );
		} );

		// Check if valid
		if ( ! empty( $file_ids[0] ) && count( $attachments ) !== count( $file_ids ) ) {
			return new WP_Error( 'error', justwpforms_get_validation_message( 'file_invalid' ) );
		}

		// Check count limit
		if ( ! empty( $attachments ) && ( $part['min_file_count'] > 0 ) && ( $part['min_file_count'] > count( $attachments ) ) ) {
			return new WP_Error( 'error',  justwpforms_get_validation_message( 'file_min_count' ) );
		}

		if ( ! empty( $attachments ) && ( $part['max_file_count'] > 0 ) && ( count( $attachments ) > $part['max_file_count'] ) ) {
			return new WP_Error( 'error', __( 'Too many files have been uploaded.', 'justwpforms' ) );
		}

		return $validated_value;
	}

	public function html_part_class( $class, $part, $form ) {
		if ( $this->type === $part['type'] ) {
			if ( justwpforms_get_part_value( $part, $form ) ) {
				$class[] = 'justwpforms-part--filled';
			}
		}

		return $class;
	}

	public function html_part_data_attributes( $attributes, $part, $form ) {
		if ( $this->type === $part['type'] ) {
			$attributes['justwpforms-part-id'] = $part['id'];
			$attributes['justwpforms-max-file-size'] = $part['max_file_size'];
			$attributes['justwpforms-max-file-count'] = $part['max_file_count'];
			$attributes['justwpforms-allowed-file-extensions'] = $part['allowed_file_extensions'];
		}

		return $attributes;
	}

	public function stringify_value( $value, $part, $form ) {
		if ( $this->type === $part['type'] ) {
			$value = wp_list_pluck( $value, 'id' );
		}

		return $value;
	}

	public function message_part_value( $value, $original_value, $part, $destination ) {
		if ( isset( $part['type'] )
			&& $this->type === $part['type'] ) {

			$hash_ids = maybe_unserialize( $original_value );
			$hash_ids = array_filter( array_values( $hash_ids ) );

			if ( ! empty( $hash_ids ) ) {
				if ( 'email' === $destination ) {
					$value = sprintf( __( '%d attachments', 'justwpforms' ), count( $hash_ids ) );
				} else if ( 'csv' === $destination ) {
					$attachments = $this->controller->get( array(
						'hash_id' => $hash_ids,
					) );

					$attachment_ids = wp_list_pluck( $attachments, 'ID' );
					$links = array_map( 'wp_get_attachment_url', $attachment_ids );
					$value = implode( ', ', $links );
				} else {
					$attachments = $this->controller->get( array(
						'hash_id' => $hash_ids,
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
							$file_info = pathinfo( $file );
							$title .= '.' . $file_info['extension'];
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
				}
			}
		}

		return $value;
	}

	public function email_part_value( $value, $message, $part, $form, $context ) {
		if ( $this->type === $part['type'] ) {
			$parts = $message['parts'];
			$part_id = $part['id'];
			$value = str_replace( ', ', '<br>', justwpforms_get_message_part_value( $parts[$part_id], $part, '' ) );
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
			'part-attachment',
			justwpforms_get_plugin_url() . 'inc/assets/js/parts/part-attachment.js',
			$deps, justwpforms_get_version(), true
		);
	}

	public function script_dependencies( $deps, $forms ) {
		$contains_attachment = false;
		$form_controller = justwpforms_get_form_controller();

		foreach ( $forms as $form ) {
			if ( $form_controller->get_first_part_by_type( $form, $this->type ) ) {
				$contains_attachment = true;
				break;
			}
		}

		if ( justwpforms_is_preview() || ! $contains_attachment ) {
			return $deps;
		}

		wp_register_script(
			'justwpforms-plupload',
			justwpforms_get_plugin_url() . 'inc/assets/js/lib/plupload.min.js',
			array(), justwpforms_get_version(), true
		);

		wp_register_script(
			'justwpforms-attachment',
			justwpforms_get_plugin_url() . 'inc/assets/js/frontend/attachment.js',
			array( 'justwpforms-plupload' ), justwpforms_get_version(), true
		);

		$deps[] = 'justwpforms-attachment';

		return $deps;
	}

	public function style_dependencies( $deps, $forms ) {
		$contains_attachment = false;
		$form_controller = justwpforms_get_form_controller();

		foreach ( $forms as $form ) {
			if ( $form_controller->get_first_part_by_type( $form, $this->type ) ) {
				$contains_attachment = true;
				break;
			}
		}

		if ( ! justwpforms_is_preview() && ! $contains_attachment ) {
			return $deps;
		}

		wp_register_style(
			'justwpforms-attachment',
			justwpforms_get_plugin_url() . 'inc/assets/css/frontend/attachment.css',
			array(), justwpforms_get_version()
		);

		$deps[] = 'justwpforms-attachment';

		return $deps;
	}

	public function transition_allowed_file_extensions( $form ) {

		foreach ( $form['parts'] as $index => $part ) {
			if ( $this->type !== $part['type'] ) {
				continue;
			}

			if ( 'deprecated' !== $part['allowed_file_types'] ) {
				$allowed_file_extensions = justwpforms_allowed_file_extensions();
				$mime_group = justwpforms_get_deprecated_mime_groups();
				$file_extensions = [];

				if ( '' == $part['allowed_file_types'] ) {
					$file_extensions = $allowed_file_extensions;
				} else if ( 'custom' !== $part['allowed_file_types'] ) {
					foreach( $mime_group[ $part['allowed_file_types'] ] as $extension ) {
						if ( in_array( $extension, $allowed_file_extensions ) ) {
							$file_extensions[] = $extension;
						}
					}
				}

				if ( ! empty ( $file_extensions ) ) {
					$form['parts'][$index]['allowed_file_extensions'] = implode( ',', $file_extensions );
				}
				$form['parts'][$index]['allowed_file_types'] = 'deprecated';
			}
		}

		return $form;
	}
}
