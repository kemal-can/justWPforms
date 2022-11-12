<?php

class justwpforms_Service_Google_Analytics extends justwpforms_Service {

	public $id = 'google-analytics';
	public $group = 'analytics';

	private $endpoint = 'https://www.google-analytics.com/collect';

	const EVENT_VIEW = 'view';
	const EVENT_SUCCESS = 'success';
	const EVENT_ERROR = 'error';
	const EVENT_ABANDON = 'abandoned';
	const EVENT_RESUME = 'resume';

	public function __construct() {
		$this->label = __( 'Google Analytics', 'justwpforms' );
	}

	public function get_default_credentials() {
		$credentials = array(
			'enabled' => '',
			'tracking_id' => '',
		);

		return $credentials;
	}

	public function set_credentials( $credentials = array(), $raw = array() ) {
		$this->credentials = wp_parse_args( $credentials, $this->get_default_credentials() );

		if ( ! empty( $raw ) ) {
			$this->credentials['enabled'] = ( isset( $raw['enabled'] ) ) ? 1 : 0;
		}
	}

	public function is_connected() {
		$is_connected = ! empty( $this->credentials['tracking_id'] );

		return $is_connected;
	}

	public function admin_widget( $previous_credentials = array() ) {
		require_once( justwpforms_get_integrations_folder() . '/services/google-analytics/partial-widget.php' );
	}

	public function configure() {
		$this->load();
	}

	public function load() {
		if ( $this->is_connected() ) {
			require_once( justwpforms_get_integrations_folder() . '/services/google-analytics/class-integration-google-analytics.php' );
		}
	}

	private function get_default_body() {
		$tracking_id = $this->credentials['tracking_id'];
		$body = array(
			// Version
			'v' => 1,
			// Tracking ID
			'tid' => $tracking_id,
			// Entry type
			't' => 'event',
			// Event category
			'ec' => 'justwpforms',
			// Event action
			'ea' => '',
			// Event label
			'el' => '',
			// Event value
			'ev' => 1,
			// Client ID
			'cid' => '',
			// User IP
			'uip' => '',
			// User agent
			'ua' => '',
			// Document location
			'dl' => '',
			// Document referer
			'dr' => '',
		);

		return $body;
	}

	public function get_default_meta() {
		$meta = array(
			'session_id' => '',
			'user_agent' => '',
			'page_url' => '',
			'referer' => '',
		);

		return $meta;
	}

	public function get_utm_meta() {
		$meta = array(
			'utm_campaign' => 'cn',
			'utm_source' => 'cs',
			'utm_medium' => 'cm',
			'utm_term' => 'ck',
			'utm_content' => 'cc',
		);

		return $meta;
	}

	public function make_request( $body ) {
		$body = wp_parse_args( $body, $this->get_default_body() );
		$body = http_build_query( $body );

		$response = wp_remote_post( $this->endpoint, array(
			'body' => $body,
		) );

		return $response;
	}

	public function track_event( $form, $event, $meta, $utm = array() ) {
		$form_title = justwpforms_get_form_title( $form );
		$meta = wp_parse_args( $meta, $this->get_default_meta() );

		$body = array(
			'ea' => $event,
			'el' => $form_title,
			'cid' => $meta['session_id'],
			'uip' => justwpforms_get_client_ip(),
			'ua' => $meta['user_agent'],
			'dl' => $meta['page_url'],
			'dr' => $meta['referer'],
		);

		$utm = array_filter( $utm );
		$body = array_merge( $body, $utm );

		$response = $this->make_request( $body );
	}

}
