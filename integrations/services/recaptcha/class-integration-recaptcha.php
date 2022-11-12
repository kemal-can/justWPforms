<?php

class justwpforms_Compat_Recaptcha {

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
		$this->service = justwpforms_get_integrations()->get_service( 'recaptcha' );

		add_filter( 'justwpforms_form_has_captcha', array( $this, 'has_captcha' ), 10, 2 );
		add_action( 'justwpforms_parts_after', 'justwpforms_recaptcha' );
		add_filter( 'justwpforms_setup_controls', array( $this, 'setup_controls' ) );
		add_filter( 'justwpforms_style_fields', array( $this, 'get_style_fields' ) );
		add_filter( 'justwpforms_style_controls', array( $this, 'get_style_controls' ) );
	}

	public function has_captcha( $has_captcha, $form ) {
		$has_captcha = $form['captcha'] || justwpforms_is_preview();

		return $has_captcha;
	}

	public function setup_controls( $controls ) {
		$setup_controls = array(
			1501 => array(
				'type' => 'group_start',
				'trigger' => 'captcha'
			),
			1502 => array(
				'type' => 'text',
				'label' => __( 'Label', 'justwpforms' ),
				'field' => 'captcha_label',
				'autocomplete' => 'off',
			),
			1503 => array(
				'type' => 'group_end'
			),
		);

		$controls = justwpforms_safe_array_merge( $controls, $setup_controls );

		return $controls;
	}

	public function get_style_fields( $fields ) {
		$fields['captcha_theme'] = array(
			'default' => 'light',
			'options' => array(
				'light' => __( 'Light color', 'justwpforms' ),
				'dark' => __( 'Dark color', 'justwpforms' )
			),
			'sanitize' => 'sanitize_text_field',
			'target' => 'recaptcha'
		);

		return $fields;
	}

	public function get_style_controls( $controls ) {
		$style_controls = array(
			511 => array(
				'type' => 'buttonset',
				'label' => __( 'reCAPTCHA theme', 'justwpforms' ),
				'field' => 'captcha_theme'
			),
		);

		$controls = justwpforms_safe_array_merge( $controls, $style_controls );

		return $controls;
	}

}

if ( ! function_exists( 'justwpforms_get_integration_recaptcha' ) ):

function justwpforms_get_integration_recaptcha() {
	return justwpforms_Compat_Recaptcha::instance();
}

endif;

justwpforms_get_integration_recaptcha();
