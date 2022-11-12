<?php

class justwpforms_Email_Encoder {

	private static $instance;
	private $encoder;

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function __construct() {
		require_once( justwpforms_get_core_folder() . '/lib/php-punycode/src/Exception/OutOfBoundsException.php' );
		require_once( justwpforms_get_core_folder() . '/lib/php-punycode/src/Exception/LabelOutOfBoundsException.php' );
		require_once( justwpforms_get_core_folder() . '/lib/php-punycode/src/Exception/DomainOutOfBoundsException.php' );
		require_once( justwpforms_get_core_folder() . '/lib/php-punycode/src/Punycode.php' );

		$this->encoder = new TrueBV\Punycode();
	}

	private function encode( $string ) {
		try {
			$string = $this->encoder->encode( $string );
		} catch( Exception $e ) {
			error_log( $e->getMessage() );
		}

		return $string;
	}

	private function decode( $string ) {
		try {
			$string = $this->encoder->decode( $string );
		} catch( Exception $e ) {
			error_log( $e->getMessage() );
		}

		return $string;
	}

	public function encode_email( $address ) {
		if ( ! apply_filters( 'justwpforms_encode_puny_address', true, $address ) ) {
			return $address;
		}

		$parts = explode( '@', $address );
		$parts = array_map( array( $this, 'encode' ), $parts );
		$address = implode( '@', $parts );

		return $address;
	}

	public function encode_url( $address ) {
		if ( ! apply_filters( 'justwpforms_encode_puny_address', true, $address ) ) {
			return $address;
		}

		return $this->encode( $address );
	}

	public function decode_email( $address ) {
		if ( ! apply_filters( 'justwpforms_decode_puny_address', true, $address ) ) {
			return $address;
		}

		$parts = explode( '@', $address );
		$parts = array_map( array( $this, 'decode' ), $parts );
		$address = implode( '@', $parts );

		return $address;
	}

	public function decode_url( $address ) {
		if ( ! apply_filters( 'justwpforms_decode_puny_address', true, $address ) ) {
			return $address;
		}

		return $this->encoder->decode( $address );
	}

}

if ( ! function_exists( 'justwpforms_get_email_encoder' ) ):

function justwpforms_get_email_encoder() {
	return justwpforms_Email_Encoder::instance();
}

endif;

justwpforms_get_email_encoder();
