<?php

class justwpforms_Service_SendGrid extends justwpforms_Service {

	public $id    = 'sendgrid';
	public $group = 'email';

	public $endpoint_lists = 'https://api.sendgrid.com/v3/marketing/lists';
	public $endpoint_contacts = 'https://api.sendgrid.com/v3/marketing/contacts';
	public $endpoint_search_emails = 'https://api.sendgrid.com/v3/marketing/contacts/search/emails';
	public $endpoint_list_contacts = 'https://api.sendgrid.com/v3/marketing/lists/%s/contacts';
	public $endpoint_fields = 'https://api.sendgrid.com/v3/marketing/field_definitions';

	public $lists = null;
	public $fields = null;

	public function __construct() {
		$this->label = __( 'SendGrid', 'justwpforms' );
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
		require_once( justwpforms_get_integrations_folder() . '/services/sendgrid/partial-widget.php' );
	}

	public function is_connected() {
		$is_connected = ! empty( $this->credentials['key'] );

		return $is_connected;
	}

	public function load() {
		if ( $this->is_connected() ) {
			require_once( justwpforms_get_integrations_folder() . '/services/sendgrid/class-integration-sendgrid.php' );
		}
	}

	public function make_request( $method, $endpoint, $body = false ) {
		$key = $this->credentials['key'];

		$arguments = array(
			'method'  => $method,
			'headers' => array(
				'Content-Type'  => 'application/json',
				'Authorization' => 'Bearer ' . $key,
			)
		);

		if ( $body ) {
			$arguments['body'] = json_encode( $body );
		}

		$response = $this->make_api_request( $endpoint, $arguments );
		$body = wp_remote_retrieve_body( $response );
		$body = json_decode( $body );

		if ( isset( $body->errors ) && justwpforms_debug_log_enabled() ) {
			foreach ( $body->errors as $error ) {
				$message = maybe_serialize( $error->message );
				$error = new WP_Error( $this->id, $message );

				justwpforms_log_error( $error );
			}
		}

		return $body;
	}

	public function get_lists() {
		if ( ! is_null( $this->lists ) ) {
			return $this->lists;
		}

		$response = $this->make_request( 'GET', $this->endpoint_lists );

		if ( isset( $response->errors ) ) {
			return $this->lists;
		}

		$lists = array_map( function( $list ) {
			$list = array(
				'id' => $list->id,
				'name' => $list->name,
			);

			return $list;
		}, $response->result );

		$this->lists = $lists;

		return $this->lists;
	}

	/**
	 * Calls sanitize method based on field's type.
	 *
	 * @param string $field_key
	 * @param string $field_value Value submitted in the form.
	 *
	 * @return string Sanitized value.
	 */
	public function sanitize_field( $field_key, $field_value = '' ) {
		if ( empty( $field_key ) ) {
			return;
		}

		$function = 'sanitize_text_field';
		$fields = $this->get_fields();
		$found = array_search( $field_key, array_column( $fields, 'id' ) );

		if ( $found ) {
			$field = $fields[$found];

			switch( strtolower( $field['type'] ) ) {
				case 'date':
					$function = array( $this, 'sanitize_date_field' );
					break;
				case 'number':
					$function = 'intval';
					break;
				default:
					break;
			}
		}

		$value = call_user_func( $function, $field_value );

		return $value;
	}

	/**
	 * Sanitize date field to the format readable by SendGrid.
	 *
	 * @param string $field_value Field value.
	 *
	 * @return string Sanitized value.
	 */
	public function sanitize_date_field( $field_value ) {
		$date = strtotime( $field_value );
		$value = date( 'm/d/Y', $date );

		return $value;
	}

	/**
	 * Push subscriber.
	 *
	 * @param array $data Subscriber data.
	 *
	 * @return void
	 */
	public function add_subscriber( $data ) {
		$body = array(
			'contacts' => array(
				array(),
			),
		);

		if ( ! empty( $data['list_id'] ) ) {
			if ( 'all' !== $data['list_id'] ) {
				$body['list_ids'] = array( $data['list_id'] );
			} else {
				$body['list_ids'] = array_values( wp_list_pluck( $this->get_lists(), 'id' ) );
			}
		}

		if ( empty( $data['fields'] ) ) {
			return;
		}

		/**
		 * SendGrid has bunch of fields reserved in their database. Those fields are not pushed to `custom_fields`
		 * array but to the top level of subscriber data.
		 */
		$reserved_fields = array(
			'address_line_1',
			'address_line_2',
			'alternate_emails',
			'city',
			'country',
			'email',
			'first_name',
			'last_name',
			'postal_code',
			'state_province_region',
		);

		foreach ( $data['fields'] as $field_key => $field_value ) {
			$sanitized_value = $this->sanitize_field( $field_key, $field_value );

			// If it's reserved field, push to top level.
			if ( in_array( $field_key, $reserved_fields ) ) {
				$body['contacts'][0][$field_key] = $sanitized_value;
			// Otherwise add it to `custom_fields` array.
			} else {
				if ( ! isset( $body['contacts'][0]['custom_fields'] ) ) {
					$body['contacts'][0]['custom_fields'] = array();
				}

				$body['contacts'][0]['custom_fields'][$field_key] = $sanitized_value;
			}
		}

		$response = $this->make_request( 'PUT', $this->endpoint_contacts, $body );
	}

	/**
	 * Get all custom fields.
	 *
	 * SendGrid returns both reserved fields and user defined fields in a separate objects. We parse both of those into
	 * array and then merge them so everything is returned as a single array.
	 *
	 * @return array Custom fields.
	 */
	public function get_fields() {
		if ( ! is_null( $this->fields ) ) {
			return $this->fields;
		}

		$response = $this->make_request( 'GET', $this->endpoint_fields );

		if ( isset( $response->errors ) ) {
			return $this->fields;
		}

		$reserved_fields = array();
		$custom_fields = array();

		$reserved_fields = array_map( function( $field ) {
			$field = array(
				'id' => $field->name,
				'name' => $field->name,
				'type' => $field->field_type,
			);

			return $field;
		}, $response->reserved_fields );

		if ( isset( $response->custom_fields ) ) {
			$custom_fields = array_map( function( $field ) {
				$field = array(
					'id' => $field->id,
					'name' => $field->name,
					'type' => $field->field_type,
				);

				return $field;
			}, $response->custom_fields );
		}

		$fields = array_merge( $reserved_fields, $custom_fields );

		$this->fields = $fields;

		return $this->fields;
	}

	public function unsubscribe( $data ) {
		if ( '' === $data['list_id'] ) {
			return;
		}

		if ( ! isset( $data['fields']['email'] ) ) {
			return;
		}

		$email = $data['fields']['email'];

		$response = $this->make_request( 'POST', $this->endpoint_search_emails, array(
			'emails' => array( $email ),
		) );

		if ( isset( $response->result ) && isset( $response->result->$email ) ) {
			$contact = $response->result->$email->contact;
			$list_ids = array( $data['list_id'] );

			if ( 'all' === $data['list_id'] ) {
				$list_ids = array_values( wp_list_pluck( $this->get_lists(), 'id' ) );
			}

			foreach( $list_ids as $list_id ) {
				$endpoint = sprintf( $this->endpoint_list_contacts, $list_id );
				$endpoint = add_query_arg( 'contact_ids', $contact->id, $endpoint );

				$this->make_request( 'DELETE', $endpoint );
			}
		}
	}

}
