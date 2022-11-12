<?php

class justwpforms_Integration_AWeber {

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
		$this->service = justwpforms_get_integrations()->get_service( 'aweber' );
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
		$fields['enable_aweber'] = array(
			'default' => 0,
			'sanitize' => 'justwpforms_sanitize_checkbox'
		);

		$fields['aweber_list'] = array(
			'default' => 0,
			'sanitize' => 'sanitize_text_field'
		);

		$fields['aweber_list_unsubscribe'] = array(
			'default' => 0,
			'sanitize' => 'sanitize_text_field'
		);

		$fields['aweber_tags'] = array(
			'default' => '',
			'sanitize' => 'sanitize_text_field'
		);

		$fields['aweber_tags_unsubscribe'] = array(
			'default' => '',
			'sanitize' => 'sanitize_text_field'
		);

		$fields["{$this->service->id}_subscribe_status"] = array(
			'default' => '',
			'sanitize' => 'sanitize_text_field'
		);

		return $fields;
	}

	public function setup_controls( $controls ) {
		$lists = $this->service->get_lists();
		$lists = wp_list_pluck( $lists, 'name', 'id' );
		
		$email_controls = array(
			161 => array(
				'type' => 'group_start',
				'trigger' => $this->service->id,
			),
			162 => array(
				'type' => 'buttongroup',
				'field' => "{$this->service->id}_subscribe_status",
				'label' => __( 'Set subscription status to', 'justwpforms' ),
				'options' => array(
					'' => 'Subscribed',
					'unsubscribe' => 'Unsubscribed',
				),
			),
			163 => array(
				'type' => 'select',
				'field' => 'aweber_list',
				'placeholder' => __( '— Select —', 'justwpforms' ),
				'label' => __( 'List to add submitter\'s email to', 'justwpforms' ),
				'options' => $lists
			),
			164 => array(
				'type' => 'select',
				'field' => 'aweber_list_unsubscribe',
				'placeholder' => __( '— Select —', 'justwpforms' ),
				'label' => __( 'List to remove submitter\'s email from', 'justwpforms' ),
				'options' => $lists
			),
			165 => array(
				'type' => 'text',
				'field' => 'aweber_tags',
				'label' => __( 'Add these tags to submitter', 'justwpforms' ),
				'placeholder' => __( 'e.g. Influencer, Prospect, Uses coupons', 'justwpforms' )
			),
			166 => array(
				'type' => 'text',
				'field' => 'aweber_tags_unsubscribe',
				'label' => __( 'Remove these tags from submitter', 'justwpforms' ),
				'placeholder' => __( 'e.g. Influencer, Prospect, Uses coupons', 'justwpforms' )
			),
			167 => array(
				'type' => 'group_end'
			),
		);

		$controls = justwpforms_safe_array_merge( $controls, $email_controls );

		return $controls;
	}

	public function conditional_enabled_setup_controls( $controls ) {
		$lists = $this->service->get_lists();
		$lists = wp_list_pluck( $lists, 'name', 'id' );
		
		$controls['aweber_list'] = array(
			'type' => 'select',
			'options' => $lists,
			'then_text' => __( 'Then add to…', 'justwpforms' )
		);

		$controls['aweber_list_unsubscribe'] = array(
			'type' => 'select',
			'options' => $lists,
			'then_text' => __( 'Then remove from…', 'justwpforms' )
		);

		$controls['aweber_tags'] = array(
			'type' => 'set',
			'then_text' => __( 'Then add tags…', 'justwpforms' )
		);

		$controls['aweber_tags_unsubscribe'] = array(
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

	public function add_part_fields( $fields ) {
		$fields['aweber_field'] = array(
			'default' => '',
			'sanitize' => 'sanitize_text_field'
		);

		return $fields;
	}

	public function add_part_controls() {
		require( justwpforms_get_integrations_folder() . '/services/aweber/templates/partial-part-controls.php' );
	}

	public function customize_enqueue_scripts() {
		$fields = $this->service->get_fields();

		$aweber_data = array(
			'fields' => $fields,
		);

		wp_localize_script(
			'justwpforms-customize',
			'_justwpformsAweberSettings',
			$aweber_data
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
		$list_id = justwpforms_get_form_property( $form, 'aweber_list' );
		$email_integration_part = $form_controller->get_first_part_by_type( $form, 'email_integration' );

		if ( $email_integration_part && 'yes' !== $submission[$email_integration_part['id']] ) {
			return;
		}

		$supported_parts = $this->get_supported_parts();
		$parts = array_filter( $form['parts'], function( $part ) use( $supported_parts ) {
			$supported = (
				( in_array( $part['type'], $supported_parts ) )
				&& ( $part['aweber_field'] != '' )
			);

			return $supported;
		} );
		$fields = wp_list_pluck( $parts, 'id', 'aweber_field' );

		if ( ! isset( $fields['email'] ) ) {
			return;
		}

		unset( $fields[''] );

		foreach( $fields as $field => $part_id ) {
			$fields[$field] = $submission['parts'][$part_id];
		}

		if ( 'unsubscribe' === justwpforms_get_form_property( $form, "{$this->service->id}_subscribe_status" ) ) {
			$list_id = justwpforms_get_form_property( $form, 'aweber_list_unsubscribe' );
			$tags = justwpforms_get_form_property( $form, 'aweber_tags_unsubscribe' );
			$tags = explode( ',', $tags );
			$tags = array_map( 'trim', $tags );
			$this->service->unsubscribe( $list_id, $fields, $tags );
		} else {
			$list_id = justwpforms_get_form_property( $form, 'aweber_list' );
			$fields['ip_address'] = justwpforms_get_meta( $submission_id, 'client_ip', true );
			$tags = justwpforms_get_form_property( $form, 'aweber_tags' );
			$tags = explode( ',', $tags );
			$tags = array_map( 'trim', $tags );

			$this->service->add_subscriber( $list_id, $fields, $tags );
		}
	}

}

if ( ! function_exists( 'justwpforms_get_integration_aweber' ) ):

function justwpforms_get_integration_aweber() {
	$instance = justwpforms_Integration_AWeber::instance();
	$instance->hook();

	return $instance;
}

endif;

justwpforms_get_integration_aweber();
