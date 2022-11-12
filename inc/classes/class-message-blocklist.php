<?php

class justwpforms_Message_Blocklist {

	private static $instance;
	private static $hooked = false;

	public $save_action = 'justwpforms_save_message_blocklist';
	public $save_nonce = 'justwpforms-validation-message-blocklist';
	public $option = 'justwpforms_blocklist';
	public $error_location = 'blocked_ip_ua';

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		self::$instance->hook();

		return self::$instance;
	}

	public function hook() {
		if ( self::$hooked ) {
			return;
		}

		self::$hooked = true;

		add_filter( 'justwpforms_meta_fields', array( $this, 'meta_fields' ) );
		add_filter( 'justwpforms_setup_controls', array( $this, 'setup_controls' ) );
		add_filter( 'justwpforms_messages_fields', array( $this, 'meta_messages_fields' ) );
		add_filter( 'justwpforms_messages_controls', array( $this, 'messages_controls' ) );
		add_filter( 'justwpforms_validate_part_submission', array( $this, 'validate_part_submission' ), 10, 3 );
		add_filter( 'justwpforms_validate_submission', array( $this, 'validate_submission' ), 99, 3 );
		add_action( 'justwpforms_submission_error', array( $this, 'append_form_error' ), 10, 2 );

		// TODO delete once support for justwpforms custom block list is completely removed
		add_filter( 'justwpforms_get_form_data', array( $this, 'transition_per_form_blocklist'), 99 );
		add_filter( 'justwpforms_get_form_data', array( $this, 'transition_to_disallowed_keys' ), 99 );
		add_filter( 'justwpforms_setup_controls', array( $this, 'setup_deprecated_controls' ) );
		add_action( 'justwpforms_do_setup_control', array( $this, 'do_deprecated_control' ), 10, 3 );
		// end of TODO

	}

	public function get_fields() {
		$fields = array(
			'validate_with_disallowed_keys' => array(
				'default' => 0,
				'sanitize' => 'justwpforms_sanitize_checkbox',
			),

			// TODO delete once support for justwpforms custom block list is completely removed
			'block_emails' => array(
				'default' => 0,
				'sanitize' => 'justwpforms_sanitize_checkbox',
			),
			'blocklist_emails' => array(
				'default' => '',
				'sanitize' => array( $this, 'sanitize_email_keys' ),
			),
			'block_language' => array(
				'default' => 0,
				'sanitize' => 'justwpforms_sanitize_checkbox',
			),
			'blocklist_language' => array(
				'default' => '',
				'sanitize' => array( $this, 'sanitize_language_keys' ),
			),
			'per_form_blocklist' => array(
				'default' => 0,
				'sanitize' => 'sanitize_text_field',
			),
			// end TODO
		);

		return $fields;
	}

	public function meta_fields( $fields ) {
		$fields = array_merge( $fields, $this->get_fields() );

		return $fields;
	}

	public function setup_controls( $controls ) {
		$label_1 = __( 'Show an error message if field contains words in', 'justwpforms' );
		$label_2 = __( 'Disallowed Comment Keys', 'justwpforms' );
		$options_discussion_url = get_admin_url( null, 'options-discussion.php' );

		$setup_controls = array(
			6000 => array(
				'type' => 'checkbox',
				'label' => sprintf( '%s <a href="%s" target="_blank" class="external">%s</a>', $label_1, $options_discussion_url, $label_2 ),
				'field' => 'validate_with_disallowed_keys',

			),
		);

		$controls = justwpforms_safe_array_merge( $controls, $setup_controls );

		return $controls;
	}

	public function get_part_value( $value, $part ) {
		$part_value = '';

		switch( $part['type'] ) {
			case 'select':
			case 'radio':
				if ( is_array( $value ) ) {
					$part_value = end( $value );
				}
				break;
			case 'checkbox':
				$other_choice = end( $value );

				if ( is_array( $other_choice ) ) {
					$part_value = end( $other_choice );
				}
				break;
			case 'signature':
				if ( 'type' === $part['signature_type'] ) {
					$part_value = $value['signature'];
				}
				break;
			// TODO remove once support for deprecate narrative/blanks is completely removed
			case 'narrative':
				$part_value = trim( implode( ' ', $value ) );
				break;
			// end TODO
			default:
				$part_value = $value;
				break;
		}

		$part_value = apply_filters( 'justwpforms_blocklist_part_value', $part_value, $value, $part );

		return $part_value;
	}

	public function validate_part_submission( $value, $part, $form ) {
		$is_supported = in_array( $part['type'], $this->get_supported_parts() );

		if ( is_wp_error( $value ) || '' === $value || ! $is_supported ) {
			return $value;
		}

		$part_value = $this->get_part_value( $value, $part );

		if ( '' === $part_value ) {
			return $value;
		}

		if ( justwpforms_is_truthy( $form['validate_with_disallowed_keys'] ) ) {
			if ( ! $this->validate_disallowed_keys( '', '', '', $part_value, '', '' ) ) {
				$value = new WP_Error( 'error', justwpforms_get_validation_message( 'some_words_blocked' ) );
			}
		} else {
			// TODO remove whole else section once support for justwpforms custom blocklist is complete removed.
			$is_spam = $this->validate_deprecated_blocklist( $part_value, $part, $form );

			if ( $is_spam ) {
				$value = new WP_Error( 'error', justwpforms_get_validation_message( 'some_words_blocked' ) );
			}
		}

		return $value;
	}

	public function validate_disallowed_keys( $author, $email, $url, $comment, $user_ip, $user_agent ) {
		if ( function_exists( 'wp_check_comment_disallowed_list' ) ) {
			return ! wp_check_comment_disallowed_list( $author, $email, $url, $comment, $user_ip, $user_agent );
		}

		return ! wp_blacklist_check( $author, $email, $url, $comment, $user_ip, $user_agent );
	}

	public function meta_messages_fields( $fields ) {
		$messages_fields = array(
			'some_words_blocked' => array(
				'default' => __( 'A word or phrase here isn\'t allowed.', 'justwpforms' ),
				'sanitize' => 'sanitize_text_field',
			),
			'ip_ua_blocked' => array(
				'default' => __( 'Your IP address or browser’s user agent isn’t allowed.', 'justwpforms' ),
				'sanitize' => 'sanitize_text_field',
			),
		);

		$fields = array_merge( $fields, $messages_fields );

		return $fields;
	}

	public function messages_controls( $controls ) {
		$message_controls = array(
			4110 => array(
				'type' => 'text',
				'label' => __( 'Field contains disallowed word', 'justwpforms' ),
				'field' => 'some_words_blocked',
			),
			4111 => array(
				'type' => 'text',
				'label' => __( 'Disallowed IP address or browser', 'justwpforms' ),
				'field' => 'ip_ua_blocked',
			),
		);

		$controls = justwpforms_safe_array_merge( $controls, $message_controls );

		return $controls;
	}

	public function get_supported_parts() {
		$parts = [
			'email', 'single_line_text', 'multi_line_text', 'rich_text',
			'narrative', 'signature', 'select', 'radio', 'checkbox'
		];

		return apply_filters( 'justwpforms_blocklist_parts', $parts );
	}

	public function validate_ip_ua( $form ) {
		$is_valid = true;

		if ( justwpforms_is_falsy( $form['validate_with_disallowed_keys'] ) ) {
			return $is_valid;
		}

		$user_ip = justwpforms_get_client_ip();
		$user_agent = justwpforms_get_client_user_agent();
		$is_valid = $this->validate_disallowed_keys( '', '', '', '', $user_ip, $user_agent );

		return $is_valid;
	}

	public function validate_submission( $is_valid, $request, $form ) {
		if ( ! $is_valid ) {
			return $is_valid;
		}

		if ( ! $this->validate_ip_ua( $form ) ) {
			$is_valid = false;

			$session = justwpforms_get_session();
			$session->add_error( $this->error_location, '' );
		}

		return $is_valid;
	}

	public function append_form_error( $submission, $form ) {
		$session = justwpforms_get_session();
		$ip_ua_errors = $session->get_messages( $this->error_location );

		if ( ! empty( $ip_ua_errors ) ) {
			$session->remove_error( $this->error_location );
			$error_msg = justwpforms_get_validation_message( 'ip_ua_blocked' );
			$session->add_error( $form['ID'], html_entity_decode( $error_msg ) );
		}
	}

	// TODO delete once support for justwpforms custom block list is completely removed
	public function setup_deprecated_controls( $controls ) {
		$deprecated_setup_controls = array(
			6001 => array(
				'type' => 'block_emails-checkbox',
				'label' => __( 'Block these email addresses, email domains and email partials', 'justwpforms' ),
				'field' => 'block_emails',
			),
			6002 => array(
				'type' => 'block_emails-group_start',
				'trigger' => 'block_emails'
			),
			6003 => array(
				'type' => 'block_emails-textarea',
				'label' => __( '', 'justwpforms' ),
				'field' => 'blocklist_emails',
				'autocomplete' => 'off',
				'description' => __( 'One per line. Not case sensitive. Blank spaces disallowed', 'justwpforms' ),
			),
			6004 => array(
				'type' => 'block_emails-group_end'
			),
			6005 => array(
				'type' => 'block_language-checkbox',
				'label' => __( 'Block these words, phrases and letters', 'justwpforms' ),
				'field' => 'block_language',
			),
			6006 => array(
				'type' => 'block_language-group_start',
				'trigger' => 'block_language'
			),
			6007 => array(
				'type' => 'block_language-textarea',
				'label' => __( '', 'justwpforms' ),
				'field' => 'blocklist_language',
				'autocomplete' => 'off',
				'description' => __( 'One per line. Not case sensitive. Punctuation, numbers and alt characters disallowed.', 'justwpforms' ),
			),
			6008 => array(
				'type' => 'block_language-group_end',
			),
		);

		$controls = justwpforms_safe_array_merge( $controls, $deprecated_setup_controls );

		return $controls;
	}
	// end TODO

	// TODO delete once handler for global blocklist has been complete removed.
	public function transition_per_form_blocklist( $form ) {
		if ( 0 === intval( $form['ID'] ) ) {
			$form['per_form_blocklist'] = 1;
			return $form;
		}

		if ( justwpforms_is_falsy( $form['per_form_blocklist'] ) ) {
			$blocklist = $this->read();

			if ( 1 === intval( $blocklist['block_emails'] ) ) {
				$form['block_emails'] = 1;
				$form['blocklist_emails'] = $blocklist['emails'];
			}

			if ( 1 === intval( $blocklist['block_language'] ) ) {
				$form['block_language'] = 1;
				$form['blocklist_language'] = $blocklist['language'];
			}

			$form['per_form_blocklist'] = 1;
		}

		return $form;

	}

	public function read( $as_array = false ) {
		$blocklist = get_option( $this->option, '' );
		$defaults = array(
			'block_language' => 0,
			'language' => '',
			'block_emails' => 0,
			'emails' => '',
		);

		$blocklist = wp_parse_args( $blocklist, $defaults );
		$blocklist = array_map( 'wp_unslash', $blocklist );

		return $blocklist;
	}
	// end of TODO

	// TODO delete when deprecated justwpforms custom blocklist won't be supported anymore.
	public function transition_to_disallowed_keys( $form ) {
		if ( justwpforms_is_truthy( $form['validate_with_disallowed_keys'] ) ) {
			$form['block_emails'] = 0;
			$form['blocklist_emails'] = '';
			$form['block_language'] = 0;
			$form['blocklist_language'] = '';
		}

		return $form;
	}

	public function sanitize_email_keys( $emails ) {
		$lines = explode( "\n", $emails );
		$lines = array_map( 'trim', $lines );
		$lines = array_filter( $lines, function( $line ) {
			return ( ! preg_match( '/\s/', $line ) );
		} );
		$lines = array_filter( $lines );
		$emails = implode( "\n", $lines );

		return $emails;
	}

	public function sanitize_language_keys( $language ) {
		$lines = explode( "\n", $language );
		$lines = array_map( 'trim', $lines );
		$lines = array_filter( $lines, function( $line ) {
			return ( ! preg_match( '/[^\p{L} ]/u', $line ) );
		} );
		$lines = array_map( function( $line ) {
			$line = preg_replace( '/\s+/u', ' ', $line );
			return $line;
		}, $lines );
		$lines = array_filter( $lines );
		$language = implode( "\n", $lines );

		return $language;
	}

	public function check_value_email( $value, $keys ) {
		$value = trim( $value );

		foreach( $keys as $key ) {
			$key = preg_quote( $key, '#' );
			$pattern = "#$key#i";

			if ( preg_match( $pattern, $value ) ) {
				return true;
			}
		}

		return false;
	}

	public function check_value_text( $value, $keys ) {
		$values = is_array( $value ) ? $value : array( $value );

		$values = array_map( function( $value ) {
			$value = preg_replace( '/<[^>]*>/u', ' ', $value );
			$value = preg_replace( '/[^\p{L} ]/u', '', $value );
			$value = preg_replace( '/\s+/u', ' ', $value );
			$value = trim( $value );

			return $value;
		}, $values );
		$values = array_filter( $values );

		foreach( $keys as $key ) {
			foreach( $values as $value ) {
				$pattern = '/\b' . $key . '\b/miu';

				if ( preg_match( $pattern, $value ) ) {
					return true;
				}
			}
		}

		return false;
	}

	public function validate_deprecated_blocklist( $value, $part, $form ) {
		$blocklist_emails = $form['blocklist_emails'];

		if ( 'email' === $part['type'] &&
			justwpforms_is_truthy( $form['block_emails'] ) &&
			'' !== trim( $blocklist_emails ) ) {

			$blocklist_emails = explode( "\n", $blocklist_emails );

			return $this->check_value_email( $value, $blocklist_emails );
		}

		$blocklist_language = $form['blocklist_language'];

		if ( justwpforms_is_truthy( $form['block_language'] ) && '' !== trim( $blocklist_language ) ) {
			$blocklist_language = explode( "\n", $blocklist_language );

			return $this->check_value_text( $value, $blocklist_language );
		}

		return false;
	}
	// end of TODO

	// TODO delete when support of deprecated controls are completely removed.
	public function do_deprecated_control( $control, $field, $index ) {
		$type = $control['type'];
		$path = justwpforms_get_core_folder() . '/templates/customize-controls/setup';

		switch( $type ) {
			case 'block_emails-checkbox':
			case 'block_emails-group_start':
			case 'block_emails-textarea':
			case 'block_emails-group_end':
				$form = justwpforms_customize_get_current_form();

				if ( justwpforms_is_falsy( $form['block_emails'] ) && '' === trim( $form['blocklist_emails'] ) ) {
					break;
				}

				$type = str_replace( 'block_emails-', '', $type );

				require( "{$path}/{$type}.php" );
				break;
			case 'block_language-checkbox':
			case 'block_language-group_start':
			case 'block_language-textarea':
			case 'block_language-group_end':
				$form = justwpforms_customize_get_current_form();

				if ( justwpforms_is_falsy( $form['block_language'] ) && '' === trim( $form['blocklist_language'] ) ) {
					break;
				}

				$type = str_replace( 'block_language-', '', $type );

				require( "{$path}/{$type}.php" );
				break;
			default:
				break;
		}
	}
	// end of TODO

}

if ( ! function_exists( 'justwpforms_get_message_blocklist' ) ):

function justwpforms_get_message_blocklist() {
	return justwpforms_Message_Blocklist::instance();
}

endif;

justwpforms_get_message_blocklist();
