<?php

class justwpforms_Integration_MailerLite {

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
		$this->service = justwpforms_get_integrations()->get_service( 'mailerlite' );
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
		$fields["enable_{$this->service->id}"] = array(
			'default' => 0,
			'sanitize' => 'justwpforms_sanitize_checkbox'
		);

		$fields["{$this->service->id}_group"] = array(
			'default' => 0,
			'sanitize' => 'sanitize_text_field'
		);

		$fields["{$this->service->id}_group_unsubscribe"] = array(
			'default' => 0,
			'sanitize' => 'sanitize_text_field'
		);

		$fields["{$this->service->id}_subscribe_status"] = array(
			'default' => '',
			'sanitize' => 'sanitize_text_field'
		);

		return $fields;
	}

	public function setup_controls( $controls ) {
		$groups = $this->service->get_groups();
		$groups_options = array(
			'all' => __( 'All', 'justwpforms' ),
		);

		foreach ( $groups as $group ) {
			$groups_options[$group['id']] = $group['name'];
		}

		$email_controls = array(
			201 => array(
				'type' => 'group_start',
				'trigger' => $this->service->id,
			),
			202 => array(
				'type' => 'buttongroup',
				'field' => "{$this->service->id}_subscribe_status",
				'label' => __( 'Set subscription status to', 'justwpforms' ),
				'options' => array(
					'' => 'Subscribed',
					'unsubscribe' => 'Unsubscribed',
				),
			),
			203 => array(
				'type' => 'select',
				'field' => "{$this->service->id}_group",
				'placeholder' => __( '— Select —', 'justwpforms' ),
				'label' => __( 'Group to add subscriber\'s email to', 'justwpforms' ),
				'options' => $groups_options,
				'allow_empty' => true,
			),
			204 => array(
				'type' => 'select',
				'field' => "{$this->service->id}_group_unsubscribe",
				'placeholder' => __( '— Select —', 'justwpforms' ),
				'label' => __( 'Group to remove subscriber\'s email from', 'justwpforms' ),
				'options' => $groups_options,
				'allow_empty' => true,
			),
			205 => array(
				'type' => 'group_end'
			),
		);

		$controls = justwpforms_safe_array_merge( $controls, $email_controls );

		return $controls;
	}

	public function add_logic_to_controls( $controls ) {
		$groups = array();
		$groups = $this->service->get_groups();
		$groups_options = array();

		if ( empty( $groups ) ) {
			return $controls;
		}

		$groups_options = array(
			'all' => __( 'All', 'justwpforms' ),
		);

		foreach ( $groups as $group ) {
			$groups_options[$group['id']] = $group['name'];
		}

		$controls["{$this->service->id}_group"] = array(
			'type' => 'select',
			'options' => $groups_options,
			'then_text' => __( 'Then add to…', 'justwpforms' )
		);

		$controls["{$this->service->id}_group_unsubscribe"] = array(
			'type' => 'select',
			'options' => $groups_options,
			'then_text' => __( 'Then remove from…', 'justwpforms' )
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
			<label for="<%= instance.id %>_mailerlite_field"><?php _e( 'Map field to MailerLite field', 'justwpforms' ); ?></label>
			<select class="widefat justwpforms-client-updated" data-bind="mailerlite_field" data-var="_justwpformsMailerLiteData" data-var-prop="fields">
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
		$fields = array();

		foreach ( $form['parts'] as $part ) {
			if ( empty( $part['mailerlite_field'] ) ) {
				continue;
			}

			$fields[$part['mailerlite_field']] = $submission['parts'][$part['id']];
		}

		if ( ! isset( $fields['email'] ) ) {
			return;
		}

		if ( 'unsubscribe' == justwpforms_get_form_property( $form, "{$this->service->id}_subscribe_status" ) ) {
			$data = array(
				'email' => $fields['email'],
				'group_id' => justwpforms_get_form_property( $form, 'mailerlite_group_unsubscribe' ),
			);

			$this->service->unsubscribe( $data );
		} else {
			$fields['signup_ip'] = justwpforms_get_meta( $submission_id, 'client_ip', true );
			$data = array(
				'group_id' => justwpforms_get_form_property( $form, 'mailerlite_group' ),
				'fields' => $fields,
			);

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
			'_justwpformsMailerLiteData',
			$data
		);
	}

}

if ( ! function_exists( 'justwpforms_get_integration_mailerlite' ) ):

function justwpforms_get_integration_mailerlite() {
	$instance = justwpforms_Integration_MailerLite::instance();
	$instance->hook();

	return $instance;
}

endif;

justwpforms_get_integration_mailerlite();
