<?php

class justwpforms_Service_Integrately extends justwpforms_Service {

	public $id = 'integrately';
	public $group = 'automation';
	public $api_key_header = 'HTTP_X_HF_INTEGRATELY_API_KEY';

	public function __construct() {
		$this->label = __( 'Integrately', 'justwpforms' );
	}

	public function get_default_credentials() {
		$credentials = array(
			'api_key' => '',
			'subscriptions' => array(),
		);

		return $credentials;
	}

	public function set_credentials( $credentials = array(), $raw = array() ) {
		$this->credentials = wp_parse_args( $credentials, $this->get_default_credentials() );

		if ( empty( $this->credentials['subscriptions'] ) ) {
			$this->credentials['subscriptions'] = array();
		}
	}

	public function admin_widget( $previous_credentials = array() ) {
		require_once( justwpforms_get_integrations_folder() . '/services/integrately/partial-widget.php' );
	}

	public function is_connected() {
		return true;
	}

	public function load() {
		if ( $this->is_connected() ) {
			require_once( justwpforms_get_integrations_folder() . '/services/integrately/class-integration-integrately.php' );
		}
	}

	public function authorized() {
		if ( ! isset( $_SERVER[$this->api_key_header] ) ) {
			return false;
		}

		if ( $_SERVER[$this->api_key_header] !== $this->credentials['api_key'] ) {
			return false;
		}

		return true;
	}

	public function authorize() {
		if ( ! $this->authorized() ) {
			wp_send_json_error( array(
				'message' => __( 'Invalid API Key', 'justwpforms' ),
			), 403 );
		}

		$site_title = get_bloginfo( 'name' );

		wp_send_json_success( array(
			'title' => $site_title,
		) );
	}

	public function subscribe() {
		if ( ! $this->authorized() ) {
			wp_send_json_error( array(
				'message' => __( 'Invalid API Key', 'justwpforms' ),
			), 403 );
		}

		if ( ! isset( $_REQUEST['webhook_url'] )
			|| ! isset( $_REQUEST['form_id'] ) ) {

			wp_send_json_error( array(
				'message' => __( 'Missing subscription parameters', 'justwpforms' ),
			), 403 );
		}

		$webhook_url = $_REQUEST['webhook_url'];
		$form_id = $_REQUEST['form_id'];
		$subscription_id = md5( $webhook_url );

		$this->credentials['subscriptions'][] = array(
			'url' => $webhook_url,
			'form_id' => $form_id,
			'id' => $subscription_id,
		);

		justwpforms_get_integrations()->write_credentials();

		wp_send_json_success( array(
			'subscription_id' => $subscription_id,
		) );
	}

	public function unsubscribe() {
		if ( ! $this->authorized() ) {
			wp_send_json_error( array(
				'message' => __( 'Invalid API Key', 'justwpforms' ),
			), 403 );
		}

		if ( ! isset( $_REQUEST['subscription_id'] ) ) {
			wp_send_json_error( array(
				'message' => __( 'Missing subscription parameters', 'justwpforms' ),
			), 403 );
		}

		$subscription_id = $_REQUEST['subscription_id'];

		foreach( $this->credentials['subscriptions'] as $s => $subscription ) {
			if ( $subscription['id'] === $subscription_id ) {
				unset( $this->credentials['subscriptions'][$s] );
				break;
			}
		}

		justwpforms_get_integrations()->write_credentials();

		wp_send_json_success( array(
			'subscription_id' => $subscription_id,
		) );
	}

	public function get_samples() {
		if ( ! $this->authorized() ) {
			wp_send_json_error( array(
				'message' => __( 'Invalid API Key', 'justwpforms' ),
			), 403 );
		}

		if ( ! isset( $_REQUEST['form_id'] ) ) {
			wp_send_json_error( array(
				'message' => __( 'Missing form parameter', 'justwpforms' ),
			), 403 );
		}

		$form_id = $_REQUEST['form_id'];
		$form = justwpforms_get_form_controller()->get( $form_id );

		if ( ! $form ) {
			wp_send_json_error( array(
				'message' => __( 'The selected form doesn\'t exist', 'justwpforms' ),
			), 403 );
		}

		$activities = justwpforms_get_message_controller()->get_by_form( $form_id, false, 10 );
		$self = $this;

		if ( count( $activities ) ) {
			$activities = array_map( function( $activity ) use( $self, $form ) {
				return $self->get_activity_entry( $activity, $form );
			}, $activities );
			$activities = wp_list_sort( $activities, 'id', 'DESC' );
		} else {
			$now = new DateTime();
			$created_at = $now->format( 'c' );
			$activities = array( array(
				'id' => 1,
				'created_at' => $created_at,
			) );
		}
		$activities = array_values( $activities );

		wp_send_json_success( $activities );
	}

	public function get_activity_entry( $activity, $form ) {
		$parts = array_filter( $form['parts'], 'justwpforms_integrately_part_visible' );
		$part_ids = wp_list_pluck( $parts, 'id' );
		$parts = array_combine( $part_ids, $parts );

		foreach( $parts as $part_id => $part_value ) {
			$part = $parts[$part_id];
			$part_value = $activity['parts'][$part_id];
			$parts[$part_id] = justwpforms_integrately_get_part_value( $part_value, $part, $form, $activity );
		}

		$activity_id = $activity['ID'];
		$created_at = get_the_date( 'c', $activity_id );
		$metadata = array(
			'ip' => justwpforms_get_meta( $activity_id, 'client_ip', true ),
			'referer' => justwpforms_get_meta( $activity_id, 'client_referer', true ),
		);

		$activity = array(
			'id' => $activity_id,
			'created_at' => $created_at,
			'form' => $parts,
			'metadata' => $metadata,
		);

		$activity = apply_filters( 'justwpforms_integrately_activity_entry', $activity );

		return $activity;
	}

	public function get_output_fields() {
		if ( ! $this->authorized() ) {
			wp_send_json_error( array(
				'message' => __( 'Invalid API Key', 'justwpforms' ),
			), 403 );
		}

		if ( ! isset( $_REQUEST['form_id'] ) ) {
			wp_send_json_error( array(
				'message' => __( 'Missing form parameter', 'justwpforms' ),
			), 403 );
		}

		$form_id = $_REQUEST['form_id'];
		$form = justwpforms_get_form_controller()->get( $form_id );

		if ( ! $form ) {
			wp_send_json_error( array(
				'message' => __( 'The selected form doesn\'t exist', 'justwpforms' ),
			), 403 );
		}

		$submission_label = __( 'Form data', 'justwpforms' );
		$parts = array_filter( $form['parts'], 'justwpforms_integrately_part_visible' );

		$fields = array_map( function( $part ) use( $submission_label ) {
			$field = array(
				'key' => 'form__' . $part['id'],
				'label' => $submission_label . ': ' . justwpforms_get_part_label( $part ),
			);

			return $field;
		}, $parts );

		$fields[] = array(
			'key' => 'metadata__ip',
			'label' => __( 'Metadata: IP', 'justwpforms' ),
		);

		$fields[] = array(
			'key' => 'metadata__referer',
			'label' => __( 'Metadata: Referer', 'justwpforms' ),
		);

		$fields = apply_filters( 'justwpforms_integrately_output_fields', $fields );
		$fields = array_values( $fields );

		wp_send_json_success( $fields );
	}

	public function push_activity( $activity, $form ) {
		$activity = $this->get_activity_entry( $activity, $form );

		foreach( $this->credentials['subscriptions'] as $subscription ) {
			if ( $subscription['form_id'] == $form['ID'] ) {
				$webhook_url = $subscription['url'];
				$body = wp_json_encode( $activity );

				wp_remote_post( $webhook_url, array(
					'headers' => array(
						'Content-Type' => 'application/json',
					),
					'body' => $body,
					'timeout' => 5,
					'blocking' => false,
					'data_format' => 'body',
				) );
			}
		}
	}

	public function get_forms() {
		if ( ! $this->authorized() ) {
			wp_send_json_error( array(
				'message' => __( 'Invalid API Key', 'justwpforms' ),
			), 403 );
		}

		$forms = justwpforms_get_form_controller()->get();
		$forms = array_filter( $forms, function( $form ) {
			return 'publish' === $form['post_status'];
		} );
		$forms = array_map( function( $form ) {
			$form = array(
				'id' => $form['ID'],
				'title' => justwpforms_get_form_title( $form ),
			);

			return $form;
		}, $forms );
		$forms = array_values( $forms );

		wp_send_json_success( $forms );
	}

}
