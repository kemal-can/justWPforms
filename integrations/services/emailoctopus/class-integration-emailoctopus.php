<?php

class justwpforms_Integration_Emailoctopus {

	private static $instance;
	private static $hooked = false;

	private $service;

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

		return self::$instance;
	}

	public function __construct() {
		$this->service = justwpforms_get_integrations()->get_service( 'emailoctopus' );
	}

	public function hook() {
		if ( self::$hooked ) {
			return;
		}

		self::$hooked = true;

		foreach ( $this->field_parts as $part_slug ) {
			add_filter( "justwpforms_part_customize_fields_{$part_slug}", array( $this, 'add_part_fields' ) );
			add_action( "justwpforms_part_customize_{$part_slug}_before_advanced_options", array( $this, "add_part_controls" ) );
		}

		add_filter( 'justwpforms_meta_fields', array( $this, 'meta_fields' ) );
		add_filter( 'justwpforms_email_controls', array( $this, 'setup_controls' ) );
		add_filter( 'justwpforms_conditional_enabled_setup_controls', array( $this, 'add_logic_to_controls' ) );

		add_action( 'justwpforms_customize_enqueue_scripts', array( $this, 'customize_enqueue_scripts' ) );
		add_action( 'justwpforms_pending_submission_success', array( $this, 'handle_submit' ), 10 );
	}

	public function meta_fields( $fields ) {
		$fields['enable_emailoctopus'] = array(
			'default' => 0,
			'sanitize' => 'justwpforms_sanitize_checkbox'
		);

		$fields['emailoctopus_list'] = array(
			'default' => '',
			'sanitize' => 'sanitize_text_field'
		);

		$fields['emailoctopus_list_unsubscribe'] = array(
			'default' => '',
			'sanitize' => 'sanitize_text_field'
		);

		$fields["{$this->service->id}_subscribe_status"] = array(
			'default' => '',
			'sanitize' => 'sanitize_text_field'
		);

		$fields['emailoctopus_tags'] = array(
			'default' => '',
			'sanitize' => 'sanitize_text_field'
		);

		$fields['emailoctopus_tags_unsubscribe'] = array(
			'default' => '',
			'sanitize' => 'sanitize_text_field'
		);

		return $fields;
	}

	public function setup_controls( $controls ) {
		$lists = $this->service->get_lists();
		$lists_options = array();

		foreach ( $lists as $list ) {
			$lists_options[$list->id] = $list->name;
		}

		$email_controls = array(
			251 => array(
				'type' => 'group_start',
				'trigger' => $this->service->id,
			),
			252 => array(
				'type' => 'buttongroup',
				'field' => "{$this->service->id}_subscribe_status",
				'label' => __( 'Set subscription status to', 'justwpforms' ),
				'options' => array(
					'' => 'Subscribed',
					'unsubscribe' => 'Unsubscribed',
				),
			),
			253 => array(
				'type' => 'select',
				'field' => "{$this->service->id}_list",
				'placeholder' => __( '— Select —', 'justwpforms' ),
				'label' => __( 'List to add submitter\'s email to', 'justwpforms' ),
				'options' => $lists_options,
				'allow_empty' => true,
			),
			254 => array(
				'type' => 'select',
				'field' => "{$this->service->id}_list_unsubscribe",
				'placeholder' => __( '— Select —', 'justwpforms' ),
				'label' => __( 'List to remove submitter\'s email from', 'justwpforms' ),
				'options' => $lists_options,
				'allow_empty' => true,
			),
			255 => array(
				'type' => 'text',
				'field' => 'emailoctopus_tags',
				'label' => __( 'Add these tags to submitter', 'justwpforms' ),
				'placeholder' => __( 'e.g. Influencer, Prospect, Uses coupons', 'justwpforms' )
			),
			256 => array(
				'type' => 'text',
				'field' => 'emailoctopus_tags_unsubscribe',
				'label' => __( 'Remove these tags from submitter', 'justwpforms' ),
				'placeholder' => __( 'e.g. Influencer, Prospect, Uses coupons', 'justwpforms' )
			),
			257 => array(
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

		if ( empty( $lists ) ) {
			return $controls;
		}

		foreach ( $lists as $list ) {
			$lists_options[$list->id] = $list->name;
		}

		$controls["{$this->service->id}_list"] = array(
			'type' => 'select',
			'options' => $lists_options,
			'then_text' => __( 'Then add to…', 'justwpforms' )
		);

		$controls["{$this->service->id}_list_unsubscribe"] = array(
			'type' => 'select',
			'options' => $lists_options,
			'then_text' => __( 'Then remove from…', 'justwpforms' )
		);

		$controls['emailoctopus_tags'] = array(
			'type' => 'set',
			'then_text' => __( 'Then add tags…', 'justwpforms' )
		);

		$controls['emailoctopus_tags_unsubscribe'] = array(
			'type' => 'set',
			'then_text' => __( 'Then remove tags…', 'justwpforms' )
		);

		return $controls;
	}

	public function add_part_fields( $fields ) {
		$fields["{$this->service->id}_field"] = array(
			'default'  => '',
			'sanitize' => 'sanitize_text_field'
		);

		return $fields;
	}

	public function add_part_controls() {
		?>
		<% if ( '<?php echo $this->service->id; ?>' == justwpforms.form.get( 'active_email_service' ) ) { %>
		<p>
			<label for="<%= instance.id %>_emailoctopus_field"><?php _e( 'Map field to EmailOctopus field', 'justwpforms' ); ?></label>
			<select class="widefat justwpforms-client-updated" data-bind="emailoctopus_field" data-source="emailoctopus_list" data-var="_justwpformsEmailoctopusData" data-var-prop="fields">
				<option value="" selected>– <?php _e( 'Select', 'justwpforms' ); ?> –</option>
			</select>
		</p>
		<% } %>
		<?php
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

		$status = justwpforms_get_form_property( $form, "{$this->service->id}_subscribe_status" );

		$list_id = 'unsubscribe' === $status ? justwpforms_get_form_property( $form, 'emailoctopus_list_unsubscribe' ) : justwpforms_get_form_property( $form, 'emailoctopus_list' );

		if ( empty( $list_id ) ) {
			return;
		}

		$fields = array();
		$email = '';

		foreach ( $form['parts'] as $part ) {
			if ( empty( $part['emailoctopus_field'] ) ) {
				continue;
			}

			if ( 'EmailAddress' === $part['emailoctopus_field'] ) {
				$email = $submission['parts'][$part['id']];
				continue;
			}

			$fields[$part['emailoctopus_field']] = $submission['parts'][$part['id']];
		}

		if ( empty( $email ) ) {
			return;
		}

		$data = array(
			'email' => $email,
			'list_id' => $list_id,
			'fields' => $fields,
		);

		// Tags
		$tags = array();
		if ( 'unsubscribe' === $status ) {
			$tags = justwpforms_get_form_property( $form, 'emailoctopus_tags_unsubscribe' );
			$tags = explode( ',', $tags );

			foreach ( $tags as $tag ) {
				$data['tags'][ $tag ] = false;
			}

			$this->service->unsubscribe( $data );
		} else {
			$tags = justwpforms_get_form_property( $form, 'emailoctopus_tags' );
			$tags = explode( ',', $tags );

			$data['tags'] = $tags;

			$this->service->add_subscriber( $data );
		}
	}

	public function customize_enqueue_scripts() {
		$fields = $this->service->get_fields();

		$data = array(
			'fields' => $fields,
		);

		wp_localize_script(
			'justwpforms-customize',
			'_justwpformsEmailoctopusData',
			$data
		);
	}

}

if ( ! function_exists( 'justwpforms_get_integration_emailoctopus' ) ):

function justwpforms_get_integration_emailoctopus() {
	$instance = justwpforms_Integration_Emailoctopus::instance();
	$instance->hook();

	return $instance;
}

endif;

justwpforms_get_integration_emailoctopus();
