<?php

class justwpforms_Service_PayPal extends justwpforms_Service {

	public $id = 'paypal';
	public $group = 'payments';

	public function __construct() {
		$this->label = __( 'PayPal', 'justwpforms' );
	}

	public function set_credentials( $credentials = array(), $raw = array() ) {
		$this->credentials = array(
			'enabled' => '',
			'client_id' => '',
			'client_secret' => '',
			'sandbox_client_id' => '',
			'sandbox_client_secret' => '',
			'mode' => 'live',
		);

		foreach ( $this->credentials as $key => $value ) {
			if ( isset( $credentials[$key] ) && '' !== $credentials[$key] ) {
				$this->credentials[$key] = $credentials[$key];
			}
		}

		if ( ! empty( $raw ) ) {
			$this->credentials['enabled']      = ( isset( $raw['enabled'] ) ) ? 1 : 0;
		}
	}

	public function is_connected() {
		$authenticated_live = (
			! empty( $this->credentials['client_id'] )
			&& ! empty( $this->credentials['client_secret'] )
		);

		$authenticated_sandbox = (
			! empty( $this->credentials['sandbox_client_id'] )
			&& ! empty( $this->credentials['sandbox_client_secret'] )
		);

		$authenticated = $authenticated_live || $authenticated_sandbox;

		return $authenticated;
	}

	public function admin_widget( $previous_credentials = array() ) {
		require_once( justwpforms_get_integrations_folder() . '/services/paypal/partial-widget.php' );
	}

	public function configure() {
		$this->load();
	}

	public function load() {
		if ( $this->is_connected() ) {
			require_once( justwpforms_get_integrations_folder() . '/services/paypal/class-integration-paypal.php' );
		}
	}

}
