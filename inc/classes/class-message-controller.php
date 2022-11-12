<?php

class justwpforms_Message_Controller {

	/**
	 * The singleton instance.
	 *
	 * @since 1.0
	 *
	 * @var justwpforms_Message_Controller
	 */
	private static $instance;

	/**
	 * The message post type slug.
	 *
	 * @since 1.0
	 *
	 * @var string
	 */
	public $post_type = 'justwpforms-message';

	/**
	 * The parameter name used to identify a
	 * submission form
	 *
	 * @since 1.0
	 *
	 * @var string
	 */
	public $form_parameter = 'justwpforms_form_id';

	/**
	 * The parameter name used to identify a
	 * submission form
	 *
	 * @var string
	 */
	public $form_step_parameter = 'justwpforms_step';

	/**
	 * The action name used to identify a
	 * message submission request.
	 *
	 * @since 1.0
	 *
	 * @var string
	 */
	public $submit_action = 'justwpforms_message';

	/**
	 * The send-user-email action name.
	 */
	public $send_email_action = 'justwpforms_send_user_email';

	public $mark_action = 'justwpforms_mark_response';
	public $action_mark_spam = 'justwpforms_mark_spam';
	public $action_mark_not_spam = 'justwpforms_mark_not_spam';
	public $action_mark_read = 'justwpforms_mark_read';
	public $action_mark_unread = 'justwpforms_mark_unread';
	public $action_trash = 'justwpforms_trash';
	public $action_restore = 'justwpforms_restore';
	public $action_delete = 'justwpforms_delete';

	public $schedule_pending_cleanup = 'justwpforms_schedule_pending_cleanup';

	/**
	 * The singleton constructor.
	 *
	 * @since 1.0
	 *
	 * @return justwpforms_Message_Controller
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		self::$instance->hook();

		return self::$instance;
	}

	/**
	 * Register hooks.
	 *
	 * @since 1.0
	 *
	 * @return void
	 */
	public function hook() {
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_filter( 'justwpforms_responses_page_url', array( $this, 'page_url' ) );
		add_action( 'parse_request', array( $this, 'admin_post' ) );
		add_action( 'admin_init', array( $this, 'admin_post' ) );
		add_action( 'before_delete_post', array( $this, 'before_delete_post' ) );
		add_action( 'delete_post', array( $this, 'delete_post' ) );
		add_action( 'trashed_post', array( $this, 'trashed_post' ) );
		add_action( 'untrashed_post', array( $this, 'untrashed_post' ) );
		add_action( 'wp_untrash_post_status',  array( $this, 'untrash_post_status' ), 10, 3 );
		add_action( 'justwpforms_form_deleted', array( $this, 'form_deleted' ) );
		add_filter( 'justwpforms_email_part_visible', array( $this, 'email_part_visible' ), 10, 4 );

		// Core multi-step hooks
		add_action( 'justwpforms_step', array( $this, 'default_submission_step' ) );
		// Submission preview and review
		add_action( 'justwpforms_step', array( $this, 'preview_submission_step' ) );
		add_action( 'justwpforms_step', array( $this, 'review_submission_step' ) );
		// Client IP
		add_action( 'justwpforms_response_created', array( $this, 'append_response_info' ), 10, 2 );
		add_action( 'justwpforms_draft_created', array( $this, 'append_response_info' ), 10, 2 );
		// Unique IDs
		add_action( 'justwpforms_response_created', array( $this, 'response_stamp_unique_id' ), 10, 2 );
		add_action( 'justwpforms_submission_success', array( $this, 'notice_append_unique_id' ), 10, 3 );
		// Asynchronous success features
		add_action( 'justwpforms_pending_submission_success', array( $this, 'pending_submission_success' ) );
		// Resend user email link
		add_action( 'wp_ajax_' . $this->send_email_action, array( $this, 'send_user_email' ) );
		add_action( 'wp_ajax_' . $this->action_mark_spam, array( $this, 'ajax_mark_spam' ) );
		add_action( 'wp_ajax_' . $this->action_mark_not_spam, array( $this, 'ajax_mark_not_spam' ) );
		add_action( 'wp_ajax_' . $this->action_mark_read, array( $this, 'ajax_mark_read' ) );
		add_action( 'wp_ajax_' . $this->action_mark_unread, array( $this, 'ajax_mark_unread' ) );
		add_action( 'wp_ajax_' . $this->action_trash, array( $this, 'ajax_trash' ) );
		add_action( 'wp_ajax_' . $this->action_restore, array( $this, 'ajax_restore' ) );
		add_action( 'wp_ajax_' . $this->action_delete, array( $this, 'ajax_delete' ) );

		add_action( 'justwpforms_stale_fields_deleted', array( $this, 'delete_stale_fields' ), 10, 2 );

		add_action( $this->schedule_pending_cleanup, array( $this, 'pending_cleanup' ) );

		$this->schedule_cleanup();
	}

	public function get_post_fields() {
		$fields = array(
			'post_title' => '',
			'post_type' => $this->post_type,
			'post_status' => 'publish',
		);

		return $fields;
	}

	public function get_meta_fields() {
		$fields = array(
			'form_id' => 0,
			'read' => false,
			'tracking_id' => '',
			'request' => array(),
		);

		return $fields;
	}

	/**
	 * Get the default values of the message post object fields.
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	public function get_defaults( $group = '' ) {
		$fields = array();

		switch ( $group ) {
			case 'post':
				$fields = $this->get_post_fields();
				break;
			case 'meta':
				$fields = $this->get_meta_fields();
				break;
			default:
				$fields = array_merge(
					$this->get_post_fields(),
					$this->get_meta_fields()
				);
				break;
		}

		return $fields;
	}

	/**
	 * Action: register the message custom post type.
	 *
	 * @hooked action init
	 *
	 * @since 1.0
	 *
	 * @return void
	 */
	public function register_post_type() {
		$labels = array(
			'name' => __( 'Submissions', 'justwpforms' ),
			'singular_name' => __( 'Submission', 'justwpforms' ),
			'edit_item' => __( 'Edit Submission', 'justwpforms' ),
			'view_item' => __( 'View Submission', 'justwpforms' ),
			'view_items' => __( 'View Submissions', 'justwpforms' ),
			'search_items' => __( 'Search Submissions', 'justwpforms' ),
			'not_found' => __( 'No submissions found.', 'justwpforms' ),
			'not_found_in_trash' => __( 'No submissions found in Trash.', 'justwpforms' ),
			'all_items' => __( 'All Submissions', 'justwpforms' ),
			'menu_name' => __( 'All Submissions', 'justwpforms' ),
		);

		$args = array(
			'labels' => $labels,
			'public' => false,
			'publicly_queryable' => false,
			'exclude_from_search' => true,
			'show_ui' => true,
			'show_in_menu' => false,
			'show_in_admin_bar' => false,
			'query_var' => true,
			'capability_type' => 'page',
			'has_archive' => false,
			'hierarchical' => false,
			'can_export' => false,
			'supports' => array( 'custom-fields' ),
		);

		register_post_type( $this->post_type, $args );
	}

	public function page_url( $url ) {
		$url = "edit.php?post_type={$this->post_type}";

		return $url;
	}

	public function get_session_reset_callback( $session, $form ) {
		$session_reset_callback = apply_filters(
			'justwpforms_session_reset_callback',
			array(
				'function' => array( $session, 'reset_step' ),
				'args'   => array(),
			),
			$session,
			$form
		);

		return $session_reset_callback;
	}

	/**
	 * Action: handle a form submission.
	 *
	 * @hooked action parse_request
	 *
	 * @since 1.0
	 *
	 * @return void
	 */
	public function admin_post() {
		// Exit early if we're not submitting any form
		if ( ! isset ( $_REQUEST['action'] ) || $this->submit_action != $_REQUEST['action'] ) {
			return;
		}

		// Check form_id parameter
		if ( ! isset ( $_REQUEST[$this->form_parameter] ) ) {
			wp_send_json_error();
		}

		$form_id = intval( $_REQUEST[$this->form_parameter] );

		$form_controller = justwpforms_get_form_controller();
		$form = $form_controller->get( $form_id );

		// Check if form found
		if ( ! $form || is_wp_error( $form ) ) {
			wp_send_json_error();
		}

		// Set form step
		$step = isset( $_REQUEST[$this->form_step_parameter] ) ?
			$_REQUEST[$this->form_step_parameter] : '';

		justwpforms_get_session()->set_step( $step );

		// Validate honeypot
		if ( justwpforms_get_form_controller()->has_honeypot_protection( $form ) ) {
			if ( ! justwpforms_validate_honeypot( $form ) && ! defined( 'justwpforms_IS_SPAMBOT' ) ) {
				define( 'justwpforms_IS_SPAMBOT', true );
			}
		}

		// Validate hash
		if ( justwpforms_get_form_controller()->has_hash_protection( $form ) ) {
			if ( ! justwpforms_validate_hash( $form ) && ! defined( 'justwpforms_IS_SPAMBOT' ) ) {
				define( 'justwpforms_IS_SPAMBOT', true );
			}
		}

		// Validate browser
		if ( justwpforms_get_form_controller()->has_browser_protection( $form ) ) {
			if ( ! justwpforms_validate_browser( $form ) && ! defined( 'justwpforms_IS_SPAMBOT' ) ) {
				define( 'justwpforms_IS_SPAMBOT', true );
			}
		}

		define( 'justwpforms_STEPPING', true );
		do_action( 'justwpforms_step', $form );
	}

	public function default_submission_step( $form ) {
		if ( 'submit' !== justwpforms_get_current_step( $form ) ) {
			return;
		}

		$form_id = $form['ID'];
		$form_controller = justwpforms_get_form_controller();
		$session = justwpforms_get_session();

		// Validate submission
		$antispam = justwpforms_get_antispam_integration();
		$antispam_result = '';

		if ( justwpforms_is_truthy( $form['captcha'] ) && $antispam->get_active_service()->is_connected() ) {
			$antispam_result = $antispam->validate_submission( $form );

			if ( is_wp_error( $antispam_result ) ) {
				$antispam_error = new WP_Error( 'error', justwpforms_get_validation_message( 'field_empty' ) );

				$session->add_error( justwpforms_get_recaptcha_part_name( $form ), $antispam_error->get_error_message() );
			}
		}

		$submission = $this->validate_submission( $form, $_REQUEST );
		$response = array();
		$session_reset_callback = $this->get_session_reset_callback( $session, $form );

		// If this submission is pending confirmation from asynchronous conditions.
		$is_pending = $this->submission_is_pending( $_REQUEST, $form );

		if ( false === $submission || is_wp_error( $antispam_result ) ) {
			// Add a general error notice at the top
			$session->add_error( $form_id, html_entity_decode( $form['error_message'] ) );

			// Reset steps
			call_user_func( $session_reset_callback['function'], $session_reset_callback['args'] );

			/**
			 * This action fires upon an invalid submission.
			 *
			 * @since 1.4
			 *
			 * @param WP_Error $submission Error data.
			 * @param array    $form   Current form data.
			 *
			 * @return void
			 */
			do_action( 'justwpforms_submission_error', $submission, $form );

			// Features that depend on asynchronous conditions should hook
			// to this action, instead of `justwpforms_submission_error`.
			if ( ! $is_pending ) {
				do_action( 'justwpforms_pending_submission_error', $submission, $form );
			}

			// Render the form
			$response['html'] = $form_controller->render( $form );

			// Send error response
			wp_send_json_error( $response );
		} else {
			$redirect_url = justwpforms_get_form_property( $form, 'redirect_url' );

			// Add a general success notice at the top
			$session->add_notice( $form_id, html_entity_decode( $form['confirmation_message'] ) );

			// Empty submitted values
			$session->clear_values();

			$form = justwpforms_get_conditional_controller()->get( $form, $_REQUEST );

			// Create message post
			if ( ! justwpforms_is_spambot() ) {
				$status = $is_pending ? 'pending' : 'publish';
				$message_id = $this->create( $form, $submission, $status );

				if ( is_wp_error( $message_id ) ) {
					return;
				}
			}

			if ( ! justwpforms_is_spambot() ) {
				$message = $this->get( $message_id );

				/**
				 * This action fires once a message is succesfully submitted.
				 *
				 * @since 1.4
				 *
				 * @param array $submission Submission data.
				 * @param array $form   Current form data.
				 *
				 * @return void
				 */
				do_action( 'justwpforms_submission_success', $submission, $form, $message );

				// Features that depend on asynchronous conditions should hook
				// to this action, instead of `justwpforms_submission_success`.
				if ( ! $is_pending ) {
					do_action( 'justwpforms_pending_submission_success', $message_id );
				}

				if ( ! empty( $redirect_url ) ) {
				 	$response['redirect'] = $form['redirect_url'];
				 	$response['redirect_after'] = apply_filters( 'justwpforms_submission_redirect_after', 5 );
				}
			}

			if ( ! empty( $submission ) ) {
				$response['hide_steps'] = true;
			}

			// Render the form
			$response['html'] = $form_controller->render( $form );
			$response['printable_data'] = $this->printable_submission_data( $form, $message );

			// Send success response
			$this->send_json_success( $response, $submission, $form );
		}
	}

	public function preview_submission_step( $form ) {
		if ( 'preview' !== justwpforms_get_current_step( $form ) ) {
			return;
		}

		$form_id = $form['ID'];
		$form_controller = justwpforms_get_form_controller();
		$session = justwpforms_get_session();

		// Validate ReCaptcha
		$antispam = justwpforms_get_antispam_integration();
		$antispam_result = '';

		if ( justwpforms_is_truthy( $form['captcha'] ) && $antispam->get_active_service()->is_connected() ) {
			$antispam_result = $antispam->validate_submission( $form );

			if ( is_wp_error( $antispam_result ) ) {
				$antispam_error = new WP_Error( 'error', justwpforms_get_validation_message( 'field_empty' ) );

				$session->add_error( justwpforms_get_recaptcha_part_name( $form ), $antispam_error->get_error_message() );
			}
		}

		$submission = $this->validate_submission( $form, $_REQUEST );
		$response = array();
		$session_reset_callback = $this->get_session_reset_callback( $session, $form );

		if ( false === $submission || is_wp_error( $antispam_result ) ) {
			// Add a general error notice at the top
			$session->add_error( $form_id, html_entity_decode( $form['error_message'] ) );

			// Reset steps
			call_user_func( $session_reset_callback['function'], $session_reset_callback['args'] );

			// Render the form
			$response['html'] = $form_controller->render( $form );

			// Send error response
			wp_send_json_error( $response );
		} else {
			// Advance step
			$session->next_step();

			$form = justwpforms_get_conditional_controller()->get( $form, $_REQUEST );

			// Render the form
			$response['html'] = $form_controller->render( $form );

			// Send success response
			$this->send_json_success( $response, $submission, $form );
		}
	}

	public function review_submission_step( $form ) {
		if ( 'review' !== justwpforms_get_current_step( $form ) ) {
			return;
		}

		$form_id = $form['ID'];
		$form_controller = justwpforms_get_form_controller();
		$session = justwpforms_get_session();
		$submission = $this->validate_submission( $form, $_REQUEST );
		$response = array();
		$session_reset_callback = $this->get_session_reset_callback( $session, $form );

		if ( false === $submission ) {
			// Add a general error notice at the top
			$session->add_error( $form_id, html_entity_decode( $form['error_message'] ) );
		}

		// Reset steps
		call_user_func( $session_reset_callback['function'], $session_reset_callback['args'] );

		// Render the form
		$response['html'] = $form_controller->render( $form );

		if ( false === $submission ) {
			// Send error response
			wp_send_json_error( $response );
		}

		// Send success response
		$this->send_json_success( $response, $submission, $form );
	}

	public function send_json_success( $response = array(), $submission = array(), $form = array() ) {
		$response = apply_filters( 'justwpforms_json_response', $response, $submission, $form );

		wp_send_json_success( $response );
	}

	public function trashed_post( $post_id ) {
		$post = get_post( $post_id );

		if ( $this->post_type !== $post->post_type ) {
			return;
		}

		do_action( 'justwpforms_submission_status_changed', $post_id );
	}

	public function untrashed_post( $post_id ) {
		$post = get_post( $post_id );

		if ( $this->post_type !== $post->post_type ) {
			return;
		}

		do_action( 'justwpforms_submission_status_changed', $post_id );
	}

	public function untrash_post_status( $new_status, $post_id, $previous_status ) {
		$post = get_post( $post_id );

		if ( ! $post ) {
			return;
		}

		if ( $this->post_type !== $post->post_type ) {
			return;
		}

		$new_status = 'publish';

		return $new_status;
	}

	public function before_delete_post( $post_id ) {
		$post = get_post( $post_id );

		if ( $this->post_type !== $post->post_type ) {
			return;
		}

		do_action( 'justwpforms_before_delete_response', $post_id );
	}

	/**
	 * Action: update the unread badge upon message deletion.
	 *
	 * @since 1.1
	 *
	 * @hooked action delete_post
	 *
	 * @param int|string $post_id The ID of the message object.
	 *
	 * @return void
	 */
	public function delete_post( $post_id ) {
		$post = get_post( $post_id );

		if ( $this->post_type !== $post->post_type ) {
			return;
		}

		do_action( 'justwpforms_response_deleted', $post_id );
	}

	public function form_deleted( $form_id ) {
		$responses = $this->get_by_form( $form_id );

		foreach ( $responses as $response ) {
			wp_delete_post( $response['ID'], true );
		}
	}

	public function validate_part( $form, $part, $request ) {
		$part_class = justwpforms_get_part_library()->get_part( $part['type'] );

		if ( false !== $part_class ) {
			$part_id = $part['id'];
			$part_name = justwpforms_get_part_name( $part, $form );
			$sanitized_value = $part_class->sanitize_value( $part, $form, $request );
			$validated_value = $part_class->validate_value( $sanitized_value, $part, $form );
			$validated_value = apply_filters( 'justwpforms_validate_part_submission', $validated_value, $part, $form, $request );

			$session = justwpforms_get_session();
			$session->add_value( $part_name, $sanitized_value );

			if ( ! is_wp_error( $validated_value ) ) {
				return $validated_value;
			} else {
				do_action( 'justwpforms_validation_error', $form, $part );

				$part_field = $part_name;
				$error_data = $validated_value->get_error_data();

				if ( ! empty( $error_data ) && isset( $error_data['components'] ) ) {
					foreach ( $error_data['components'] as $component ) {
						$session->add_error( $part_field, $validated_value->get_error_message(), $component );
					}
				} else {
					$session->add_error( $part_field, $validated_value->get_error_message() );
				}
			}
		}

		return false;
	}

	public function validate_submission( $form, $request = array() ) {
		$submission = array();
		$is_valid = true;

		// Apply conditional logic
		$form = justwpforms_get_conditional_controller()->get( $form, $request );

		foreach( $form['parts'] as $part ) {
			$part_id = $part['id'];
			$validated_value = $this->validate_part( $form, $part, $request );

			if ( false !== $validated_value ) {
				$string_value = justwpforms_stringify_part_value( $validated_value, $part, $form );
				$submission[$part_id] = $string_value;
			} else {
				$is_valid = false;
			}
		}

		$is_valid = apply_filters( 'justwpforms_validate_submission', $is_valid, $request, $form );

		return $is_valid ? $submission : false;
	}

	public function get_raw_request( $form, $submission ) {
		$request = array();

		foreach( $form['parts'] as $part_id => $part ) {
			$part_name = justwpforms_get_part_name( $part, $form );

			if ( ! isset( $_REQUEST[$part_name] ) ) {
				continue;
			}

			$part_class = justwpforms_get_part_library()->get_part( $part['type'] );
			$value = $part_class->sanitize_value( $part, $form, $_REQUEST );
			$request[$part_name] = $value;
		}

		return $request;
	}

	public function get_insert_post_data( $form, $submission ) {
		$defaults = $this->get_post_fields();
		$defaults_meta = $this->get_meta_fields();
		$raw_request = $this->get_raw_request( $form, $submission );
		$message_meta = wp_parse_args( array(
			'form_id' => $form['ID'],
			'request' => $raw_request,
		), $defaults_meta );
		$message_meta = array_merge( $message_meta, $submission );
		$message_meta = justwpforms_prefix_meta( $message_meta );
		$post_data = array_merge( $defaults, array(
			'meta_input' => $message_meta
		) );

		return $post_data;
	}

	/**
	 * Create a new message post object.
	 *
	 * @since 1.0
	 *
	 * @param array $form       The message form data.
	 * @param array $submission The message form data.
	 *
	 * @return int|boolean
	 */
	public function create( $form, $submission, $status = 'publish' ) {
		$post_data = $this->get_insert_post_data( $form, $submission );
		$message_id = wp_insert_post( wp_slash( $post_data ), true );

		wp_update_post( array(
			'ID' => $message_id,
			'post_title' => justwpforms_get_message_title( $message_id ),
			'post_status' => $status,
		) );

		do_action( 'justwpforms_response_created', $message_id, $form );

		return $message_id;
	}

	public function append_response_info( $response_id, $form ) {
		$client_referer = (
			isset( $_REQUEST['justwpforms_client_referer'] ) ?
			$_REQUEST['justwpforms_client_referer'] : ''
		);

		justwpforms_update_meta( $response_id, 'client_referer', $client_referer );

		if ( justwpforms_capture_client_ip() ) {
			justwpforms_update_meta( $response_id, 'client_ip', justwpforms_get_client_ip() );
		}
	}

	public function response_stamp_unique_id( $response_id, $form ) {
		if ( intval( $form['unique_id'] ) ) {
			$increment = $form['unique_id_start_from'];
			$prefix = $form['unique_id_prefix'];
			$suffix = $form['unique_id_suffix'];
			$tracking_id = "{$prefix}{$increment}{$suffix}";

			justwpforms_update_meta( $response_id, 'tracking_id', $tracking_id );
		}
	}

	public function notice_append_unique_id( $submission, $form, $message ) {
		if ( intval( $form['unique_id'] ) ) {
			$tracking_id = $message['tracking_id'];
			$notice = $form['confirmation_message'];
			$label = __( 'Tracking number', 'justwpforms' );
			$notice = "{$notice}<span>{$label}: {$tracking_id}</span>";
			$notice = html_entity_decode( $notice );

			justwpforms_get_session()->add_notice( $form['ID'], $notice );
		}
	}

	public function submission_is_pending( $request, $form ) {
		$is_pending = apply_filters( 'justwpforms_submission_is_pending', false, $request, $form );

		return $is_pending;
	}

	public function pending_submission_success( $submission_id ) {
		$form_id = justwpforms_get_meta( $submission_id, 'form_id', true );
		$form = justwpforms_get_form_controller()->get( $form_id );

		if ( ! justwpforms_is_spambot() ) {
			if ( justwpforms()->is_registered() ) {
				if ( 1 === intval( $form['receive_email_alerts'] ) ) {
					justwpforms_get_task_controller()->add( 'justwpforms_Task_Email_Owner', $submission_id );
				}

				if ( 1 === intval( $form['send_confirmation_email'] ) ) {
					justwpforms_get_task_controller()->add( 'justwpforms_Task_Email_User', $submission_id );
				}
			}

			$save_entries = apply_filters( 'justwpforms_save_entries', true, $form );

			if ( ! $save_entries && ! $is_pending ) {
				wp_delete_post( $submission_id );
			}
		}
	}

	public function process_pending_submission( $submission_id ) {
		$form_controller = justwpforms_get_form_controller();
		$form_id = justwpforms_get_meta( $submission_id, 'form_id', true );
		$form = $form_controller->get( $form_id );
		$submission = justwpforms_get_message_controller()->get( $submission_id );

		if ( ! $this->submission_is_pending( $submission['request'], $form ) ) {
			return;
		}

		$is_success = apply_filters( 'justwpforms_pending_submission_succeeded', true, $submission_id );

		if ( ! $is_success ) {
			do_action( 'justwpforms_pending_submission_error', $submission, $form );

			return;
		}

		wp_update_post( array(
			'ID' => $submission_id,
			'post_status' => 'publish',
		) );

		do_action( 'justwpforms_pending_submission_success', $submission_id );
	}

	/**
	 * Get one or more message post objects.
	 *
	 * @since 1.0
	 *
	 * @param string $post_ids The IDs of the messages to retrieve.
	 *
	 * @return array
	 */
	public function do_get( $post_ids = '' ) {
		$query_params = array(
			'post_type' => $this->post_type,
			'post_status' => array( 'publish', 'pending', 'draft', 'trash' ),
			'posts_per_page' => -1,
		);

		if ( ! empty( $post_ids ) ) {
			if ( is_numeric( $post_ids ) ) {
				$query_params['p'] = $post_ids;
			} else if ( is_array( $post_ids ) )  {
				$query_params['post__in'] = $post_ids;
			}
		}

		$messages = get_posts( $query_params );
		$message_entries = array_map( array( $this, 'to_array'), $messages );

		if ( is_numeric( $post_ids ) ) {
			if ( count( $message_entries ) > 0 ) {
				return $message_entries[0];
			} else {
				return false;
			}
		}

		return $message_entries;
	}

	public function get( $post_ids, $force = false ) {
		$args = md5( serialize( func_get_args() ) );
		$key = "_justwpforms_cache_responses_get_{$args}";
		$found = false;
		$result = justwpforms_cache_get( $key, $found );

		if ( false === $found || $force ) {
			$result = $this->do_get( $post_ids );
			justwpforms_cache_set( $key, $result );
		}

		return $result;
	}

	/**
	 * Get all messages relative to a form.
	 *
	 * @since 1.0
	 *
	 * @param string $form_id The ID of the form.
	 *
	 * @return array
	 */
	public function get_by_form( $form_id, $ids_only = false, $count = -1 ) {
		if ( $ids_only ) {
			global $wpdb;

			$query = $wpdb->prepare( "
				SELECT p.ID FROM $wpdb->posts p
				JOIN $wpdb->postmeta m ON p.ID = m.post_id
				WHERE p.post_type = 'justwpforms-message'
				AND m.meta_key = '_justwpforms_form_id'
				AND m.meta_value = %d;
			", $form_id );

			$results = $wpdb->get_col( $query );

			return $results;
		}

		$query_params = array(
			'post_type'   => $this->post_type,
			'post_status' => 'any',
			'posts_per_page' => $count,
			'meta_query' => array( array(
				'key' => '_justwpforms_form_id',
				'value' => $form_id,
			) )
		);

		$messages = get_posts( $query_params );
		$message_entries = array_map( array( $this, 'to_array'), $messages );

		return $message_entries;
	}

	/**
	 * Get messages by a list of meta fields.
	 *
	 * @param string $metas An array of meta fields.
	 *
	 * @return array
	 */
	public function get_by_metas( $metas ) {
		$metas = justwpforms_prefix_meta( $metas );
		$meta_query = array();

		foreach ( $metas as $field => $value ) {
			$meta_query[] = array(
				'field' => $field,
				'value' => $value,
			);
		}

		$query_params = array(
			'post_type'   => $this->post_type,
			'post_status' => 'any',
			'posts_per_page' => -1,
			'meta_query' => $meta_query,
		);

		$messages = get_posts( $query_params );
		$message_entries = array_map( array( $this, 'to_array'), $messages );

		return $message_entries;
	}

	/**
	 * Turn a message post object into an array.
	 *
	 * @since 1.0
	 *
	 * @param WP_Post $message The message post object.
	 *
	 * @return array
	 */
	public function to_array( $message ) {
		$message_array = $message->to_array();
		$message_meta = justwpforms_unprefix_meta( get_post_meta( $message->ID ) );
		$form_id = $message_meta['form_id'];
		$form = justwpforms_get_form_controller()->get( $form_id );
		$meta_defaults = $this->get_meta_fields();
		$message_array = array_merge( $message_array, wp_parse_args( $message_meta, $meta_defaults ) );
		$message_array['parts'] = array();

		if ( $form ) {
			foreach ( $form['parts'] as $part_data ) {
				$part = justwpforms_get_part_library()->get_part( $part_data['type'] );

				if ( $part ) {
					$part_id = $part_data['id'];
					$part_value = $part->get_default_value( $part_data );

					if ( isset( $message_meta[$part_id] ) ) {
						$part_value = $message_meta[$part_id];
					}

					$message_array['parts'][$part_id] = $part_value;
					unset( $message_array[$part_id] );
				}
			}
		}

		return $message_array;
	}

	public function email_part_visible( $visible, $part, $form, $response ) {
		$required = justwpforms_is_truthy( $part['required'] );
		$value = justwpforms_get_email_part_value( $response, $part, $form );

		if ( false === $required && empty( $value ) ) {
			$visible = false;
		}

		if ( isset( $part['use_as_subject'] ) && $part['use_as_subject'] ) {
			$visible = false;
		}

		return $visible;
	}

	public function do_search_metas( $term ) {
		global $wpdb;

		$sql = "
		SELECT m.post_id, m.meta_key, m.meta_value
		FROM $wpdb->postmeta m JOIN $wpdb->posts p ON m.post_id = p.ID
		WHERE p.post_type = %s AND m.meta_value LIKE %s
		GROUP BY m.post_id;
		";

		$term = '%' . $wpdb->esc_like( $term ) . '%';
		$post_type = justwpforms_get_message_controller()->post_type;
		$query = $wpdb->prepare( $sql, $post_type, $term );
		$metas = $wpdb->get_results( $query );

		return $metas;
	}

	public function search_metas( $term ) {
		$args = md5( serialize( func_get_args() ) );
		$key = "__justwpforms_cache_responses_metas_search_{$args}";
		$found = false;
		$result = justwpforms_cache_get( $key, $found );

		if ( false === $found ) {
			$result = $this->do_search_metas( $term );
			justwpforms_cache_set( $key, $result );
		}

		return $result;
	}

	public function send_user_email() {
		if ( ! check_ajax_referer( $this->send_email_action ) ) {
			wp_send_json_error();
		}

		if ( ! isset( $_REQUEST['response_id'] ) || ! isset( $_REQUEST['email'] ) ) {
			wp_send_json_error();
		}

		$error_message = __( 'Invalid email.', 'justwpforms' );
		$success_message = __( 'Email sent.', 'justwpforms' );

		$emails = explode( ',', $_REQUEST['email'] );
		$emails = array_map( 'trim', $emails );
		$emails = array_filter( $emails );

		foreach( $emails as $email ) {
			if ( ! justwpforms_is_email( $email ) ) {
				wp_send_json_error( array(
					'message' => $error_message,
				) );
			}
		}

		if ( empty( $emails ) ) {
			wp_send_json_error( array(
				'message' => $error_message,
			) );
		}

		$response_id = $_REQUEST['response_id'];
		$response = $this->get( $response_id );

		if ( ! $response ) {
			wp_send_json_error( array(
				'message' => $error_message,
			) );
		}

		add_filter( 'justwpforms_email_confirmation', function( $email_message ) use( $emails ) {
			$email_message->set_to( $emails );

			return $email_message;
		} );

		justwpforms_get_task_controller()->add( 'justwpforms_Task_Email_User', $response_id );

		wp_send_json_success( array(
			'message' => $success_message,
		) );
	}

	public function get_admin_title() {
		$before_title = __( 'Submissions', 'justwpforms' );
		$after_title = sprintf( __( '&lsaquo; %s &#8212; WordPress' ), get_bloginfo( 'name' ) );
		$title = "{$before_title} {$after_title}";
		$count = justwpforms_submission_counter()->get_total_unread();

		if ( ! empty( $count ) ) {
			$title = "{$before_title} ({$count}) {$after_title}";
		}

		return $title;
	}

	public function get_admin_edit_title() {
		$before_title = __( 'Edit Submission', 'justwpforms' );
		$after_title = sprintf( __( '&lsaquo; %s &#8212; WordPress' ), get_bloginfo( 'name' ) );
		$title = "{$before_title} {$after_title}";

		return $title;
	}

	public function get_ajax_mark_response( $activity_id ) {
		$page_title = html_entity_decode( justwpforms_get_message_controller()->get_admin_title() );
		$form_id = justwpforms_get_meta( $activity_id, 'form_id', true );
		$read_unread_badge = justwpforms_read_unread_badge( $form_id );
		$response = array(
			'counters' => justwpforms_submission_counter()->get_totals(),
			'pageTitle' => $page_title,
			'formID' => $form_id,
			'badge' => $read_unread_badge,
		);

		return $response;
	}

	public function ajax_mark_spam() {
		if ( empty( $_REQUEST['post'] ) ) {
			wp_send_json_error();
		}

		$action = $this->action_mark_spam . '-' . $_REQUEST['post'];

		if ( ! check_ajax_referer( $action ) ) {
			wp_send_json_error();
		}

		$post_id = $_REQUEST['post'];
		$current_status = justwpforms_get_meta( $post_id, 'read', true );

		if ( 'trash' == get_post_status( $post_id ) ) {
			justwpforms_update_meta( $post_id, 'previously_trash', true );
		} else {
			justwpforms_update_meta( $post_id, 'previously_trash', false );
		}

		if ( 2 !== $current_status ) {
			justwpforms_update_meta( $post_id, 'previously_read', $current_status );
		}

		justwpforms_update_meta( $post_id, 'read', 2 );
		wp_untrash_post( $post_id );

		do_action( 'justwpforms_submission_status_changed', $post_id );

		wp_send_json_success( $this->get_ajax_mark_response( $post_id ) );
	}

	public function ajax_mark_not_spam() {
		if ( empty( $_REQUEST['post'] ) ) {
			wp_send_json_error();
		}

		$action = $this->action_mark_not_spam . '-' . $_REQUEST['post'];

		if ( ! check_ajax_referer( $action ) ) {
			wp_send_json_error();
		}

		$post_id = $_REQUEST['post'];
		$current_status = '';

		if ( justwpforms_meta_exists( $post_id, 'previously_read' ) ) {
			$current_status = justwpforms_get_meta( $post_id, 'previously_read', true );
		}

		if ( justwpforms_meta_exists( $post_id, 'previously_trash' ) && justwpforms_get_meta( $post_id, 'previously_trash', true ) ) {
			wp_trash_post( $post_id );
		}

		justwpforms_update_meta( $post_id, 'read', $current_status );

		do_action( 'justwpforms_submission_status_changed', $post_id );

		wp_send_json_success( $this->get_ajax_mark_response( $post_id ) );
	}

	public function ajax_mark_read() {
		if ( empty( $_REQUEST['post'] ) ) {
			wp_send_json_error();
		}

		$action = $this->action_mark_read . '-' . $_REQUEST['post'];

		if ( ! check_ajax_referer( $action ) ) {
			wp_send_json_error();
		}

		$post_id = $_REQUEST['post'];

		justwpforms_update_meta( $post_id, 'read', 1 );

		do_action( 'justwpforms_submission_status_changed', $post_id );

		wp_send_json_success( $this->get_ajax_mark_response( $post_id ) );
	}

	public function ajax_mark_unread() {
		if ( empty( $_REQUEST['post'] ) ) {
			wp_send_json_error();
		}

		$action = $this->action_mark_unread . '-' . $_REQUEST['post'];

		if ( ! check_ajax_referer( $action ) ) {
			wp_send_json_error();
		}

		$post_id = $_REQUEST['post'];

		justwpforms_update_meta( $post_id, 'read', '' );

		do_action( 'justwpforms_submission_status_changed', $post_id );

		wp_send_json_success( $this->get_ajax_mark_response( $post_id ) );
	}

	public function ajax_trash() {
		if ( empty( $_REQUEST['post'] ) ) {
			wp_send_json_error();
		}

		$action = $this->action_trash . '-' . $_REQUEST['post'];

		if ( ! check_ajax_referer( $action ) ) {
			wp_send_json_error();
		}

		$post_id = $_REQUEST['post'];

		wp_trash_post( $post_id );

		do_action( 'justwpforms_submission_status_changed', $post_id );

		wp_send_json_success( $this->get_ajax_mark_response( $post_id ) );
	}

	public function ajax_restore() {
		if ( empty( $_REQUEST['post'] ) ) {
			wp_send_json_error();
		}

		$action = $this->action_restore . '-' . $_REQUEST['post'];

		if ( ! check_ajax_referer( $action ) ) {
			wp_send_json_error();
		}

		$post_id = $_REQUEST['post'];

		wp_untrash_post( $post_id );

		do_action( 'justwpforms_submission_status_changed', $post_id );

		wp_send_json_success( $this->get_ajax_mark_response( $post_id ) );
	}

	public function ajax_delete() {
		if ( empty( $_REQUEST['post'] ) ) {
			wp_send_json_error();
		}

		$action = $this->action_delete . '-' . $_REQUEST['post'];

		if ( ! check_ajax_referer( $action ) ) {
			wp_send_json_error();
		}

		$post_id = $_REQUEST['post'];

		wp_untrash_post( $post_id );

		$form_id = justwpforms_get_meta( $post_id, 'form_id', true );

		wp_delete_post( $post_id, true );

		justwpforms_submission_counter()->update_form_counters( $form_id );

		wp_send_json_success( $this->get_ajax_mark_response( $post_id ) );
	}

	public function delete_stale_fields( $fields, $form_id ) {
		global $wpdb;

		$field_placeholder = implode( ', ', array_fill( 0, count( $fields ), '%s' ) );
		$sql = "
			DELETE f
			FROM $wpdb->postmeta f
			JOIN $wpdb->postmeta p ON f.post_id = p.post_id
			WHERE p.meta_key = '_justwpforms_form_id'
			AND p.meta_value = %d
			AND f.meta_key IN ({$field_placeholder});
		";

		$query = call_user_func_array(
			array( $wpdb, 'prepare' ),
			array_merge( array( $sql ), array( $form_id ), $fields )
		);

		$wpdb->query( $query );
	}

	public function schedule_cleanup() {
		// Pending submissions
		if ( ! wp_next_scheduled( $this->schedule_pending_cleanup ) ) {
			wp_schedule_event( time(), 'daily', $this->schedule_pending_cleanup );
		}
	}

	public function pending_cleanup() {
		$post_ids = get_posts( array(
			'post_type' => $this->post_type,
			'post_status' => 'pending',
			'posts_per_page' => -1,
			'fields' => 'ids',
			'date_query' => array(
				'before' => '-1 day'
			),
		) );

		foreach( $post_ids as $post_id ) {
			wp_delete_post( $post_id );
		}
	}

	public function printable_submission_data( $form, $message ) {
		ob_start();
		require_once( justwpforms_printable_submission_template() );
		$submission_html = ob_get_clean();

		return $submission_html;
	}

}

if ( ! function_exists( 'justwpforms_get_message_controller' ) ):
/**
 * Get the justwpforms_Message_Controller class instance.
 *
 * @since 1.0
 *
 * @return justwpforms_Message_Controller
 */
function justwpforms_get_message_controller() {
	return justwpforms_Message_Controller::instance();
}

endif;

/**
 * Initialize the justwpforms_Message_Controller class immediately.
 */
justwpforms_get_message_controller();
