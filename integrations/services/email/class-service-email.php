<?php

class justwpforms_Service_Email extends justwpforms_Service {

	private static $instance;
	private static $hooked = false;

	public $id = 'email';
	public $active_service_option_name = '';
	public $active_service = false;

	public function __construct() {
		$this->label = __( 'Email', 'justwpforms' );
		$this->active_service_option_name = "_justwpforms_{$this->id}_service_active";
	}

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function hook() {
		if ( self::$hooked ) {
			return;
		}

		self::$hooked = true;
	}

	public function set_active_service( $service_id ) {
		update_option( $this->active_service_option_name, $service_id );
	}

	public function get_active_service() {
		$service = get_option( $this->active_service_option_name, false );

		if ( ! empty( $service ) ) {
			$service = justwpforms_get_integrations()->get_service( $service );
		}

		return $service;
	}

}

if ( ! function_exists( 'justwpforms_deprecated_email_service' ) ):

function justwpforms_deprecated_email_service() {
	$instance = justwpforms_Service_Email::instance();
	$instance->hook();

	return $instance;
}

endif;

justwpforms_deprecated_email_service();
