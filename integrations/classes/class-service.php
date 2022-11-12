<?php

class justwpforms_Service {

	protected $credentials = array();
	protected $data = array();

	public $id = '';
	public $label = '';
	public $group = '';
	public $display_widget = true;
	
	public function set_credentials( $credentials = array(), $raw = array() ) {
		$this->credentials = $credentials;
	}

	public function get_credentials() {
		return $this->credentials;
	}

	public function is_connected() {
		return false;
	}

	public function admin_widget( $previous_credentials = array() ) {
		// Noop
	}

	public function configure() {
		// Noop
	}

	public function load() {
		// Noop
	}

	public function make_api_request( $url, $arguments ) {
		$request = new justwpforms_API_Request( $url, $arguments );
		$request = apply_filters( 'justwpforms_api_request', $request, $this );
		$response = wp_remote_request( $request->url, $request->arguments );

		return $response;
	}

}
