<?php
class justwpforms_Integration_AntiSpam {

	private static $instance;
	private static $hooked = false;
	public $service = '';

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		self::$instance->hook();

		return self::$instance;
	}

	public function __construct() {
		$this->service = justwpforms_get_integrations()->get_service( 'antispam' );
	}

	public function hook() {
		if ( self::$hooked ) {
			return;
		}

		self::$hooked = true;

		add_filter( 'justwpforms_get_form_data', array( $this, 'get_form_data' ) );
		add_filter( 'justwpforms_frontend_dependencies', array( $this, 'script_dependencies' ), 10, 2 );
		add_filter( 'justwpforms_frontend_settings', array( $this, 'frontend_settings' ) );
		add_action( 'justwpforms_customize_enqueue_scripts', array( $this, 'customize_enqueue_scripts' ) );

		if ( $this->service->get_active_service()->is_connected() ) {
			add_filter( 'justwpforms_setup_controls', array( $this, 'setup_controls' ) );
		}
	}

	public function setup_controls( $controls ) {
		$setup_controls = array(
			1500 => array(
				'type' => 'checkbox',
				'label' => __( 'Use reCAPTCHA', 'justwpforms' ),
				'field' => 'captcha',
			),
		);

		$controls = justwpforms_safe_array_merge( $controls, $setup_controls );

		return $controls;
	}

	/**
	 * Add site key and secret key to form data if there is active service connected.
	 *
	 * @hooked filter `justwpforms_get_form_data`
	 *
	 * @param array $form Form data.
	 *
	 * @return array Form data with site key and secret key.
	 */
	public function get_form_data( $form ) {
		$active_service = $this->service->get_active_service();

		if ( $active_service && $active_service->is_connected() ) {
			$credentials = $active_service->get_credentials();
			$form['captcha_site_key'] = $credentials['site'];
			$form['captcha_secret_key'] = $credentials['secret'];
		}

		return $form;
	}

	public function script_dependencies( $deps, $forms ) {
		$has_captcha = false;

		foreach ( $forms as $form ) {
			if ( $form['captcha'] ) {
				$has_captcha = true;
				break;
			}
		}

		if ( ! justwpforms_is_preview() && ! $has_captcha ) {
			return $deps;
		}

		$active_service = $this->service->get_active_service();

		if ( ! $active_service->is_connected() ) {
			return $deps;
		}

		$frontend_script_url = $active_service->get_frontend_script_url();

		if ( ! empty( $frontend_script_url ) ) {
			wp_register_script(
				'recaptcha',
				$frontend_script_url,
				array(), false, true
			);

			$deps[] = 'recaptcha';
		}

		return $deps;
	}

	public function frontend_settings( $settings ) {
		$active_service = $this->service->get_active_service();

		if ( ! $active_service->is_connected() ) {
			return $settings;
		}

		$settings['googleRecaptcha'] = array(
			'libraryURL' => $active_service->get_recaptcha_script_url(),
		);

		return $settings;
	}

	public function customize_enqueue_scripts( $deps ) {
		wp_enqueue_script(
			'justwpforms-antispam',
			justwpforms_get_plugin_url() . 'integrations/services/antispam/customize.js',
			$deps, justwpforms_get_version(), true
		);
	}

	public function get_active_service() {
		return $this->service->active_service;
	}

	public function validate_submission( $form ) {
		if ( ! $this->get_active_service() ) {
			return;
		}

		return $this->get_active_service()->validate_submission( $form );
	}

}

if ( ! function_exists( 'justwpforms_get_antispam_integration' ) ):

function justwpforms_get_antispam_integration() {
	$instance = justwpforms_Integration_AntiSpam::instance();
	$instance->hook();

	return $instance;
}

endif;

justwpforms_get_antispam_integration();
