<?php

class justwpforms_Service_Google_Geocoding extends justwpforms_Service {

	public $id    = 'google-geocoding';
	public $group = 'address';

	private $api_url_geocode = 'https://maps.googleapis.com/maps/api/geocode/json';

	public function __construct() {
		$this->label = __( 'Google Geocoding', 'justwpforms' );
	}

	public function get_default_credentials() {
		return array(
			'key' => '',
			//TODO remove once migration of apikeys in address field ins't supported
			'has_migrated' => 0,
		);
	}

	public function set_credentials( $credentials = array(), $raw = array() ) {
		$this->credentials = wp_parse_args( $credentials, $this->get_default_credentials() );
	}

	public function admin_widget( $previous_credentials = array() ) {
		require_once( justwpforms_get_integrations_folder() . '/services/google-geocoding/partial-widget.php' );
	}

	public function is_connected() {
		$is_connected = ! empty( $this->credentials['key'] );

		return $is_connected;
	}

	public function load() {
		if ( $this->is_connected() ) {
			require_once( justwpforms_get_integrations_folder() . '/services/google-geocoding/class-integration-google-geocoding.php' );
		}
	}

	//TODO remove once migration of apikeys in address field ins't supported
	public function try_migrating_keys() {
		$credentials = $this->get_credentials();

		if ( 1 == $credentials['has_migrated'] ) {
			return;
		}

		$form_controller = justwpforms_get_form_controller();
		$forms = $form_controller->get();
		$api_key = '';

		foreach ( $forms as $form ) {
			$parts = array_values( array_filter( $form['parts'], function( $part ) {
				return (
					'address' === $part['type'] 
					&& '' !== $part['apikey']
					&& 1 === $part['has_geolocation']
				);
			} ) );

			if ( empty( $parts ) ) {
				continue;
			}

			$api_key = $parts[0]['apikey'];
			break;
		}

		if ( '' !== $api_key ) {
			$credentials['key'] = $api_key;
		}

		$credentials['has_migrated'] = 1;
		$this->set_credentials( $credentials );

		justwpforms_get_integrations()->write_credentials();

		return;
	}

	public function geolocate_address( $latitude, $longitude ) {
		$results = [];
		$url = $this->api_url_geocode;
		$latlng = "{$latitude},{$longitude}";
		$args = array(
			'key' => $this->credentials['key'],
			'result_type' => 'street_address',
			'latlng' => $latlng,
		);
		$args = apply_filters( 'justwpforms_address_google_geocode_args', $args );
		$query = http_build_query( $args );
		$response = wp_remote_get( "{$url}?{$query}" );
		$response = wp_remote_retrieve_body( $response );
		$response = json_decode( $response, true );

		if ( isset( $response['status'] ) && 'OK' === $response['status'] ) {
			if ( count( $response['results'] ) ) {
				$results = $response['results'][0];
			}
		}
		return $results;
	}

}
