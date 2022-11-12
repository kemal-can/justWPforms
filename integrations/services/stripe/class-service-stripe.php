<?php

class justwpforms_Service_Stripe extends justwpforms_Service {

	public $id = 'stripe';
	public $group = 'payments';
	public $webhook_endpoint_url = '';
	public $action_webhook = 'justwpforms-webhook-stripe';

	public function __construct() {
		$this->label = __( 'Stripe', 'justwpforms' );
		$this->webhook_endpoint_url = add_query_arg( array(
			'action' => $this->action_webhook,
		), get_site_url() );
	}

	public function set_credentials( $credentials = array(), $raw = array() ) {
		$this->credentials = array(
			'enabled' => '',
			'key' => '',
			'test_key' => '',
			'secret_key' => '',
			'test_secret_key' => '',
			'mode' => 'live',
			'webhook_endpoint_secret_key' => '',
			'test_webhook_endpoint_secret_key' => '',
		);

		foreach ( $this->credentials as $key => $value ) {
			if ( isset( $credentials[$key] ) && '' !== $credentials[$key] ) {
				$this->credentials[$key] = $credentials[$key];
			}
		}

		if ( ! empty( $raw ) ) {
			$this->credentials['enabled'] = ( isset( $raw['enabled'] ) ) ? 1 : 0;
		}
	}

	public function is_connected() {
		$authenticated_live = (
			! empty( $this->credentials['key'] )
			&& ! empty( $this->credentials['secret_key'] )
		);

		$authenticated_test = (
			! empty( $this->credentials['test_key'] )
			&& ! empty( $this->credentials['test_secret_key'] )
		);

		$authenticated =  $authenticated_live || $authenticated_test ;

		return $authenticated;
	}

	public function get_currency() {
		$currency = ( isset( $this->data['currency'] ) ) ? $this->data['currency'] : '';

		return $currency;
	}

	public function get_keys() {
		$service_credentials = $this->get_credentials();

		$credentials = array(
			'key' => $service_credentials['key'],
			'secret_key' => $service_credentials['secret_key'],
			'webhook_endpoint_secret_key' => $service_credentials['webhook_endpoint_secret_key'],
		);

		if ( $this->is_test_mode() ) {
			$credentials = array(
				'key' => $service_credentials['test_key'],
				'secret_key' => $service_credentials['test_secret_key'],
				'webhook_endpoint_secret_key' => $service_credentials['test_webhook_endpoint_secret_key'],
			);
		}

		return $credentials;
	}

	public function get_publishable_key() {
		$keys = $this->get_keys();

		if ( ! isset( $keys['key'] ) ) {
			return;
		}

		return $keys['key'];
	}

	public function get_secret_key() {
		$keys = $this->get_keys();

		if ( ! isset( $keys['secret_key'] ) ) {
			return;
		}

		return $keys['secret_key'];
	}

	public function get_webhook_endpoint_secret_key() {
		$keys = $this->get_keys();

		if ( ! isset( $keys['webhook_endpoint_secret_key'] ) ) {
			return;
		}

		return $keys['webhook_endpoint_secret_key'];
	}

	public function is_test_mode() {
		$credentials = $this->get_credentials();
		$is_test_mode = 'test' === $credentials['mode'];

		return $is_test_mode;
	}

	public function create_intent( $amount ) {
		require_once( justwpforms_get_integrations_folder() . '/services/stripe/lib/stripe-php/init.php' );
		\Stripe\Stripe::setApiKey( $this->get_secret_key() );

		try {
			$intent = \Stripe\PaymentIntent::create( array(
				'amount' => $amount,
				'currency' => $this->data['currency'],
				'payment_method_types' => array( 'card' )
			) );

			return $intent;
		} catch( \Stripe\Error\OAuth\OAuthBase $e ) {
			// error handling
		}
	}

	public function admin_widget( $previous_credentials = array() ) {
		require_once( justwpforms_get_integrations_folder() . '/services/stripe/partial-widget.php' );
	}

	public function configure() {
		$this->load();
	}

	public function load() {
		if ( $this->is_connected() ) {
			require_once( justwpforms_get_integrations_folder() . '/services/stripe/class-integration-stripe.php' );
		}
	}

}
