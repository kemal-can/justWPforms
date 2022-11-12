<?php

class justwpforms_Part_Payments extends justwpforms_Form_Part {

	public $type = 'payments';
	public $template_id = 'justwpforms-payments-template';

	public function __construct() {
		$this->label = __( 'Payment', 'justwpforms' );
		$this->description = __( 'For processing payments using your favorite services.', 'justwpforms' );

		add_filter( 'justwpforms_frontend_dependencies', array( $this, 'script_dependencies' ), 10, 2 );
		add_filter( 'justwpforms_frontend_settings', array( $this, 'frontend_settings' ) );
		add_filter( 'justwpforms_style_dependencies', array( $this, 'style_dependencies' ), 10, 2 );
		add_filter( 'justwpforms_part_attributes', array( $this, 'html_part_attributes' ), 10, 4 );
		add_filter( 'justwpforms_part_data_attributes', array( $this, 'part_data_attributes' ), 10, 3 );
	}

	public function get_customize_fields() {
		$fields = array(
			'type' => array(
				'default' => $this->type,
				'sanitize' => 'sanitize_text_field',
			),
			'label' => array(
				'default' => __( '', 'justwpforms' ),
				'sanitize' => 'sanitize_text_field'
			),
			'label_placement' => array(
				'default' => 'show',
				'sanitize' => 'sanitize_text_field'
			),
			'description' => array(
				'default' => '',
				'sanitize' => 'sanitize_text_field'
			),
			'description_mode' => array(
				'default' => '',
				'sanitize' => 'sanitize_text_field'
			),
			'currency' => array(
				'default' => 'usd',
				'sanitize' => array(
					'justwpforms_sanitize_choice',
					array_keys( justwpforms_payment_get_currencies() ),
				),
			),
			'price' => array(
				'default' => 10,
				'sanitize' => 'sanitize_text_field'
			),
			'show_user_price_field' => array(
				'default' => 0,
				'sanitize' => 'justwpforms_sanitize_checkbox'
			),
			'user_price_min' => array(
				'default' => 1,
				'sanitize' => 'sanitize_text_field'
			),
			'user_price_placeholder' => array(
				'default' => '',
				'sanitize' => 'sanitize_text_field'
			),
			'user_price_step' => array(
				'default' => 1,
				'sanitize' => 'floatval'
			),
			'accept_coupons' => array(
				'default' => '',
				'sanitize' => 'justwpforms_sanitize_checkbox'
			),
			'width' => array(
				'default' => 'full',
				'sanitize' => 'sanitize_key'
			),
			'css_class' => array(
				'default' => '',
				'sanitize' => 'sanitize_text_field'
			),
			'required' => array(
				'default' => 1,
				'sanitize' => 'justwpforms_sanitize_checkbox',
			),
		);

		return justwpforms_get_part_customize_fields( $fields, $this->type );
	}

	public function customize_templates() {
		$template_path = justwpforms_get_integrations_folder() . '/services/payments/templates/customize-payments.php';
		$template_path = justwpforms_get_part_customize_template_path( $template_path, $this->type );

		require_once( $template_path );
	}

	public function frontend_template( $part = array(), $form = array() ) {
		$part = wp_parse_args( $part, $this->get_customize_defaults() );

		include( justwpforms_get_integrations_folder() . '/services/payments/templates/frontend-payments.php' );
	}

	public function get_default_value( $part = array() ) {
		$defaults = array(
			'payment_method' => '',
			'price' => 0,
			'currency' => '',
		);

		if ( isset( $part['currency'] ) ) {
			$defaults['currency'] = $part['currency'];
		}

		if ( isset( $part['show_user_price_field'] ) && justwpforms_is_truthy( $part['show_user_price_field'] ) ) {
			$defaults['price'] = $part['user_price_min'];
		}

		return $defaults;
	}

	/**
	 * Sanitize submitted value before storing it.
	 *
	 * @since 1.0.0.
	 *
	 * @param array $part Form part data.
	 *
	 * @return string
	 */
	public function sanitize_value( $part = array(), $form = array(), $request = array() ) {
		$sanitized_value = $this->get_default_value( $part );
		$part_name = justwpforms_get_part_name( $part, $form );

		if ( isset( $request[$part_name] ) ) {
			$sanitized_value = wp_parse_args( $request[$part_name], $sanitized_value );

			if ( justwpforms_is_falsy( $part['show_user_price_field'] ) && justwpforms_is_falsy( $part['accept_coupons'] ) ) {
				$sanitized_value['price'] = $part['price'];
			} else {
				$currency_key = $part['currency'];
				$currencies = justwpforms_payment_get_currencies();
				$currency = $currencies[$currency_key];
				$format = $currency['format'];
				$price = $sanitized_value['price'];

				if ( 'integer' === $format ) {
					$price = intval( $price );
				} else {
					$price = floor( floatval( $price ) * 100 ) / 100;
				}

				$sanitized_value['price'] = $price;
			}
		}

		return $sanitized_value;
	}

	/**
	 * Validate value before submitting it. If it fails validation, return WP_Error object, showing respective error message.
	 *
	 * @since 1.0.0.
	 *
	 * @param array $part Form part data.
	 * @param string $value Submitted value.
	 *
	 * @return string|object
	 */
	public function validate_value( $value, $part = array(), $form = array() ) {
		$validated_value = $value;

		if ( 1 == $part['required'] ) {
			if ( empty( $validated_value['payment_method'] ) ) {
				
				return new WP_Error( 'error', justwpforms_get_validation_message( 'field_empty' ) );
			}

			if ( 'stripe' === $validated_value['payment_method'] &&
				( ! isset( $validated_value['filled'] ) || 0 === intval( $validated_value['filled'] ) ) ) {
				
				return new WP_Error( 'error', justwpforms_get_validation_message( 'field_empty' ) );
			}
		} else {
			if ( 'stripe' === $validated_value['payment_method'] &&
				isset( $validated_value['filled'] ) &&
				'0' === $validated_value['filled'] ) {
				
				return new WP_Error( 'error', justwpforms_get_validation_message( 'field_empty' ) );
			}
		}

		if ( 1 == $part['show_user_price_field'] ) {
			$currency_key = $part['currency'];
			$currencies = justwpforms_payment_get_currencies();
			$currency = $currencies[$currency_key];
			$format = $currency['format'];
			$price = $value['price'];
			$min_price = $part['user_price_min'];

			if ( ( 'integer' === $format && intval( $price ) < intval( $min_price ) ) || floatval( $price ) < floatval( $min_price ) ) {
				return new WP_Error( 'error', justwpforms_get_validation_message( 'amount_too_low' ) );
			}
		}

		return $validated_value;
	}

	public function get_part_value( $value, $part, $form ) {
		if ( $this->type === $part['type']
			&& ( 'review' !== justwpforms_get_current_step( $form ) ) ) {

			$value = '';
		}

		return $value;
    }

	/**
	 * Enqueue scripts in customizer area.
	 *
	 * @since 1.0.0.
	 *
	 * @param array	List of dependencies.
	 *
	 * @return void
	 */
	public function customize_enqueue_scripts( $deps = array() ) {
		wp_enqueue_script(
			'part-payments',
			justwpforms_get_plugin_url() . 'integrations/services/payments/assets/js/customize-payments.js',
			$deps, justwpforms_get_version(), true
		);

		$currencies = justwpforms_payment_get_currencies();

		wp_localize_script(
			'part-payments',
			'_justwpformsPaymentsPartSettings',
			array(
				'currencies' => $currencies
			)
		);
	}

	public function html_part_attributes( $attributes, $part, $form, $component ) {
		if ( $this->type !== $part['type'] ) {
			return $attributes;
		}

		if ( 'price' === $component ) {
			$currencies = justwpforms_payment_get_currencies();
			$currency_key = $part['currency'];

			if ( ! isset( $currencies[$currency_key] ) ) {
				return $attributes;
			}

			$currency = $currencies[$currency_key];
			$format = $currency['format'];
			$step = ( 'float' === $format ) ? '0.01' : '1';
			$attributes[] = "step=\"$step\"";
		}

		return $attributes;
	}

	public function part_data_attributes( $attributes, $part, $form ) {
		if ( $this->type !== $part['type'] ) {
			return $attributes;
		}

		if ( justwpforms_payment_part_has_credit_card( $part, $form ) ) {
			$attributes['justwpforms-has-stripe'] = '';
		}

		if ( justwpforms_payment_part_has_paypal( $part, $form ) ) {
			$attributes['justwpforms-has-paypal'] = '';
		}

		return $attributes;
	}

	public function script_dependencies( $deps, $forms ) {
		$contains_payments = false;
		$form_controller = justwpforms_get_form_controller();

		foreach ( $forms as $form ) {
			if ( $form_controller->get_first_part_by_type( $form, $this->type ) ) {
				$contains_payments = true;
				break;
			}
		}

		if ( ! justwpforms_is_preview() && ! $contains_payments ) {
			return $deps;
		}

		wp_register_script(
			'justwpforms-part-payments',
			justwpforms_get_plugin_url() . 'integrations/services/payments/assets/js/payments.js',
			array(), justwpforms_get_version(), true
		);

		$deps[] = 'justwpforms-part-payments';

		return $deps;
	}

	public function frontend_settings( $settings ) {
		$integrations = justwpforms_get_integrations();
		$paypal = $integrations->get_service( 'paypal' );
		$stripe = $integrations->get_service( 'stripe' );

		$settings['payments'] = array(
			'stripe' => $stripe->is_connected(),
			'paypal' => $paypal->is_connected(),
			'ajaxurl' => admin_url( 'admin-ajax.php' )
		);

		return $settings;
	}

	public function style_dependencies( $deps, $forms ) {
		$contains_payments = false;
		$form_controller = justwpforms_get_form_controller();

		foreach ( $forms as $form ) {
			if ( $form_controller->get_first_part_by_type( $form, $this->type ) ) {
				$contains_payments = true;
				break;
			}
		}

		if ( ! justwpforms_is_preview() && ! $contains_payments ) {
			return $deps;
		}

		wp_register_style(
			'justwpforms-payments',
			justwpforms_get_plugin_url() . 'integrations/services/payments/assets/css/payments.css',
			array(), justwpforms_get_version()
		);

		$deps[] = 'justwpforms-payments';

		return $deps;
	}

}
