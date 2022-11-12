<?php

class justwpforms_Service_Google_Places extends justwpforms_Service {

	public $id    = 'google-places';
	public $group = 'address';

	private $api_url = 'https://maps.googleapis.com/maps/api/place/autocomplete/json';

	public function __construct() {
		$this->label = __( 'Google Places', 'justwpforms' );
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
		require_once( justwpforms_get_integrations_folder() . '/services/google-places/partial-widget.php' );
	}

	public function is_connected() {
		$is_connected = ! empty( $this->credentials['key'] );

		return $is_connected;
	}

	public function load() {
		if ( $this->is_connected() ) {
			require_once( justwpforms_get_integrations_folder() . '/services/google-places/class-integration-google-places.php' );
		}
	}

	//TODO remove once migration of apikeys in address field ins't supported
	public function try_migrating_keys(){
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
					&& 'autocomplete' === $part['mode']
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

	public function get_address_suggestions( $term ) {
		$results = [];
		$url = $this->api_url;

		$args = array(
				'key' => $this->credentials['key'],
				'types' => 'address',
				'input' => $term,
			);
		$args = apply_filters( 'justwpforms_address_google_autocomplete_args', $args );

		$query = http_build_query( $args );
		$response = wp_remote_get( "{$url}?{$query}" );
		$response = wp_remote_retrieve_body( $response );
		$response = json_decode( $response, true );

		if ( isset( $response['status'] ) && 'OK' === $response['status'] ) {
			$results = wp_list_pluck( $response['predictions'], 'description' );
		}

		return $results;
	}

}
