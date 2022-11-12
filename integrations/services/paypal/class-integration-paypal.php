<?php

class justwpforms_Integration_PayPal {

	private static $instance;

	private $action = 'justwpforms_handle_checkout';
	private $cookie = 'justwpforms_checkout';
	private $payment = false;
	private $checkout = false;

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		self::$instance->hook();

		return self::$instance;
	}

	public function hook() {
		add_action( 'justwpforms_submission_success', array( $this, 'attach_checkout' ), 10, 3 );
		add_action( 'parse_request', array( $this, 'parse_request' ) );
		add_filter( 'justwpforms_frontend_dependencies', array( $this, 'script_dependencies' ), 10, 2 );
		add_action( 'justwpforms_part_input_after', array( $this, 'add_paypal_controls' ), 10, 2 );
		add_filter( 'justwpforms_payment_part_has_paypal', array( $this, 'part_has_paypal' ), 10, 3 );
		add_filter( 'justwpforms_payment_currencies', array( $this, 'get_currencies' ), 10, 2 );

		add_filter( 'justwpforms_messages_fields', array( $this, 'meta_messages_fields' ) );
		add_filter( 'justwpforms_messages_controls', array( $this, 'get_message_controls' ) );
	}

	public function form_has_paypal( $form ) {
		$part = justwpforms_get_form_controller()->get_first_part_by_type( $form, 'payments' );
		$service = justwpforms_get_integrations()->get_service( 'paypal' );
		$has_paypal = $part && $service->is_connected();

		return $has_paypal;
	}

	public function get_credentials() {
		$service = justwpforms_get_integrations()->get_service( 'paypal' );
		$service_credentials = $service->get_credentials();
		$credentials = array(
			'client_id' => $service_credentials['client_id'],
			'client_secret' => $service_credentials['client_secret'],
		);

		if ( $this->is_test_mode() ) {
			$credentials = array(
				'client_id' => $service_credentials['sandbox_client_id'],
				'client_secret' => $service_credentials['sandbox_client_secret'],
			);
		}

		return $credentials;
	}

	public function is_test_mode() {
		$service = justwpforms_get_integrations()->get_service( 'paypal' );
		$service_credentials = $service->get_credentials();
		$is_test_mode = 'sandbox' === $service_credentials['mode'];

		return $is_test_mode;
	}

	private function get_api_context() {
		$credentials = $this->get_credentials();
		$client_id = $credentials['client_id'];
		$client_secret = $credentials['client_secret'];
		$environment = ( $this->is_test_mode() ?
			new PayPalCheckoutSdk\Core\SandboxEnvironment( $client_id, $client_secret ) :
			new PayPalCheckoutSdk\Core\ProductionEnvironment( $client_id, $client_secret )
		);
		$context = new PayPalCheckoutSdk\Core\PayPalHttpClient( $environment );
		$context = apply_filters( 'justwpforms_paypal_api_context', $context );

		return $context;
	}

	private function create_payment( $price, $currency, $description ) {
		require_once( justwpforms_get_integrations_folder() . '/services/paypal/lib/paypal-php/vendor/autoload.php' );

		// Setup order creation request
		$api_context = $this->get_api_context();
		$request = new PayPalCheckoutSdk\Orders\OrdersCreateRequest();

		// Configure order creation
		$return_url = $this->get_callback_url( 'confirm' );
		$cancel_url = $this->get_callback_url( 'cancel' );
		$body = array(
			'intent' => 'CAPTURE',
			'application_context' => array(
				'return_url' => $return_url,
				'cancel_url' => $cancel_url
			),
			'purchase_units' => array(
				array(
					'amount' => array(
						'currency_code' => $currency,
						'value' => $price,
					),
					'description' => $description,
				),
			),
		);

		apply_filters( 'justwpforms_paypal_transaction', $body );

		$request->body = $body;

		try {
			$response = $api_context->execute( $request );

			return $response->result;
		} catch ( PayPalHttp\HttpException $e ) {
			$error = new WP_Error( 'paypal', $e->getMessage() );

			return $error;
		} catch ( Exception $e ) {
			$error = new WP_Error( 'paypal', __( 'Unknown error', 'justwpforms' ) );

			return $error;
		}
	}

	private function execute_payment( $payment_token ) {
		require_once( justwpforms_get_integrations_folder() . '/services/paypal/lib/paypal-php/vendor/autoload.php' );

		// Setup order execution request
		$api_context = $this->get_api_context();
		$request = new PayPalCheckoutSdk\Orders\OrdersCaptureRequest( $payment_token );

		try {
			$response = $api_context->execute( $request );

			return $response->result;
		} catch ( PayPalHttp\HttpException $e ) {
			$error = new WP_Error( 'paypal', $e->getMessage() );

			return $error;
		} catch ( Exception $e ) {
			$error = new WP_Error( 'paypal', __( 'Unknown error', 'justwpforms' ) );

			return $error;
		}
	}

	private function get_order_details( $payment_token ) {
		// Setup order execution request
		$api_context = $this->get_api_context();
		$request = new PayPalCheckoutSdk\Orders\OrdersGetRequest( $payment_token );

		try {
			$response = $api_context->execute( $request );

			return $response->result;
		} catch ( PayPalHttp\HttpException $e ) {
			$error = new WP_Error( 'paypal', $e->getMessage() );

			return $error;
		} catch ( Exception $e ) {
			$error = new WP_Error( 'paypal', __( 'Unknown error', 'justwpforms' ) );

			return $error;
		}
	}

	public function get_transaction_description( $form, $submission ) {
		$description = justwpforms_get_payments_integration()->get_transaction_description( $form, $submission );
		$description = substr( $description, 0, 127 );

		return $description;
	}

	public function attach_checkout( $submission, $form, $response ) {
		if ( ! $this->form_has_paypal( $form ) ) {
			return;
		}

		// Grab field settings
		$part = justwpforms_get_form_controller()->get_first_part_by_type( $form, 'payments' );
		$part_name = justwpforms_get_part_name( $part, $form );
		$value = maybe_unserialize( $response['request'][$part_name] );
		$price = $value['price'];
		$currency = strtoupper( $part['currency'] );

		if ( 'paypal' !== $value['payment_method'] ) {
			return;
		}

		// Append generic transaction details to submission
		justwpforms_get_payments_integration()->append_response_transaction( $response['ID'], 'paypal' );

		// Execute payment request
		$description = $this->get_transaction_description( $form, $submission );
		$payment = $this->create_payment( $price, $currency, $description );

		if ( is_wp_error( $payment ) ) {
			justwpforms_log_error( $payment );

			return;
		}

		$this->payment = $payment;

		// Append PayPal transaction transaction token to submission
		$payment_token = $this->payment->id;
		justwpforms_update_meta( $response['ID'], 'paypal_checkout', $payment_token );

		// Inject redirect into response
		add_filter( 'justwpforms_json_response', array( $this, 'set_redirect' ), 10, 3 );
	}

	private function get_callback_url( $action ) {
		$url = add_query_arg( array(
			$this->action => $action,
		), home_url() );

		return $url;
	}

	public function set_redirect( $response, $submission, $form ) {
		$approval_url = wp_list_filter( $this->payment->links, array( 'rel' => 'approve' ) );
		$approval_url = array_values( $approval_url );
		$approval_url = $approval_url[0]->href;
		$response['redirect'] = $approval_url;
		$response['paypal'] = array(
			'paymentId' => $this->payment->id,
		);

		return $response;
	}

	public function read_checkout() {
		$checkout = array();

		if ( isset( $_COOKIE[$this->cookie] ) ) {
			$checkout = json_decode( wp_unslash( $_COOKIE[$this->cookie] ), true );
		}

		$this->checkout = wp_parse_args( $checkout, array(
			'id' => 0,
			'status' => '',
			'form_id' => 0,
		) );
	}

	public function write_checkout( $id, $status ) {
		$this->checkout['id'] = $id;
		$this->checkout['status'] = $status;

		setcookie( $this->cookie, json_encode( $this->checkout ), 0, '/' );
	}

	public function destroy_checkout() {
		setcookie( $this->cookie, '', time() - 3600, '/' );
	}

	private function complete_checkout( $action, $payment_token ) {
		if ( $payment_token !== $this->checkout['id'] ) {
			return;
		}

		$redirect_url = $this->checkout['status'];
		$response_id = $this->get_response_id_by_payment_token( $payment_token );

		if ( false === $response_id ) {
			return;
		}

		if ( 'confirm' === $action ) {
			// Capture transaction
			$transaction = $this->execute_payment( $payment_token );

			if ( is_wp_error( $transaction ) ) {
				justwpforms_log_error( $transaction );

				return;
			}

			// Populate transaction details for admin screens
			$payment_id = $transaction->purchase_units[0]->payments->captures[0]->id;
			$details = array(
				'sandbox' => $this->is_test_mode(),
			);

			justwpforms_get_payments_integration()->append_response_transaction( $response_id, 'paypal', 'confirmed', $payment_id, $details );
		} else {
			justwpforms_get_payments_integration()->append_response_transaction( $response_id, 'paypal', 'canceled' );
		}

		$this->write_checkout( $payment_token, $action );

		// Trigger pending submission checks.
		justwpforms_get_message_controller()->process_pending_submission( $response_id );

		$this->handle_checkout_complete_redirect( $action, $redirect_url );

		exit;
	}

	public function handle_checkout_complete_redirect( $checkout_status, $redirect_url ) {
		$form_id = $this->checkout['form_id'];
		$form = justwpforms_get_form_controller()->get( $form_id );

		wp_redirect( $redirect_url );
	}

	public function parse_request() {
		$this->read_checkout();

		if ( isset( $_GET[$this->action] )
			&& in_array( $_GET[$this->action], array( 'confirm', 'cancel' ) )
			&& isset( $_GET['token'] )
			&& ! empty( $_GET['token'] ) ) {

			$action = $_GET[$this->action];
			$payment_token = $_GET['token'];

			return $this->complete_checkout( $action, $payment_token );
		}

		if ( in_array( $this->checkout['status'], array( 'confirm', 'cancel' ) ) ) {
			add_action( 'justwpforms_form_before', array( $this, 'render_checkout_notice' ), 20 );
		}

		$this->destroy_checkout();
	}

	private function get_response_id_by_payment_token( $token ) {
		global $wpdb;

		$query = $wpdb->prepare( "
			SELECT post_id
			FROM $wpdb->postmeta
			WHERE meta_key = %s AND meta_value = %s
		", '_justwpforms_paypal_checkout', $token );

		$response_ids = $wpdb->get_col( $query );

		if ( empty( $response_ids ) ) {
			return false;
		}

		$response_id = $response_ids[0];

		return $response_id;
	}

	private function token_belongs_to_form( $token, $form ) {
		global $wpdb;

		$response_id = $this->get_response_id_by_payment_token( $token );

		$query = $wpdb->prepare( "
			SELECT meta_value
			FROM $wpdb->postmeta
			WHERE meta_key = '_justwpforms_form_id' AND post_id = %d
		", $response_id );

		$form_ids = $wpdb->get_col( $query );

		if ( empty( $form_ids ) ) {
			return false;
		}

		$form_id = $form_ids[0];

		if ( intval( $form_id ) !== intval( $form['ID'] ) ) {
			return false;
		}

		return true;
	}

	public function render_checkout_notice( $form ) {
		if ( ! $this->form_has_paypal( $form ) ) {
			return;
		}

		$token = $this->checkout['id'];

		if ( ! $this->token_belongs_to_form( $token, $form ) ) {
			return;
		}

		$message = ( 'confirm' === $this->checkout['status'] ?
			justwpforms_get_validation_message( 'payment_completed' ) :
			justwpforms_get_validation_message( 'payment_cancelled' )
		);

		justwpforms_get_session()->add_notice( $form['ID'], $message );
	}

	public function script_dependencies( $deps, $forms ) {
		$has_paypal = false;
		$form_controller = justwpforms_get_form_controller();

		foreach ( $forms as $form ) {
			if ( $this->form_has_paypal( $form ) ) {
				$has_paypal = true;
				break;
			}
		}

		if ( ! justwpforms_is_preview() && ! $has_paypal ) {
			return $deps;
		}

		wp_register_script(
			'justwpforms-cookies',
			justwpforms_get_plugin_url() . 'inc/assets/js/lib/js.cookie.js',
			array(), justwpforms_get_version(), true
		);

		wp_register_script(
			'justwpforms-integration-paypal',
			justwpforms_get_plugin_url() . 'integrations/services/paypal/assets/js/paypal.js',
			array( 'jquery', 'justwpforms-cookies' ), justwpforms_get_version(), true
		);

		$deps[] = 'justwpforms-integration-paypal';

		return $deps;
	}

	public function add_paypal_controls( $part, $form ) {
		if ( 'payments' !== $part['type'] ) {
			return;
		}

		require( justwpforms_get_integrations_folder() . '/services/paypal/templates/frontend.php' );
	}

	public function part_has_paypal( $has_paypal, $part, $form ) {
		$service = justwpforms_get_integrations()->get_service( 'paypal' );
		$has_paypal = $service->is_connected();

		return $has_paypal;
	}

	public function get_currencies( $currencies, $service ) {
		if ( 'paypal' !== $service ) {
			return $currencies;
		}

		if ( isset ( $currencies['isk'] ) ) {
			unset( $currencies['isk'] );
		}

		return $currencies;
	}

	public function meta_messages_fields( $fields ) {
		$fields = array_merge( $fields, $this->get_messages_fields() );

		return $fields;
	}

	public function get_messages_fields() {
		$fields = array(
			'paypal_redirect_hint' => array(
				'default' => __( "After submitting this form, you'll be redirected to PayPal to complete your purchase securely.", 'justwpforms' ),
				'sanitize' => 'sanitize_text_field',
			),
			'paypal_option_label' => array(
				'default' => __( 'PayPal', 'justwpforms' ),
				'sanitize' => 'sanitize_text_field',
			),
		);

		return $fields;
	}

	public function get_message_controls( $controls ) {
		$message_controls = array(
			6140 => array(
				'type' => 'text',
				'label' => __( 'Submitter will be redirected to PayPal', 'justwpforms' ),
				'field' => 'paypal_redirect_hint',
			),
			6142 => array(
				'type' => 'text',
				'label' => __( 'PayPal payment', 'justwpforms' ),
				'field' => 'paypal_option_label',
			),
		);

		$controls = justwpforms_safe_array_merge( $controls, $message_controls );

		return $controls;
	}

}

if ( ! function_exists( 'justwpforms_get_paypal_integration' ) ):

function justwpforms_get_paypal_integration() {
	return justwpforms_Integration_PayPal::instance();
}

endif;

justwpforms_get_paypal_integration();
