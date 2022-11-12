<?php

class justwpforms_Service_MailerLite extends justwpforms_Service {

	public $id    = 'mailerlite';
	public $group = 'email';

	public $endpoint_groups = 'https://api.mailerlite.com/api/v2/groups';
	public $endpoint_groups_subcribers = 'https://api.mailerlite.com/api/v2/groups/%s/subscribers';
	public $endpoint_groups_subcriber = 'https://api.mailerlite.com/api/v2/groups/%s/subscribers/%s';
	public $endpoint_subscribers = 'https://api.mailerlite.com/api/v2/subscribers';
	public $endpoint_fields = 'https://api.mailerlite.com/api/v2/fields';

	public $groups = null;
	public $fields = null;

	public function __construct() {
		$this->label = __( 'MailerLite', 'justwpforms' );
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
		require_once( justwpforms_get_integrations_folder() . '/services/mailerlite/partial-widget.php' );
	}

	public function is_connected() {
		$is_connected = ! empty( $this->credentials['key'] );

		return $is_connected;
	}

	public function load() {
		if ( $this->is_connected() ) {
			require_once( justwpforms_get_integrations_folder() . '/services/mailerlite/class-integration-mailerlite.php' );
		}
	}

	public function make_request( $method, $endpoint, $body = false ) {
		$key = $this->credentials['key'];

		$arguments = array(
			'method'  => $method,
			'headers' => array(
				'Content-Type' => 'application/json',
				'X-MailerLite-ApiKey' => $key,
			)
		);

		if ( $body ) {
			$arguments['body'] = json_encode( $body );
		}

		$response = $this->make_api_request( $endpoint, $arguments );
		$body = wp_remote_retrieve_body( $response );
		$body = json_decode( $body );

		if ( isset( $body->error ) && justwpforms_debug_log_enabled() ) {
			$error = new WP_Error( $this->id, $body->error->message );

			justwpforms_log_error( $error );
		}

		return $body;
	}

	/**
	 * MailerLite refers to lists as groups, hence `get_groups` method.
	 *
	 * @return array Groups.
	 */
	public function get_groups() {
		if ( ! is_null( $this->groups ) ) {
			return $this->groups;
		}

		$response = $this->make_request( 'GET', $this->endpoint_groups );

		if ( isset( $response->error ) ) {
			return $this->groups;
		}

		$groups = array_map( function( $group ) {
			$group = array(
				'id' => $group->id,
				'name' => $group->name,
			);

			return $group;
		}, $response );

		$this->groups = $groups;

		return $this->groups;
	}

	/**
	 * Calls sanitize method based on field type.
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

			switch( $field['type'] ) {
				case 'DATE':
					$function = array( $this, 'sanitize_date_field' );
					break;
				default:
					break;
			}
		}

		$value = call_user_func( $function, $field_value );

		return $value;
	}

	/**
	 * Sanitize date field to the format readable by MailerLite which is MySQL timestamp.
	 *
	 * @param string $field_value Value submitted in the form.
	 *
	 * @return string Sanitized value.
	 */
	public function sanitize_date_field( $field_value ) {
		$date  = strtotime( $field_value );
		$value = date( 'Y-m-d H:i:s', $date );

		return $value;
	}

	/**
	 * Add subscriber to MailerLite.
	 *
	 * @return void
	 */
	public function add_subscriber( $data ) {
		if ( empty( $data['fields'] ) ) {
			return;
		}

		if ( empty( $data['group_id'] ) ) {
			return;
		}

		// Email and Name are pushed directly to the top level. All other fields go to `fields` array.
		$direct_fields = array(
			'email',
			'name',
			'signup_ip',
		);

		$body = array(
			'email'  => '',
			'name'   => '',
			'signup_ip',
			'fields' => array(),
			'resubscribe' => true,
		);

		foreach ( $data['fields'] as $field_key => $field_value ) {
			$sanitized_value = $this->sanitize_field( $field_key, $field_value );

			// If this field is found in `$direct_fields` array, push it to top level.
			if ( in_array( $field_key, $direct_fields ) ) {
				$body[$field_key] = $sanitized_value;
			// Otherwise add it to `fields` array.
			} else {
				$body['fields'][$field_key] = $sanitized_value;
			}
		}

		$group_ids = array( $data['group_id'] );

		if ( 'all' === $data['group_id'] ) {
			$group_ids = array_values( wp_list_pluck( $this->get_groups(), 'id' ) );
		}

		foreach( $group_ids as $group_id ) {
			$endpoint = sprintf( $this->endpoint_groups_subcribers, $group_id );
			$response = $this->make_request( 'POST', $endpoint, $body );
		}

		return $response;
	}

	/**
	 * Gets all fields.
	 *
	 * @return array Fields.
	 */
	public function get_fields() {
		if ( ! is_null( $this->fields ) ) {
			return $this->fields;
		}

		$response = $this->make_request( 'GET', $this->endpoint_fields );
		$fields = array();

		if ( isset( $response->error ) ) {
			return $this->fields;
		}

		if ( ! empty( $response ) ) {
			$fields = array_map( function( $field ) {
				$field = array(
					'id' => $field->key,
					'name' => $field->title,
					'type' => $field->type,
				);

				return $field;
			}, $response );
		}

		$this->fields = $fields;

		return $this->fields;
	}

	public function unsubscribe( $data ) {
		if ( '' === $data['group_id'] ) {
			return;
		}

		if ( ! isset( $data['email'] ) ) {
			return;
		}

		$group_ids = array( $data['group_id'] );

		if ( 'all' === $data['group_id'] ) {
			$group_ids = array_values( wp_list_pluck( $this->get_groups(), 'id' ) );
		}

		$email = $data['email'];

		foreach( $group_ids as $group_id ) {
			$endpoint = sprintf( $this->endpoint_groups_subcriber, $group_id, $email );
			$response = $this->make_request( 'DELETE', $endpoint );
		}
	}

}
