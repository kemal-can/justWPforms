<?php

class justwpforms_Integration_ActiveCampaign {

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
		$this->service = justwpforms_get_integrations()->get_service( 'active-campaign' );
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
		$fields['enable_active_campaign'] = array(
			'default' => 0,
			'sanitize' => 'justwpforms_sanitize_checkbox'
		);

		$fields['active_campaign_list'] = array(
			'default' => 0,
			'sanitize' => 'sanitize_text_field'
		);

		$fields['active_campaign_list_unsubscribe'] = array(
			'default' => 0,
			'sanitize' => 'sanitize_text_field'
		);

		$fields['active_campaign_tags'] = array(
			'default' => '',
			'sanitize' => 'sanitize_text_field'
		);

		$fields['active_campaign_tags_unsubscribe'] = array(
			'default' => '',
			'sanitize' => 'sanitize_text_field'
		);

		$fields['active_campaign_subscribe_status'] = array(
			'default' => '',
			'sanitize' => 'sanitize_text_field'
		);

		return $fields;
	}

	public function setup_controls( $controls ){
		$lists = $this->service->get_lists();
		$lists = wp_list_pluck( $lists, 'name', 'id' );

		$email_controls = array(
			151 => array(
				'type' => 'group_start',
				'trigger' => $this->service->id,
			),
			152 => array(
				'type' => 'buttongroup',
				'field' => 'active_campaign_subscribe_status',
				'label' => __( 'Set subscription status to', 'justwpforms' ),
				'options' => array(
					'' => 'Subscribed',
					'unsubscribe' => 'Unsubscribed',
				),
			),
			153 => array(
				'type' => 'select',
				'field' => 'active_campaign_list',
				'placeholder' => __( '— Select —', 'justwpforms' ),
				'label' => __( 'List to add submitter\'s email to', 'justwpforms' ),
				'options' => $lists
			),
			154 => array(
				'type' => 'select',
				'field' => 'active_campaign_list_unsubscribe',
				'placeholder' => __( '— Select —', 'justwpforms' ),
				'label' => __( 'List to remove submitter\'s email from', 'justwpforms' ),
				'options' => $lists
			),
			155 => array(
				'type' => 'text',
				'field' => 'active_campaign_tags',
				'label' => __( 'Add these tags to submitter', 'justwpforms' ),
				'placeholder' => __( 'e.g. Influencer, Prospect, Uses coupons', 'justwpforms' )
			),
			156 => array(
				'type' => 'text',
				'field' => 'active_campaign_tags_unsubscribe',
				'label' => __( 'Remove these tags from submitter', 'justwpforms' ),
				'placeholder' => __( 'e.g. Influencer, Prospect, Uses coupons', 'justwpforms' )
			),
			157 => array(
				'type' => 'group_end'
			),
		);

		$controls = justwpforms_safe_array_merge( $controls, $email_controls );

		return $controls;
	}

	public function conditional_enabled_setup_controls( $controls ) {
		$lists = $this->service->get_lists();
		$lists = wp_list_pluck( $lists, 'name', 'id' );

		$controls['active_campaign_list'] = array(
			'type' => 'select',
			'options' => $lists,
			'then_text' => __( 'Then add to…', 'justwpforms' )
		);

		$controls['active_campaign_list_unsubscribe'] = array(
			'type' => 'select',
			'options' => $lists,
			'then_text' => __( 'Then remove from…', 'justwpforms' )
		);

		$controls['active_campaign_tags'] = array(
			'type' => 'set',
			'then_text' => __( 'Then add tags…', 'justwpforms' )
		);

		$controls['active_campaign_tags_unsubscribe'] = array(
			'type' => 'set',
			'then_text' => __( 'Then remove tags…', 'justwpforms' )
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
			'single_line_text' => array( 'textarea', 'text', 'hidden' ),
			'multi_line_text' => array( 'textarea', 'text', 'hidden' ),
			'email' => array( 'textarea', 'text', 'hidden' ),
			'website_url' => array( 'textarea', 'text', 'hidden' ),
			'radio' => array( 'dropdown', 'radio' ),
			'checkbox' => array( 'listbox', 'checkbox' ),
			'select' => array( 'dropdown', 'radio' ),
			'number' => array( 'textarea', 'text', 'hidden' ),
			'poll' => array( 'textarea', 'text', 'hidden' ),
			'phone' => array( 'textarea', 'text', 'hidden' ),
			'date' => array( 'textarea', 'text', 'hidden', 'date' ),
			'scale' => array( 'textarea', 'text', 'hidden' ),
			'rich_text' => array( 'textarea' ),
			'title' => array( 'textarea', 'text', 'hidden' ),
			'legal' => array( 'textarea', 'text', 'hidden' ),
			'rating' => array( 'textarea', 'text', 'hidden' ),
		);

		return $parts;
	}

	public function add_part_fields( $fields ) {
		$fields['active_campaign_field'] = array(
			'default' => '',
			'sanitize' => 'sanitize_text_field'
		);

		return $fields;
	}

	public function add_part_controls() {
		require( justwpforms_get_integrations_folder() . '/services/active-campaign/templates/partial-part-controls.php' );
	}

	public function customize_enqueue_scripts() {
		$fields = $this->service->get_fields();
		$mappings = $this->get_field_part_mappings();

		$active_campaign_data = array(
			'fields' => $fields,
			'mappings' => $mappings,
		);

		wp_localize_script(
			'justwpforms-customize',
			'_justwpformsActiveCampaignSettings',
			$active_campaign_data
		);
	}

	public function get_field_submission_value( $field, $part, $form, $submission ) {
		$value = '';
		$part_type = $part['type'];
		$part_id = $part['id'];

		switch( $part_type ) {
			case 'checkbox':
				$part_class = justwpforms_get_part_library()->get_part( $part_type );
				$value = $part_class->sanitize_value( $part, $form, $submission['request'] );
				$value = array_map( function( $option ) use( $part ) {
					return $part['options'][$option]['label'];
				}, $value );
				break;
			default:
				$value = $submission['parts'][$part_id];
				break;
		}

		return $value;
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

		$parts = array_filter( $form['parts'], function( $part ) {
			return ! empty( $part['active_campaign_field'] );
		} );

		$fields = wp_list_pluck( $parts, 'id', 'active_campaign_field' );

		if ( ! isset( $fields['email'] ) ) {
			return;
		}

		unset( $fields[''] );

		$part_ids = wp_list_pluck( $parts, 'id' );
		$parts = array_combine( $part_ids, $parts );

		foreach( $fields as $field => $part_id ) {
			$part = $parts[$part_id];
			$fields[$field] = $this->get_field_submission_value( $field, $part, $form, $submission );
		}

		$fields['ip4'] = justwpforms_get_meta( $submission_id, 'client_ip', true );
		
		if ( 'unsubscribe' !== justwpforms_get_form_property( $form, 'active_campaign_subscribe_status' ) ) {
			$fields['status'] = 1;
			$list_id = justwpforms_get_form_property( $form, 'active_campaign_list' );
			$tags = justwpforms_get_form_property( $form, 'active_campaign_tags' );
		} else {
			$fields['status'] = 2;
			$list_id = justwpforms_get_form_property( $form, 'active_campaign_list_unsubscribe' );
			$tags = justwpforms_get_form_property( $form, 'active_campaign_tags_unsubscribe' );
		}

		$this->service->create_contact( $list_id, $fields, $tags );
	}

}

if ( ! function_exists( 'justwpforms_get_integration_active_campaign' ) ):

function justwpforms_get_integration_active_campaign() {
	$instance = justwpforms_Integration_ActiveCampaign::instance();
	$instance->hook();

	return $instance;
}

endif;

justwpforms_get_integration_active_campaign();
