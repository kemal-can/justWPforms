<?php

class justwpforms_Integration_Integrately {

	private static $instance;
	private static $hooked = false;
	private $service;

	public $action_auth = 'justwpforms_integrately_auth';
	public $action_subscribe = 'justwpforms_integrately_subscribe';
	public $action_unsubscribe = 'justwpforms_integrately_unsubscribe';
	public $action_samples = 'justwpforms_integrately_samples';
	public $action_output_fields = 'justwpforms_integrately_output_fields';
	public $action_forms = 'justwpforms_integrately_forms';
	public $action_activity = 'justwpforms_integrately_activity';

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		self::$instance->hook();

		return self::$instance;
	}

	public function __construct() {
		$this->service = justwpforms_get_integrations()->get_service( 'integrately' );
	}

	public function hook() {
		require_once( justwpforms_get_integrations_folder() . '/services/integrately/helper-integrately.php' );

		add_action( 'parse_request', array( $this, 'parse_request' ) );
		add_action( 'justwpforms_pending_submission_success', array( $this, 'submission_success' ), 10 );
		add_filter( 'justwpforms_integrately_part_visible', array( $this, 'part_is_visible' ), 10, 2 );
		add_filter( 'justwpforms_integrately_part_value', array( $this, 'part_value' ), 10, 2 );
	}

	public function parse_request() {
		if ( isset( $_REQUEST['action'] ) ) {
			switch ( $_REQUEST['action'] ) {
				case $this->action_auth:
					return $this->service->authorize();
					break;

				case $this->action_subscribe:
					return $this->service->subscribe();
					break;

				case $this->action_unsubscribe:
					return $this->service->unsubscribe();
					break;

				case $this->action_samples:
					return $this->service->get_samples();
					break;

				case $this->action_output_fields:
					return $this->service->get_output_fields();
					break;

				case $this->action_forms:
					return $this->service->get_forms();
					break;
			}
		}
	}

	public function submission_success( $submission_id ) {
		$submission = justwpforms_get_message_controller()->get( $submission_id );
		$form = justwpforms_get_form_controller()->get( $submission['form_id'] );
		
		return $this->service->push_activity( $submission, $form );
	}

	public function part_is_visible( $visible, $part ) {
		switch( $part['type'] ) {
			case 'page_break':
			case 'layout_title':
			case 'placeholder':
			case 'media':
			case 'divider':
				$visible = false;
				break;
		}

		return $visible;
	}

	public function part_value( $value, $part ) {
		if ( 'attachment' === $part['type'] ) {
			$value = maybe_unserialize( $value );
			$value = array_filter( array_values( $value ) );

			if ( ! empty( $value ) ) {
				$attachments = justwpforms_get_attachment_controller()->get( array(
					'hash_id' => $value,
				) );

				$attachment_ids = wp_list_pluck( $attachments, 'ID' );
				$value = array_map( 'wp_get_attachment_url', $attachment_ids );
			}
		} else if( 'table' === $part['type'] ) {
			$value = str_replace( '<br>', "\n", $value );
		} else if( 'signature' === $part['type'] ) {
			$value = maybe_unserialize( $value );
			$value = $value['signature'];
		}

		return $value;
	}

}

if ( ! function_exists( 'justwpforms_get_integration_integrately' ) ):

function justwpforms_get_integration_integrately() {
	$instance = justwpforms_Integration_Integrately::instance();
	$instance->hook();

	return $instance;
}

endif;

justwpforms_get_integration_integrately();
