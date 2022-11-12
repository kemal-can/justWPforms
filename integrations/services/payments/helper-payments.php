<?php

if ( ! function_exists( 'justwpforms_payment_part_has_user_price' ) ) :

function justwpforms_payment_part_has_user_price( $part, $form ) {

}

endif;

if ( ! function_exists( 'justwpforms_payment_part_has_credit_card' ) ) :

function justwpforms_payment_part_has_credit_card( $part, $form ) {
	$has_credit_card = apply_filters( 'justwpforms_payment_part_has_credit_card', false, $part, $form  );

	return $has_credit_card;
}

endif;

if ( ! function_exists( 'justwpforms_payment_part_has_paypal' ) ) :

function justwpforms_payment_part_has_paypal( $part, $form ) {
	$has_paypal = apply_filters( 'justwpforms_payment_part_has_paypal', false, $part, $form  );

	return $has_paypal;
}

endif;


if ( ! function_exists( 'justwpforms_payment_get_currencies' ) ) :

function justwpforms_payment_get_currencies( $service = '' ) {
	$currencies = array(
		'aud' => array(
			'label' => __( 'Australian dollar (AUD)', 'justwpforms' ),
			'symbol' => '$',
			'format' => 'float'
		),
		'thb' => array(
			'label' => __( 'Thai Baht (THB)', 'justwpforms' ),
			'symbol' => '฿',
			'format' => 'float'
		),
		'brl' => array(
			'label' => __( 'Brazilian real (BRL)', 'justwpforms' ),
			'symbol' => 'R$',
			'format' => 'float'
		),
		'cad' => array(
			'label' => __( 'Canadian dollar (CAD)', 'justwpforms' ),
			'symbol' => '$',
			'format' => 'float'
		),
		'cny' => array(
			'label' => __( 'Chinese Renmenbi (CNY)', 'justwpforms' ),
			'symbol' => '&yen;',
			'format' => 'float'
		),
		'czk' => array(
			'label' => __( 'Czech koruna (CZK)', 'justwpforms' ),
			'symbol' => 'Kč',
			'format' => 'float'
		),
		'dkk' => array(
			'label' => __( 'Danish krone (DKK)', 'justwpforms' ),
			'symbol' => 'kr.',
			'format' => 'float'
		),
		'eur' => array(
			'label' => __( 'Euro (EUR)', 'justwpforms' ),
			'symbol' => '€',
			'format' => 'float'
		),
		'hkd' => array(
			'label' => __( 'Hong Kong dollar (HKD)', 'justwpforms' ),
			'symbol' => 'HK$',
			'format' => 'float'
		),
		'huf' => array(
			'label' => __( 'Hungarian forint (HUF)', 'justwpforms' ),
			'symbol' => 'Ft',
			'format' => 'float',
		),
		'inr' => array(
			'label' => __( 'Indian rupee (INR)', 'justwpforms' ),
			'symbol' => '₹',
			'format' => 'float',
		),
		'ils' => array(
			'label' => __( 'Israeli new shekel (ILS)', 'justwpforms' ),
			'symbol' => '₪',
			'format' => 'float',
		),
		'jpy' => array(
			'label' => __( 'Japanese yen (JPY)', 'justwpforms' ),
			'symbol' => '¥',
			'format' => 'int'
		),
		'myr' => array(
			'label' => __( 'Malaysian ringgit (MYR)', 'justwpforms' ),
			'symbol' => 'RM',
			'format' => 'float'
		),
		'mxn' => array(
			'label' => __( 'Mexican peso (MXN)', 'justwpforms' ),
			'symbol' => '$',
			'format' => 'float'
		),
		'twd' => array(
			'label' => __( 'New Taiwan dollar (TWD)', 'justwpforms' ),
			'symbol' => 'NT$',
			'format' => 'float'
		),
		'nzd' => array(
			'label' => __( 'New Zealand dollar (NZD)', 'justwpforms' ),
			'symbol' => 'NZ$',
			'format' => 'float'
		),
		'nok' => array(
			'label' => __( 'Norwegian krone (NOK)', 'justwpforms' ),
			'symbol' => 'kr',
			'format' => 'float'
		),
		'php' => array(
			'label' => __( 'Philippine peso (PHP)', 'justwpforms' ),
			'symbol' => '₱',
			'format' => 'float'
		),
		'pln' => array(
			'label' => __( 'Polish złoty (PLN)', 'justwpforms' ),
			'symbol' => 'zł',
			'format' => 'float'
		),
		'gbp' => array(
			'label' => __( 'Pound sterling (GBP)', 'justwpforms' ),
			'symbol' => '£',
			'format' => 'float'
		),
		'rub' => array(
			'label' => __( 'Russian ruble (RUB)', 'justwpforms' ),
			'symbol' => '₽',
			'format' => 'float'
		),
		'sgd' => array(
			'label' => __( 'Singapore dollar (SGD)', 'justwpforms' ),
			'symbol' => '$',
			'format' => 'float'
		),
		'sek' => array(
			'label' => __( 'Swedish krona (SEK)', 'justwpforms' ),
			'symbol' => 'Kr',
			'format' => 'float'
		),
		'chf' => array(
			'label' => __( 'Swiss franc (CHF)', 'justwpforms' ),
			'symbol' => 'CHF',
			'format' => 'float'
		),
		'usd' => array(
			'label' => __( 'United States dollar (USD)', 'justwpforms' ),
			'symbol' => '$',
			'format' => 'float'
		),
	);

	$currencies = apply_filters( 'justwpforms_payment_currencies', $currencies, $service );

	return $currencies;
}

endif;

if ( ! function_exists( 'justwpforms_payment_get_formatted_details' ) ) :

function justwpforms_payment_get_formatted_details( $response_id, $context ) {
	$transaction_details_meta = justwpforms_get_payments_integration()->transaction_details_meta;
	$transaction_details = justwpforms_get_meta( $response_id, $transaction_details_meta, true );

	if ( empty( $transaction_details ) ) {
		return '';
	}

	ob_start();
	do_action( 'justwpforms_payment_the_transaction_details', $transaction_details, $context );
	$details = ob_get_clean();
	$details = preg_replace( '/\s+/', ' ', $details);

	return $details;
}

endif;
