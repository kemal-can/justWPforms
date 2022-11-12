<?php

class justwpforms_Integrations {

	private static $instance;
	private static $hooked = false;

	private $option_name = '_justwpforms_service_credentials';
	private $services = array();
	private $grouped_services = [];
	private $data = array();
	private $credentials = array();
	private $notice = [];

	public $action_update = 'justwpforms-service-update';
	public $integrations_action = 'justwpforms-integrations-update';
	public $nonce_update = 'justwpforms_update_nonce';

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function __construct() {
		require_once( justwpforms_get_integrations_folder() . '/classes/class-api-request.php' );
		require_once( justwpforms_get_integrations_folder() . '/classes/class-service.php' );

		 // Google Places
		 require_once( justwpforms_get_integrations_folder() . '/services/google-places/class-service-google-places.php' );
		 $this->register_service( 'justwpforms_Service_Google_Places' );

		 // Google Geocoding
		 require_once( justwpforms_get_integrations_folder() . '/services/google-geocoding/class-service-google-geocoding.php' );
		 $this->register_service( 'justwpforms_Service_Google_Geocoding' );

		// reCaptcha
		require_once( justwpforms_get_integrations_folder() . '/services/recaptcha/class-service-recaptcha.php' );
		$this->register_service( 'justwpforms_Service_Recaptcha' );

		// reCaptcha V3
		require_once( justwpforms_get_integrations_folder() . '/services/recaptchav3/class-service-recaptchav3.php' );
		$this->register_service( 'justwpforms_Service_RecaptchaV3' );

		// AntiSpam
		require_once( justwpforms_get_integrations_folder() . '/services/antispam/class-service-antispam.php' );
		$this->register_service( 'justwpforms_Service_AntiSpam' );

		// ActiveCampaign
		require_once( justwpforms_get_integrations_folder() . '/services/active-campaign/class-service-active-campaign.php' );
		$this->register_service( 'justwpforms_Service_ActiveCampaign' );

		// AWeber
		require_once( justwpforms_get_integrations_folder() . '/services/aweber/class-service-aweber.php' );
		$this->register_service( 'justwpforms_Service_AWeber' );

		// Constant Contact
		require_once( justwpforms_get_integrations_folder() . '/services/constant-contact/class-service-constant-contact.php' );
		$this->register_service( 'justwpforms_Service_ConstantContact' );

		// ConvertKit
		require_once( justwpforms_get_integrations_folder() . '/services/convertkit/class-service-convertkit.php' );
		$this->register_service( 'justwpforms_Service_ConvertKit' );

		// Mailchimp
		require_once( justwpforms_get_integrations_folder() . '/services/mailchimp/class-service-mailchimp.php' );
		$this->register_service( 'justwpforms_Service_Mailchimp' );

		// MailerLite
		require_once( justwpforms_get_integrations_folder() . '/services/mailerlite/class-service-mailerlite.php' );
		$this->register_service( 'justwpforms_Service_MailerLite' );

		// MailPoet
		require_once( justwpforms_get_integrations_folder() . '/services/mailpoet/class-service-mailpoet.php' );
		$this->register_service( 'justwpforms_Service_MailPoet' );

		// SendFox
		require_once( justwpforms_get_integrations_folder() . '/services/sendfox/class-service-sendfox.php' );
		$this->register_service( 'justwpforms_Service_SendFox' );

		// SendGrid
		require_once( justwpforms_get_integrations_folder() . '/services/sendgrid/class-service-sendgrid.php' );
		$this->register_service( 'justwpforms_Service_SendGrid' );

		// Sendinblue
		require_once( justwpforms_get_integrations_folder() . '/services/sendinblue/class-service-sendinblue.php' );
		$this->register_service( 'justwpforms_Service_Sendinblue' );

		// Stripe
		require_once( justwpforms_get_integrations_folder() . '/services/stripe/class-service-stripe.php' );
		$this->register_service( 'justwpforms_Service_Stripe' );

		// PayPal
		require_once( justwpforms_get_integrations_folder() . '/services/paypal/class-service-paypal.php' );
		$this->register_service( 'justwpforms_Service_PayPal' );

		// Payments
		require_once( justwpforms_get_integrations_folder() . '/services/payments/class-service-payments.php' );
		require_once( justwpforms_get_integrations_folder() . '/services/payments/class-integration-payments.php' );
		$this->register_service( 'justwpforms_Service_Payments' );

		// Google Analytics
		require_once( justwpforms_get_integrations_folder() . '/services/google-analytics/class-service-google-analytics.php' );
		$this->register_service( 'justwpforms_Service_Google_Analytics' );

		// Analytics
		require_once( justwpforms_get_integrations_folder() . '/services/analytics/class-service-analytics.php' );
		$this->register_service( 'justwpforms_Service_Analytics' );

		// Zapier
		require_once( justwpforms_get_integrations_folder() . '/services/zapier/class-service-zapier.php' );
		$this->register_service( 'justwpforms_Service_Zapier' );

		// Integromat
		require_once( justwpforms_get_integrations_folder() . '/services/integromat/class-service-integromat.php' );
		$this->register_service( 'justwpforms_Service_Integromat' );

		// Integrately
		require_once( justwpforms_get_integrations_folder() . '/services/integrately/class-service-integrately.php' );
		$this->register_service( 'justwpforms_Service_Integrately' );

		// Emailoctopus
		require_once( justwpforms_get_integrations_folder() . '/services/emailoctopus/class-service-emailoctopus.php' );
		$this->register_service( 'justwpforms_Service_Emailoctopus' );

		// Depreacted
		require_once( justwpforms_get_integrations_folder() . '/services/email/class-service-email.php' );

	}

	public function hook() {
		if ( self::$hooked ) {
			return;
		}

		self::$hooked = true;

		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		add_action( 'wp_ajax_' . $this->action_update, array( $this, 'ajax_service_update' ) );
		add_action( 'wp_ajax_' . $this->integrations_action, array( $this, 'ajax_integrations_update' ) );
		add_action( 'justwpforms_customize_enqueue_scripts', array( $this, 'customize_enqueue_scripts' ) );
		add_action( 'justwpforms_integrations_print_notices', array( $this, 'print_notices' ) );

		add_filter( 'justwpforms_email_controls', array( $this, 'setup_controls' ), 999 );
		add_action( 'justwpforms_do_email_control', array( $this, 'do_control' ), 10, 3 );
		add_filter( 'justwpforms_meta_fields', array( $this, 'meta_fields' ) );

		$this->read_credentials();
		$this->configure_services();
		$this->hook_optin_part();
		$this->migrate_google_geo_apis();
	}

	public function read_credentials() {
		$this->credentials = get_option( $this->option_name, array() );
	}

	public function get_all_credentials() {
		return $this->credentials;
	}

	public function write_credentials() {
		$this->credentials = array_map( function( $service ) {
			return $service->get_credentials();
		}, $this->services );

		update_option( $this->option_name, $this->credentials );
	}

	public function register_service( $service ) {
		$service = $service instanceof justwpforms_Service ? $service : new $service();
		$this->services[$service->id] = $service;
	}

	public function configure_services() {
		foreach( $this->services as $service ) {
			$credentials = array();

			if ( isset ( $this->credentials[$service->id] ) ) {
				$credentials = $this->credentials[$service->id];
			}

			$service->set_credentials( $credentials );
			$service->configure();
			$service->load();
		}
	}

	public function get_services() {
		return $this->services;
	}

	public function get_service( $id ) {
		if ( isset( $this->services[$id] ) ) {
			return $this->services[$id];
		}

		return false;
	}

	public function get_service_group( $group ){
		if ( empty( $this->grouped_services ) ) {
			$this->set_grouped_services();
		}

		return $this->grouped_services[ $group ];
	}

	public function set_grouped_services(){
		$grouped_services = array();

		foreach( $this->services as $service ) {
			$grouped_services[$service->group][] = $service;
		}

		$this->grouped_services = $grouped_services;
	}

	public function hook_optin_part(){
		$email_services = $this->get_service_group( 'email' );

		foreach( $email_services as $service ){
			if( $service->is_connected() ) {
				require_once( justwpforms_get_integrations_folder() . '/services/email/class-integration-email.php' );
				break;
			}
		}
	}

	public function migrate_google_geo_apis() {
		$google_geocoding = $this->get_service( 'google-geocoding' );
		$google_places = $this->get_service( 'google-places' );

		$google_geocoding->try_migrating_keys();
		$google_places->try_migrating_keys();
	}

	public function ajax_service_update() {
		if ( ! check_ajax_referer( $this->action_update ) ) {
			wp_die();
		}

		$services = $_REQUEST['services'];
		$group = '';
		$group_service = null;

		if ( isset( $_REQUEST['group'] ) ) {
			$group = sanitize_text_field( $_REQUEST['group'] );
			$group_service = $this->services[$group];

			if ( empty( array_filter( $services ) ) ) {
				$group_service->reset_active_service();
			}
		}

		$response        = '';
		$success_message = __( 'Changes saved.', 'justwpforms' );

		ob_start();

		foreach ( $services as $service ) {

			if ( ! isset( $this->services[$service] ) ) {
				continue;
			}

			$the_service = $this->services[$service];
			$service_credentials = $the_service->get_credentials();

			if ( ! isset( $_REQUEST['credentials'][$the_service->id] ) ) {
				$_REQUEST['credentials'][$the_service->id] = $service_credentials;
			}

			$credentials = wp_parse_args( $_REQUEST['credentials'][$the_service->id], $service_credentials );
			$credentials = array_intersect_key( $credentials, $service_credentials );
			$credentials = array_map( 'sanitize_text_field', $credentials );

			if ( ! empty( $group ) && ! $group_service->supports_multiple ) {
				$group_service->set_active_service( $the_service->id );
			}

			$previous_credentials = $the_service->get_credentials();
			$the_service->set_credentials( $credentials, $_REQUEST['credentials'][$the_service->id] );
			$this->write_credentials();
		}

		$this->notice = array(
				'status' => 'success',
				'message' => $success_message,
			);

		switch( $group ){
			case 'antispam':
				require( justwpforms_get_integrations_folder() . '/templates/admin-antispam-integrations.php' );
				break;
			default:
				break;
		}

		$response = ob_get_clean();

		echo $response;
		die();
	}

	public function ajax_integrations_update() {
		if ( ! check_ajax_referer( $this->integrations_action ) ) {
			wp_die();
		}

		if ( ! isset( $_REQUEST['service'] ) ) {
			wp_die();
		}

		if ( ! isset( $_REQUEST['credentials'] ) ) {
			wp_die();
		}

		$service = $_REQUEST['service'];
		$response        = '';

		if ( ! isset( $this->services[$service] ) ) {
			wp_send_json_error();
		}

		ob_start();

		$the_service = $this->services[$service];
		$service_credentials = $the_service->get_credentials();
		$this->notice = [];

		if ( ! isset( $_REQUEST['credentials'][$the_service->id] ) ) {
			$_REQUEST['credentials'][$the_service->id] = $service_credentials;
		}

		$credentials = wp_parse_args( $_REQUEST['credentials'][$the_service->id], $service_credentials );
		$credentials = array_intersect_key( $credentials, $service_credentials );
		$credentials = array_map( 'sanitize_text_field', $credentials );

		$previous_credentials = $the_service->get_credentials();

		$the_service->set_credentials( $credentials, $_REQUEST['credentials'][$the_service->id] );
		$this->write_credentials();

		$this->notice = array(
				'status' => 'success',
				'message' => __( 'Changes saved.', 'justwpforms' ),
			);
		$the_service->admin_widget( $previous_credentials );

		$response = ob_get_clean();

		echo $response;
		die();
	}

	public function print_notices(){
		if( empty( $this->notice ) ) {
			return;
		}
	?>
	  <div class="notice notice-<?php echo $this->notice['status']; ?>"><p><?php echo $this->notice['message']; ?></p></div>
	<?php
	}

	public function admin_enqueue_scripts() {
		wp_enqueue_style(
			'justwpforms-integrations',
			justwpforms_get_plugin_url() . 'integrations/assets/css/admin.css'
		);

		if ( ! isset( $_GET['page'] ) || 'justwpforms-integrations' !== $_GET['page'] ) {
			return;
		}

		wp_enqueue_script(
			'justwpforms-integrations',
			justwpforms_get_plugin_url() . 'integrations/assets/js/dashboard.js',
			array( 'jquery' ), justwpforms_get_version(), true
		);
	}

	public function customize_enqueue_scripts() {
		$services = array();

		foreach ( $this->services as $service ) {
			switch( $service->id ) {
				case 'email':
					$services['email'] = ( ! empty( $service->active_service ) ) ? $service->active_service->id : 0;
					break;
				case 'antispam':
					$services['antispam'] = ( ! empty( $service->active_service ) ) ? $service->active_service->id : 0;
					break;
				default:
					break;
			}

			if ( empty( $service->group ) || 'email' === $service->group || 'antispam' === $service->group ) {
				continue;
			}

			$services[$service->id] = ( $service->is_connected() ) ? 1 : 0;
		}

		wp_localize_script(
			'justwpforms-customize',
			'_justwpformsIntegrations',
			$services
		);
	}

	public function setup_controls( $controls ) {
		$integrations_controls = array(
			150 => array(
				'type' => 'email_integration_list',
				'field' => 'active_email_service',
				'label' => 'Connect with',
			)
		);

		$controls = justwpforms_safe_array_merge( $controls, $integrations_controls );

		return $controls;
	}

	public function do_control( $control, $field, $index ) {
		$type = $control['type'];
		$path = justwpforms_get_integrations_folder() . '/templates/customize-controls/email';

		switch ( $control['type'] ) {
			case 'email_integration_list':
				require( "{$path}/{$type}.php" );
				break;
			default:
				break;
		}
	}

	public function meta_fields( $fields ) {
		$fields['active_email_service'] = array(
			'default' => '',
			'sanitize' => 'sanitize_text_field'
		);

		return $fields;
	}

}

if ( ! function_exists( 'justwpforms_get_integrations' ) ):

function justwpforms_get_integrations() {
	$instance = justwpforms_Integrations::instance();
	$instance->hook();

	return $instance;
}

endif;

justwpforms_get_integrations();
