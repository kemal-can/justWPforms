<?php

class justwpforms_Service_ConstantContact extends justwpforms_Service {

	public $id = 'constant-contact';
	public $group = 'email';

	public $endpoint_authorize = 'https://authz.constantcontact.com/oauth2/default/v1/authorize';
	public $endpoint_tokens = 'https://authz.constantcontact.com/oauth2/default/v1/token';
	public $endpoint_lists = 'https://api.cc.email/v3/contact_lists';
	public $endpoint_fields = 'https://api.cc.email/v3/contact_custom_fields';
	public $endpoint_subscriber = 'https://api.cc.email/v3/contacts/sign_up_form';
	public $endpoint_contacts = 'https://api.cc.email/v3/contacts';
	public $endpoint_contact = 'https://api.cc.email/v3/contacts/%s';

	public $redirect_uri = '';
	public $scopes = 'offline_access+contact_data+campaign_data';
	public $oauth_action = 'justwpforms-oauth';
	public $nonce_prefix = 'justwpforms_constant_contact';

	public $lists = null;
	public $fields = null;

	public function __construct() {
		$this->label = __( 'Constant Contact', 'justwpforms' );
		$this->redirect_uri = add_query_arg( array(
			$this->oauth_action => $this->id,
		), admin_url() );

		add_action( 'admin_init', array( $this, 'handle_authorize_redirect' ) );
	}

	public function get_default_credentials() {
		$credentials = array(
			'client_id' => '',
			'client_secret' => '',
			'access_token' => '',
			'refresh_token' => '',
			'expires_in' => 7200,
			'last_updated' => '',
		);

		return $credentials;
	}

	public function set_credentials( $credentials = array(), $raw = array() ) {
		$this->credentials = wp_parse_args( $credentials, $this->get_default_credentials() );
	}

	public function admin_widget( $previous_credentials = array() ) {
		if ( wp_doing_ajax() && $this->credentials_changed( $previous_credentials ) ) {

			$html = '<script>';
			$html .= 'window.location.href = "' . $this->get_authorize_url() . '";';
			$html .= '</script>';
			echo $html;
			die();

		}

		require_once( justwpforms_get_integrations_folder() . '/services/constant-contact/partial-widget.php' );
	}

	public function credentials_changed( $previous_credentials ) {
		if ( $this->credentials['client_id'] !== $previous_credentials['client_id'] ) {
			return true;
		}

		if ( $this->credentials['client_secret'] !== $previous_credentials['client_secret'] ) {
			return true;
		}

		return false;
	}

	public function is_connected() {
		$is_connected = (
			! empty( $this->credentials['client_id'] ) &&
			! empty( $this->credentials['client_secret'] )
		);

		return $is_connected;
	}

	public function load() {
		if ( $this->is_connected() ) {
			require_once( justwpforms_get_integrations_folder() . '/services/constant-contact/class-integration-constant-contact.php' );
		}
	}

	public function set_tokens( $access_token, $refresh_token ) {
		$this->access_token = $access_token;
		$this->refresh_token = $refresh_token;
	}

	private function get_nonce_name( $client_id ) {
		$nonce_name = wp_create_nonce( "{$this->nonce_prefix}-{$client_id}" );

		return $nonce_name;
	}

	public function get_authorize_url() {
		$client_id = $this->credentials['client_id'];
		$nonce = wp_create_nonce( $this->get_nonce_name( $client_id ) );

		$url = add_query_arg( array(
			'response_type' => 'code',
			'client_id' => $client_id,
			'redirect_uri' => $this->redirect_uri,
			'state' => $nonce,
			'scope' => $this->scopes,
		), $this->endpoint_authorize );

		return $url;
	}

	public function handle_authorize_redirect() {
		if ( ! isset( $_GET[$this->oauth_action] ) || $_GET[$this->oauth_action] !== $this->id ) {
			return;
		}

		$client_id = $this->credentials['client_id'];

		if ( ! isset( $_GET['state'] ) || ! wp_verify_nonce( $_GET['state'], $this->get_nonce_name( $client_id ) ) ) {
			return;
		}

		if ( ! isset( $_GET['code'] ) ) {
			return;
		}

		$authorization_code = $_GET['code'];
		$tokens = $this->get_tokens( $authorization_code );
		$this->credentials = wp_parse_args( $tokens, $this->credentials );
		$this->credentials['last_updated'] = time();

		justwpforms_get_integrations()->write_credentials();

		$redirect_url = admin_url( 'admin.php?page=justwpforms-integrations' );

		wp_redirect( $redirect_url );
	}

	public function get_tokens( $authorization_code ) {
		$client_id = $this->credentials['client_id'];
		$client_secret = $this->credentials['client_secret'];
		$authorization_header = base64_encode( "{$client_id}:{$client_secret}" );

		$response = wp_remote_post( $this->endpoint_tokens, array(
			'headers' => array(
				'Authorization' => 'Basic ' . $authorization_header,
			),
			'body' => array(
				'grant_type' => 'authorization_code',
				'code' => $authorization_code,
				'redirect_uri' => $this->redirect_uri,
			)
		) );

		$body = wp_remote_retrieve_body( $response );
		$body = json_decode( $body, true );

		return $body;
	}

	public function get_refresh_tokens() {
		$client_id = $this->credentials['client_id'];
		$client_secret = $this->credentials['client_secret'];
		$refresh_token = $this->credentials['refresh_token'];
		$authorization_header = base64_encode( "{$client_id}:{$client_secret}" );

		$response = wp_remote_post( $this->endpoint_tokens, array(
			'headers' => array(
				'Authorization' => 'Basic ' . $authorization_header,
			),
			'body' => array(
				'grant_type' => 'refresh_token',
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

		if ( 200 !== $code ) {
			$message = '';

			if ( is_array( $response ) ) {
				$response = $response[0];
			}

			$message = isset( $response->error_message ) ? $response->error_message : '';
			$response = new WP_Error( $this->id, $message );
		}

		if ( is_wp_error( $response ) && justwpforms_debug_log_enabled() ) {
			justwpforms_log_error( $response );
		}

		return $response;
	}

	public function get_lists() {
		if ( ! is_null( $this->lists ) ) {
			return $this->lists;
		}

		$response = $this->make_request( 'GET', $this->endpoint_lists );
		$this->lists = array();

		if ( is_wp_error( $response ) ) {
			return $this->lists;
		}

		$this->lists = array_map( function( $list ) {
			$list = array(
				'id' => $list->list_id,
				'name' => $list->name,
			);

			return $list;
		}, $response->lists );

		return $this->lists;
	}

	public function get_standard_fields() {
		$fields = array( array(
			'id' => 'email_address',
			'name' => __( 'Email', 'justwpforms' ),
			'type' => 'string',
		), array(
			'id' => 'first_name',
			'name' => __( 'First name', 'justwpforms' ),
			'type' => 'string',
		), array(
			'id' => 'last_name',
			'name' => __( 'Last name', 'justwpforms' ),
			'type' => 'string',
		), array(
			'id' => 'job_title',
			'name' => __( 'Job title', 'justwpforms' ),
			'type' => 'string',
		), array(
			'id' => 'company_name',
			'name' => __( 'Company name', 'justwpforms' ),
			'type' => 'string',
		), array(
			'id' => 'phone_number',
			'name' => __( 'Phone number', 'justwpforms' ),
			'type' => 'string',
		), array(
			'id' => 'anniversary',
			'name' => __( 'Anniversary', 'justwpforms' ),
			'type' => 'date',
		) );

		return $fields;
	}

	public function get_address_fields() {
		$fields = array( array(
			'id' => 'street',
			'name' => __( 'Street', 'justwpforms' ),
			'type' => 'string',
		), array(
			'id' => 'city',
			'name' => __( 'City', 'justwpforms' ),
			'type' => 'string',
		), array(
			'id' => 'state',
			'name' => __( 'State', 'justwpforms' ),
			'type' => 'string',
		), array(
			'id' => 'postal_code',
			'name' => __( 'Postal code', 'justwpforms' ),
			'type' => 'string',
		), array(
			'id' => 'country',
			'name' => __( 'Country', 'justwpforms' ),
			'type' => 'string',
		) );

		return $fields;
	}

	public function get_fields() {
		if ( ! is_null( $this->fields ) ) {
			return $this->fields;
		}

		$response = $this->make_request( 'GET', $this->endpoint_fields );
		$this->fields = array();

		if ( is_wp_error( $response ) ) {
			return $this->fields;
		}

		$this->fields = array_map( function( $field ) {
			$field = array(
				'id' => $field->custom_field_id,
				'name' => $field->label,
				'type' => $field->type,
			);

			return $field;
		}, $response->custom_fields );

		$this->fields = array_merge(
			$this->get_standard_fields(),
			$this->get_address_fields(),
			$this->fields
		);

		return $this->fields;
	}

	public function add_subscriber( $list_id, $fields ) {
		if ( '' === $list_id ) {
			return;
		}

		$standard_fields = wp_list_pluck( $this->get_standard_fields(), 'name', 'id' );
		$standard_fields = array_intersect_key( $fields, $standard_fields );
		$address_fields = wp_list_pluck( $this->get_address_fields(), 'name', 'id' );
		$address_fields = array_intersect_key( $fields, $address_fields );
		$custom_fields = array_diff_key( $fields, $standard_fields );
		$custom_fields = array_diff_key( $custom_fields, $address_fields );

		if ( ! empty( $address_fields ) ) {
			$address = wp_parse_args( $address_fields, array(
				'kind' => 'other',
			) );
			$standard_fields['street_address'] = $address;
		}

		if ( ! empty( $custom_fields ) ) {
			foreach( $custom_fields as $id => $value ) {
				$custom_fields[] = array(
					'custom_field_id' => $id,
					'value' => $value,
				);

				unset( $custom_fields[$id] );
			}

			$standard_fields['custom_fields'] = $custom_fields;
		}

		$list_memberships = array( $list_id );

		if ( 'all' === $list_id ) {
			$list_memberships = array_values( wp_list_pluck( $this->get_lists(), 'id' ) );
		}

		$standard_fields['list_memberships'] = $list_memberships;
		$response = $this->make_request( 'POST', $this->endpoint_subscriber, $standard_fields );
	}

	public function unsubscribe( $list_id, $fields ) {
		if ( '' === $list_id ) {
			return;
		}

		if ( ! isset( $fields['email_address'] ) ) {
			return;
		}

		$endpoint_contacts = add_query_arg( 'email', $fields['email_address'], $this->endpoint_contacts );

		$contact_response = $this->make_request( 'GET', $endpoint_contacts );

		if ( ! empty( $contact_response->contacts ) ) {
			$contact = $contact_response->contacts[0];
			$endpoint_contact = sprintf( $this->endpoint_contact, $contact->contact_id );
			$endpoint_contact = add_query_arg( 
				'include', 
				'custom_fields,list_memberships,phone_numbers,street_addresses,taggings,notes',
				$endpoint_contact
			);

			$contact_response = $this->make_request( 'GET', $endpoint_contact );

			if ( is_wp_error( $contact_response ) && justwpforms_debug_log_enabled() ) {
				justwpforms_log_error( $response );

				return;
			}

			$list_memberships = array();

			if ( 'all' !== $list_id ) {
				$list_memberships = array_diff( $contact_response->list_memberships, array( $list_id ) );
			}

			$contact_response->list_memberships = $list_memberships;
			$contact_response = json_decode( json_encode( $contact_response ), true );
			unset( $contact_response['contact_id'] );
			$contact_response['update_source'] = 'Contact';
			$endpoint_contact = sprintf( $this->endpoint_contact, $contact->contact_id );
			
			$contact_response = $this->make_request( 'PUT', $endpoint_contact, $contact_response );
		}
	}

}
