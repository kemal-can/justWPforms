<?php

class justwpforms_Service_AntiSpam extends justwpforms_Service {

	public $id = 'antispam';
	public $supports_multiple = false;
	public $active_service_option_name = '';
	public $active_service = false;

	public function __construct() {
		$this->label = __( 'Anti-Spam', 'justwpforms' );
		$this->active_service_option_name = "_justwpforms_{$this->id}_service_active";
	}

	public function set_active_service( $service_id ) {
		update_option( $this->active_service_option_name, $service_id );
	}

	public function get_active_service() {
		$service = get_option( $this->active_service_option_name, 'recaptchav3' );

		if ( empty( $service ) ) {
			$service = 'recaptchav3';
		}

		if ( ! empty( $service ) ) {
			$service = justwpforms_get_integrations()->get_service( $service );
		}

		return $service;
	}

	public function reset_active_service() {
		update_option( $this->active_service_option_name, '' );
	}

	public function configure() {
		$this->active_service = $this->get_active_service();
		$this->load();
	}

	public function load() {
		$active_service = null;

		/**
		 * Back compatibility code to handle the cases from when:
		 *
		 * - We only had reCAPTCHA V2 available
		 * - We had both reCAPTCHA versions available but instead of a dropdown to pick the service, there was a checkbox.
		 *
		 * It sets V2 as active when 'enabled' flag is missing but site key and secret key is not empty
		 * (handling the first situation).
		 *
		 * It also sets reCAPTCHA V2 as an active service if 'enabled' flag is set to 1
		 * (checkbox, handling the second situation).
		 */
		if ( false === $this->active_service ) {
			$recaptcha_v2_service     = justwpforms_get_integrations()->get_service( 'recaptcha' );
			$recaptcha_v2_credentials = $recaptcha_v2_service->get_credentials();

			if ( isset( $recaptcha_v2_credentials['enabled'] ) ) {
				/**
				 * If there is `enabled` flag and it's set to 1, set active service to reCAPTCHA V2.
				 */
				if ( 1 === (int) $recaptcha_v2_credentials['enabled'] ) {
					$active_service = 'recaptcha';
				}

				/**
				 * If `enabled` flag is empty but keys are set, it means we're coming from earlier version without
				 * a checkbox to enable service or dropdown to select multiple services. In this case, enable
				 * reCAPTCHA V2.
				 */
				if ( '' === $recaptcha_v2_credentials['enabled'] && ! empty( $recaptcha_v2_credentials['site'] ) && ! empty( $recaptcha_v2_credentials['secret'] ) ) {
					$active_service = 'recaptcha';
				}

				/**
				 * Unset `enabled` flag as it's not needed anymore and write credentials so this check
				 * only happens once.
				 */
				unset( $recaptcha_v2_credentials['enabled'] );
				$recaptcha_v2_service->set_credentials( $recaptcha_v2_credentials );

				justwpforms_get_integrations()->write_credentials();
			}

			if ( ! is_null( $active_service ) ) {
				$this->set_active_service( $active_service );
				$this->active_service = $this->get_active_service();
			}
		}

		require_once( justwpforms_get_integrations_folder() . '/services/antispam/class-integration-antispam.php' );

		// Load active service
		if ( $this->active_service->is_connected() ) {
			$this->active_service->load();
		}
	}

}
