<?php

class justwpforms_Service_Payments extends justwpforms_Service {

	public $id = 'payments';
	public $supports_multiple = true;

	public function __construct() {
		$this->label = __( 'Payment', 'justwpforms' );
	}

	public function set_credentials( $credentials = array(), $raw = array() ) {
		// Noop
	}

}
