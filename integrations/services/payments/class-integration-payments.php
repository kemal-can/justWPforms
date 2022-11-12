<?php
class justwpforms_Integration_Payments {

	private static $instance;

	private $frontend_styles = false;

	public $transaction_id_meta = 'transaction_id';
	public $transaction_details_meta = 'transaction_details';

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		self::$instance->hook();

		return self::$instance;
	}

	public function hook() {
		require_once( justwpforms_get_integrations_folder() . '/services/payments/helper-payments.php' );
		require_once( justwpforms_get_integrations_folder() . '/services/payments/class-part-payments.php' );

		require_once( justwpforms_get_integrations_folder() . '/services/payments/class-coupon-controller.php' );
		require_once( justwpforms_get_integrations_folder() . '/services/payments/class-coupon-admin.php' );

		justwpforms_get_part_library()->register_part( 'justwpforms_Part_Payments', 22 );

		add_filter( 'justwpforms_message_part_value', array( $this, 'message_part_value' ), 10, 4 );
		add_filter( 'justwpforms_email_part_value', array( $this, 'email_part_value' ), 10, 5 );
		add_filter( 'justwpforms_stringify_part_value', array( $this, 'stringify_part_value' ), 10, 3 );
		add_filter( 'justwpforms_default_validation_messages', array( $this, 'add_payment_validation_messages' ), 20 );
		add_filter( 'justwpforms_messages_fields', array( $this, 'meta_messages_fields' ) );
		add_filter( 'justwpforms_messages_controls', array( $this, 'get_message_controls' ) );
		add_action( 'justwpforms_payment_the_transaction_details', array( $this, 'do_paypal_transaction_details' ), 10, 2 );
		add_action( 'justwpforms_payment_the_transaction_details', array( $this, 'do_stripe_transaction_details' ), 10, 2 );
		add_filter( 'justwpforms_get_csv_value', array( $this, 'get_csv_value' ), 10, 3 );

		$payment_details_parts = $this->get_details_supported_parts();

		foreach ( $payment_details_parts as $part ) {
			add_filter( "justwpforms_part_customize_fields_{$part}", array( $this, 'add_part_fields' ) );
			add_action( "justwpforms_part_customize_{$part}_before_advanced_options", array( $this, 'add_part_controls' ) );
		}

		add_filter( 'justwpforms_submission_is_pending', array( $this, 'submission_is_pending' ), 10, 3 );
		add_filter( 'justwpforms_pending_submission_succeeded', array( $this, 'pending_submission_succeeded' ), 10, 2 );
	}

	public function append_response_transaction( $response_id, $gateway, $status = 'pending', $transaction_id = '', $transaction_details = array() ) {
		$transaction_details = wp_parse_args( $transaction_details, array(
			'gateway' => $gateway,
			'status' => $status,
			'transaction_id' => $transaction_id,
		) );
		$transaction_id = "{$gateway}:{$transaction_id}";

		justwpforms_update_meta( $response_id, $this->transaction_id_meta, $transaction_id );
		justwpforms_update_meta( $response_id, $this->transaction_details_meta, $transaction_details );
	}

	public function get_response_transaction( $response_id ) {
		$transaction_details = justwpforms_get_meta( $response_id, $this->transaction_details_meta, true );

		return $transaction_details;
	}

	public function stringify_part_value( $value, $part, $form ) {
		if ( 'payments' !== $part['type'] ) {
			return $value;
		}

		if ( empty( $value['payment_method'] ) ) {
			$value = __( '', 'justwpforms' );
			return $value;
		}

		$currencies = justwpforms_payment_get_currencies();
		$currency = $currencies[$value['currency']];
		$currency_format = $currency['format'];
		$currency_symbol = $currency['symbol'];
		$price = floatval( $value['price'] );
		$decimals = 'float' === $currency_format ? 2 : 0;
		$gateway = $value['payment_method'];

		$service = justwpforms_get_integrations()->get_service( $gateway );
		$gateway_label = $service->label;
		$price_value = number_format( $price, $decimals );
		$paid_through = "&rarr;";
		$string = "{$currency_symbol}{$price_value} {$paid_through} {$gateway_label}";

		$string = apply_filters( 'justwpforms_get_currency_value', $string, $value, $value['currency'] );

		return $string;
	}

	public function message_part_value( $value, $original_value, $part, $context ) {
		if ( 'payments' !== $part['type'] ) {
			return $value;
		}

		if ( 'N/A' === $value ) {
			$value = '';
			
			return $value;
		}

		$value = htmlspecialchars_decode( $value );

		$details = '';

		if ( 'admin-column' === $context || 'admin-edit' === $context ) {
			global $post;

			$details = justwpforms_payment_get_formatted_details( $post->ID, $context );
		} else if ( 'csv' === $context ) {
			global $justwpforms_submission;

			$details = justwpforms_payment_get_formatted_details( $justwpforms_submission['ID'], $context );
		} else {
			return $value;
		}

		if ( '' === $details ) {
			$value = '';
			
			return $value;
		}



		$value = $value . $details;

		return $value;
	}

	public function email_part_value( $value, $message, $part, $form, $context ) {
		if ( 'payments' !== $part['type'] ) {
			return $value;
		}

		$response_id = $message['ID'];
		$details = justwpforms_payment_get_formatted_details( $response_id, $context );

		if ( $details ) {
			$value = $value . $details;
		} else {
			$value = '';
		}

		return $value;
	}

	public function add_payment_validation_messages( $messages ) {
		$validation_messages = wp_list_pluck( $this->get_messages_fields(), 'default' );
		$messages = array_merge( $messages, $validation_messages );

		return $messages;
	}

	public function add_part_fields( $fields ) {
		$fields['add_to_payment_details'] = array(
			'default' => 0,
			'sanitize' => 'justwpforms_sanitize_checkbox'
		);

		return $fields;
	}

	public function get_details_supported_parts() {
		return array(
			'single_line_text',
			'email',
			'website_url',
			'radio',
			'checkbox',
			'select',
			'number',
			'phone',
			'date',
			'title',
			'address',
		);
	}

	public function add_part_controls() {
		require( justwpforms_get_integrations_folder() . '/services/payments/templates/partial-part-controls.php' );
	}

	public function get_transaction_description( $form, $submission ) {
		$description = array();

		foreach ( $form['parts'] as $part ) {
			if ( ! isset( $part['add_to_payment_details'] ) || 0 === (int) $part['add_to_payment_details'] ) {
				continue;
			}

			$part_id = $part['id'];
			$part_label = justwpforms_get_part_label( $part );
			$description[] = "{$part_label}: {$submission[$part_id]}";
		}

		$description = implode( ', ', $description );

		return $description;
	}

	public function get_messages_fields() {
		$fields = array(
			'amount_too_low' => array(
				'default' => __( "This price isn't high enough.", 'justwpforms' ),
				'sanitize' => 'sanitize_text_field',
			),
			'payment_completed' => array(
				'default' => __( 'Thank you! Your payment was successful.', 'justwpforms' ),
				'sanitize' => 'sanitize_text_field',
			),
			'payment_failed' => array(
				'default' => __( 'Payment failed.', 'justwpforms' ),
				'sanitize' => 'sanitize_text_field',
			),
			'payment_cancelled' => array(
				'default' => __( 'Payment canceled.', 'justwpforms' ),
				'sanitize' => 'sanitize_text_field',
			),
			'payment_method_choice_label' => array(
				'default' => __( 'Choose a payment method', 'justwpforms' ),
				'sanitize' => 'sanitize_text_field',
			),
			'user_price_label' => array(
				'default' => __( "Name a price", 'justwpforms' ),
				'sanitize' => 'sanitize_text_field',
			),
		);

		return $fields;
	}

	public function meta_messages_fields( $fields ) {
		$fields = array_merge( $fields, $this->get_messages_fields() );

		return $fields;
	}

	public function get_message_controls( $controls ) {
		$message_controls = array(
			4340 => array(
				'type' => 'text',
				'label' => __( 'Price is too low', 'justwpforms' ),
				'field' => 'amount_too_low',
			),
			200 => array(
				'type' => 'text',
				'label' => __( 'Payment completed', 'justwpforms' ),
				'field' => 'payment_completed',
			),
			201 => array(
				'type' => 'text',
				'label' => __( 'Payment failed', 'justwpforms' ),
				'field' => 'payment_failed',
			),
			202 => array(
				'type' => 'text',
				'label' => __( 'Payment cancelled', 'justwpforms' ),
				'field' => 'payment_cancelled',
			),
			6135 => array(
				'type' => 'text',
				'label' => __( 'Payment method', 'justwpforms' ),
				'field' => 'payment_method_choice_label',
			),
			6136 => array(
				'type' => 'text',
				'label' => __( 'Pay what you want', 'justwpforms' ),
				'field' => 'user_price_label',
			),
		);

		$controls = justwpforms_safe_array_merge( $controls, $message_controls );

		return $controls;
	}

	public function do_paypal_transaction_details( $details, $context ) {
		$gateway = $details['gateway'];
		$status = $details['status'];
		$transaction_id = $details['transaction_id'];

		if ( 'paypal' !== $gateway ) {
			return;
		}

		if ( 'confirmed' === $status || 'completed' === $status ) : ?>

			<?php
			$detail_url = 'https://www.paypal.com/activity/payment';

			if ( isset( $details['sandbox'] ) && justwpforms_is_truthy( $details['sandbox'] ) ) {
				$detail_url = 'https://www.sandbox.paypal.com/activity/payment';
			}

			$detail_url = "{$detail_url}/$transaction_id";
			?>

			<?php if ( 'admin-column' === $context || 'admin-edit' === $context ) : ?>
				(<?php _e( 'completed', 'justwpforms' ); ?>, <a href="<?php echo $detail_url; ?>" target="_blank"><?php _e( 'see details', 'justwpforms' ); ?><?php justwpforms_the_external_link_icon(); ?></a>)
			<?php elseif ( 'admin-email' === $context ) : ?>
				(<?php _e( 'completed', 'justwpforms' ); ?>, <a href="<?php echo $detail_url; ?>" target="_blank"><?php _e( 'see details', 'justwpforms' ); ?></a>)
			<?php elseif ( 'user-email' === $context ) : ?>
				(<?php _e( 'completed', 'justwpforms' ); ?>)
			<?php elseif ( 'csv' === $context ) : ?>
				(<?php _e( 'completed', 'justwpforms' ); ?>, <?php echo $detail_url; ?>)
			<?php endif; ?>

		<?php else: ?>

			(<?php _e( 'canceled', 'justwpforms' ); ?>)
		
		<?php endif;
	}

	public function do_stripe_transaction_details( $details, $context ) {
		$gateway = $details['gateway'];
		$status = $details['status'];
		
		if ( 'stripe' !== $gateway ) {
			return;
		}
		
		if ( 'confirmed' === $status || 'completed' === $status ) : ?>
			
			<?php $transaction_id = $details['transaction_id']; ?>

			<?php if ( 'admin-column' === $context || 'admin-edit' === $context ) : ?>
				(<?php _e( 'completed', 'justwpforms' ); ?>, <a href="https://dashboard.stripe.com/payments/<?php echo $transaction_id; ?>" target="_blank"><?php _e( 'see details', 'justwpforms' ); ?><?php justwpforms_the_external_link_icon(); ?></a>)
			<?php elseif ( 'admin-email' === $context ) : ?>
				(<?php _e( 'completed', 'justwpforms' ); ?>, <a href="https://dashboard.stripe.com/payments/<?php echo $transaction_id; ?>" target="_blank"><?php _e( 'see details', 'justwpforms' ); ?></a>)
			<?php elseif ( 'user-email' === $context ) : ?>
				(<?php _e( 'completed', 'justwpforms' ); ?>)
			<?php elseif ( 'csv' === $context ) : ?>
				(<?php _e( 'completed', 'justwpforms' ); ?>, https://dashboard.stripe.com/payments/<?php echo $transaction_id; ?>)
			<?php endif; ?>
		
		<?php else: ?>
			
			(<?php _e( 'canceled', 'justwpforms' ); ?>)
		
		<?php endif;
	}

	public function get_csv_value( $value, $message, $part ) {
		if ( 'payments' !== $part['type'] ) {
			return $value;
		}

		$value = str_replace( '&rarr;', __( 'through', 'justwpforms' ), $value );

		return $value;
	}

	public function submission_is_pending( $is_pending, $request, $form ) {
		$form = justwpforms_get_conditional_controller()->get( $form, $request );
		$part = justwpforms_get_form_controller()->get_first_part_by_type( $form, 'payments' );

		if ( ! $part ) {
			return false;
		}

		if ( 1 == $part['required'] ) {
			return true;
		}

		$part_class = justwpforms_get_part_library()->get_part( $part['type'] );
		$part_name = justwpforms_get_part_name( $part, $form );
		$sanitized_value = $part_class->sanitize_value( $part, $form, $request );
		$validated_value = $part_class->validate_value( $sanitized_value, $part, $form );

		if ( is_wp_error( $validated_value ) ) {
			return false;
		}

		if ( ! empty( $validated_value['payment_method'] ) ) {
			if ( 'stripe' === $validated_value['payment_method'] &&
				isset( $validated_value['filled'] ) &&
				'' !== $validated_value['filled'] ) {

				return true;
			}

			if ( 'paypal' === $validated_value['payment_method'] ) {
				return true;
			}
		}

		return $is_pending;
	}

	public function pending_submission_succeeded( $succeeded, $submission_id ) {
		$form_id = justwpforms_get_meta( $submission_id, 'form_id', true );
		$form_controller = justwpforms_get_form_controller();
		$message_controller = justwpforms_get_message_controller();
		$form = $form_controller->get( $form_id );
		$submission = $message_controller->get( $submission_id );
		
		if ( $message_controller->submission_is_pending( $submission['request'], $form ) ) {
			$part = $form_controller->get_first_part_by_type( $form, 'payments' );

			if ( 1 == $part['required'] ) {
				$transaction_details = $this->get_response_transaction( $submission_id );

				if ( $transaction_details ) {
					$succeeded &= ( 'confirmed' === $transaction_details['status'] );
				}
			}
		}

		return $succeeded;
	}

}

if ( ! function_exists( 'justwpforms_get_payments_integration' ) ):

function justwpforms_get_payments_integration() {
	return justwpforms_Integration_Payments::instance();
}

endif;

justwpforms_get_payments_integration();
