<?php

class justwpforms_Service_ConvertKit extends justwpforms_Service {

	public $id    = 'convertkit';
	public $group = 'email';

	public $endpoint_forms = 'https://api.convertkit.com/v3/forms';
	public $endpoint_form_subscribe = 'https://api.convertkit.com/v3/forms/%s/subscribe';
	public $endpoint_fields = 'https://api.convertkit.com/v3/custom_fields';
	public $endpoint_tags = 'https://api.convertkit.com/v3/tags';
	public $endpoint_unsubscribe = 'https://api.convertkit.com/v3/unsubscribe';
	public $endpoint_unsubscribe_tag = 'https://api.convertkit.com/v3/tags/%s/unsubscribe';

	public $forms = null;
	public $tags = null;
	public $fields = null;
	public $reserved_fields = null;

	public function __construct() {
		$this->label = __( 'ConvertKit', 'justwpforms' );

		$this->reserved_fields = array(
			array(
				'id' => 'email',
				'name' => __( 'Email Address', 'justwpforms' ),
			),
			array(
				'id' => 'first_name',
				'name' => __( 'First Name', 'justwpforms' ),
			),
		);
	}

	public function get_default_credentials() {
		return array(
			'key' => '',
			'client_secret' => '',
		);
	}

	public function set_credentials( $credentials = array(), $raw = array() ) {
		$this->credentials = wp_parse_args( $credentials, $this->get_default_credentials() );
	}

	public function admin_widget( $previous_credentials = array() ) {
		require_once( justwpforms_get_integrations_folder() . '/services/convertkit/partial-widget.php' );
	}

	public function is_connected() {
		$is_connected = ! empty( $this->credentials['key'] );

		return $is_connected;
	}

	public function load() {
		if ( $this->is_connected() ) {
			require_once( justwpforms_get_integrations_folder() . '/services/convertkit/class-integration-convertkit.php' );
		}
	}

	public function make_request( $method, $endpoint, $body = false ) {
		$key = $this->credentials['key'];

		$arguments = array(
			'method'  => $method,
			'headers' => array(
				'Content-Type' => 'application/json',
			)
		);

		if ( $body ) {
			$arguments['body'] = json_encode( $body );
		}

		$endpoint = add_query_arg( 'api_key', $key, $endpoint );
		$response = $this->make_api_request( $endpoint, $arguments );
		$body = wp_remote_retrieve_body( $response );
		$body = json_decode( $body );

		if ( isset( $body->error ) && justwpforms_debug_log_enabled() ) {
			$message = maybe_serialize( $body->error );
			$error = new WP_Error( $this->id, $message );

			justwpforms_log_error( $error );

			return;
		}

		return $body;
	}

	public function get_forms() {
		if ( ! is_null( $this->forms ) ) {
			return $this->forms;
		}

		$response = $this->make_request( 'GET', $this->endpoint_forms );

		if ( is_null( $response ) || isset( $response->error ) ) {
			return $this->forms;
		}

		$forms = array_map( function( $form ) {
			$form = array(
				'id' => $form->id,
				'name' => $form->name,
			);

			return $form;
		}, $response->forms );

		$this->forms = $forms;

		return $this->forms;
	}

	public function add_subscriber( $data ) {
		$form_ids = array( $data['form_id'] );

		if ( 'all' === $data['form_id'] ) {
			$form_ids = array_values( wp_list_pluck( $this->get_forms(), 'id' ) );
		}

		if ( empty( $data['fields'] ) ) {
			return;
		}

		if ( ! isset( $data['fields']['email'] ) ) {
			return;
		}

		$email = $data['fields']['email'];

		$reserved_field_keys = array_map( function( $field ) {
			return $field['id'];
		}, $this->reserved_fields );

		$custom_fields = array();

		foreach( $form_ids as $form_id ) {
			$endpoint = sprintf(
				$this->endpoint_form_subscribe,
				$form_id
			);

			foreach ( $data['fields'] as $field_key => $field_value ) {
				if ( in_array( $field_key, $reserved_field_keys ) ) {
					$endpoint = add_query_arg( $field_key, $field_value, $endpoint );
				} else {
					$custom_fields[$field_key] = $field_value;
				}
			}

			if ( ! empty( $custom_fields ) ) {
				$endpoint = add_query_arg( 'fields', $custom_fields, $endpoint );
			}

			$response = $this->make_request( 'POST', $endpoint );
		}
		
		if ( ! empty( $data['tags'] ) ) {
			$tags = explode( ',', $data['tags'] );
			$tags_to_create = array();
			$created_tags = array();
			$tag_ids = array();

			foreach ( $tags as $tag_name ) {
				$tag_name = trim( $tag_name );
				$tag_id = $this->get_tag_id( $tag_name );

				if ( ! $tag_id ) {
					$tags_to_create[] = array(
						'name' => $tag_name,
					);
				} else {
					$tag_ids[] = $tag_id;
				}
			}

			if ( ! empty( $tags_to_create ) ) {
				$created_tags = $this->create_tags( $tags_to_create );
			}

			$tag_ids = array_merge( $tag_ids, $created_tags );

			if ( ! empty( $tag_ids ) ) {
				$this->subscribe_to_tags( $tag_ids, $email );
			}
		}
	}

	public function get_fields() {
		if ( ! is_null( $this->fields ) ) {
			return $this->fields;
		}

		$response = $this->make_request( 'GET', $this->endpoint_fields );
		$custom_fields = array();

		if ( isset( $response->error ) ) {
			return $this->fields;
		}

		if ( isset( $response->custom_fields ) ) {
			$custom_fields = array_map( function( $field ) {
				$field = array(
					'id' => $field->key,
					'name' => $field->label,
				);

				return $field;
			}, $response->custom_fields );
		}

		$fields = array_merge( $this->reserved_fields, $custom_fields );

		$this->fields = $fields;

		return $this->fields;
	}

	public function get_tags() {
		if ( ! is_null( $this->tags ) ) {
			return $this->tags;
		}

		$response = $this->make_request( 'GET', $this->endpoint_tags );

		if ( isset( $response->error ) ) {
			return $this->tags;
		}

		if ( isset( $response->tags ) ) {
			$tags = array_map( function( $tag ) {
				$tag = array(
					'id' => $tag->id,
					'name' => $tag->name,
				);

				return $tag;
			}, $response->tags );

			$this->tags = $tags;
		}

		return $this->tags;
	}

	public function create_tags( $tag_names ) {
		$tags = array_map( function( $tag_name ) {
			return array( 'name' => $tag_name );
		}, $tag_names );

		$body = array(
			'tag' => $tags,
		);

		$response = $this->make_request( 'POST', $this->endpoint_tags, $body );

		if ( isset( $response->error ) || ! is_array( $response ) ) {
			return array();
		}

		$tag_ids = array_map( function( $tag ) {
			$tag = $tag->id;

			return $tag;
		}, $response );

		return $tag_ids;
	}

	public function get_tag_id( $name ) {
		$tags = $this->get_tags();

		if ( empty( $tags ) ) {
			return false;
		}

		$tag = array_search( $name, array_column( $tags, 'name' ) );

		if ( ! $tag ) {
			return false;
		}

		return $tags[$tag]['id'];
	}

	public function subscribe_to_tags( $tag_ids, $email ) {
		$first_tag = $tag_ids[0];

		$endpoint = sprintf(
			"{$this->endpoint_tags}/%d/subscribe",
			$first_tag
		);

		$endpoint = add_query_arg( 'email', $email, $endpoint );
		$endpoint = add_query_arg( 'tags', json_encode( $tag_ids ), $endpoint );

		$response = $this->make_request( 'POST', $endpoint );
	}

	public function unsubscribe( $data ) {
		if ( ! isset( $data['fields']['email'] ) ) {
			return;
		}

		$email = $data['fields']['email'];
		$remote_tags = $this->get_tags();

		if ( ! empty( $remote_tags ) ) {
			foreach( explode( ',', $data['tags'] ) as $tag ) {
				$tag_index = array_search( trim( $tag ), array_column( $remote_tags, 'name' ) );

				if ( false === $tag_index ) {
					continue;
				}

				$endpoint_unsubscribe_tag = sprintf( $this->endpoint_unsubscribe_tag, $remote_tags[ $tag_index ]['id'] );
				$endpoint_unsubscribe_tag = add_query_arg( array(
					'api_secret' => $this->credentials['client_secret'],
					'email' => $email,
				), $endpoint_unsubscribe_tag );

				$this->make_request( 'POST', $endpoint_unsubscribe_tag );
			}
		}

		$endpoint_unsubscribe = add_query_arg( array(
			'api_secret' => $this->credentials['client_secret'],
			'email' => $email,
		), $this->endpoint_unsubscribe );

		$this->make_request( 'PUT', $endpoint_unsubscribe );
	}

}
