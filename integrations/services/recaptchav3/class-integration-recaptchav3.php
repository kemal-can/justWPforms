<?php

class justwpforms_Integration_RecaptchaV3 {

	private static $instance;

	private $service;

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		self::$instance->hook();

		return self::$instance;
	}

	public function __construct() {
		require_once( justwpforms_get_integrations_folder() . '/services/antispam/helpers-recaptcha.php' );
	}

	public function hook() {
		$this->service = justwpforms_get_integrations()->get_service( 'recaptchav3' );

		add_filter( 'justwpforms_form_has_captcha', array( $this, 'has_captcha' ), 10, 2 );
		add_action( 'justwpforms_parts_after', 'justwpforms_recaptcha' );
	}

	public function has_captcha( $has_captcha, $form ) {
		$has_captcha = $form['captcha'] || justwpforms_is_preview();

		return $has_captcha;
	}

}

if ( ! function_exists( 'justwpforms_get_integration_recaptchav3' ) ):

function justwpforms_get_integration_recaptchav3() {
	return justwpforms_Integration_RecaptchaV3::instance();
}

endif;

justwpforms_get_integration_recaptchav3();
