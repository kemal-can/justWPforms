<?php

class justwpforms_Service_Emailoctopus extends justwpforms_Service {

	public $id    = 'emailoctopus';
	public $group = 'email';

	public $endpoint_lists = 'https://emailoctopus.com/api/1.6/lists';
	public $endpoint_add_contact = 'https://emailoctopus.com/api/1.6/lists/%s/contacts';
	public $endpoint_update_contact = 'https://emailoctopus.com/api/1.6/lists/%s/contacts/%s';

	public $fields = null;
	public $folders = null;
	public $lists = null;
	public $templates = array();

	public function __construct() {
		$this->label = __( 'EmailOctopus', 'justwpforms' );
	}

	public function get_default_credentials() {
		return array(
			'key' => '',
		);
	}

	public function set_credentials( $credentials = array(), $raw = array() ) {
		$this->credentials = wp_parse_args( $credentials, $this->get_default_credentials() );
	}

	public function admin_widget( $previous_credentials = array() ) {
		require_once( justwpforms_get_integrations_folder() . '/services/emailoctopus/partial-widget.php' );
	}

	public function is_connected() {
		$is_connected = ! empty( $this->credentials['key'] );

		return $is_connected;
	}

	public function load() {
		if ( $this->is_connected() ) {
			require_once( justwpforms_get_integrations_folder() . '/services/emailoctopus/class-integration-emailoctopus.php' );
		}
	}

	public function make_request( $method, $endpoint, $body = array() ) {
		$arguments = array(
			'method'  => $method,
			'headers' => array(
				'Content-Type' => 'application/json',
			)
		);

		if ( $body ) {
			$arguments['body'] = json_encode( $body );
		}

		$response = $this->make_api_request( $endpoint, $arguments );

		$body = wp_remote_retrieve_body( $response );
		$body = json_decode( $body );

		if ( isset( $body->error ) && justwpforms_debug_log_enabled() ) {
			$message = '';

			if ( isset( $body->error->code ) ) {
				$message = 'error code: ' . $body->error->code;
			}

			if ( isset( $body->error->message ) ) {
				if ( ! empty( $message ) ) {
					$message .= ' || ';
				}
				$message .= 'message: ' . $body->error->message;
			}

			$error = new WP_Error( $this->id, $message );

			justwpforms_log_error( $error );

			return false;
		}

		return $body;
	}

	/**
	 * Get lists.
	 *
	 * @return array Lists.
	 */
	public function get_lists() {
		if ( ! is_null( $this->lists ) ) {
			return $this->lists;
		}
		$key = $this->credentials['key'];

		$endpoint_lists = $this->endpoint_lists;
		$endpoint_lists = add_query_arg( 'api_key', $key, $endpoint_lists );

		$response = $this->make_request( 'GET', $endpoint_lists );

		$this->lists = array();

		if ( ! $response ) {
			return $this->lists;
		}

		if ( ! isset( $response->data ) ) {
			return $this->lists;
		}

		if ( is_array( $response->data ) ) {
			$this->lists = $response->data;
		}

		return $this->lists;
	}

	/**
	 * Get all fields.
	 *
	 * @return array Fields.
	 */
	public function get_fields() {
		if ( ! is_null( $this->fields ) ) {
			return $this->fields;
		}

		$lists = $this->get_lists();

		if ( ! $lists ) {
			return $this->fields;
		}

		$this->fields = array();

		foreach ( $lists as $list ) {
			$list_fields = array_map( function( $list_field ) {
				$field = array(
					'id' => $list_field->tag,
					'name' => $list_field->label,

				);

				return $field;
			}, $list->fields );

			$this->fields[ $list->id ] = $list_fields;
		}

		return $this->fields;
	}


	public function add_subscriber( $data ) {
		$key = $this->credentials['key'];
		$email = $data['email'];

		$body = array(
			'api_key' => $key,
			'email_address' => $email,
		);

		if ( ! empty( $data['fields'] ) ) {
			$body['fields'] = $data['fields'];
		}

		if ( ! empty( $data['tags'] ) ) {
			$body['tags'] = $data['tags'];
		}

		$endpoint_add_contact = sprintf( $this->endpoint_add_contact, $data['list_id'] );

		$response = $this->make_request( 'POST', $endpoint_add_contact, $body );

		return $response;
	}

	public function unsubscribe( $data ) {
		$key = $this->credentials['key'];
		$email = $data['email'];
		$email_hash = md5( strtolower( $email ) );

		$endpoint_update_contact = sprintf( $this->endpoint_update_contact, $data['list_id'], $email_hash );

		$body = array(
			'api_key' => $key,
			'status' => 'UNSUBSCRIBED',
		);

		if ( ! empty( $data['fields'] ) ) {
			$body['fields'] = $data['fields'];
		}

		if ( ! empty( $data['tags'] ) ) {
			$body['tags'] = $data['tags'];
		}

		$this->make_request( 'PUT', $endpoint_update_contact, $body );
	}
}
