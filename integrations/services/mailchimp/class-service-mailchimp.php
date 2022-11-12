<?php

class justwpforms_Service_Mailchimp extends justwpforms_Service {

	public $id = 'mailchimp';
	public $group = 'email';

	private $api_url = '';
	private $api_call_headers;

	public $lists = array();

	public function __construct() {
		$this->label = __( 'Mailchimp', 'justwpforms' );
	}

	public function get_default_credentials() {
		return array(
			'key' => '',
		);
	}

	public function set_credentials( $credentials = array(), $raw = array() ) {
		$this->credentials = wp_parse_args( $credentials, $this->get_default_credentials() );
	}

	public function is_connected() {
		$authenticated = ! empty( $this->credentials['key'] );

		return $authenticated;
	}

	public function admin_widget( $previous_credentials = array() ) {
		require_once( justwpforms_get_integrations_folder() . '/services/mailchimp/partial-widget.php' );
	}

	public function configure() {
		$this->set_api_url();
	}

	public function load() {
		if ( $this->is_connected() ) {
			require_once( justwpforms_get_integrations_folder() . '/services/mailchimp/class-integration-mailchimp.php' );
		}
	}

	/**
	 * Sets base API URL.
	 *
	 * In every Mailchimp API key, there's a data centre to base any API calls on included in the key
	 * like so: `key-us19`. We extract this key by doing a little string operation in this method and store the API URL
	 * to `api_url` var.
	 *
	 * @return void
	 */
	public function set_api_url() {
		$credentials = $this->get_credentials();
		$key = $credentials['key'];

		$data_center = substr( $key, strpos( $key, '-' ) + 1, strlen( $key ) );

		$this->api_url = "https://{$data_center}.api.mailchimp.com/3.0";
		$this->api_call_headers = array(
			'Authorization' => 'Basic ' . base64_encode( "user:{$key}" )
		);
	}

	/**
	 * Adds subscriber to the list.
	 *
	 * @param int   $list_id         ID of the list.
	 * @param array $subscriber_data Data from the form to push to Mailchimp.
	 * @param array $tags            List of tags to push to subscriber.
	 *
	 * @return void
	 */
	public function add_subscriber( $list_id, $subscriber_data, $tags ) {
		if ( empty( $list_id ) ) {
			return;
		}

		// First, get merge fields for that list from API.
		$merge_fields = $this->get_merge_fields( $list_id );
		$to_sanitize = array();

		// Loop through all the fields and if it's found in the `$subscriber_data`, run it through sanitization function.
		foreach ( $merge_fields as $field ) {
			if ( isset( $subscriber_data['merge_fields'][$field['id']] ) && ! empty( $field['sanitize'] ) ) {
				$sanitized_value = call_user_func( $field['sanitize'], $subscriber_data['merge_fields'][$field['id']], $field['format'] );
				$subscriber_data['merge_fields'][$field['id']] = $sanitized_value;
			}
		}

		// Send subscriber data to Mailchimp.
		$subscriber_hash = md5( strtolower( $subscriber_data['email_address'] ) );
		$member_request = wp_remote_request(
			"{$this->api_url}/lists/{$list_id}/members/{$subscriber_hash}",
			array(
				'method' => 'PUT',
				'headers' => $this->api_call_headers,
				'body' => json_encode( $subscriber_data )
			)
		);

		// If subscriber already exists, we still want to add tags to the existing entry if applicable.
		if ( ! empty( $tags ) ) {
			$tags_request = wp_remote_post(
				"{$this->api_url}/lists/{$list_id}/members/{$subscriber_hash}/tags",
				array(
					'headers' => $this->api_call_headers,
					'body' => json_encode(
						array(
							'tags' => $tags
						)
					)
				)
			);
		}
	}

	/**
	 * Sanitizes data field to comply with Mailchimp's expected format.
	 *
	 * @param string $value  Value as submitted through the form.
	 * @param string $format Mailchimp's format of the date.
	 *
	 * @return string Sanitized date value.
	 */
	public function sanitize_date_field( $value, $format ) {
		if ( empty( $value ) || empty( $format ) ) {
			return $value;
		}

		// Replace Mailchimp's format with PHP format.
		$replace = array( 'DD', 'MM', 'YYYY' );
		$replace_with = array( 'd', 'm', 'Y' );
		$format = str_replace( $replace, $replace_with, $format );

		// Create time from the value submitted and date parse it to the expected format.
		$date = strtotime( $value );
		$value = date( $format, $date );

		return $value;
	}

	/**
	 * Gets information on merge fields from Mailchimp.
	 *
	 * @param int $list_id Mailchimp list ID.
	 *
	 * @return array List of merge fields.
	 */
	public function get_merge_fields( $list_id ) {
		if ( empty( $list_id ) ) {
			return;
		}

		// Add `count` to the endpoint to make sure maximum number of fields is pulled in one request.
		$endpoint = "{$this->api_url}/lists/{$list_id}/merge-fields?count=150";
		$response = wp_remote_get(
			$endpoint,
			array(
				'headers' => $this->api_call_headers
			)
		);

		$body = json_decode( wp_remote_retrieve_body( $response ) );
		$fields = array();

		if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return $fields;
		}

		if ( ! isset( $body->merge_fields ) ) {
			return $fields;
		}

		/**
		 * Define 'EMAIL' merge field which is not available in Mailchimp's response because it's considered
		 * to be default field.
		 */
		$fields[0] = array(
			'id' => 'EMAIL',
			'name' => __( 'Email Address', 'justwpforms' )
		);

		foreach ( $body->merge_fields as $field ) {
			$field_array = array(
				'id' => $field->tag,
				'name' => $field->name
			);

			if ( ! empty( $field->options ) ) {
				if ( isset( $field->options->date_format ) ) {
					$field_array['sanitize'] = array( $this, 'sanitize_date_field' );
					$field_array['format'] = $field->options->date_format;
				}
			}

			// Do special handling for address merge field as it contains a bunch of sub-fields.
			if ( 'address' === $field->type ) {
				$field_array['items'] = array(
					array(
						'id' => "{$field->tag}::addr1",
						'name' => __( 'Address Line', 'justwpforms' )
					),
					array(
						'id' => "{$field->tag}::city",
						'name' => __( 'City', 'justwpforms' )
					),
					array(
						'id' => "{$field->tag}::state",
						'name' => __( 'State', 'justwpforms' )
					),
					array(
						'id' => "{$field->tag}::zip",
						'name' => __( 'ZIP', 'justwpforms' )
					),
				);

				// Disable address field mapping.
				continue;
			}

			$fields[$field->display_order] = $field_array;
		}

		ksort( $fields, SORT_NUMERIC );

		return $fields;
	}

	/**
	 * Get all available lists.
	 *
	 * @return array Lists.
	 */
	public function get_lists() {
		if ( ! empty( $this->lists ) ) {
			return $this->lists;
		}

		$endpoint = $this->api_url . '/lists?count=1000';
		$response = wp_remote_get(
			$endpoint,
			array(
				'headers' => $this->api_call_headers
			)
		);

		$body = json_decode( wp_remote_retrieve_body( $response ) );

		if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return $this->lists;
		}

		if ( ! isset( $body->lists ) ) {
			return $this->lists;
		}

		if ( is_array( $body->lists ) ) {
			$this->lists = $body->lists;
		}

		return $this->lists;
	}

	/**
	 * Get all groups for given list ID.
	 *
	 * @param int $list_id ID of the list.
	 *
	 * @return array List of groups.
	 */
	public function get_groups( $list_id ) {
		if ( empty( $list_id ) ) {
			return;
		}

		$endpoint = "{$this->api_url}/lists/{$list_id}/interest-categories";
		$response = wp_remote_get(
			$endpoint,
			array(
				'headers' => $this->api_call_headers
			)
		);

		$body = json_decode( wp_remote_retrieve_body( $response ) );

		$groups = array();

		if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return $groups;
		}

		if ( ! isset( $body->categories ) ) {
			return $groups;
		}

		/**
		 * API comes back with the data on groups. Those are just top-level groups though. The actual options
		 * need to be pulled in separate requests for each group.
		 */
		foreach ( $body->categories as $group ) {
			$group_array = array(
				'id' => $group->id,
				'title' => $group->title,
				'type' => $group->type,
				'options' => array()
			);

			$interests_endpoint = "{$endpoint}/{$group->id}/interests";
			$interests_response = wp_remote_get(
				$interests_endpoint,
				array(
					'headers' => $this->api_call_headers
				)
			);

			$interests_body = $interests_response['body'];
			$interests_json = json_decode( $interests_body );

			foreach( $interests_json->interests as $interest_group ) {
				$group_array['options'][] = array(
					'value' => $interest_group->id,
					'label' => $interest_group->name
				);
			}

			$groups[] = $group_array;
		}

		return $groups;
	}

}
