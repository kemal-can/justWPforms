<?php

class justwpforms_Integration_Sendinblue {

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
		$this->service = justwpforms_get_integrations()->get_service( 'sendinblue' );
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
		$fields['enable_sendinblue'] = array(
			'default' => 0,
			'sanitize' => 'justwpforms_sanitize_checkbox'
		);

		$fields['sendinblue_double_opt_in'] = array(
			'default' => 0,
			'sanitize' => 'justwpforms_sanitize_checkbox'
		);

		$fields['sendinblue_double_opt_in_template'] = array(
			'default' => 0,
			'sanitize' => 'sanitize_text_field'
		);

		$fields['sendinblue_double_opt_in_redirect_url'] = array(
			'default' => '',
			'sanitize' => 'sanitize_text_field'
		);

		$fields['sendinblue_list'] = array(
			'default' => '',
			'sanitize' => 'sanitize_text_field'
		);

		$fields['sendinblue_list_unsubscribe'] = array(
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
		$templates = $this->service->get_templates();
		$template_options = wp_list_pluck( $templates, 'name', 'id' );

		$lists = $this->service->get_lists();
		$lists_options = array();

		$lists_options[''] = array( array(
			'value' => 'all',
			'label' => __( 'All', 'justwpforms' ),
		) );

		foreach ( $lists as $folder_key => $folder_lists ) {
			$folder_lists = array_map( function( $list ) {
				$list = array(
					'label' => $list['name'],
					'value' => $list['id'],
				);

				return $list;
			}, $folder_lists );

			$lists_options[$folder_key] = $folder_lists;
		}

		$email_controls = array(
			241 => array(
				'type' => 'group_start',
				'trigger' => $this->service->id,
			),
			242 => array(
				'type' => 'buttongroup',
				'field' => "{$this->service->id}_subscribe_status",
				'label' => __( 'Set subscription status to', 'justwpforms' ),
				'options' => array(
					'' => 'Subscribed',
					'unsubscribe' => 'Unsubscribed',
				),
			),
			243 => array(
				'type' => 'checkbox',
				'field' => 'sendinblue_double_opt_in',
				'label' => __( 'Send email to confirm subscription', 'justwpforms' )
			),
			244 => array(
				'type' => 'group_start',
				'trigger' => 'sendinblue_double_opt_in',
			),
			245 => array(
				'type' => 'select',
				'field' => 'sendinblue_double_opt_in_template',
				'placeholder' => __( '— Select —', 'justwpforms' ),
				'label' => __( 'Double opt-in email template', 'justwpforms' ),
				'options' => $template_options,
			),
			246 => array(
				'type' => 'url',
				'label' => __( 'Double opt-in redirect address', 'justwpforms' ),
				'placeholder' => __( 'Paste web address or type to search', 'justwpforms' ),
				'field' => 'sendinblue_double_opt_in_redirect_url',
			),
			247 => array(
				'type' => 'group_end'
			),
			248 => array(
				'type' => 'select',
				'field' => "{$this->service->id}_list",
				'placeholder' => __( '— Select —', 'justwpforms' ),
				'label' => __( 'List to add submitter\'s email to', 'justwpforms' ),
				'options' => $lists_options,
				'allow_empty' => true,
			),
			249 => array(
				'type' => 'select',
				'field' => "{$this->service->id}_list_unsubscribe",
				'placeholder' => __( '— Select —', 'justwpforms' ),
				'label' => __( 'List to remove submitter\'s email from', 'justwpforms' ),
				'options' => $lists_options,
				'allow_empty' => true,
			),
			250 => array(
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

		$lists_options[''] = array( 
			'title' => '',
			'options' => array( array(
				'id' => 'all',
				'label' => __( 'All', 'justwpforms' )
			) ),
		);

		foreach ( $lists as $folder_key => $folder_lists ) {
			$folder_lists = array_map( function( $list ) {
				$list = array(
					'label' => $list['name'],
					'value' => $list['id'],
				);

				return $list;
			}, $folder_lists );

			$lists_options[$folder_key] = array(
				'title'   => $folder_key,
				'options' => $folder_lists,
			);
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
			<label for="<%= instance.id %>_sendinblue_field"><?php _e( 'Map field to Sendinblue field', 'justwpforms' ); ?></label>
			<select class="widefat justwpforms-client-updated" data-bind="sendinblue_field" data-var="_justwpformsSendinblueData" data-var-prop="fields">
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
			if ( empty( $part['sendinblue_field'] ) ) {
				continue;
			}

			$fields[$part['sendinblue_field']] = $submission['parts'][$part['id']];
		}

		$data = array(
			'fields' => $fields,
		);

		if ( 'unsubscribe' === justwpforms_get_form_property( $form, "{$this->service->id}_subscribe_status" ) ) {
			$data['list_id'] = justwpforms_get_form_property( $form, 'sendinblue_list_unsubscribe' );
			$this->service->unsubscribe( $data );
		} else {
			$data['list_id'] = justwpforms_get_form_property( $form, 'sendinblue_list' );
			$use_double_opt_in = false;
			$use_double_opt_in = justwpforms_get_form_property( $form, 'sendinblue_double_opt_in' );
			$double_opt_in_template = justwpforms_get_form_property( $form, 'sendinblue_double_opt_in_template' );
			$double_opt_in_redirect_url = justwpforms_get_form_property( $form, 'sendinblue_double_opt_in_redirect_url' );

			if ( justwpforms_is_truthy( $use_double_opt_in ) ) {
				$data['double_opt_in_template'] = $double_opt_in_template;
				$data['double_opt_in_redirect_url'] = $double_opt_in_redirect_url;
				$use_double_opt_in = true;
			}

			$this->service->add_subscriber( $data, $use_double_opt_in );
		}
	}

	public function customize_enqueue_scripts() {
		$fields = $this->service->get_fields();

		$data = array(
			'fields' => $fields,
		);

		wp_localize_script(
			'justwpforms-customize',
			'_justwpformsSendinblueData',
			$data
		);
	}

}

if ( ! function_exists( 'justwpforms_get_integration_sendinblue' ) ):

function justwpforms_get_integration_sendinblue() {
	$instance = justwpforms_Integration_Sendinblue::instance();
	$instance->hook();

	return $instance;
}

endif;

justwpforms_get_integration_sendinblue();
