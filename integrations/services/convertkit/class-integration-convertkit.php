<?php

class justwpforms_Integration_ConvertKit {

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
		$this->service = justwpforms_get_integrations()->get_service( 'convertkit' );
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

		$fields["{$this->service->id}_form"] = array(
			'default' => 0,
			'sanitize' => 'sanitize_text_field'
		);

		$fields["{$this->service->id}_tags"] = array(
			'default' => '',
			'sanitize' => 'sanitize_text_field'
		);

		$fields["{$this->service->id}_tags_unsubscribe"] = array(
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
		$forms = $this->service->get_forms();
		$forms_options = array();

		if ( ! empty( $forms ) ) {
			foreach ( $forms as $form ) {
				$forms_options[$form['id']] = $form['name'];
			}
		}

		$email_controls = array(
			181 => array(
				'type' => 'group_start',
				'trigger' => $this->service->id,
			),
			182 => array(
				'type' => 'buttongroup',
				'field' => "{$this->service->id}_subscribe_status",
				'label' => __( 'Set subscription status to', 'justwpforms' ),
				'options' => array(
					'' => 'Subscribed',
					'unsubscribe' => 'Unsubscribed',
				),
			),
			183 => array(
				'type' => 'select',
				'field' => "{$this->service->id}_form",
				'placeholder' => __( '— Select —', 'justwpforms' ),
				'label' => __( 'Form to add submitter\'s email to', 'justwpforms' ),
				'options' => $forms_options
			),
			184 => array(
				'type' => 'text',
				'field' => "{$this->service->id}_tags",
				'label' => __( 'Add these tags to submitter', 'justwpforms' ),
				'placeholder' => __( 'e.g. Influencer, Prospect, Uses coupons', 'justwpforms' )
			),
			185 => array(
				'type' => 'text',
				'field' => "{$this->service->id}_tags_unsubscribe",
				'label' => __( 'Remove these tags from submitter', 'justwpforms' ),
				'placeholder' => __( 'e.g. Influencer, Prospect, Uses coupons', 'justwpforms' )
			),
			187 => array(
				'type' => 'group_end'
			),
		);

		$controls = justwpforms_safe_array_merge( $controls, $email_controls );

		return $controls;
	}

	public function add_logic_to_controls( $controls ) {
		$forms = array();
		$forms = $this->service->get_forms();
		$forms_options = array();

		if ( ! empty( $forms ) ) {
			foreach ( $forms as $form ) {
				$forms_options[$form['id']] = $form['name'];
			}
		}

		$controls["{$this->service->id}_form"] = array(
			'type' => 'select',
			'options' => $forms_options,
			'then_text' => __( 'Then add to…', 'justwpforms' )
		);

		$controls["{$this->service->id}_tags"] = array(
			'type' => 'set',
			'then_text' => __( 'Then add tags…', 'justwpforms' )
		);

		$controls["{$this->service->id}_tags_unsubscribe"] = array(
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
			<label for="<%= instance.id %>_convertkit_field"><?php _e( 'Map field to ConvertKit field', 'justwpforms' ); ?></label>
			<select class="widefat justwpforms-client-updated" data-bind="convertkit_field" data-var="_justwpformsConvertKitData" data-var-prop="fields">
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
		$form_id = justwpforms_get_form_property( $form, 'convertkit_form' );
		$tags = justwpforms_get_form_property( $form, 'convertkit_tags' );

		if ( empty( $form_id ) ) {
			return;
		}

		$fields = array();

		foreach ( $form['parts'] as $part ) {
			if ( empty( $part['convertkit_field'] ) ) {
				continue;
			}

			$fields[$part['convertkit_field']] = $submission['parts'][$part['id']];
		}

		$data = array(
			'form_id' => $form_id,
			'fields' => $fields,
			'tags' => $tags,
		);

		if ( 'unsubscribe' === justwpforms_get_form_property( $form, "{$this->service->id}_subscribe_status" ) ) {
			$this->service->unsubscribe( $data );
		} else {
			$this->service->add_subscriber( $data );
		}
	}

	public function customize_enqueue_scripts() {
		$fields = array();
		$fields = $this->service->get_fields();

		$data = array(
			'fields' => $fields,
		);

		wp_localize_script(
			'justwpforms-customize',
			'_justwpformsConvertKitData',
			$data
		);
	}

}

if ( ! function_exists( 'justwpforms_get_integration_convertkit' ) ):

function justwpforms_get_integration_convertkit() {
	$instance = justwpforms_Integration_ConvertKit::instance();
	$instance->hook();

	return $instance;
}

endif;

justwpforms_get_integration_convertkit();
