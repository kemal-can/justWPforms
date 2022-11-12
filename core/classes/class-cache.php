<?php

class justwpforms_Cache {

	private static $instance;

	private $cache = array();

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function exists( $key ) {
		$exists = isset( $this->cache[$key] );

		return $exists;
	}

	public function get( $key, &$found = null ) {
		$found = false;

		if ( $this->exists( $key ) ) {
			$found = true;

			if ( is_object( $this->cache[$key] ) ) {
				return clone( $this->cache[$key] );
			} else {
				return $this->cache[$key];
			}
		}

		return false;
	}

	public function set( $key, $value ) {
		if ( is_object( $value ) ) {
			$value = clone( $value );
		}

		$this->cache[$key] = $value;

		return true;
	}

}

if ( ! function_exists( 'justwpforms_get_cache' ) ):

function justwpforms_get_cache() {
	return justwpforms_Cache::instance();
}

endif;

justwpforms_get_cache();