<?php

class justwpforms_Service_SendFox extends justwpforms_Service {

	public $id = 'sendfox';
	public $group = 'email';

	public $endpoint_lists = 'https://api.sendfox.com/lists';
	public $endpoint_contacts = 'https://api.sendfox.com/contacts';
	public $endpoint_list_contacts = 'https://api.sendfox.com/lists/%s/contacts/%s';

	public $lists = null;

	public function __construct() {
		$this->label = __( 'SendFox', 'justwpforms' );
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
		require_once( justwpforms_get_integrations_folder() . '/services/sendfox/partial-widget.php' );
	}

	public function is_connected() {
		$is_connected = ! empty( $this->credentials['key'] );

		return $is_connected;
	}

	public function load() {
		if ( $this->is_connected() ) {
			require_once( justwpforms_get_integrations_folder() . '/services/sendfox/class-integration-sendfox.php' );
		}
	}

	public function make_request( $method, $endpoint, $body = false ) {
		$key = $this->credentials['key'];

		$arguments = array(
			'method'  => $method,
			'headers' => array(
				'Content-Type' => 'application/json',
				'Authorization' => 'Bearer ' . $key,
			)
		);

		if ( $body ) {
			$arguments['body'] = json_encode( $body );
		}

		$response = $this->make_api_request( $endpoint, $arguments );
		$body = wp_remote_retrieve_body( $response );
		$body = json_decode( $body );

		return $body;
	}

	/**
	 * Get all lists.
	 *
	 * @return array Lists.
	 */
	public function get_lists() {
		if ( ! is_null( $this->lists ) ) {
			return $this->lists;
		}

		$this->lists = array();
		$next_page_url = $this->endpoint_lists;

		do {
			$response = $this->make_request( 'GET', $next_page_url );

			if ( ! $response ) {
				return $this->lists;
			}

			$lists = array_map( function( $list ) {
				$list = array(
					'id' => $list->id,
					'name' => $list->name,
				);

				return $list;
			}, $response->data );

			$this->lists = array_merge( $this->lists, $lists );
			$next_page_url = $response->next_page_url;
		} while ( '' !== $next_page_url );

		return $this->lists;
	}

	/**
	 * Returns fields supported by SendFox.
	 *
	 * SendFox is minimal when it comes to fields and it doesn't have an API endpoint to pull fields
	 * for that reason. Instead, it only supports:
	 * - Email
	 * - First Name
	 * - Last Name
	 *
	 * This is a method to return those fields in the format that we need for other methods.
	 *
	 * @return array Fields.
	 */
	public function get_fields() {
		return array(
			array(
				'id'   => 'email',
				'name' => __( 'Email Address', 'justwpforms' ),
			),
			array(
				'id'   => 'first_name',
				'name' => __( 'First Name', 'justwpforms' ),
			),
			array(
				'id'   => 'last_name',
				'name' => __( 'Last Name', 'justwpforms' ),
			),
		);
	}

	/**
	 * Push subscriber to SendFox.
	 *
	 * @param array $data Subscriber data.
	 *
	 * @return object Response body.
	 */
	public function add_subscriber( $data ) {
		if ( empty( $data['fields'] ) || empty( $data['list_id'] ) ) {
			return;
		}

		$lists = array( $data['list_id'] );

		if ( 'all' === $data['list_id'] ) {
			$lists = array_values( wp_list_pluck( $this->get_lists(), 'id' ) );
		}

		$body = array(
			'lists' => $lists,
		);

		foreach ( $data['fields'] as $field_key => $field_value ) {
			$body[$field_key] = sanitize_text_field( $field_value );
		}

		$response = $this->make_request( 'POST', $this->endpoint_contacts, $body );

		return $response;
	}

	public function unsubscribe( $data ) {
		if ( ! isset( $data['fields']['email'] ) ) {
			return;
		}

		$email = $data['fields']['email'];
		$endpoint_contacts = add_query_arg( 'email', $email, $this->endpoint_contacts );

		$response = $this->make_request( 'GET', $endpoint_contacts );

		if ( ! empty( $response->data ) ) {
			$contact = $response->data[0];
			$list_ids = array( $data['list_id'] );

			if ( 'all' === $data['list_id'] ) {
				$list_ids = array_values( wp_list_pluck( $this->get_lists(), 'id' ) );
			}

			foreach( $list_ids as $list_id ) {
				$endpoint = sprintf( $this->endpoint_list_contacts, $list_id, $contact->id );

				$this->make_request( 'DELETE', $endpoint );
			}
		}
	}
}
