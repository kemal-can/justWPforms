<?php

class justwpforms_Service_AWeber extends justwpforms_Service {

	public $id = 'aweber';
	public $group = 'email';

	public $oauth_client_id = 'zatmkvrQBaR0Vge5kCcerRPCG5PRlvbz';
	public $oauth_redirect_uri = 'urn:ietf:wg:oauth:2.0:oob';
	public $oauth_scopes = 'account.read list.read list.write subscriber.write';
	public $oauth_state_nonce = 'justwpforms_aweber_auth';
	
	public $endpoint_authorize = 'https://auth.aweber.com/oauth2/authorize';
	public $endpoint_tokens = 'https://auth.aweber.com/oauth2/token';
	public $endpoint_account = 'https://api.aweber.com/1.0/accounts';
	public $endpoint_lists = 'https://api.aweber.com/1.0/accounts/%s/lists';
	public $endpoint_fields = 'https://api.aweber.com/1.0/accounts/%s/lists/%s/custom_fields';
	public $endpoint_subscriber = 'https://api.aweber.com/1.0/accounts/%s/lists/%s/subscribers';

	public $lists = null;
	public $fields = null;

	public function __construct() {
		$this->label = __( 'AWeber', 'justwpforms' );
	}

	public function get_default_credentials() {
		$credentials = array(
			'client_id' => $this->oauth_client_id,	
			'client_secret' => '',
			'access_token' => '',
			'refresh_token' => '',
			'expires_in' => '',
			'last_updated' => '',
			'account_id' => '',
		);

		return $credentials;
	}

	public function set_credentials( $credentials = array(), $raw = array() ) {
		$credentials = array_intersect_key( $credentials, $this->get_default_credentials() );
		$this->credentials = wp_parse_args( $credentials, $this->get_default_credentials() );

		if ( isset( $raw['verification_code'] ) 
			&& ! empty( $raw['verification_code'] )
			&& isset( $raw['code_verifier'] ) 
			&& ! empty( $raw['code_verifier'] ) ) {

			// Migrate to new credentials layout
			$this->credentials['client_id'] = $this->oauth_client_id;
			$this->credentials['client_secret'] = '';

			$verification_code = $raw['verification_code'];
			$code_verifier = $raw['code_verifier'];
			$tokens = $this->get_tokens( $code_verifier, $verification_code );
			$this->credentials = wp_parse_args( $tokens, $this->credentials );
			$this->credentials['last_updated'] = time();
			$this->credentials['account_id'] = $this->get_account_id();
			$this->credentials['code_verifier'] = '';


			justwpforms_get_integrations()->write_credentials();
		}
	}

	public function admin_widget( $previous_credentials = array() ) {
		require_once( justwpforms_get_integrations_folder() . '/services/aweber/partial-widget.php' );
	}

	public function is_connected() {
		$is_connected = ! empty( $this->credentials['access_token'] );

		return $is_connected;
	}

	public function load() {
		if ( $this->is_connected() ) {
			require_once( justwpforms_get_integrations_folder() . '/services/aweber/class-integration-aweber.php' );
		}
	}

	public function generate_code_verifier() {
		$verifier_bytes = wp_generate_uuid4();
		$code_verifier = rtrim( strtr( base64_encode( $verifier_bytes ), '+/', '-_' ), '=' );

		return $code_verifier;
	}

	public function get_authorize_url( $code_verifier ) {
		$state = wp_create_nonce( $this->oauth_state_nonce );
		$challenge_bytes = hash( 'sha256', $code_verifier, true );
		$code_challenge = rtrim( strtr( base64_encode( $challenge_bytes ), '+/', '-_' ), '=' );

		$url = add_query_arg( array(
			'response_type' => 'code',
			'client_id' => $this->oauth_client_id,
			'redirect_uri' => $this->oauth_redirect_uri,
			'scope' => $this->oauth_scopes,
			'state' => $state,
			'code_challenge' => $code_challenge, 
			'code_challenge_method' => 'S256',
		), $this->endpoint_authorize );

		return $url;
	}

	public function get_tokens( $code_verifier, $authorization_code ) {
		$client_id = $this->oauth_client_id;

		$response = wp_remote_post( $this->endpoint_tokens, array(
			'body' => array(
				'grant_type' => 'authorization_code',
				'client_id' => $this->oauth_client_id,
				'code' => $authorization_code,
				'code_verifier' => $code_verifier,
			)
		) );

		$body = wp_remote_retrieve_body( $response );
		$body = json_decode( $body, true );

		return $body;
	}

	public function set_tokens( $access_token, $refresh_token ) {
		$this->access_token = $access_token;
		$this->refresh_token = $refresh_token;
	}

	public function get_refresh_tokens() {
		$refresh_token = $this->credentials['refresh_token'];
		$client_id = $this->credentials['client_id'];

		$response = wp_remote_post( $this->endpoint_tokens, array(
			'body' => array(
				'grant_type' => 'refresh_token',
				'client_id' => $client_id,
				'refresh_token' => $refresh_token,
			)
		) );

		$body = wp_remote_retrieve_body( $response );
		$body = json_decode( $body, true );

		return $body;
	}

	public function tokens_expired() {
		$now = time();
		$expires_in = intval( $this->credentials['expires_in'] );
		$last_updated = intval( $this->credentials['last_updated'] );
		$expired = $now - $last_updated >= $expires_in;

		return $expired;
	}

	public function refresh_tokens() {
		$tokens = $this->get_refresh_tokens();
		$this->credentials = wp_parse_args( $tokens, $this->credentials );
		$this->credentials['last_updated'] = time();

		justwpforms_get_integrations()->write_credentials();
	}

	public function make_request( $method, $enpoint, $body = false ) {
		if ( $this->tokens_expired() ) {
			$this->refresh_tokens();
		}

		$access_token = $this->credentials['access_token'];
		$arguments = array(
			'method' => $method,
			'headers' => array(
				'Authorization' => 'Bearer ' . $access_token,
				'Content-Type' => 'application/json',
				'Accept' => 'application/json',
			)
		);

		if ( $body ) {
			$arguments['body'] = json_encode( $body );
		}

		$response = $this->make_api_request( $enpoint, $arguments );
		$code = wp_remote_retrieve_response_code( $response );
		$response = wp_remote_retrieve_body( $response );
		$response = json_decode( $response );

		if ( isset( $response->error ) && isset( $response->error->message ) ) {
			$response = new WP_Error( $response->error->message );
		} else if ( isset( $response->error_description ) ) {
			$response = new WP_Error( $response->error_description );
		}

		if ( is_wp_error( $response ) && justwpforms_debug_log_enabled() ) {
			justwpforms_log_error( $response );
		}

		return $response;
	}

	public function get_account_id() {
		$response = $this->make_request( 'GET', $this->endpoint_account );

		if ( ! isset( $response->entries ) || 1 > count( $response->entries ) ) {
			$account_id = '';

			return $account_id;
		}

		$account_id = $response->entries[0]->id;

		return $account_id;
	}

	public function get_lists() {
		if ( ! is_null( $this->lists ) ) {
			return $this->lists;
		}

		$account_id = $this->credentials['account_id'];
		$endpoint = sprintf( $this->endpoint_lists, $account_id );
		$response = $this->make_request( 'GET', $endpoint );
		$this->lists = array();

		if ( is_wp_error( $response ) ) {
			return $this->lists;
		}

		$this->lists = array_map( function( $list ) {
			$list = array(
				'id' => $list->id,
				'name' => $list->name,
			);

			return $list;
		}, $response->entries );

		return $this->lists;
	}

	public function get_standard_fields() {
		$fields = array( array(
			'id' => 'email',
			'name' => __( 'Email', 'justwpforms' ),
		), array(
			'id' => 'name',
			'name' => __( 'Full name', 'justwpforms' ),
		) );

		return $fields;
	}

	public function get_fields() {
		if ( ! is_null( $this->fields ) ) {
			return $this->fields;
		}

		$account_id = $this->credentials['account_id'];
		$lists = $this->get_lists();
		$fields = array();

		foreach( $lists as $list ) {
			$list_id = $list['id'];
			$endpoint = sprintf( $this->endpoint_fields, $account_id, $list_id );
			$response = $this->make_request( 'GET', $endpoint );

			$list_fields = array_map( function( $field ) {
				$field = array(
					'id' => $field->name,
					'name' => $field->name,
				);

				return $field;
			}, $response->entries );

			$this->fields[$list_id] = array_merge( $list_fields, $this->get_standard_fields() );
		}

		return $this->fields;
	}

	public function add_subscriber( $list_id, $fields, $tags = array() ) {
		if ( '' === $list_id ) {
			return;
		}

		$account_id = $this->credentials['account_id'];
		$endpoint = sprintf( $this->endpoint_subscriber, $account_id, $list_id );
		$standard_fields = wp_list_pluck( $this->get_standard_fields(), 'name', 'id' );
		$subscriber = array_intersect_key( $fields, $standard_fields );
		$subscriber['ip_address'] = '90.39.124.254'; //$fields['ip_address'];
		$custom_fields = array_diff_key( $fields, $subscriber );

		if ( ! empty( array_keys( $custom_fields ) ) ) {
			$subscriber['custom_fields'] = $custom_fields;
		}

		if ( ! empty( $tags ) ) {
			$subscriber['tags'] = $tags;
		}

		$response = $this->make_request( 'POST', $endpoint, $subscriber );
	}

	public function unsubscribe( $list_id, $fields, $tags ) {
		if ( '' === $list_id ) {
			return;
		}

		$account_id = $this->credentials['account_id'];
		$endpoint = sprintf( $this->endpoint_subscriber, $account_id, $list_id );
		$endpoint = add_query_arg( 'subscriber_email', $fields['email'], $endpoint );

		$standard_fields = wp_list_pluck( $this->get_standard_fields(), 'name', 'id' );
		$body = array_intersect_key( $fields, $standard_fields );
		$body['status'] = 'unsubscribed';

		if ( ! empty( $tags ) ) {
			$body['tags'] = array(
				'remove' => $tags
			);
		}

		$response = $this->make_request( 'PATCH', $endpoint, $body );
	}

}
