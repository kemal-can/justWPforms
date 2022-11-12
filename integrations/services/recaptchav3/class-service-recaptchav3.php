<?php

class justwpforms_Service_RecaptchaV3 extends justwpforms_Service {

	public $id = 'recaptchav3';
	public $group = 'antispam';

	public $captcha_verify_url = 'https://www.google.com/recaptcha/api/siteverify';
	public $captcha_field = 'g-recaptcha-response';
	public $captcha_action = 'justwpforms_submit';

	public function __construct() {
		$this->label = __( 'v3', 'justwpforms' );
	}

	public function set_credentials( $credentials = array(), $raw = array() ) {
		$this->credentials = array(
			'site' => '',
			'secret' => '',
			'min_score' => '0.5',
		);

		if ( isset( $credentials['site'] ) && ! empty( $credentials['site'] ) ) {
			$this->credentials['site'] = $credentials['site'];
		}

		if ( isset( $credentials['secret'] ) && ! empty( $credentials['secret'] ) ) {
			$this->credentials['secret'] = $credentials['secret'];
		}

		if ( isset( $credentials['min_score'] ) && ! empty( $credentials['min_score'] ) ) {
			$this->credentials['min_score'] = $credentials['min_score'];
		}

		if ( ! empty( $raw ) ) {
			$this->credentials['min_score'] = ( isset( $raw['min_score'] ) ) ? $raw['min_score'] : $credentials['min_score'];
		}
	}

	public function is_connected() {
		$authenticated = (
			! empty( $this->credentials['site'] )
			&& ! empty( $this->credentials['secret'] )
		);

		return $authenticated;
	}

	/**
	 * Returns reCAPTCHA script URL.
	 *
	 * `render` query arg is passed according to the documentation here https://developers.google.com/recaptcha/docs/v3#programmatically_invoke_the_challenge
	 *
	 * @return string reCAPTCHA V3 script URL.
	 */
	public function get_recaptcha_script_url() {
		$recaptcha_url = 'https://www.google.com/recaptcha/api.js';
		$recaptcha_url = add_query_arg( 'render', $this->credentials['site'], $recaptcha_url );

		return $recaptcha_url;
	}

	public function get_frontend_script_url() {
		return justwpforms_get_plugin_url() . 'integrations/services/recaptchav3/frontend.js';
	}

	public function admin_widget( $previous_credentials = array() ) {
		require_once( justwpforms_get_integrations_folder() . '/services/recaptchav3/partial-widget.php' );
	}

	public function configure() {
		$this->load();
	}

	public function load() {
		$antispam = justwpforms_get_integrations()->get_service( 'antispam' );

		if ( ! $antispam->get_active_service()->is_connected() ) {
			return;
		}

		// Make sure we don't load integration unless the active service is reCAPTCHA V3.
		if ( $antispam->get_active_service() && $this->id === $antispam->get_active_service()->id ) {
			require_once( justwpforms_get_integrations_folder() . '/services/recaptchav3/class-integration-recaptchav3.php' );
		}
	}

	/**
	 * Validate form submission.
	 *
	 * Makes request to reCAPTCHA endpoint and handles the response. In reCAPTCHA V3, the response contains a score
	 * which we then need to compare with whatever the `min_score` is in service settings (set by user).
	 *
	 * reCAPTCHA client responds with the hash that we pass to this method using a hidden input field. That's what we
	 * then pass to reCAPTCHA servers for evaluation. This method is executed on form submission, so all form fields
	 * are available in $_REQUEST.
	 *
	 * @param array $form Form data.
	 *
	 * @return string|WP_Error reCAPTCHA hashed value on success, WP_Error on failure.
	 */
	public function validate_submission( $form ) {
		$secret_key = $form['captcha_secret_key'];
		$captcha_value = isset ( $_REQUEST[$this->captcha_field] ) ? $_REQUEST[$this->captcha_field] : '';
		$captcha_value = sanitize_text_field( $captcha_value );
		$request_body = array(
			'secret' => $secret_key,
			'response' => $captcha_value,
		);

		$request = wp_remote_post( $this->captcha_verify_url, array( 'body' => $request_body ) );
		$response = wp_remote_retrieve_body( $request );

		if ( empty( $response ) ) {
			return new WP_Error( 'captcha', 'captcha_invalid_configuration' );
		}

		$response = json_decode( $response, true );

		if ( ! $response['success'] ) {
			$configuration_errors = array_intersect( array(
				'missing-input-secret', 'invalid-input-secret', 'bad-request'
			), $response['error-codes'] );

			$value_errors = array_intersect( array(
				'missing-input-response', 'invalid-input-response'
			), $response['error-codes'] );

			if ( count( $configuration_errors ) > 0 ) {
				return new WP_Error( 'captcha', 'captcha_invalid_configuration' );
			} else if ( count( $value_errors ) > 0 ) {
				return new WP_Error( 'captcha', 'captcha_not_verified' );
			}
		} else {
			if ( ! isset( $response['action'] ) || $response['action'] !== $this->captcha_action ) {
				return new WP_Error( 'captcha', 'captcha_invalid_action' );
			}

			// Compare the returned score with `min_score` set by a user.
			if ( ! isset( $response['score'] ) || (float) $response['score'] < (float) $this->credentials['min_score'] ) {
				return new WP_Error( 'captcha', 'captcha_insufficient_score' );
			}
		}

		return $captcha_value;
	}

}
