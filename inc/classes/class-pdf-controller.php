<?php

class justwpforms_PDF_Controller {

	private static $instance;

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		self::$instance->hook();

		return self::$instance;
	}

	public function hook() {
		add_filter( 'justwpforms_meta_fields', array( $this, 'add_fields' ) );
		add_filter( 'justwpforms_email_controls', array( $this, 'add_setup_controls' ) );
		add_action( 'justwpforms_do_email_control', array( $this, 'do_control' ), 10, 3 );
		add_action( 'justwpforms_do_email_control', array( $this, 'do_deprecated_control' ), 10, 4 );
		add_action( 'justwpforms_customize_enqueue_scripts', array( $this, 'customize_enqueue_scripts' ) );
		add_filter( 'justwpforms_email_confirmation', array( $this, 'attach_pdf_to_email_confirmation' ) );
		add_filter( 'justwpforms_email_alert', array( $this, 'attach_pdf_to_email_alert' ) );
		add_action( 'justwpforms_email_confirmation_sent', array( $this, 'confirmation_destroy_pdf_attachments' ) );
		add_action( 'justwpforms_email_alert_sent', array( $this, 'alert_destroy_pdf_attachments' ) );

		$supported_parts = $this->get_supported_parts();

		foreach ( $supported_parts as $part_type ) {
			add_filter( "justwpforms_part_customize_fields_{$part_type}", array( $this, 'add_part_pdf_field' ) );
			add_action( "justwpforms_part_customize_{$part_type}_after_options", array( $this, 'add_pdf_checkbox' ) );
		}
	}

	public function get_supported_parts() {
		$parts = array(
			'single_line_text',
			'multi_line_text',
			'email',
			'website_url',
			'radio',
			'checkbox',
			'table',
			'select',
			'number',
			'poll',
			'phone',
			'date',
			'address',
			'scale',
			'rank_order',
			'likert_scale',
			'rich_text',
			'title',
			'rating',
			'narrative',
			'signature',
			'scrollable_terms'
		);

		$parts = apply_filters( 'justwpforms_pdf_supported_parts', $parts );

		return $parts;
	}

	public function add_part_pdf_field( $fields ) {
		$fields['include_in_pdf'] = array(
			'default' => 1,
			'sanitize' => 'justwpforms_sanitize_checkbox'
		);

		return $fields;
	}

	public function add_pdf_checkbox() {
		?>
		<p class="justwpforms-customize-part-include_in_pdf">
			<label>
				<input type="checkbox" class="checkbox" value="1" <% if ( instance.include_in_pdf ) { %>checked="checked"<% } %> data-bind="include_in_pdf" /> <?php _e( 'Include in .pdf export', 'justwpforms' ); ?>
			</label>
		</p>
		<?php
	}

	public function add_fields( $fields ) {
		$fields['owner_attach_pdf'] = array(
			'default'  => 0,
			'sanitize' => 'justwpforms_sanitize_checkbox'
		);

		$fields['owner_pdf_title'] = array(
			'default'  => __( 'A copy of your message', 'justwpforms' ),
			'sanitize' => 'sanitize_text_field'
		);

		$fields['owner_pdf_file_name'] = array(
			'default'  => __( 'your-receipt', 'justwpforms' ),
			'sanitize' => 'sanitize_text_field'
		);

		$fields['owner_pdf_logo'] = array(
			'default'  => 0,
			'sanitize' => 'intval'
		);

		$fields['owner_pdf_header_message'] = array(
			'default'  => '',
			'sanitize' => 'sanitize_textarea_field'
		);

		$fields['owner_pdf_footer_message'] = array(
			'default'  => '',
			'sanitize' => 'sanitize_textarea_field'
		);

		$fields['attach_pdf'] = array(
			'default'  => 0,
			'sanitize' => 'justwpforms_sanitize_checkbox'
		);

		$fields['pdf_title'] = array(
			'default'  => __( 'A copy of your message', 'justwpforms' ),
			'sanitize' => 'sanitize_text_field'
		);

		$fields['pdf_file_name'] = array(
			'default'  => __( 'your-receipt', 'justwpforms' ),
			'sanitize' => 'sanitize_text_field'
		);

		$fields['pdf_logo'] = array(
			'default'  => 0,
			'sanitize' => 'intval'
		);

		$fields['pdf_header_message'] = array(
			'default'  => '',
			'sanitize' => 'sanitize_textarea_field'
		);

		$fields['pdf_footer_message'] = array(
			'default'  => '',
			'sanitize' => 'sanitize_textarea_field'
		);

		return $fields;
	}

	public function add_setup_controls( $controls ) {
		$pdf_controls = array(
			541 => array(
				'field' => 'owner_attach_pdf',
				'label' => __( 'Attach .pdf', 'justwpforms' ),
				'type' => 'attach-pdf-owner-checkbox'
			),
			542 => array(
				'type' => 'attach-pdf-owner-group_start',
				'trigger' => 'owner_attach_pdf'
			),
			543 => array(
				'field' => 'owner_pdf_title',
				'label' => __( 'Page title', 'justwpforms' ),
				'type' => 'attach-pdf-owner-text'
			),
			544 => array(
				'field' => 'owner_pdf_file_name',
				'label' => __( 'File name', 'justwpforms' ),
				'type' => 'attach-pdf-owner-text'
			),
			545 => array(
				'field' => 'owner_pdf_logo',
				'label' => __( 'Page logo', 'justwpforms' ),
				'type' => 'attach-pdf-owner-upload',
				'settings' => array(
					'overlay_title' => __( 'Select document logo', 'justwpforms' ),
					'overlay_button' => __( 'Use image', 'justwpforms' )
				)
			),
			546 => array(
				'field' => 'owner_pdf_header_message',
				'label' => __( 'Header message', 'justwpforms' ),
				'type' => 'attach-pdf-owner-textarea'
			),
			547 => array(
				'field' => 'owner_pdf_footer_message',
				'label' => __( 'Footer message', 'justwpforms' ),
				'type' => 'attach-pdf-owner-textarea'
			),
			548 => array(
				'type' => 'attach-pdf-owner-group_end'
			),
			871 => array(
				'field' => 'attach_pdf',
				'label' => __( 'Attach .pdf', 'justwpforms' ),
				'type' => 'attach-pdf-checkbox'
			),
			872 => array(
				'type' => 'attach-pdf-group_start',
				'trigger' => 'attach_pdf'
			),
			873 => array(
				'field' => 'pdf_title',
				'label' => __( 'Page title', 'justwpforms' ),
				'type' => 'attach-pdf-text'
			),
			874 => array(
				'field' => 'pdf_file_name',
				'label' => __( 'File name', 'justwpforms' ),
				'type' => 'attach-pdf-text'
			),
			875 => array(
				'field' => 'pdf_logo',
				'label' => __( 'Page logo', 'justwpforms' ),
				'type' => 'attach-pdf-upload',
				'settings' => array(
					'overlay_title' => __( 'Select document logo', 'justwpforms' ),
					'overlay_button' => __( 'Use image', 'justwpforms' )
				)
			),
			876 => array(
				'field' => 'pdf_header_message',
				'label' => __( 'Header message', 'justwpforms' ),
				'type' => 'attach-pdf-textarea'
			),
			877 => array(
				'field' => 'pdf_footer_message',
				'label' => __( 'Footer message', 'justwpforms' ),
				'type' => 'attach-pdf-textarea'
			),
			878 => array(
				'type' => 'attach-pdf-group_end'
			),
		);

		$controls = justwpforms_safe_array_merge( $controls, $pdf_controls );

		return $controls;
	}

	public function do_control( $control, $field, $index ) {
		$type = $control['type'];

		if ( 'upload' === $type ) {
			require( justwpforms_get_include_folder() . '/templates/customize-controls/upload.php' );
		}
	}

	public function attaches_pdf( $form, $context = '' ) {
		$attaches = justwpforms_get_form_property( $form, 'attach_pdf' );
		$filename = justwpforms_get_form_property( $form, 'pdf_file_name' );

		if ( 'alert' === $context ) {
			$attaches = justwpforms_get_form_property( $form, 'owner_attach_pdf' );
			$filename = justwpforms_get_form_property( $form, 'owner_pdf_file_name' );
		}

		$attaches = $attaches && ! empty( $filename );

		return $attaches;
	}

	public function do_deprecated_control( $control, $field, $index ) {
		$type = $control['type'];
		$path = justwpforms_get_core_folder() . '/templates/customize-controls/setup';

		switch( $type ) {
			case 'attach-pdf-owner-checkbox':
			case 'attach-pdf-owner-text':
			case 'attach-pdf-owner-textarea':
			case 'attach-pdf-owner-group_start':
			case 'attach-pdf-owner-group_end':
				$form = justwpforms_customize_get_current_form();

				if ( justwpforms_is_falsy( $form['owner_attach_pdf'] ) ) {
					break;
				}

				$true_type = str_replace( 'attach-pdf-owner-', '', $type );

				require( "{$path}/{$true_type}.php" );
				break;
			case 'attach-pdf-checkbox':
			case 'attach-pdf-text':
			case 'attach-pdf-textarea':
			case 'attach-pdf-group_start':
			case 'attach-pdf-group_end':
				$form = justwpforms_customize_get_current_form();

				if ( justwpforms_is_falsy( $form['attach_pdf'] ) ) {
					break;
				}

				$true_type = str_replace( 'attach-pdf-', '', $type );

				require( "{$path}/{$true_type}.php" );
				break;
			case 'attach-pdf-owner-upload':
				$form = justwpforms_customize_get_current_form();
				$path = justwpforms_get_include_folder() . '/templates/customize-controls';

				if ( justwpforms_is_falsy( $form['owner_attach_pdf'] ) ) {
					break;
				}

				$true_type = str_replace( 'attach-pdf-owner-', '', $type );

				require( "{$path}/{$true_type}.php" );
				break;
			case 'attach-pdf-upload':
				$form = justwpforms_customize_get_current_form();
				$path = justwpforms_get_include_folder() . '/templates/customize-controls';

				if ( justwpforms_is_falsy( $form['attach_pdf'] ) ) {
					break;
				}

				$true_type = str_replace( 'attach-pdf-', '', $type );

				require( "{$path}/{$true_type}.php" );
				break;
			default:
				break;
		}
	}

	public function get_pdf_title( $form, $response, $context = '' ) {
		$title = justwpforms_get_form_property( $form, 'pdf_title' );

		if ( 'alert' === $context ) {
			$title = justwpforms_get_form_property( $form, 'owner_pdf_title' );
		}

		if ( justwpforms_get_form_property( $form, 'unique_id' ) ) {
			$tracking_id = $response['tracking_id'];
			$title = "{$title} ($tracking_id)";
		}

		return $title;
	}

	public function get_pdf_file_name( $form, $response, $context = '' ) {
		$filename = justwpforms_get_form_property( $form, 'pdf_file_name' );

		if ( 'alert' === $context ) {
			$filename = justwpforms_get_form_property( $form, 'owner_pdf_file_name' );
		}

		$filename = sanitize_title( $filename );

		if ( ! justwpforms_get_form_property( $form, 'unique_id' ) ) {
			$response_id = $response['ID'];
			$filename = "{$filename}-{$response_id}.pdf";
		} else {
			$tracking_id = $response['tracking_id'];
			$filename = "{$filename}-{$tracking_id}.pdf";
		}

		return $filename;
	}

	public function get_pdf_logo( $form, $context = '' ) {
		$attachment_id = justwpforms_get_form_property( $form, 'pdf_logo' );

		if ( 'alert' === $context ) {
			$attachment_id = justwpforms_get_form_property( $form, 'owner_pdf_logo' );
		}

		$logo = get_attached_file( $attachment_id );

		if ( ! file_exists( $logo ) ) {
			$logo = '';
		}

		return $logo;
	}

	public function get_pdf_header_content( $form, $context = '' ) {
		$header_content = justwpforms_get_form_property( $form, 'pdf_header_message' );

		if ( 'alert' === $context ) {
			$header_content = justwpforms_get_form_property( $form, 'owner_pdf_header_message' );
		}

		return $header_content;
	}

	public function get_pdf_footer_content( $form, $context = '' ) {
		$footer_content = justwpforms_get_form_property( $form, 'pdf_footer_message' );

		if ( 'alert' === $context ) {
			$footer_content = justwpforms_get_form_property( $form, 'owner_pdf_footer_message' );
		}

		return $footer_content;
	}

	public function get_pdf_content_data( $form, $response, $context = '' ) {
		$form = justwpforms_get_conditional_controller()->get( $form, $_REQUEST );

		$parts = array_filter( $form['parts'], function( $part ) use( $form ) {
			return $this->pdf_part_visible( $part, $form );
		} );

		$data = array_map( function( $part ) use( $response, $form ) {
			$part_id = $part['id'];
			$label = $part['label'];
			$value = justwpforms_get_pdf_part_value( $response['parts'][$part_id], $part, $form );
			$row = array( $label, $value );

			return $row;
		}, $parts );
		$data = array_values( $data );

		return $data;
	}

	public function pdf_part_visible( $part, $form ) {
		$supported = in_array( $part['type'], $this->get_supported_parts() );
		$visible = apply_filters( 'justwpforms_pdf_part_visible', true, $part, $form );
		$visible = $supported && $visible;

		return $visible;
	}

	public function attach_pdf_to_email_confirmation( $email_message ) {
		return $this->do_attach_pdf_to_email( $email_message, 'confirmation' );
	}

	public function attach_pdf_to_email_alert( $email_message ) {
		return $this->do_attach_pdf_to_email( $email_message, 'alert' );
	}

	private function do_attach_pdf_to_email( $email_message, $context = '' ) {
		$response = $email_message->message;
		$form = justwpforms_get_form_controller()->get( $response['form_id'] );

		if ( ! $form ) {
			return;
		}

		if ( ! $this->attaches_pdf( $form, $context ) ) {
			return $email_message;
		}

		$pdf = new justwpforms_PDF( $form, $response );
		$settings = $this->get_pdf_settings( $form, $response, $context );
		$pdf->generate( $settings );
		$pdf->save();
		$email_message->add_attachment( $pdf->path );

		return $email_message;
	}

	private function get_pdf_settings( $form, $response, $context = '' ) {
		$title = $this->get_pdf_title( $form, $response, $context );
		$header = $this->get_pdf_header_content( $form, $context );
		$content = $this->get_pdf_content_data( $form, $response, $context );
		$footer = $this->get_pdf_footer_content( $form, $context );
		$logo = $this->get_pdf_logo( $form, $context );
		$filename = $this->get_pdf_file_name( $form , $response, $context );

		$settings = array(
			'title' => $title,
			'header' => $header,
			'content' => $content,
			'footer' => $footer,
			'logo' => $logo,
			'filename' => $filename,
		);

		return $settings;
	}

	public function confirmation_destroy_pdf_attachments( $email_message ) {
		return $this->destroy_pdf_attachments( $email_message, 'confirmation' );
	}

	public function alert_destroy_pdf_attachments( $email_message ) {
		return $this->destroy_pdf_attachments( $email_message, 'alert' );
	}

	public function destroy_pdf_attachments( $email_message, $context = '' ) {
		$form = justwpforms_get_form_controller()->get( $email_message->message['form_id'] );
		$pdf = new justwpforms_PDF( $form, $email_message->message );
		$pdf_filename = $this->get_pdf_file_name( $form, $email_message->message, $context );
		$pdf_path = $pdf->get_path( $pdf_filename );

		wp_delete_file( $pdf_path );
	}

	public function customize_enqueue_scripts( $deps ) {
		wp_enqueue_script(
			'justwpforms-pdf-ui',
			justwpforms_get_plugin_url() . 'inc/assets/js/customize/pdf.js',
			array( 'justwpforms-media-handle' ), justwpforms_get_version(), true
		);
	}

}

if ( ! function_exists( 'justwpforms_get_pdf_controller' ) ):

function justwpforms_get_pdf_controller() {
	return justwpforms_PDF_Controller::instance();
}

endif;

justwpforms_get_pdf_controller();
