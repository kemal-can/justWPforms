<?php

class justwpforms_Service_MailPoet extends justwpforms_Service {

	public $id= 'mailpoet';
	public $group = 'email';
	public $display_widget = false;

	public $api = null;
	public $lists= null;
	public $fields = null;
	
	public $reserved_fields = null;

	public function __construct() {
		$this->label = __( 'MailPoet', 'justwpforms' );
	}

	public function get_default_credentials() {
		return array();
	}

	public function set_credentials( $credentials = array(), $raw = array() ) {
		$this->credentials = wp_parse_args( $credentials, $this->get_default_credentials() );
	}

	public function is_connected() {
		$is_connected = ( class_exists( \MailPoet\API\API::class ) );

		return $is_connected;
	}

	/**
	 * Loads service and integration.
	 *
	 * MailPoet is different from other more traditional email services because it communicates with PHP API
	 * rather than making requests to API servers.
	 *
	 * This method checks if MailPoet plugin is activated and then stores API's instance to `api` var.
	 *
	 * There are also some reserved fields in MailPoet which we define here.
	 *
	 * @return void
	 */
	public function load() {
		if ( $this->is_connected() ) {
			$this->api = \MailPoet\API\API::MP( 'v1' );

			$this->reserved_fields = array(
				array(
					'id' => 'email',
					'name' => __( 'Email address', 'justwpforms' ),
					'type' => 'email',
				),
				array(
					'id' => 'first_name',
					'name' => __( 'First name', 'justwpforms' ),
					'type' => 'text',
				),
				array(
					'id' => 'last_name',
					'name' => __( 'Last name', 'justwpforms' ),
					'type' => 'text',
				)
			);

			require_once( justwpforms_get_integrations_folder() . '/services/mailpoet/class-integration-mailpoet.php' );
		}
	}

	public function log_error( $message ) {
		if ( empty( $message ) || ! justwpforms_debug_log_enabled() ) {
			return;
		}

		$error = new WP_Error( $this->id, $message );

		justwpforms_log_error( $error );
	}

	/**
	 * Get all lists using try catch.
	 *
	 * @return array Lists.
	 */
	public function get_lists() {
		if ( ! is_null( $this->lists ) ) {
			return $this->lists;
		}

		try {
			$lists = $this->api->getLists();
		} catch ( \MailPoet\API\MP\v1\APIException $e ) {
			$this->log_error( $e->getMessage() );

			return $this->lists;
		}

		if ( empty( $lists ) ) {
			return $this->lists;
		}

		$this->lists = array_map( function( $list ) {
			$list = array(
				'id' => $list['id'],
				'name' => $list['name'],
			);

			return $list;
		}, $lists );

		return $this->lists;
	}

	/**
	 * Get all fields in MailPoet.
	 *
	 * @return array Fields.
	 */
	public function get_fields() {
		$mp_fields = \MailPoet\Models\CustomField::findMany();

		if ( ! $mp_fields ) {
			return $this->reserved_fields;
		}

		$fields = array();

		foreach ( $mp_fields as $field ) {
			// Add a `cf_` prefix to field ID because that's how field is stored internally in MailPoet.
			$field_data = array(
				'id' => 'cf_' . $field->id,
				'name' => $field->name,
				'type' => $field->type,
			);

			if ( 'checkbox' === $field->type ) {
				$field_data['values'] = $field->params;
			}

			$fields[] = $field_data;
		}

		$this->fields = array_merge( $this->reserved_fields, $fields );

		return $this->fields;
	}

	/**
	 * Calls sanitize method to sanitize the field value based on field type.
	 *
	 * @param string $field_key
	 * @param string $field_value Value of the field submitted through the form.
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

			switch ( $field['type'] ) {
				case 'email':
					$function = 'sanitize_email';
					break;
				case 'date':
					$function = array( $this, 'sanitize_date_field' );
					break;
				case 'checkbox':
					$function = array( $this, 'sanitize_checkbox' );
					break;
				default:
					break;
			}
		}

		$value = call_user_func( $function, $field_value );

		return $value;
	}

	/**
	 * Sanitize date field to the format readable by MailPoet.
	 *
	 * @param string $field_value Field value as submitted in the form.
	 *
	 * @return string Sanitized date.
	 */
	public function sanitize_date_field( $field_value ) {
		$date  = strtotime( $field_value );

		$value = array(
			'year' => date( 'Y', $date ),
			'month' => date( 'n', $date ),
			'day' => date( 'j', $date ),
		);

		return $value;
	}

	/**
	 * Sanitize checkbox to the format readable by MailPoet.
	 *
	 * MailPoet accepts `0` in case of unchecked checkbox and `1` in case it's checked.
	 *
	 * @param string $field_value Value as submitted in the form.
	 *
	 * @return int Sanitized value.
	 */
	public function sanitize_checkbox( $field_value ) {
		$value = 1;

		if ( 1 !== (int) $field_value && 'yes' !== strtolower( $field_value ) && $expected_value !== $field_value ) {
			$value = 0;
		}

		return $value;
	}

	/**
	 * Add subscriber data to MailPoet.
	 *
	 * @return void
	 */
	public function add_subscriber( $data ) {
		if ( empty( $data['fields'] ) ) {
			return;
		}

		if ( empty( $data['list_id'] ) ) {
			return;
		}

		$subscriber = null;
		$subscriber_fields = array(
			'email' => '',
		);
		$subscriber_lists = array( $data['list_id'] );

		if ( 'all' === $data['list_id'] ) {
			$subscriber_lists = array_values( wp_list_pluck( $this->get_lists(), 'id' ) );
		}

		$subscriber_options = array(
			'send_confirmation_email' => (int) $data['send_confirmation_email'],
		);

		if ( ! isset( $data['fields']['email'] ) || empty( $data['fields']['email'] ) ) {
			return;
		}

		$email = $data['fields']['email'];

		foreach ( $data['fields'] as $field_key => $field_value ) {
			$subscriber_fields[$field_key] = $this->sanitize_field( $field_key, $field_value );
		}

		/**
		 * Check if there's existing subscriber with this email.
		 */
		try {
			$subscriber = $this->api->getSubscriber( $email );
		} catch ( \MailPoet\API\MP\v1\APIException $e ) {
			$this->log_error( $e->getMessage() );
		}

		/**
		 * If subscriber is not found, add new subscriber.
		 */
		if ( is_null( $subscriber ) ) {
			try {
				$this->api->addSubscriber( $subscriber_fields, $subscriber_lists, $subscriber_options );
			} catch( \MailPoet\API\MP\v1\APIException $e ) {
				$this->log_error( $e->getMessage() );
			}
		/**
		 * Otherwise, subscribe existing subscriber to this list.
		 */
		} else {
			try {
				$this->api->subscribeToLists( $subscriber['id'], $subscriber_lists, $subscriber_options );
			} catch ( \MailPoet\API\MP\v1\APIException $e ) {
				$this->log_error( $e->getMessage() );
			}
		}
	}

	public function unsubscribe( $data ) {
		if ( empty( $data['fields'] ) ) {
			return;
		}

		if ( ! isset( $data['fields']['email'] ) || empty( $data['fields']['email'] ) ) {
			return;
		}

		if ( empty( $data['list_id'] ) ) {
			return;
		}

		$subscriber = null;
		$subscriber_lists = array( $data['list_id'] );

		if ( 'all' === $data['list_id'] ) {
			$subscriber_lists = array_values( wp_list_pluck( $this->get_lists(), 'id' ) );
		}

		try {
			$subscriber = $this->api->getSubscriber( $data['fields']['email'] );
		} catch ( \MailPoet\API\MP\v1\APIException $e ) {
			$this->log_error( $e->getMessage() );
		}

		if ( ! is_null( $subscriber ) ) {
			try {
				$this->api->unsubscribeFromLists( $subscriber['id'], $subscriber_lists );
			} catch ( \MailPoet\API\MP\v1\APIException $e ) {
				$this->log_error( $e->getMessage() );
			}
		}
	}
}
