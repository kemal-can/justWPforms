<?php

class justwpforms_Session_Controller {

	private static $instance;

	public $post_type;
	public $message_controller;
	public $cookie_prefix = 'justwpforms_session_';
	public $cookie = 'justwpforms_sessions';
	public $action = 'justwpforms_session_id';
	public $sessions = array();
	public $schedule_cleanup = 'justwpforms_schedule_cleanup_sessions';

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		self::$instance->hook();

		return self::$instance;
	}

	public function hook() {
		$this->message_controller = justwpforms_get_message_controller();
		$this->post_type = $this->message_controller->post_type;

		add_action( 'wp_loaded', array( $this, 'initialize' ) );
		add_action( 'parse_request', array( $this, 'admin_post' ) );
		add_action( 'admin_init', array( $this, 'admin_post' ) );
		add_action( 'justwpforms_form_before', array( $this, 'read_client_session' ), 10 );
		add_action( 'justwpforms_form_before', array( $this, 'render_resume_notice' ), 20 );
		add_action( 'justwpforms_form_open', array( $this, 'render_session_field' ) );
		add_filter( 'justwpforms_resume_session_notice', array( $this, 'toggle_session_notice' ), 10, 2 );
		add_action( 'justwpforms_session_submit', array( $this, 'submit_session' ), 10, 2 );
		add_action( 'justwpforms_response_created', array( $this, 'publish_session' ), 10, 2 );
		add_action( $this->schedule_cleanup, array( $this, 'cleanup' ) );
		add_filter( 'justwpforms_update_form_data', array( $this, 'handle_old_whitelist_value' ) );

		$this->schedule_cleanup();
	}

	public function initialize() {
		$this->sessions = $this->get_client_sessions();
	}

	public function get_client_sessions() {
		$sessions = array();

		foreach( $_COOKIE as $name => $data ) {
			if ( 0 !== strpos( $name, $this->cookie_prefix ) ) {
				continue;
			}

			$form_id = str_replace( $this->cookie_prefix, '', $name );
			$sessions[$form_id] = $data;
		}

		return $sessions;
	}

	public function get_session_id( $form ) {
		$form_id = $form['ID'];

		// Generate a new session id
		$session_id = $this->generate_session_id( $form );

		// Look for volatile session id
		if ( isset ( $_REQUEST[$this->action] ) &&
			! empty( $_REQUEST[$this->action] ) ) {
			$session_id = $_REQUEST[$this->action];
		}

		// Look for persistent session id, if the form supports it
		if ( justwpforms_get_form_sessions()->is_resumable( $form ) ) {
			if ( isset( $this->sessions[$form_id] ) ) {
				$session_id = $this->sessions[$form_id];
			}
		}

		return $session_id;
	}

	public function destroy_session_id( $form ) {
		$form_id = $form['ID'];
		unset( $this->sessions[$form_id] );
		unset( $_REQUEST[$this->action] );
	}

	public function generate_session_id( $form ) {
		$ip = justwpforms_get_client_ip();
		$ua = justwpforms_get_client_user_agent();
		$time = time();
		$form_id = $form['ID'];
		$id = md5( "{$form_id}_{$ip}_{$ua}_{$time}" );

		return $id;
	}

	public function read_client_session( $form ) {
		if ( ! justwpforms_get_form_sessions()->has_sessions( $form ) ) {
			return;
		}

		if ( isset ( $_REQUEST['action'] ) && justwpforms_get_message_controller()->submit_action === $_REQUEST['action'] ) {
			return;
		}


		$form_id = $form['ID'];
		$session_id = $this->get_session_id( $form );

		if ( empty( $session_id ) ) {
			$this->destroy_session_id( $form );
			return;
		}

		$session = $this->get( $session_id );

		if ( ! $session ) {
			$this->destroy_session_id( $form );
			return;
		}

		$data = justwpforms_get_session()->unserialize( $session->post_content );
		$apply_step = apply_filters( 'justwpforms_session_unserialize_step', false, $data, $form );
		justwpforms_get_session()->from_data( $data, $apply_step );
	}

	public function render_session_field( $form ) {
		if ( ! justwpforms_get_form_sessions()->has_sessions( $form ) ) {
			return;
		}

		$field_name = $this->action;
		$session_id = $this->get_session_id( $form );
		?>
		<div data-justwpforms-type="session">
			<input type="hidden" name="<?php echo $field_name; ?>" value="<?php echo $session_id; ?>" />
		</div>
		<?php
	}

	public function render_resume_notice( $form ) {
		if ( justwpforms_is_preview_context() ) {
			return;
		}

		if ( ! justwpforms_get_form_sessions()->is_resumable( $form ) ) {
			return;
		}

		$form_id = $form['ID'];

		if ( ! isset( $this->sessions[$form_id] ) ) {
			return;
		}

		$session_id = $this->get_session_id( $form );

		if ( empty( $session_id ) ) {
			return;
		}

		$show_notice = apply_filters( 'justwpforms_resume_session_notice', true, $form );

		if ( ! $show_notice ) {
			return;
		}

		$message = html_entity_decode( justwpforms_get_form_property( $form, 'abandoned_resume_return_message' ) );
		$link = $this->get_session_reset_link( $form );

		justwpforms_get_session()->add_notice(
			$form_id,
			sprintf( '%s %s', $message, $link )
		);
	}

	public function toggle_session_notice( $show, $form ) {
		$show = ! justwpforms_is_stepping();

		return $show;
	}

	public function get_session_reset_link( $form ) {
		$form_id = $form['ID'];
		$format = '<button type="button" data-justwpforms-form-id="%s" class="justwpforms-text-button justwpforms-clear-session">%s</button>';
		$text = justwpforms_get_form_property( $form, 'abandoned_resume_clear_all_label' );
		$link = sprintf( $format, $form_id, $text );

		return $link;
	}

	public function admin_post() {
		// Exit early if we're not submitting any form
		if ( ! isset ( $_REQUEST[$this->action] ) ||
			empty( $_REQUEST[$this->action] ) ) {

			return;
		}

		// Check form_id parameter
		$message_controller = justwpforms_get_message_controller();
		$form_parameter = $message_controller->form_parameter;

		if ( ! isset ( $_REQUEST[$form_parameter] ) ) {
			wp_send_json_error();
		}

		// Check if form found
		$form_id = intval( $_REQUEST[$form_parameter] );
		$form_controller = justwpforms_get_form_controller();
		$form = $form_controller->get( $form_id );

		if ( ! $form || is_wp_error( $form ) ) {
			wp_send_json_error();
		}

		// Check if form supports sessions
		if ( ! justwpforms_get_form_sessions()->has_sessions( $form ) ) {
			wp_send_json_error();
		}

		// Handle session
		$session_id = $_REQUEST[$this->action];
		do_action( 'justwpforms_session_submit', $session_id, $form );
	}

	public function submit_session( $session_id, $form ) {
		$session_data = $this->validate_session( $form, $_REQUEST );

		if ( ! $session_data ) {
			wp_send_json_error();
		}

		// Standard submission data
		$post_data = $this->message_controller->get_insert_post_data( $form, $session_data );

		// Current submission session data
		$step = isset( $_REQUEST[$this->message_controller->form_step_parameter] ) ?
			$_REQUEST[$this->message_controller->form_step_parameter] : '';
		justwpforms_get_session()->set_step( $step );
		$post_content = justwpforms_get_session()->serialize();

		// Session data
		$date = current_time( 'mysql' );
		$date_gmt = get_gmt_from_date( $date );

		$post_data = wp_parse_args( array(
			'post_status' => 'draft',
			'post_name' => $session_id,
			'post_content' => $post_content,
			'post_date' => $date,
			'post_date_gmt' => $date_gmt,
		), $post_data );

		$session = $this->get( $session_id, array( 'publish', 'draft' ) );

		if ( ! $session ) {
			$session_id = wp_insert_post( $post_data );

			if ( ! $session_id ) {
				wp_send_json_error();
			}

			do_action( 'justwpforms_draft_created', $session_id, $form );
		} else {
			// Don't create a new session if already published.
			if ( 'publish' === $session->post_status ) {
				wp_send_json_error();
			}

			$session_id = $session->ID;
			$post_data['ID'] = $session_id;
			$session = wp_update_post( $post_data );

			do_action( 'justwpforms_draft_updated', $session_id, $form );
		}

		wp_send_json_success();
	}

	public function publish_session( $response_id, $form ) {
		if ( ! justwpforms_get_form_sessions()->has_sessions( $form ) ) {
			return;
		}

		$session_id = $this->get_session_id( $form );
		$session = $this->get( $session_id );

		if ( $session ) {
			wp_delete_post( $session->ID );
		}

		$this->destroy_session_id( $form );

		wp_update_post( array(
			'ID' => $response_id,
			'post_name' => $session_id,
		) );
	}

	public function validate_session( $form = array(), $request = array() ) {
		$session_data = array();

		// Reject invalid IPs and UAs
		if ( ! justwpforms_get_message_blocklist()->validate_ip_ua( $form ) ) {
			return false;
		}

		// Reject invalid parts
		foreach( $form['parts'] as $part ) {
			$visible = apply_filters( 'justwpforms_message_part_visible', true, $part );

			// Exclude "invisible" parts like placeholder.
			if ( ! $visible ) {
				continue;
			}

			// Treat parts as required to avoid empty/useless data.
			$part['required'] = 1;
			$value = $this->message_controller->validate_part( $form, $part, $request );

 			if ( false !== $value ) {
 				$part_id = $part['id'];
				$value = justwpforms_stringify_part_value( $value, $part, $form );
				$session_data[$part_id] = $value;
			}
		}

		$form_controller = justwpforms_get_form_controller();
		$parts = $form['parts'];

 		// For non resumable drafts, check phone and email if configured.
		if ( ( '' === justwpforms_get_form_property( $form, 'abandoned_resume_response_expire' ) ) ) {
			$whitelist_value = justwpforms_get_form_property( $form, 'abandoned_response_whitelist' );

			if ( ! empty( $whitelist_value ) ) {
				if ( 'phone-or-email' === $whitelist_value ) {
					$part = $form_controller->get_first_part_by_type( $form, 'email' );
				} else {
					$part = $form_controller->get_part_by_id( $form, $whitelist_value );
				}

				$parts = array( $part );
			}
		}

 		$part_ids = wp_list_pluck( $parts, 'id' );
		$parts = array_combine( $part_ids, $parts );
		$non_empty_parts = array_values( array_filter( array_intersect_key( $session_data, $parts ) ) );

		// Reject empty drafts
		if ( count( $non_empty_parts ) > 0 ) {
			return $session_data;
		}

 		return false;
	}

	public function get( $session_id = null, $status = 'draft' ) {
		$query = array(
			'post_type' => $this->post_type,
			'post_status' => $status,
		);

		if ( null !== $session_id ) {
			$query['name'] = $session_id;
			$query['posts_per_page'] = 1;
			$query['offset'] = 0;
		}

		$sessions = get_posts( $query );

		if ( count( $sessions ) ) {
			$sessions = ( null !== $session_id ) ? $sessions[0] : $sessions;
			return $sessions;
		}

		return false;
	}

	public function send_alert( $session_id ) {
		$session = $this->get( $session_id );

		if ( ! $session ) {
			return;
		}

		$alert_datetime = justwpforms_get_meta( $session->ID, 'alert_datetime', true );

		if ( $alert_datetime ) {
			return;
		}

		$alert_datetime = current_time( 'mysql' );

		justwpforms_update_meta( $session->ID, 'alert_datetime', $alert_datetime );
		justwpforms_get_task_controller()->add( 'justwpforms_Task_Email_Abandonment', $session->ID );
	}

	public function schedule_cleanup() {
		if ( ! wp_next_scheduled( $this->schedule_cleanup ) ) {
			wp_schedule_event( time(), 'daily', $this->schedule_cleanup );
		}
	}

	public function cleanup() {
		$form_controller = justwpforms_get_form_controller();
		$form_sessions = justwpforms_get_form_sessions();
		$forms = $form_controller->get();

		foreach( $forms as $form ) {
			if ( ! $form_sessions->has_sessions( $form ) ) {
				continue;
			}

			if ( $form_sessions->is_abandonable( $form ) ) {
				$expire = justwpforms_get_form_property( $form, 'abandoned_response_expire' );
				$this->do_cleanup( $form, $expire );
			}

			if ( $form_sessions->is_resumable( $form ) ) {
				$expire = justwpforms_get_form_property( $form, 'abandoned_resume_response_expire' );
				$this->do_cleanup( $form, $expire );
			}
		}
	}

	public function do_cleanup( $form, $expire ) {
		$date = "-{$expire} day";
		$args = array(
			'post_type' => $this->post_type,
			'post_status' => 'draft',
			'date_query' => array(
				'before' => $date,
			),
			'meta_key' => '_justwpforms_form_id',
			'meta_value' => $form['ID'],
			'posts_per_page' => -1,
			'fields' => 'ids',
		);

		$args = apply_filters( 'justwpforms_cleanup_session_args', $args, $form );
		$session_ids = get_posts( $args );

		foreach ( $session_ids as $session_id ) {
			wp_delete_post( $session_id, true );
		}
	}

	/**
	 * Updates 'abandoned_response_whitelist' value to first found Email part
	 * if set to old value `phone-or-email`.
	 *
	 * @hooked filter `justwpforms_update_form_data`
	 *
	 * @return array
	 */
	public function handle_old_whitelist_value( $update_data ) {
		if ( 'phone-or-email' === $update_data['_justwpforms_abandoned_response_whitelist'] ) {
			$form_controller = justwpforms_get_form_controller();
			$form = $form_controller->get( $update_data['ID'] );

			$email_part = $form_controller->get_first_part_by_type( $form, 'email' );
			$update_data['_justwpforms_abandoned_response_whitelist'] = $email_part['id'];
		}

		return $update_data;
	}

}

if ( ! function_exists( 'justwpforms_get_session_controller' ) ):

function justwpforms_get_session_controller() {
	return justwpforms_Session_Controller::instance();
}

endif;

justwpforms_get_session_controller();
