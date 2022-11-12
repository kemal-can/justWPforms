<?php

class justwpforms_Service_Analytics extends justwpforms_Service {

	public $id = 'analytics';
	public $supports_multiple = true;

	public function __construct() {
		$this->label = __( 'Analytics', 'justwpforms' );
	}

	public function set_credentials( $credentials = array(), $raw = array() ) {
		// Noop
	}

}
