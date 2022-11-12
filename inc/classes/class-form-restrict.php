<?php

class justwpforms_Form_Restrict {

	/**
	 * The singleton instance.
	 *
	 * @var justwpforms_Form_Restrict
	 */
	private static $instance;

	private $was_restricted = false;

	private $counter_key_users = 'counter_users';
	private $form_submission_state = 'form_submission';

	/**
	 * The singleton constructor.
	 *
	 * @return justwpforms_Form_Restrict
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		self::$instance->hook();

		return self::$instance;
	}

	/**
	 * Hook into WordPress.
	 *
	 * @return void
	 */
	public function hook() {
		add_filter( 'justwpforms_meta_fields', array( $this, 'meta_fields' ) );
		add_filter( 'justwpforms_setup_controls', array( $this, 'setup_controls' ) );
		add_filter( 'justwpforms_messages_controls', array( $this, 'messages_controls' ) );
		add_filter( 'justwpforms_validate_submission', array( $this, 'validate_submission' ), 10, 3 );
		add_filter( 'justwpforms_validate_part_submission', array( $this, 'validate_part_submission' ), 10, 4 );
		add_filter( 'justwpforms_messages_fields', array( $this, 'meta_messages_fields' ) );
		add_filter( 'justwpforms_form_template_path', array( $this, 'form_template_path' ), 10, 3 );
		add_filter( 'justwpforms_submission_success', array( $this, 'add_submission_state' ), 20, 2 );

		// TODO delete code block once support for deprecated control is removed.
		add_filter( 'justwpforms_meta_fields', array( $this, 'meta_fields_deprecated' ) );
		add_filter( 'justwpforms_setup_controls', array( $this, 'setup_controls_deprecated' ) );
		add_action( 'justwpforms_do_setup_control', array( $this, 'do_setup_control_deprecated' ), 10, 3 );
		add_filter( 'justwpforms_messages_fields', array( $this, 'meta_messages_fields_deprecated' ) );
		add_filter( 'justwpforms_messages_controls', array( $this, 'messages_controls_deprecated' ) );
		add_filter( 'justwpforms_submission_success', array( $this, 'bump_counter_data_deprecated' ), 10, 2 );
		add_filter( 'justwpforms_get_form_data', array( $this, 'transition_form_restrict_settings'), 99 );
		add_action( 'justwpforms_form_updated', array( $this, 'cleanup_deprecated_meta_fields' ) );
		// end of TODO
	}

	public function meta_fields( $fields ) {
		$restrict_fields = array(
			'max_entries' => array(
				'default' => '',
				'sanitize' => 'justwpforms_sanitize_intval_empty',
			),
		);

		$fields = array_merge( $fields, $restrict_fields );

		return $fields;
	}

	public function meta_messages_fields( $fields ) {
		$messages_fields = array(
			'max_entries_message' => array(
				'default' => __( "This form isn't accepting any more replies.", 'justwpforms' ),
				'sanitize' => 'sanitize_text_field',
			),
		);

		$fields = array_merge( $fields, $messages_fields );

		return $fields;
	}

	public function setup_controls( $controls ) {
		$setup_controls = array(
			2302 => array(
				'type' => 'number',
				'label' => __( 'Max number of submissions', 'justwpforms' ),
				'min' => 0,
				'field' => 'max_entries',
			),
		);

		$controls = justwpforms_safe_array_merge( $controls, $setup_controls );

		return $controls;
	}

	public function messages_controls( $controls ) {
		$message_controls = array(
			60 => array(
				'type' => 'text',
				'label' => __( 'Form has reached its reply limit', 'justwpforms' ),
				'field' => 'max_entries_message',
			),
		);

		$controls = justwpforms_safe_array_merge( $controls, $message_controls );

		return $controls;
	}

	public function is_restricted( $form = array() ) {
		$restricted = false;

		$max_entries = justwpforms_get_form_property( $form, 'max_entries' );

		if ( '' === $max_entries ) {
			return $restricted;
		}

		$form_id = $form['ID'];

		$count = $this->get_submission_count( $form_id );


		if ( $max_entries <= $count ) {
			$restricted = true;
		}

		return $restricted;
	}

	public function validate_submission( $is_valid, $request, $form ) {
		if ( $this->is_restricted( $form ) ) {
			$this->was_restricted = true;
			$is_valid = false;

			return $is_valid;
		}

		return $is_valid;
	}

	public function form_template_path( $template_path, $form ) {
		if ( $this->was_restricted || $this->is_restricted( $form ) ) {
			$session = justwpforms_get_session();
			$submission_state = $session->get_states( $this->form_submission_state );

			if ( ! in_array( 'success', $submission_state ) ) {
				$session->add_error(
					$form['ID'],
					html_entity_decode( $form['max_entries_message'] )
				);
			}

			$template_path = justwpforms_get_include_folder() . '/templates/single-form-restricted.php';
		}

		return $template_path;
	}

	public function add_submission_state() {
		$session = justwpforms_get_session();
		$session->add_state( $this->form_submission_state, 'success' );
	}

	public function get_submission_count( $form_id ) {
		global $wpdb;

		$query = $wpdb->prepare("
			SELECT COUNT(p.ID) as count FROM $wpdb->posts AS p
			JOIN $wpdb->postmeta AS pm1 ON ( p.ID = pm1.post_id )
			JOIN $wpdb->postmeta AS pm2 ON ( p.ID = pm2.post_id )
			WHERE ( ( pm1.meta_key = '_justwpforms_form_id' AND pm1.meta_value = %d )
				AND ( pm2.meta_key = '_justwpforms_read' AND pm2.meta_value IN ( '', '1') ) )
			AND (p.post_type = 'justwpforms-message' AND p.post_status = 'publish' )
		", $form_id );

		$count = $wpdb->get_var( $query );

		return $count;
	}

	public function validate_part_submission( $value, $part, $form, $request ) {
		if ( is_wp_error( $value ) || justwpforms_is_falsy( $form['restrict_user_entries'] ) ) {
			return $value;
		}

		$identifier_part = justwpforms_get_form_controller()->get_first_part_by_type( $form, 'email' );
		$identifier_part = apply_filters( 'justwpforms_submission_identifier_part', $identifier_part, $form );

		if ( ! $identifier_part ) {
			return $value;
		}

		if ( $identifier_part['id'] !== $part['id'] ) {
			return $value;
		}

		if ( $this->is_restricted_for_user( $part, $form, $request ) ) {
			$value = new WP_error( 'error', justwpforms_get_form_property( $form, 'max_entries_per_user_message' ) );
		}

		return $value;
	}

	public function is_restricted_for_user( $part, $form = array(), $request = array() ) {
		$restricted = false;

		if ( ! justwpforms_get_form_property( $form, 'restrict_user_entries' ) && '' === justwpforms_get_form_property( $form, 'max_entries' ) ) {
			return $restricted;
		}

		if ( ! justwpforms_get_meta( $form['ID'], 'restrict_user_entries', true ) && '' === justwpforms_get_form_property( $form, 'max_entries' ) ) {
			return $restricted;
		}

		$message_controller = justwpforms_get_message_controller();
		$form_controller = justwpforms_get_form_controller();
		$max_entries_per_user = justwpforms_get_form_property( $form, 'max_entries_per_user' );

		if ( ! $max_entries_per_user || empty( $request ) ) {
			return $restricted;
		}

		$part_class = justwpforms_get_part_library()->get_part( $part['type'] );

		if ( false === $part_class ) {
			return $restricted;
		}

		$form_id = $form['ID'];
		$part_name = justwpforms_get_part_name( $part, $form );
		$sanitized_value = $part_class->sanitize_value( $part, $form, $request );
		$validated_value = $part_class->validate_value( $sanitized_value, $part, $form );

		if ( is_wp_error( $validated_value ) ) {
			return $restricted;
		}

		$this->try_migrate_limit_count_users( $form, $part['id'] );

		$user_email = justwpforms_stringify_part_value( $validated_value, $part, $form );

		$count = 0;
		$meta_count = justwpforms_get_meta( $form_id, $this->counter_key_users, true );

		if ( isset( $meta_count[ $user_email ] ) ) {
			$count = $meta_count[ $user_email ];
		}

		if ( $max_entries_per_user <= $count ) {
			$restricted = true;
		}

		return $restricted;
	}

	public function try_migrate_limit_count_users( $form, $part_id )  {
		$form_id = $form['ID'];
		$meta_count = justwpforms_get_meta( $form_id, $this->counter_key_users, true );

		if ( 0 !== intval( $form_id ) && empty( $meta_count ) && justwpforms_is_truthy( $form['restrict_user_entries'] ) ) {
			$meta_count = [];
			$messages = justwpforms_get_message_controller()->get_by_form( $form_id );

			foreach( $messages as $message ) {
				$user_email = '';

				if ( isset( $message['parts'][ $part_id ] ) ) {
					$user_email = $message['parts'][ $part_id ];
				}

				// if old submissions doesn't have the email field yet on the time they are submitted,
				// the part value default was and empty array
				if ( is_array( $user_email ) || empty( $user_email ) ) {
					continue;
				}

				$count = 1;

				if ( isset( $meta_count[ $user_email ] ) ) {
					$count = $meta_count[ $user_email ] + 1;
				}

				$meta_count[ $user_email ] = $count;
			}

			justwpforms_update_meta( $form_id, $this->counter_key_users, $meta_count );
		}
	}

	// TODO delete when support for deprecated controls are completely removed.
	public function bump_counter_data_deprecated( $submission, $form ) {
		// bump users count
		if ( justwpforms_is_truthy( $form['restrict_user_entries'] ) ) {
			$user_email = '';
			$part = justwpforms_get_form_controller()->get_first_part_by_type( $form, 'email' );

			if ( $part ) {
				$user_email = $submission[ $part['id'] ];
			}

			if ( ! empty( $user_email ) ) {
				$count_users_key = $this->counter_key_users;
				$count = 1;
				$meta_count = justwpforms_get_meta( $form['ID'], $count_users_key, true );
				if( empty( $meta_count ) ) {
					$meta_count = [];
				}

				if( isset( $meta_count[ $user_email ] ) ) {
					$count = $meta_count[ $user_email ] + 1;
				}

				$meta_count[ $user_email ] = $count;

				justwpforms_update_meta( $form['ID'], $count_users_key, $meta_count );
			}
		}
	}

	public function setup_controls_deprecated( $controls ) {
		$setup_controls = array(
			2306 => array(
				'type' => 'restrict_user_entries-checkbox',
				'label' => __( 'Limit submissions per user', 'justwpforms' ),
				'field' => 'restrict_user_entries',
			),
			2307 => array(
				'type' => 'restrict_user_entries-group_start',
				'trigger' => 'restrict_user_entries'
			),
			2308 => array(
				'type' => 'restrict_user_entries-number',
				'label' => __( 'Max submissions per user', 'justwpforms' ),
				'min' => 0,
				'field' => 'max_entries_per_user',
			),
			2310 => array(
				'type' => 'restrict_user_entries-group_end',
			),
		);

		$controls = justwpforms_safe_array_merge( $controls, $setup_controls );

		return $controls;
	}

	public function meta_fields_deprecated( $fields ) {
		$restrict_fields = array(
			'max_entries_per_user' => array(
				'default' => 1,
				'sanitize' => 'intval',
			),
			'restrict_user_entries' => array(
				'default' => 0,
				'sanitize' => 'justwpforms_sanitize_checkbox',
			),
		);

		$fields = array_merge( $fields, $restrict_fields );

		return $fields;
	}

	public function meta_messages_fields_deprecated( $fields ) {
		$messages_fields = array(
			'max_entries_per_user_message' => array(
				'default' => __( 'You’ve already replied to this form.',
			'justwpforms' ),
				'sanitize' => 'sanitize_text_field',
			),
		);

		$fields = array_merge( $fields, $messages_fields );

		return $fields;
	}

	public function do_setup_control_deprecated( $control, $field, $index ) {
		$type = $control['type'];
		$path = justwpforms_get_core_folder() . '/templates/customize-controls/setup';

		switch( $type ) {
			case 'restrict_user_entries-checkbox':
			case 'restrict_user_entries-group_start':
			case 'restrict_user_entries-number':
			case 'restrict_user_entries-group_end':
				$form = justwpforms_customize_get_current_form();

				if ( justwpforms_is_falsy( $form['restrict_user_entries'] ) ) {
					break;
				}

				$type = str_replace( 'restrict_user_entries-', '', $type );

				require( "{$path}/{$type}.php" );
				break;
			default:
				break;
		}
	}

	public function messages_controls_deprecated( $controls ) {
		$message_controls = array(
			100 => array(
				'type' => 'text',
				'label' => __( 'Submitter has reached their submission limit', 'justwpforms' ),
				'field' => 'max_entries_per_user_message',
			),
		);

		$controls = justwpforms_safe_array_merge( $controls, $message_controls );

		return $controls;
	}

	public function transition_form_restrict_settings( $form ) {
		if ( isset( $form['restrict_entries'] ) && justwpforms_is_falsy( $form['restrict_entries'] ) ) {
			$form['max_entries'] = '';
		}

		return $form;
	}

	public function cleanup_deprecated_meta_fields( $form ) {
		if ( isset( $form['restrict_entries'] ) ) {
			delete_post_meta( $form['ID'], '_justwpforms_restrict_entries' );
		}
	}
	// end of TODO

}

if ( ! function_exists( 'justwpforms_get_form_restrict' ) ):

function justwpforms_get_form_restrict() {
	return justwpforms_Form_Restrict::instance();
}

endif;

justwpforms_get_form_restrict();
