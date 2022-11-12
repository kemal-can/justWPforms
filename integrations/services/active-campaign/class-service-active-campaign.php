<?php

class justwpforms_Service_ActiveCampaign extends justwpforms_Service {

	public $id = 'active-campaign';
	public $group = 'email';
	public $api_endpoint = '%s/admin/api.php';
	public $lists = null;
	public $fields = null;

	public function __construct() {
		$this->label = __( 'ActiveCampaign', 'justwpforms' );
	}

	public function get_default_credentials() {
		$credentials = array(
			'api_url' => '',
			'api_key' => '',
		);

		return $credentials;
	}

	public function set_credentials( $credentials = array(), $raw = array() ) {
		$this->credentials = wp_parse_args( $credentials, $this->get_default_credentials() );
	}

	public function admin_widget( $previous_credentials = array() ) {
		require_once( justwpforms_get_integrations_folder() . '/services/active-campaign/partial-widget.php' );
	}

	public function is_connected() {
		$is_connected = (
			! empty( $this->credentials['api_url'] )
			&& ! empty( $this->credentials['api_key'] )
		);

		return $is_connected;
	}

	public function load() {
		if ( $this->is_connected() ) {
			require_once( justwpforms_get_integrations_folder() . '/services/active-campaign/class-integration-active-campaign.php' );
		}
	}

	public function make_request( $method, $action, $args = array(), $body = false ) {
		$api_key = $this->credentials['api_key'];
		$api_url = $this->credentials['api_url'];
		$endpoint = sprintf( $this->api_endpoint, $api_url );
		$args = wp_parse_args( $args, array(
			'api_action' => $action,
			'api_key' => $api_key,
			'api_output' => 'serialize'
		) );
		$endpoint = add_query_arg( $args, $endpoint );
		$request = array(
			'method' => $method,
		);

		if ( $body ) {
			$request['body'] = $body;
		}

		$response = $this->make_api_request( $endpoint, $request );
		$response = wp_remote_retrieve_body( $response );
		$response = unserialize( $response );

		if ( ! $response || ! $response['result_code'] ) {
			$message = isset( $response['result_message'] ) ? $response['result_message'] : __( 'Unknown', 'justwpforms' );
			$response = new WP_Error( $this->id, $message );
		}

		if ( is_wp_error( $response ) && justwpforms_debug_log_enabled() ) {
			justwpforms_log_error( $response );

			return $response;
		}

		$response = array_filter( $response, 'is_int', ARRAY_FILTER_USE_KEY );

		return $response;
	}

	private function fetch_lists_and_fields() {
		$lists = $this->make_request( 'GET', 'list_list', array(
			'ids' => 'all',
			'global_fields' => 1,
			'full' => 1,
		) );

		$this->lists = array();
		$this->fields = array();

		if ( is_wp_error( $lists ) ) {
			return;
		}

		foreach( $lists as $list ) {
			$this->lists[] = array(
				'id' => $list['id'],
				'name' => $list['name'],
			);

			$list_fields = array();

			foreach( $list['fields'] as $field ) {
				$list_fields[] = array(
					'id' => $field['id'],
					'name' => $field['title'],
					'type' => $field['type'],
					'options' => isset( $field['options'] ) ? $field['options'] : array(),
				);
			}

			$this->fields[$list['id']] = array_merge( $this->get_standard_fields(), $list_fields );
		}
	}

	public function get_standard_fields() {
		$fields = array( array(
			'id' => 'email',
			'name' => __( 'Email', 'justwpforms' ),
			'type' => 'text',
			'options' => array(),
		), array(
			'id' => 'name',
			'name' => __( 'Full name', 'justwpforms' ),
			'type' => 'text',
			'options' => array(),
		), array(
			'id' => 'first_name',
			'name' => __( 'First name', 'justwpforms' ),
			'type' => 'text',
			'options' => array(),
		), array(
			'id' => 'last_name',
			'name' => __( 'Last name', 'justwpforms' ),
			'type' => 'text',
			'options' => array(),
		), array(
			'id' => 'phone',
			'name' => __( 'Phone', 'justwpforms' ),
			'type' => 'text',
			'options' => array(),
		) );

		return $fields;
	}

	public function get_lists() {
		if ( ! is_null( $this->lists ) ) {
			return $this->lists;
		}

		$this->fetch_lists_and_fields();

		return $this->lists;
	}

	public function get_fields() {
		if ( ! is_null( $this->fields ) ) {
			return $this->fields;
		}

		$this->fetch_lists_and_fields();

		return $this->fields;
	}

	public function create_contact( $list_id, $fields = array(), $tags = '' ) {
		if ( '' === $list_id ) {
			return;
		}

		$standard_fields = wp_list_pluck( $this->get_standard_fields(), 'name', 'id' );
		$contact = array_intersect_key( $fields, $standard_fields );
		$contact['ip4'] = $fields['ip4'];
		$contact["p[$list_id]"] = $list_id;
		$contact["status[$list_id]"] = $fields['status'];
		$contact['tags'] = $tags;

		if ( 2 === $fields['status'] && ! empty( $contact['tags'] ) ) {
			$this->make_request( 'POST', 'contact_tag_remove', array(), $contact );
			unset( $contact['tags'] );
		}

		unset( $fields['status'] );
		$custom_fields = array_diff_key( $fields, $contact );

		foreach( $custom_fields as $id => $value ) {
			if ( empty( $value ) ) {
				continue;
			}

			if ( is_array( $value ) ) {
				$value = '||' . implode( '||', $value ) . '||';
			}

			$contact["field[$id,0]"] = $value;
		}

		$contact = $this->make_request( 'POST', 'contact_sync', array(), $contact );
	}

}
