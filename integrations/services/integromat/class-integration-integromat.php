<?php

class justwpforms_Integration_Integromat {

	private static $instance;
	private static $hooked = false;
	private $service;

	public $action_auth = 'justwpforms_integromat_auth';
	public $action_subscribe = 'justwpforms_integromat_subscribe';
	public $action_unsubscribe = 'justwpforms_integromat_unsubscribe';
	public $action_forms = 'justwpforms_integromat_forms';
	public $action_output_fields = 'justwpforms_integromat_output_fields';

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		self::$instance->hook();

		return self::$instance;
	}

	public function __construct() {
		$this->service = justwpforms_get_integrations()->get_service( 'integromat' );
	}

	public function hook() {
		require_once( justwpforms_get_integrations_folder() . '/services/integromat/helper-integromat.php' );

		add_action( 'init', array( $this, 'parse_request' ) );
		add_action( 'justwpforms_pending_submission_success', array( $this, 'submission_success' ), 10 );
		add_filter( 'justwpforms_integromat_part_visible', array( $this, 'part_is_visible' ), 10, 2 );
		add_filter( 'justwpforms_integromat_part_value', array( $this, 'part_value' ), 10, 2 );
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

				case $this->action_forms:
					return $this->service->get_forms();
					break;

				case $this->action_output_fields:
					return $this->service->get_output_fields();
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
			$hash_ids = maybe_unserialize( $value );
			$hash_ids = array_filter( array_values( $hash_ids ) );
			$attachments = justwpforms_get_attachment_controller()->get( array(
				'hash_id' => $hash_ids,
			) );

			$attachment_ids = wp_list_pluck( $attachments, 'ID' );
			$value = array_map( 'wp_get_attachment_url', $attachment_ids );
		} else if( 'table' === $part['type'] ) {
			$value = str_replace( '<br>', "\n", $value );
		} else if( 'signature' === $part['type'] ) {
			$value = maybe_unserialize( $value );
			$value = $value['signature'];
		}

		return $value;
	}

}

if ( ! function_exists( 'justwpforms_get_integration_integromat' ) ):

function justwpforms_get_integration_integromat() {
	$instance = justwpforms_Integration_Integromat::instance();
	$instance->hook();

	return $instance;
}

endif;

justwpforms_get_integration_integromat();
