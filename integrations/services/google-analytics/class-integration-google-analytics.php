<?php

class justwpforms_Integration_Google_Analytics {

	private static $instance;
	private static $hooked = false;
	private $service;

	public $session_cookie = 'justwpforms_ga_session';
	public $action_track_abandonment = 'justwpforms-ga-abandonment';

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		self::$instance->hook();

		return self::$instance;
	}

	public function __construct() {
		$this->service = justwpforms_get_integrations()->get_service( 'google-analytics' );
	}

	public function hook() {
		if ( self::$hooked ) {
			return;
		}

		self::$hooked = true;

		add_filter( 'justwpforms_meta_fields', array( $this, 'meta_fields' ) );
		add_filter( 'justwpforms_setup_controls', array( $this, 'setup_controls' ) );
		add_action( 'send_headers', array( $this, 'set_session_id' ) );
		add_action( 'justwpforms_form_open', array( $this, 'render_field' ) );
		add_action( 'justwpforms_form_open', array( $this, 'track_render_event' ) );
		add_action( 'justwpforms_pending_submission_success', array( $this, 'track_success_event' ), 10 );
		add_action( 'justwpforms_pending_submission_error', array( $this, 'track_error_event' ), 10, 2 );
		add_action( 'wp_ajax_' . $this->action_track_abandonment, array( $this, 'track_abandon_event' ) );
		add_action( 'wp_ajax_nopriv_' . $this->action_track_abandonment, array( $this, 'track_abandon_event' ) );
		add_filter( 'justwpforms_frontend_dependencies', array( $this, 'script_dependencies' ), 10, 2 );
		add_filter( 'justwpforms_frontend_settings', array( $this, 'frontend_settings' ) );

		add_filter( 'justwpforms_zapier_activity_entry', array( $this, 'zapier_activity_entry' ) );
		add_filter( 'justwpforms_zapier_output_fields', array( $this, 'zapier_output_fields' ) );
	}

	public function meta_fields( $fields ) {
		$fields['enable_google_analytics'] = array(
			'default' => 1,
			'sanitize' => 'justwpforms_sanitize_checkbox'
		);

		return $fields;
	}

	public function setup_controls( $controls ) {
		$setup_controls = array(
			3100 => array(
				'type' => 'checkbox',
				'field' => 'enable_google_analytics',
				'label' => __( 'Use Google Analytics', 'justwpforms' ),
			),
		);

		$controls = justwpforms_safe_array_merge( $controls, $setup_controls );

		return $controls;
	}

	public function render_field( $form ) {
		if ( ! justwpforms_get_form_property( $form, 'enable_google_analytics' ) ) {
			return '';
		}

		$session_id = $this->get_session_id();

		include( justwpforms_get_integrations_folder() . '/services/google-analytics/templates/frontend-google-analytics.php' );
	}

	public function get_session_id() {
		$session_id = (
			isset( $_COOKIE[$this->session_cookie] ) ?
			$_COOKIE[$this->session_cookie] :
			wp_generate_uuid4()
		);

		return $session_id;
	}

	public function set_session_id() {
		$session_id = $this->get_session_id();
		setcookie( $this->session_cookie, $session_id, 0, COOKIEPATH, COOKIE_DOMAIN );
	}

	public function get_field( $field ) {
		$field_name = "justwpforms_ga_{$field}";
		$value = isset( $_REQUEST[$field_name] ) ? $_REQUEST[$field_name] : '';

		return $value;
	}

	public function get_utm_field( $field ) {
		$value = isset( $_GET[$field] ) ? $_GET[$field] : '';

		return $value;
	}

	public function get_utm_fields( $preserve_keys = false ) {
		$utm_fields = $this->service->get_utm_meta();
		$utm_field_keys = array_keys( $utm_fields );
		$utm_field_parameters = array_values( $utm_fields );
		$utm_field_values = array_map( array( $this, 'get_utm_field' ), $utm_field_keys );

		if ( ! $preserve_keys ) {
			$utm_fields = array_combine( $utm_field_parameters, $utm_field_values );
		} else {
			$utm_fields = array_combine( $utm_field_keys, $utm_field_values );
		}

		return $utm_fields;
	}

	public function get_referer( $form ) {
		$referer = $this->get_field( 'referer' );

		if ( ! empty( $referer ) ) {
			return $referer;
		}

		$referer = justwpforms_get_referer();

		return $referer;
	}

	public function track_event( $form, $event ) {
		$session_id = $this->get_session_id();
		$user_agent = $this->get_field( 'user_agent' );
		$user_agent = empty( $user_agent ) ? justwpforms_get_client_user_agent() : $user_agent;
		$page_url = $this->get_field( 'page_url' );
		$page_url = empty( $page_url ) ? justwpforms_get_current_url() : $page_url;
		$referer = $this->get_referer( $form );

		$meta = array(
			'session_id' => $session_id,
			'user_agent' => $user_agent,
			'page_url' => $page_url,
			'referer' => $referer,
		);

		$utm = $this->get_utm_fields();

		$this->service->track_event( $form, $event, $meta, $utm );
	}

	public function is_session_resume( $form ) {
		$form_id = $form['ID'];

		if ( ! justwpforms_get_form_sessions()->is_resumable( $form ) ) {
			return false;
		}

		$session_controller = justwpforms_get_session_controller();
		$client_sessions = $session_controller->get_client_sessions();

		if ( ! isset( $client_sessions[$form_id] ) ) {
			return false;
		}

		$session_id = $session_controller->get_session_id( $form );

		if ( empty( $session_id ) ) {
			return false;
		}

		$show_notice = apply_filters( 'justwpforms_resume_session_notice', true, $form );

		if ( ! $show_notice ) {
			return false;
		}

		return true;
	}

	public function track_render_event( $form ) {
		if ( ! justwpforms_get_form_property( $form, 'enable_google_analytics' ) ) {
			return '';
		}

		$message_controller = justwpforms_get_message_controller();
		$form_id = $form['ID'];

		if ( isset ( $_REQUEST['action'] )
			&& isset ( $_REQUEST[$message_controller->form_parameter] )
			&& ( $message_controller->submit_action === $_REQUEST['action'] )
			&& ( $_REQUEST[$message_controller->form_parameter] === $form_id ) ) {

			return;
		}

		if ( $this->is_session_resume( $form ) ) {
			$this->track_event( $form, justwpforms_Service_Google_Analytics::EVENT_RESUME );
		} else {
			$this->track_event( $form, justwpforms_Service_Google_Analytics::EVENT_VIEW );
		}
	}

	public function track_success_event( $submission_id ) {
		$submission = justwpforms_get_message_controller()->get( $submission_id );
		$form = justwpforms_get_form_controller()->get( $submission['form_id'] );

		if ( ! justwpforms_get_form_property( $form, 'enable_google_analytics' ) ) {
			return '';
		}

		$this->track_event( $form, justwpforms_Service_Google_Analytics::EVENT_SUCCESS );

		$utm = $this->get_utm_fields( true );
		justwpforms_update_meta( $submission_id, 'utm', $utm );

		unset( $_REQUEST['justwpforms_ga_referer'] );
	}

	public function track_error_event( $submission, $form ) {
		if ( ! justwpforms_get_form_property( $form, 'enable_google_analytics' ) ) {
			return '';
		}

		$this->track_event( $form, justwpforms_Service_Google_Analytics::EVENT_ERROR );
	}

	public function track_abandon_event() {
		$form_id = isset( $_REQUEST['form_id'] ) ? $_REQUEST['form_id'] : false;

		if ( ! $form_id ) {
			return;
		}

		$form = justwpforms_get_form_controller()->get( $form_id );

		if ( ! $form || ! justwpforms_get_form_property( $form, 'enable_google_analytics' ) ) {
			return '';
		}

		if ( ! justwpforms_get_form_sessions()->is_abandonable( $form ) ) {
			return;
		}

		$this->track_event( $form, justwpforms_Service_Google_Analytics::EVENT_ABANDON );
	}

	public function has_analytics( $form ) {
		$has_analytics = justwpforms_get_form_property( $form, 'enable_google_analytics' );

		return $has_analytics;
	}

	public function script_dependencies( $deps, $forms ) {
		$forms_with_analytics = array_filter( $forms, array( $this, 'has_analytics' ) );

 		if ( empty( $forms_with_analytics ) ) {
			return $deps;
		}

		wp_register_script(
			'justwpforms-google-analytics',
			justwpforms_get_plugin_url() . 'integrations/services/google-analytics/assets/js/google-analytics.js',
			array(), justwpforms_get_version(), true
		);

		$deps[] = 'justwpforms-google-analytics';

		return $deps;
	}

	public function frontend_settings( $settings ) {
		$settings['googleAnalytics'] = array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'action' => $this->action_track_abandonment,
		);

		return $settings;
	}

	public function zapier_activity_entry( $activity ) {
		$activity['utm'] = justwpforms_get_meta( $activity['id'], 'utm', true );

		return $activity;
	}

	public function zapier_output_fields( $fields ) {
		$keys = array_keys( $this->service->get_utm_meta() );

		foreach ( $keys as $key ) {
			$suffix = str_replace( 'utm_', '', $key );
			$fields[] = array(
				'key' => 'utm__' . $key,
				'label' => 'UTM: ' . ucfirst( $suffix ),
			);
		}

		return $fields;
	}

}

if ( ! function_exists( 'justwpforms_get_integration_google_analytics' ) ):

function justwpforms_get_integration_google_analytics() {
	$instance = justwpforms_Integration_Google_Analytics::instance();
	$instance->hook();

	return $instance;
}

endif;

justwpforms_get_integration_google_analytics();
