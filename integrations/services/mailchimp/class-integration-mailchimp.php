<?php
class justwpforms_Integration_Mailchimp {

	private static $instance;

	public $service = '';

	public $field_parts = array(
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

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		self::$instance->hook();

		return self::$instance;
	}

	public function hook() {
		$this->service = justwpforms_get_integrations()->get_service( 'mailchimp' );

		add_filter( 'justwpforms_meta_fields', array( $this, 'meta_fields' ) );

		if ( isset( $_GET['justwpforms'] ) && isset( $_GET['form_id'] ) ) {
			add_filter( 'justwpforms_email_controls', array( $this, 'email_controls' ) );
			add_filter( 'justwpforms_conditional_enabled_setup_controls', array( $this, 'add_logic_to_controls' ) );
		}

		foreach ( $this->field_parts as $part_slug ) {
			add_filter( "justwpforms_part_customize_fields_{$part_slug}", array( $this, 'add_part_fields' ) );
			add_action( "justwpforms_part_customize_{$part_slug}_before_advanced_options", array( $this, 'add_part_controls' ) );
		}

		add_action( 'justwpforms_customize_enqueue_scripts', array( $this, 'customize_enqueue_scripts' ) );
		add_action( 'justwpforms_pending_submission_success', array( $this, 'handle_submit' ), 10 );
		add_action( 'justwpforms_do_email_control', array( $this, 'add_mailchimp_groups_control' ), 10, 3 );
		add_action( 'customize_controls_print_footer_scripts', array( $this, 'add_mailchimp_groups_js_control' ) );
	}

	public function handle_submit( $submission_id ) {
		$submission = justwpforms_get_message_controller()->get( $submission_id );
		$form_controller = justwpforms_get_form_controller();
		$form = $form_controller->get( $submission['form_id'] );

		if ( $this->service->id !== $form['active_email_service'] ) {
			return;
		}

		$email_integration_part = $form_controller->get_first_part_by_type( $form, 'email_integration' );

		if ( $email_integration_part && 'yes' !== $submission[$email_integration_part['id']] ) {
			return;
		}

		$form = justwpforms_get_conditional_controller()->get( $form, $submission['request'] );
		$email = '';
		$groups = array();
		$merge_fields = array();

		if ( 'unsubscribe' === justwpforms_get_form_property( $form, "{$this->service->id}_subscribe_status" ) ) {
			$list_id = justwpforms_get_form_property( $form, 'mailchimp_list_unsubscribe' );
			$status = 'unsubscribed';
		} else {
			$list_id = justwpforms_get_form_property( $form, 'mailchimp_list' );
			$status = ( 1 == justwpforms_get_form_property( $form, 'mailchimp_double_opt_in' ) ) ? 'pending' : 'subscribed';
		}

		if ( empty( $list_id ) ) {
			return;
		}

		// Fields
		foreach ( $form['parts'] as $part ) {
			if ( empty( $part['mailchimp_field'] ) ) {
				continue;
			}

			$field_tag = $part['mailchimp_field'];
			$field_parent = '';

			if ( strpos( $field_tag, '::' ) ) {
				$field_full = explode( '::', $field_tag );
				$field_parent = $field_full[0];
				$field_tag = $field_full[1];
			}

			switch( $field_tag ) {
				case 'EMAIL':
					$email = $submission['parts'][$part['id']];
					break;
				case 'addr1':
				case 'city':
				case 'zip':
				case 'state':
					if ( ! empty( $field_parent ) ) {
						$merge_fields[$field_parent][$field_tag] = $submission['parts'][$part['id']];
					} else {
						$merge_fields[$field_tag] = $submission['parts'][$part['id']];
					}
					break;
				default:
					$merge_fields[$field_tag] = $submission['parts'][$part['id']];
					break;
			}
		}

		if ( empty( $email ) ) {
			return;
		}

		$ip_signup = justwpforms_get_meta( $submission_id, 'client_ip', true );
		
		$subscriber_data = array(
			'email_address' => $email,
			'status' => $status,
			'ip_signup' => $ip_signup,
		);

		if ( ! empty( $merge_fields ) ) {
			$subscriber_data['merge_fields'] = $merge_fields;
		}

		// Tags
		$tags = (
			( 'unsubscribe' !== justwpforms_get_form_property( $form, "{$this->service->id}_subscribe_status" ) ) ?
			justwpforms_get_form_property( $form, 'mailchimp_tags' ) :
			justwpforms_get_form_property( $form, 'mailchimp_tags_unsubscribe' )
		);
		$tag_status = (
			( 'unsubscribe' !== justwpforms_get_form_property( $form, "{$this->service->id}_subscribe_status" ) ) ?
			'active' : 'inactive'
		);

		if ( ! empty( $tags ) ) {
			$tags = explode( ',', $tags );
			$tags = array_map( function( $tag ) use( $tag_status ) {
				$tag = trim( $tag );
				$tag = array(
					'name' => $tag,
					'status' => $tag_status
				);

				return $tag;
			}, $tags );
		}

		// Groups
		$groups = (
			( 'unsubscribe' !== justwpforms_get_form_property( $form, "{$this->service->id}_subscribe_status" ) ) ?
			justwpforms_get_form_property( $form, 'mailchimp_groups' ) :
			justwpforms_get_form_property( $form, 'mailchimp_groups_unsubscribe' )
		);
		$interest_status = ( 'unsubscribe' !== justwpforms_get_form_property( $form, "{$this->service->id}_subscribe_status" ) );

		if ( ! empty( $groups ) ) {
			$subscriber_data['interests'] = array();

			if ( is_array( $groups ) ) {
				foreach( $groups as $group_id ) {
					$subscriber_data['interests'][$group_id] = $interest_status;
				}
			} else {
				$subscriber_data['interests'][$groups] = $interest_status;
			}
		}

		$request = $this->service->add_subscriber( $list_id, $subscriber_data, $tags );
	}

	public function add_part_fields( $fields ) {
		$fields['mailchimp_field'] = array(
			'default' => '',
			'sanitize' => 'sanitize_text_field'
		);

		return $fields;
	}

	public function add_part_controls() {
		require( justwpforms_get_integrations_folder() . '/services/mailchimp/templates/partial-part-controls.php' );
	}

	public function meta_fields( $fields ) {
		$fields['enable_mailchimp'] = array(
			'default' => 0,
			'sanitize' => 'justwpforms_sanitize_checkbox'
		);

		$fields['mailchimp_list'] = array(
			'default' => '',
			'sanitize' => 'sanitize_text_field'
		);

		$fields['mailchimp_list_unsubscribe'] = array(
			'default' => '',
			'sanitize' => 'sanitize_text_field'
		);

		$fields['mailchimp_groups'] = array(
			'default' => array(),
			'sanitize' => 'sanitize_groups'
		);

		$fields['mailchimp_groups_unsubscribe'] = array(
			'default' => array(),
			'sanitize' => 'sanitize_groups'
		);

		$fields['mailchimp_tags'] = array(
			'default' => '',
			'sanitize' => 'sanitize_text_field'
		);

		$fields['mailchimp_tags_unsubscribe'] = array(
			'default' => '',
			'sanitize' => 'sanitize_text_field'
		);

		$fields['mailchimp_double_opt_in'] = array(
			'default' => 0,
			'sanitize' => 'justwpforms_sanitize_checkbox'
		);

		$fields["{$this->service->id}_subscribe_status"] = array(
			'default' => '',
			'sanitize' => 'sanitize_text_field'
		);

		return $fields;
	}

	public function sanitize_groups( $groups ) {
		return $groups;
	}

	public function email_controls( $controls ) {
		$lists = $this->service->get_lists();
		$lists_options = array();

		foreach ( $lists as $list ) {
			$lists_options[$list->id] = $list->name;
		}

		$email_controls = array(
			191 => array(
				'type' => 'group_start',
				'trigger' => $this->service->id,
			),
			192 => array(
				'type' => 'buttongroup',
				'field' => "{$this->service->id}_subscribe_status",
				'label' => __( 'Set subscription status to', 'justwpforms' ),
				'options' => array(
					'' => 'Subscribed',
					'unsubscribe' => 'Unsubscribed',
				),
			),
			193 => array(
				'type' => 'select',
				'field' => 'mailchimp_list',
				'placeholder' => __( '— Select —', 'justwpforms' ),
				'label' => __( 'List to add submitter\'s email to', 'justwpforms' ),
				'options' => $lists_options
			),
			194 => array(
				'type' => 'select',
				'field' => 'mailchimp_list_unsubscribe',
				'placeholder' => __( '— Select —', 'justwpforms' ),
				'label' => __( 'List to remove submitter\'s email from', 'justwpforms' ),
				'options' => $lists_options
			),
			195 => array(
				'type' => 'mailchimp_groups',
				'field' => 'mailchimp_groups',
				'include_js_template' => true,
				'label' => __( 'Assign submitter to these group(s)', 'justwpforms' ),
				'no_options' => __( 'No groups available for the selected list.', 'justwpforms' )
			),
			196 => array(
				'type' => 'mailchimp_groups',
				'field' => 'mailchimp_groups_unsubscribe',
				'include_js_template' => true,
				'label' => __( 'Remove submitter from these group(s)', 'justwpforms' ),
				'no_options' => __( 'No groups available for the selected list.', 'justwpforms' )
			),
			197 => array(
				'type' => 'text',
				'field' => 'mailchimp_tags',
				'label' => __( 'Add these tags to submitter', 'justwpforms' ),
				'placeholder' => __( 'e.g. Influencer, Prospect, Uses coupons', 'justwpforms' )
			),
			198 => array(
				'type' => 'text',
				'field' => 'mailchimp_tags_unsubscribe',
				'label' => __( 'Remove these tags from submitter', 'justwpforms' ),
				'placeholder' => __( 'e.g. Influencer, Prospect, Uses coupons', 'justwpforms' )
			),
			199 => array(
				'type' => 'checkbox',
				'field' => 'mailchimp_double_opt_in',
				'label' => __( 'Send email to confirm subscription', 'justwpforms' )
			),
			200 => array(
				'type' => 'group_end'
			),
		);

		$controls = justwpforms_safe_array_merge( $controls, $email_controls );

		return $controls;
	}

	public function add_logic_to_controls( $controls ) {
		$lists = array();
		$lists = $this->service->get_lists();
		$lists_options = array();

		foreach ( $lists as $list ) {
			$lists_options[$list->id] = $list->name;
		}

		$controls['mailchimp_list'] = array(
			'type' => 'select',
			'options' => $lists_options,
			'then_text' => __( 'Then add to…', 'justwpforms' )
		);

		$controls['mailchimp_list_unsubscribe'] = array(
			'type' => 'select',
			'options' => $lists_options,
			'then_text' => __( 'Then remove from…', 'justwpforms' )
		);

		$controls['mailchimp_groups'] = array(
			'type' => 'select',
			'then_text' => __( 'Then assign to group…', 'justwpforms' )
		);

		$controls['mailchimp_groups_unsubscribe'] = array(
			'type' => 'select',
			'then_text' => __( 'Then remove from group…', 'justwpforms' )
		);

		$controls['mailchimp_tags'] = array(
			'type' => 'set',
			'then_text' => __( 'Then add tags…', 'justwpforms' )
		);

		$controls['mailchimp_tags_unsubscribe'] = array(
			'type' => 'set',
			'then_text' => __( 'Then remove tags…', 'justwpforms' )
		);

		return $controls;
	}

	public function add_mailchimp_groups_control( $control, $field, $index ) {
		if ( 'mailchimp_groups' === $control['type'] ) {
			require( justwpforms_get_integrations_folder() . '/services/mailchimp/templates/customize-groups.php' );
		}
	}

	public function add_mailchimp_groups_js_control() {
		require_once( justwpforms_get_integrations_folder() . '/services/mailchimp/templates/customize-groups-js.php' );
	}

	public function customize_enqueue_scripts( $deps ) {
		wp_enqueue_script(
			'justwpforms-mailchimp',
			justwpforms_get_plugin_url() . 'integrations/services/mailchimp/assets/js/customize-mailchimp.js',
			array( 'justwpforms-conditionals' ), justwpforms_get_version(), true
		);

		$lists = $this->service->get_lists();

		$groups_array = array();
		$fields_array = array();

		foreach ( $lists as $list ) {
			$groups = $this->service->get_groups( $list->id );
			$groups_array[$list->id] = array();

			foreach ( $groups as $group ) {
				$groups_array[$list->id][] = $group;
			}

			$fields = $this->service->get_merge_fields( $list->id );
			$fields_array[$list->id] = $fields;
		}

		$mailchimp_data = array(
			'groups' => $groups_array,
			'fields' => $fields_array
		);

		wp_localize_script(
			'justwpforms-mailchimp',
			'_justwpformsMailchimpData',
			$mailchimp_data
		);
	}

}

if ( ! function_exists( 'justwpforms_get_mailchimp_integration' ) ):

function justwpforms_get_mailchimp_integration() {
	return justwpforms_Integration_Mailchimp::instance();
}

endif;

justwpforms_get_mailchimp_integration();
