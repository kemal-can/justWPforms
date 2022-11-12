<?php

class justwpforms_Integration_ConstantContact {

	private static $instance;
	private static $hooked = false;
	private $service;

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		self::$instance->hook();

		return self::$instance;
	}

	public function __construct() {
		$this->service = justwpforms_get_integrations()->get_service( 'constant-contact' );
	}

	public function hook() {
		add_filter( 'justwpforms_meta_fields', array( $this, 'meta_fields' ) );
		add_filter( 'justwpforms_email_controls', array( $this, 'setup_controls' ) );
		add_filter( 'justwpforms_conditional_enabled_setup_controls', array( $this, 'conditional_enabled_setup_controls' ) );

		$supported_parts = $this->get_supported_parts();

		foreach ( $supported_parts as $part ) {
			add_filter( "justwpforms_part_customize_fields_{$part}", array( $this, 'add_part_fields' ) );
			add_action( "justwpforms_part_customize_{$part}_before_advanced_options", array( $this, 'add_part_controls' ) );
		}

		add_action( 'justwpforms_customize_enqueue_scripts', array( $this, 'customize_enqueue_scripts' ) );
		add_action( 'justwpforms_pending_submission_success', array( $this, 'handle_submit' ), 10 );
	}

	public function meta_fields( $fields ) {
		$fields['enable_constant_contact'] = array(
			'default' => 0,
			'sanitize' => 'justwpforms_sanitize_checkbox'
		);

		$fields['constant_contact_list'] = array(
			'default' => 0,
			'sanitize' => 'sanitize_text_field'
		);

		$fields['constant_contact_list_unsubscribe'] = array(
			'default' => 0,
			'sanitize' => 'sanitize_text_field'
		);

		$fields['constant_contact_subscribe_status'] = array(
			'default' => '',
			'sanitize' => 'sanitize_text_field'
		);

		return $fields;
	}

	public function setup_controls( $controls ) {
		$lists = $this->service->get_lists();
		$lists = wp_list_pluck( $lists, 'name', 'id' );
		$list_options = array(
			'all' => __( 'All', 'justwpforms' ),
		);

		foreach( $lists as $list_id => $list_name ) {
			$list_options[$list_id] = $list_name;
		}

		$email_controls = array(
			171 => array(
				'type' => 'group_start',
				'trigger' => $this->service->id,
			),
			172 => array(
				'type' => 'buttongroup',
				'field' => 'constant_contact_subscribe_status',
				'label' => __( 'Set subscription status to', 'justwpforms' ),
				'options' => array(
					'' => 'Subscribed',
					'unsubscribe' => 'Unsubscribed',
				),
			),
			173 => array(
				'type' => 'select',
				'field' => 'constant_contact_list',
				'placeholder' => __( '— Select —', 'justwpforms' ),
				'label' => __( 'List to add submitter\'s email to', 'justwpforms' ),
				'options' => $list_options
			),
			174 => array(
				'type' => 'select',
				'field' => 'constant_contact_list_unsubscribe',
				'placeholder' => __( '— Select —', 'justwpforms' ),
				'label' => __( 'List to remove submitter\'s email from', 'justwpforms' ),
				'options' => $list_options
			),
			175 => array(
				'type' => 'group_end'
			),
		);

		$controls = justwpforms_safe_array_merge( $controls, $email_controls );

		return $controls;
	}

	public function conditional_enabled_setup_controls( $controls ) {
		$lists = $this->service->get_lists();
		$lists = wp_list_pluck( $lists, 'name', 'id' );
		$list_options = array(
			'all' => __( 'All', 'justwpforms' ),
		);

		foreach( $lists as $list_id => $list_name ) {
			$list_options[$list_id] = $list_name;
		}

		$controls['constant_contact_list'] = array(
			'type' => 'select',
			'options' => $list_options,
			'then_text' => __( 'Then add to…', 'justwpforms' )
		);

		$controls['constant_contact_list_unsubscribe'] = array(
			'type' => 'select',
			'options' => $list_options,
			'then_text' => __( 'Then remove from…', 'justwpforms' )
		);

		return $controls;
	}

	public function get_supported_parts() {
		$parts = array(
			'single_line_text',
			'multi_line_text',
			'email',
			'website_url',
			'radio',
			'checkbox',
			'select',
			'number',
			'poll',
			'phone',
			'date',
			'scale',
			'rich_text',
			'title',
			'legal',
			'rating'
		);

		return $parts;
	}

	public function get_field_part_mappings() {
		$parts = array(
			'single_line_text' => array( 'string' ),
			'multi_line_text' => array( 'string' ),
			'email' => array( 'string' ),
			'website_url' => array( 'string' ),
			'radio' => array( 'string' ),
			'checkbox' => array( 'string' ),
			'select' => array( 'string' ),
			'number' => array( 'string' ),
			'poll' => array( 'string' ),
			'phone' => array( 'string' ),
			'date' => array( 'string', 'date' ),
			'scale' => array( 'string' ),
			'rich_text' => array( 'string' ),
			'title' => array( 'string' ),
			'legal' => array( 'string' ),
			'rating' => array( 'string' ),
		);

		return $parts;
	}

	public function add_part_fields( $fields ) {
		$fields['constant_contact_field'] = array(
			'default' => '',
			'sanitize' => 'sanitize_text_field'
		);

		return $fields;
	}

	public function add_part_controls() {
		require( justwpforms_get_integrations_folder() . '/services/constant-contact/templates/partial-part-controls.php' );
	}

	public function customize_enqueue_scripts() {
		$fields = $this->service->get_fields();
		$mappings = $this->get_field_part_mappings();

		$address_field_ids = wp_list_pluck( $this->service->get_address_fields(), 'id' );
		$address_fields = array_values( array_filter( $fields, function( $field ) use( $address_field_ids ) {
			return in_array( $field['id'], $address_field_ids );
		} ) );

		$fields = array_values( array_filter( $fields, function( $field ) use( $address_field_ids ) {
			return ! in_array( $field['id'], $address_field_ids );
		} ) );

		$fields[] = array(
			'id' => 'address',
			'name' => __( 'Address', 'justwpforms' ),
			'items' => $address_fields,
		);

		$constant_contact_data = array(
			'fields' => $fields,
			'mappings' => $mappings,
		);

		wp_localize_script(
			'justwpforms-customize',
			'_justwpformsConstantContactSettings',
			$constant_contact_data
		);
	}

	public function handle_submit( $submission_id ) {
		$submission = justwpforms_get_message_controller()->get( $submission_id );
		$form_controller = justwpforms_get_form_controller();
		$form = $form_controller->get( $submission['form_id'] );

		if ( $this->service->id !== $form['active_email_service'] ) {
			return;
		}

		$form = justwpforms_get_conditional_controller()->get( $form, $submission['request'] );
		$email_integration_part = $form_controller->get_first_part_by_type( $form, 'email_integration' );

		if ( $email_integration_part && 'yes' !== $submission[$email_integration_part['id']] ) {
			return;
		}

		$fields = array_filter( $form['parts'], function( $part ) {
			return array_key_exists( 'constant_contact_field', $part ) && '' !== $part['constant_contact_field'];
		} );
		$fields = wp_list_pluck( $fields, 'id', 'constant_contact_field' );

		if ( ! isset( $fields['email_address'] ) ) {
			return;
		}

		unset( $fields[''] );
		
		$field_types = wp_list_pluck( $this->service->get_fields(), 'type', 'id' );

		foreach( $fields as $field => $part_id ) {
			$field_type = $field_types[$field];
			$field_value = $submission['parts'][$part_id];

			if ( 'date' === $field_type ) {
				$field_value = new DateTime( $field_value );
				$field_value = $field_value->format( 'Y-m-d' );
			}

			$fields[$field] = $field_value;
		}

		if ( 'unsubscribe' === justwpforms_get_form_property( $form, 'constant_contact_subscribe_status' ) ) {
			$list_id = justwpforms_get_form_property( $form, 'constant_contact_list_unsubscribe' );
			$this->service->unsubscribe( $list_id, $fields );
		} else {
			$list_id = justwpforms_get_form_property( $form, 'constant_contact_list' );
			$this->service->add_subscriber( $list_id, $fields );
		}
	}

}

if ( ! function_exists( 'justwpforms_get_integration_constant_contact' ) ):

function justwpforms_get_integration_constant_contact() {
	$instance = justwpforms_Integration_ConstantContact::instance();
	$instance->hook();

	return $instance;
}

endif;

justwpforms_get_integration_constant_contact();
