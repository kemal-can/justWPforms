<?php
class justwpforms_Integration_Email {

	private static $instance;

	public $service = '';

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		self::$instance->hook();

		return self::$instance;
	}

	public function hook() {
		$part_library = justwpforms_get_part_library();
		require_once( justwpforms_get_integrations_folder() . '/services/email/class-part-email-integration.php' );
		$part_library->register_part( 'justwpforms_Part_EmailIntegration', 21 );

		add_filter( 'justwpforms_library_get_part_mailchimp', array( $this, 'library_get_part' ) );
		add_filter( 'justwpforms_customize_get_current_form', array( $this, 'customize_get_current_form' ) );
		add_filter( 'justwpforms_get_first_part_by_type_email_integration', array( $this, 'get_first_part_by_type' ), 10, 2 );
	}

	public function library_get_part( $part ) {
		$part = justwpforms_get_part_library()->get_part( 'email_integration' );

		return $part;
	}

	public function customize_get_current_form( $form ) {
		foreach( $form['parts'] as $p => $part ) {
			if ( 'mailchimp' === $part['type'] ) {
				$form['parts'][$p]['type'] = 'email_integration';
			}
		}

		return $form;
	}

	public function get_first_part_by_type( $part, $form ) {
		if ( $part ) {
			return $part;
		}

		$part = justwpforms_get_form_controller()->get_first_part_by_type( $form, 'mailchimp' );

		return $part;
	}

}

if ( ! function_exists( 'justwpforms_get_email_integration' ) ):

function justwpforms_get_email_integration() {
	return justwpforms_Integration_Email::instance();
}

endif;

justwpforms_get_email_integration();
