<?php

class justwpforms_Integration_Stripe {

	private static $instance;

	private $authorize_payment_nonce = 'justwpforms_ajax_stripe_authorize_payment_nonce';
	private $intent;
	private $response_id;
	private $webhook_signature_header = 'HTTP_STRIPE_SIGNATURE';

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		self::$instance->hook();

		return self::$instance;
	}

	public function hook() {
		add_action( 'justwpforms_submission_success', array( $this, 'do_checkout' ), 10, 3 );
		add_action( 'justwpforms_part_input_after', array( $this, 'add_stripe_controls' ), 10, 2 );
		add_action( 'wp_ajax_justwpforms_stripe_authorize_payment', array( $this, 'authorize_payment' ) );
		add_action( 'wp_ajax_nopriv_justwpforms_stripe_authorize_payment', array( $this, 'authorize_payment' ) );

		add_filter( 'justwpforms_frontend_dependencies', array( $this, 'script_dependencies' ), 10, 2 );
		add_filter( 'justwpforms_frontend_settings', array( $this, 'frontend_settings' ) );
		add_filter( 'justwpforms_payment_part_has_credit_card', array( $this, 'part_has_credit_card' ), 10, 3 );
		add_filter( 'justwpforms_part_data_attributes', array( $this, 'html_part_data_attributes' ), 10, 3 );

		add_filter( 'justwpforms_messages_fields', array( $this, 'meta_messages_fields' ) );
		add_filter( 'justwpforms_messages_controls', array( $this, 'get_message_controls' ) );

		add_action( 'parse_request', array( $this, 'parse_request' ) );
	}

	/**
	 * Check if form has Stripe integration enabled.
	 *
	 * @param array $form Form data.
	 *
	 * @return boolean
	 */
	public function form_has_stripe( $form ) {
		$part = justwpforms_get_form_controller()->get_first_part_by_type( $form, 'payments' );
		$service = justwpforms_get_integrations()->get_service( 'stripe' );
		$has_stripe = $part && $service->is_connected();

		return $has_stripe;
	}

	/**
	 * Adds Stripe controls to the Payment part.
	 *
	 * @hooked `justwpforms_part_input_after`
	 *
	 * @param array $part Part data.
	 * @param array $form Form data.
	 *
	 * @return void
	 */
	public function add_stripe_controls( $part, $form ) {
		if ( 'payments' !== $part['type'] ) {
			return;
		}

		require( justwpforms_get_integrations_folder() . '/services/stripe/templates/frontend.php' );
	}

	/**
	 * Adjust value of `justwpforms_payment_part_has_credit_card` function through the filter. This function
	 * is used to check if Payment part has credit card fields (and supports credit card checkout).
	 *
	 * @hooked filter `justwpforms_payment_part_has_credit_card`
	 *
	 * @param boolean $has_credit_card Value at the time of calling this method.
	 * @param array   $part            Part data.
	 * @param array   $form            Form data.
	 *
	 * @return boolean
	 */
	public function part_has_credit_card( $has_credit_card, $part, $form ) {
		$service = justwpforms_get_integrations()->get_service( 'stripe' );
		$has_credit_card = $service->is_connected();

		return $has_credit_card;
	}

	public function script_dependencies( $deps, $forms ) {
		$has_stripe = false;
		$form_controller = justwpforms_get_form_controller();

		foreach ( $forms as $form ) {
			if ( $this->form_has_stripe( $form ) ) {
				$has_stripe = true;
				break;
			}
		}

		if ( ! justwpforms_is_preview() && ! $has_stripe ) {
			return $deps;
		}

		wp_register_script(
			'justwpforms-cookies',
			justwpforms_get_plugin_url() . 'inc/assets/js/lib/js.cookie.js',
			array(), justwpforms_get_version(), true
		);

		wp_register_script(
			'justwpforms-integration-stripe',
			justwpforms_get_plugin_url() . 'integrations/services/stripe/assets/js/stripe.js',
			array( 'jquery', 'justwpforms-cookies' ), justwpforms_get_version(), true
		);

		$deps[] = 'justwpforms-integration-stripe';

		return $deps;
	}

	public function frontend_settings( $settings ) {
		$service = justwpforms_get_integrations()->get_service( 'stripe' );

		$settings['stripe'] = array(
			'libraryURL' => 'https://js.stripe.com/v3/',
			'key' => $service->get_publishable_key(),
			'hidePostalCode' => apply_filters( 'justwpforms_payment_stripe_hide_postal_code', true ),
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( $this->authorize_payment_nonce ),
		);

		return $settings;
	}

	/**
	 * Check if payment succeeded and update payment information in submission and display success or error message.
	 *
	 * @hooked action `wp_ajax_justwpforms_stripe_authorize_payment`
	 * @hooked action `wp_ajax_nopriv_justwpforms_stripe_authorize_payment`
	 *
	 * @return void
	 */
	public function authorize_payment() {
		check_ajax_referer( $this->authorize_payment_nonce, 'nonce' );

		if ( ! isset( $_REQUEST['form_id'] ) || ! isset( $_REQUEST['success'] ) || ! isset( $_REQUEST['response_id'] ) ) {
			wp_send_json_error();
		}

		$form_id = intval( $_REQUEST['form_id'] );
		$response_id = intval( $_REQUEST['response_id'] );
		$intent_id = esc_attr( $_REQUEST['intent_id'] );
		$success = ( 'true' === $_REQUEST['success'] ) ? true : false;

		$form = justwpforms_get_form_controller()->get( $form_id );

		if ( $success ) {
			$message = $form['payment_completed'];
			justwpforms_get_payments_integration()->append_response_transaction( $response_id, 'stripe', 'confirmed', $intent_id );
		} else {
			$message = $form['payment_failed'];
			justwpforms_get_payments_integration()->append_response_transaction( $response_id, 'stripe', 'cancelled', $intent_id );
		}

		$notices = array(
			$form_id => array(
				'type' => ( $success ) ? 'success' : 'error',
				'message' => $message
			)
		);

		ob_start();
			justwpforms_the_message_notices( $notices, '' );
		$data = ob_get_clean();

		// Trigger pending submission checks.
		justwpforms_get_message_controller()->process_pending_submission( $response_id );

		wp_send_json_success( $data );
	}

	/**
	 * Process Stripe payment.
	 *
	 * @hooked action `justwpforms_submission_success`
	 *
	 * @param array $submission Submission data.
	 * @param array $form       Form data.
	 * @param array $response   Submission entry data.
	 *
	 * @return void
	 */
	public function do_checkout( $submission, $form, $response ) {
		if ( ! $this->form_has_stripe( $form ) ) {
			return;
		}

		$form_controller = justwpforms_get_form_controller();
		$part = $form_controller->get_first_part_by_type( $form, 'payments' );
		$part_name = justwpforms_get_part_name( $part, $form );
		$value = maybe_unserialize( $response['request'][$part_name] );
		$notices = justwpforms_get_session()->get_messages( $form['ID'] );

		if ( 'stripe' !== $value['payment_method'] ) {
			return;
		}

		if ( ! isset( $value['filled'] ) || 1 !== intval( $value['filled'] ) ) {
			return;
		}

		$form_id = $form['ID'];
		$cookie = $_COOKIE["justwpforms_{$form_id}_stripe_checkout"];

		if ( ! isset( $cookie ) ) {
			return;
		}

		/**
		 * The cookie that's read and parsed here contains details on payment method used for creating
		 * Stripe payment intent and charge.
		 */
		$cookie = json_decode( wp_unslash( $cookie ) );

		if ( ! $cookie->payment_method ) {
			return;
		}

		// This is the ID of Submission entry.
		$this->response_id = $response['ID'];

		justwpforms_get_session()->remove_notice( $form['ID'] );
		justwpforms_get_session()->add_notice( $form['ID'], $form['stripe_processing_hint'] );

		add_filter( 'justwpforms_message_notices_class', function( $class ) {
			$class .= ' justwpforms-stripe-authorization-notices';

			return $class;
		} );

		$amount = (int) $value['price'];
		$currencies = justwpforms_payment_get_currencies( 'stripe' );
		$currency = $part['currency'];

		// Validate that the currency specified is in the list of supported currencies.
		if ( ! isset( $currencies[$currency] ) ) {
			return;
		}

		/**
		 * If currency format is set to `float`, it means we're passing amount of cents (or the smallest entity of currency)
		 * to Stripe API, so we're multiplying amount by 100.
		 */
		if ( 'float' === $currencies[$currency]['format'] ) {
			$amount = (float) $value['price'];
			$amount = $amount * 100;
		}

		if ( 0 == $amount ) {
			return;
		}

		$secret_key = justwpforms_get_integrations()->get_service( 'stripe' )->get_secret_key();
		$description = justwpforms_get_payments_integration()->get_transaction_description( $form, $submission );
		$transaction = array(
			'amount' => $amount,
			'currency' => $part['currency'],
			'description' => substr( $description, 0, 1000 ),
			'payment_method_types' => array( 'card' ),
		);

		// Allow user customization
		$transaction = apply_filters( 'justwpforms_stripe_transaction', $transaction );

		// Create payment intent
		$request = wp_remote_post(
			'https://api.stripe.com/v1/payment_intents',
			array(
				'headers' => array(
					'Authorization' => "Bearer {$secret_key}",
				),
				'body' => $transaction,
			)
		);

		$response = wp_remote_retrieve_body( $request );

		if ( ! $response ) {
			return;
		}

		$response_json = json_decode( $response );

		if ( ! $response_json->id || ! $response_json->client_secret ) {
			return;
		}

		$this->intent = $response_json;

		justwpforms_get_payments_integration()->append_response_transaction( $this->response_id, 'stripe', 'pending', $this->intent->id );

		// Update JSON response with Stripe response data for further processing in JS.
		add_filter( 'justwpforms_json_response', array( $this, 'update_response' ), 10, 3 );
	}

	/**
	 * Update form response data on successful payment with Stripe response.
	 *
	 * @param array $response   Form response data.
	 * @param array $submission Submission data.
	 * @param array $form       Form data.
	 *
	 * @return array Response with Stripe data included.
	 */
	public function update_response( $response, $submission, $form ) {
		$response['stripe'] = array(
			'intent' => array(
				'id' => $this->intent->id,
				'secret' => $this->intent->client_secret
			),
			'response_id' => $this->response_id
		);

		return $response;
	}

	public function meta_messages_fields( $fields ) {
		$fields = array_merge( $fields, $this->get_messages_fields() );

		return $fields;
	}

	public function get_messages_fields() {
		$fields = array(
			'stripe_processing_hint' => array(
				'default' => __( 'Please wait while we process your payment.', 'justwpforms' ),
				'sanitize' => 'sanitize_text_field',
			),
			'stripe_option_label' => array(
				'default' => __( 'Credit card', 'justwpforms' ),
				'sanitize' => 'sanitize_text_field',
			),
			'card_label' => array(
				'default' => __( 'Card', 'justwpforms' ),
				'sanitize' => 'sanitize_text_field',
			),
			'card_number_label' => array(
				'default' => __( 'Number', 'justwpforms' ),
				'sanitize' => 'sanitize_text_field',
			),
			'card_expiry_label' => array(
				'default' => __( 'Expiry', 'justwpforms' ),
				'sanitize' => 'sanitize_text_field',
			),
			'card_cvc_label' => array(
				'default' => __( 'CVV/CVC', 'justwpforms' ),
				'sanitize' => 'sanitize_text_field',
			),
		);

		return $fields;
	}

	public function get_message_controls( $controls ) {
		$message_controls = array(
			6160 => array(
				'type' => 'text',
				'label' => __( 'Stripe is processing payment', 'justwpforms' ),
				'field' => 'stripe_processing_hint',
			),
			6162 => array(
				'type' => 'text',
				'label' => __( 'Stripe payment', 'justwpforms' ),
				'field' => 'stripe_option_label',
			),
			6163 => array(
				'type' => 'text',
				'label' => __( 'Stripe card field', 'justwpforms' ),
				'field' => 'card_label',
			),
			6164 => array(
				'type' => 'text',
				'label' => __( 'Card number', 'justwpforms' ),
				'field' => 'card_number_label',
			),
			6165 => array(
				'type' => 'text',
				'label' => __( 'Card expiration', 'justwpforms' ),
				'field' => 'card_expiry_label',
			),
			6166 => array(
				'type' => 'text',
				'label' => __( 'Card security code', 'justwpforms' ),
				'field' => 'card_cvc_label',
			),
		);

		$controls = justwpforms_safe_array_merge( $controls, $message_controls );

		return $controls;
	}

	public function html_part_data_attributes( $attributes, $part, $form ) {
		if ( 'payments' !== $part['type'] ) {
			return $attributes;
		}

		$attributes['justwpforms-stripe-locale'] = apply_filters( 'justwpforms_payment_stripe_locale', 'en', $form );

		return $attributes;
	}

	public function parse_request() {
		$service = justwpforms_get_integrations()->get_service( 'stripe' );

		if ( isset( $_REQUEST['action'] ) && $service->action_webhook === $_REQUEST['action'] ) {
			$endpoint_secret_key = $service->get_webhook_endpoint_secret_key();
			$payload = @file_get_contents( 'php://input' );
			
			if ( ! $this->verify_webhook_signature( $payload, $endpoint_secret_key ) ) {
				if ( justwpforms_debug_log_enabled() ) {
					$error = new WP_Error( $service->id, __( 'Couldn\'t verify webhook signature' ) );

					justwpforms_log_error( $error );
				}

				return;
			}

			$body = json_decode( $payload, true );
			
			if ( ! isset( $body ['data'] ) ||
				! isset( $body['data']['object'] ) ||
				! isset( $body['data']['object']['id'] ) ) {

				return;
			}

			$transaction_id = 'stripe:' . $body['data']['object']['id'];

			$pending_submission_ids = get_posts( array(
				'post_type' => justwpforms_get_message_controller()->post_type,
				'post_status' => 'pending',
				'posts_per_page' => 1,
				'meta_key' => '_justwpforms_transaction_id',
				'meta_value' => $transaction_id,
				'fields' => 'ids',
			) );

			if ( empty( $pending_submission_ids ) ) {
				return;
			}

			foreach( $pending_submission_ids as $pending_submission_id ) {
				// Trigger pending submission checks.
				justwpforms_get_payments_integration()->append_response_transaction( $pending_submission_id, 'stripe', 'confirmed', $transaction_id );
				justwpforms_get_message_controller()->process_pending_submission( $pending_submission_id );
			}
		}
	}

	private function verify_webhook_signature( $body, $secret_key ) {
		if ( ! isset( $_SERVER[$this->webhook_signature_header] ) ) {
			return false;
		}

		preg_match( 
			'/t=(?<timestamp>[^,]*),v1=(?<signature>[^,]*)/m', 
			$_SERVER[$this->webhook_signature_header], 
			$signature_data
		);

		$signature_data = wp_parse_args( $signature_data, array(
			'timestamp' => '',
			'signature' => '',
		) );

		if ( '' === $signature_data['timestamp'] || '' === $signature_data['signature'] ) {
			return false;
		}

		$timestamp = $signature_data['timestamp'];
		$signed_payload = "{$timestamp}.{$body}";
		$expected_signature = hash_hmac( 'sha256', $signed_payload, $secret_key );

		if ( $expected_signature !== $signature_data['signature'] ) {
			return false;
		}

		return true;
	}

}

if ( ! function_exists( 'justwpforms_get_stripe_integration' ) ):

function justwpforms_get_stripe_integration() {
	return justwpforms_Integration_Stripe::instance();
}

endif;

justwpforms_get_stripe_integration();
