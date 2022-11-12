<?php

class justwpforms_Service_Sendinblue extends justwpforms_Service {

	public $id    = 'sendinblue';
	public $group = 'email';

	public $endpoint_folders = 'https://api.sendinblue.com/v3/contacts/folders';
	public $endpoint_lists = 'https://api.sendinblue.com/v3/contacts/lists';
	public $endpoint_contacts = 'https://api.sendinblue.com/v3/contacts';
	public $endpoint_update_contact = 'https://api.sendinblue.com/v3/contacts/%s';
	public $endpoint_contacts_doi = 'https://api.sendinblue.com/v3/contacts/doubleOptinConfirmation';
	public $endpoint_attributes = 'https://api.sendinblue.com/v3/contacts/attributes';
	public $endpoint_templates = 'https://api.sendinblue.com/v3/smtp/templates';

	public $fields = null;
	public $folders = null;
	public $lists = null;
	public $templates = array();

	public function __construct() {
		$this->label = __( 'Sendinblue', 'justwpforms' );
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
		require_once( justwpforms_get_integrations_folder() . '/services/sendinblue/partial-widget.php' );
	}

	public function is_connected() {
		$is_connected = ! empty( $this->credentials['key'] );

		return $is_connected;
	}

	public function load() {
		if ( $this->is_connected() ) {
			require_once( justwpforms_get_integrations_folder() . '/services/sendinblue/class-integration-sendinblue.php' );
		}
	}

	public function make_request( $method, $endpoint, $body = false ) {
		$key = $this->credentials['key'];

		$arguments = array(
			'method'  => $method,
			'headers' => array(
				'Content-Type' => 'application/json',
				'api-key' => $key,
			)
		);

		if ( $body ) {
			$arguments['body'] = json_encode( $body );
		}

		$response = $this->make_api_request( $endpoint, $arguments );
		$body = wp_remote_retrieve_body( $response );
		$body = json_decode( $body );

		if ( isset( $body->code ) && justwpforms_debug_log_enabled() ) {
			$error = new WP_Error( $this->id, $body->message );

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

		$folders  = $this->get_folders();

		/**
		 * Set `?limit` argument to endpoint URL. The default value is `10`.
		 *
		 * `50` is the maximum amount Sendinblue's API can return in single request.
		 */
		$endpoint = add_query_arg( 'limit', 50, $this->endpoint_lists );
		$response = $this->make_request( 'GET', $endpoint );

		if ( ! $response ) {
			return $this->lists;
		}

		$this->lists = array();

		$lists = array_map( function( $list ) {
			$list = array(
				'id' => $list->id,
				'folder_id' => $list->folderId,
				'name' => $list->name,
			);

			return $list;
		}, $response->lists );

		foreach ( $lists as $list ) {
			$folder_index = array_search( $list['folder_id'], array_column( $folders, 'id' ) );
			$folder_name  = $folders[$folder_index]['name'];

			$this->lists[$folder_name][] = $list;
		}

		return $this->lists;
	}

	/**
	 * Gets folders from API.
	 *
	 * Folders in Sendinblue are used for further categorization of lists. We show them in `<optgroup>`.
	 *
	 * @return array List of folders.
	 */
	public function get_folders() {
		if ( ! is_null( $this->folders ) ) {
			return $this->folders;
		}

		$response = $this->make_request( 'GET', $this->endpoint_folders );

		if ( ! $response || ! isset( $response->folders ) ) {
			return $this->folders;
		}

		$this->folders = array_map( function( $folder ) {
			$folder = array(
				'id' => $folder->id,
				'name' => $folder->name,
			);

			return $folder;
		}, $response->folders );

		return $this->folders;
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

		$response = $this->make_request( 'GET', $this->endpoint_attributes );

		if ( ! $response || ! isset( $response->attributes ) ) {
			return $this->fields;
		}

		/**
		 * Add 'EMAIL' field ourselves as it's considered to be a default one not returned by API.
		 * Yet we still need it to be around when looping through fields and adding them to
		 * 'Map this field' dropdown in form fields.
		 */
		$fields = array(
			array(
				'id' => 'EMAIL',
				'name' => 'EMAIL',
				'type' => 'text',
			)
		);

		$pulled_fields = array_filter( $response->attributes, function( $field ) {
			if ( ! property_exists( $field, 'type' ) ) {
				return false;
			}

			if ( 'global' !== $field->category ) {
				return true;
			}

			return false;
		} );

		$pulled_fields = array_map( function( $field ) {
			$field = array(
				'id' => $field->name,
				'name' => $field->name,
				'type' => $field->type,
			);

			return $field;
		}, $pulled_fields );

		$this->fields = array_merge( $fields, $pulled_fields );

		return $this->fields;
	}

	public function get_templates() {
		if ( ! empty( $this->templates ) ) {
			return $this->templates;
		}

		$endpoint = add_query_arg( array(
			'templateStatus' => 'true',
		), $this->endpoint_templates );
		$response = $this->make_request( 'GET', $endpoint );
		
		if ( ! $response || ! isset( $response->templates ) ) {
			return $this->templates;
		}

		$this->templates = array_map( function( $template ) {
			$template = array(
				'id' => $template->id,
				'name' => $template->name,
			);

			return $template;
		}, $response->templates );

		return $this->templates;
	}

	/**
	 * Calls appropriate sanitize method based on the type of field.
	 *
	 * @param string $field_key   Key as seen in array returned by `get_fields`.
	 * @param string $field_value Value to sanitize.
	 *
	 * @return void
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

			switch ( strtolower( $field['type'] ) ) {
				case 'number':
					$function = 'intval';
					break;
				case 'date':
					$function = array( $this, 'sanitize_date_field' );
					break;
				case 'boolean':
					$function = array( $this, 'sanitize_boolean_field' );
					break;
				default:
					break;
			}
		}

		$value = call_user_func( $function, $field_value );

		return $value;
	}

	/**
	 * Sanitize submitted date value to the format accepted by Sendinblue.
	 *
	 * @param string Date as it comes from the submitted form.
	 *
	 * @return string Date in format accepted by Sendinblue.
	 */
	public function sanitize_date_field( $field_value ) {
		$date  = strtotime( $field_value );
		$value = date( 'd-m-Y', $date );

		return $value;
	}

	/**
	 * Sanitize boolean field. This is converting our checkbox field's values that output a stringified `yes`
	 * to the format readable by Sendinblue. Their API expects unsurprisingly either `true` or `false` there.
	 *
	 * @param string $field_value Value from the form.
	 *
	 * @return string Value sanitized for Sendinblue.
	 */
	public function sanitize_boolean_field( $field_value ) {
		$value = strtolower( trim( $field_value ) );

		if ( 'yes' === $value || 'true' === $value ) {
			$value = (bool) true;
		} else {
			$value = (bool) false;
		}

		return $value;
	}

	/**
	 * Push subscriber data to Sendinblue.
	 *
	 * @param array $data Subscriber data.
	 *
	 * @return void
	 */
	public function add_subscriber( $data, $use_double_opt_in = false ) {
		// Return early if there are no fields or list ID specified.
		if ( empty( $data['fields'] ) || empty( $data['list_id'] ) ) {
			return;
		}

		// Return early if there's no email address to push.
		if ( ! isset( $data['fields']['EMAIL'] ) ) {
			return;
		}

		$email = $data['fields']['EMAIL'];

		/**
		 * Unset email address after storing it to variable to prevent API error when we loop through
		 * `$data` and push it to API.
		 */
		unset( $data['fields']['EMAIL'] );

		$body = array(
			'email' => $email,
			'updateEnabled' => true,
		);

		$attributes = array();

		foreach ( $data['fields'] as $field_key => $field_value ) {
			$attributes[$field_key] = $this->sanitize_field( $field_key, $field_value );
		}

		if ( ! empty( $attributes ) ) {
			$body['attributes'] = $attributes;
		}

		$list_ids = array( $data['list_id'] );

		if ( 'all' === $data['list_id'] ) {
			$lists = $this->get_lists();
			$list_ids = array();

			foreach( $lists as $folder_name => $folder_lists ) {
				foreach( $folder_lists as $folder_list ) {
					$list_ids[] = $folder_list['id'];
				}
			}
		}

		if ( ! $use_double_opt_in ) {
			$body['listIds'] = $list_ids;
			$response = $this->make_request( 'POST', $this->endpoint_contacts, $body );
			
			return $response;
		}

		if ( empty( $data['double_opt_in_template'] ) || empty( $data['double_opt_in_redirect_url'] ) ) {
			return;
		}

		$body['includeListIds'] = $list_ids;
		$body['templateId'] = $data['double_opt_in_template'];
		$body['redirectionUrl'] = $data['double_opt_in_redirect_url'];
		$response = $this->make_request( 'POST', $this->endpoint_contacts_doi, $body );
			
		return $response;
	}

	public function unsubscribe( $data ) {
		if ( ! isset( $data['fields']['EMAIL'] ) ) {
			return;
		}

		$email = $data['fields']['EMAIL'];
		$endpoint_update_contact = sprintf( $this->endpoint_update_contact, $email );

		if ( '' === $data['list_id'] ) {
			return;
		}

		$list_ids = array( $data['list_id'] );

		if ( 'all' === $data['list_id'] ) {
			$lists = $this->get_lists();
			$list_ids = array();

			foreach( $lists as $folder_name => $folder_lists ) {
				foreach( $folder_lists as $folder_list ) {
					$list_ids[] = $folder_list['id'];
				}
			}
		}

		$this->make_request( 'PUT', $endpoint_update_contact, array(
			'unlinkListIds' => $list_ids,
		) );
	}
}
