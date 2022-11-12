<?php
class justwpforms_Integration_Google_Places {

	private static $instance;

	private $ajax_action_autocomplete = 'justwpforms_address_autocomplete';

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		self::$instance->hook();

		return self::$instance;
	}

	public function hook() {
		add_filter( 'justwpforms_meta_fields', array( $this, 'meta_fields' ) );
		add_filter( 'justwpforms_frontend_dependencies', array( $this, 'script_dependencies' ), 10, 2 );
		add_filter( 'justwpforms_frontend_settings', array( $this, 'frontend_settings' ) );
		add_filter( 'justwpforms_part_class', array( $this, 'html_part_class' ), 10, 2 );
		add_filter( 'justwpforms_get_form_data', array( $this, 'migrate_address_mode' ) );

		add_action( 'justwpforms_part_customize_address_after_options', array( $this, 'add_part_controls' ) );
		add_action( 'justwpforms_customize_enqueue_scripts', array( $this, 'customize_enqueue_scripts' ) );

		add_action( 'wp_ajax_' . $this->ajax_action_autocomplete, array( $this, 'ajax_autocomplete' ) );
		add_action( 'wp_ajax_nopriv_' . $this->ajax_action_autocomplete, array( $this, 'ajax_autocomplete' ) );
	}

	public function add_part_controls() {
		require( justwpforms_get_integrations_folder() . '/services/google-places/templates/partial-part-controls.php' );
	}

	public function html_part_class( $class, $part ) {
		if ( 'address' === $part['type'] ) {
			$service = justwpforms_get_integrations()->get_service( 'google-places' );

			if ( $service->is_connected() && 'simple' === $part['mode'] && 1 === $part['has_autocomplete'] ){
				$class[] = 'justwpforms-part--address-googleplaces';
				$class[] = 'justwpforms-part--with-autocomplete';
			}
		}

		return $class;
	}

	public function customize_enqueue_scripts( $deps = array() ) {
		wp_enqueue_script(
			'google-part-places',
			justwpforms_get_plugin_url() . 'integrations/services/google-places/assets/js/parts/part-google-places.js',
			$deps, justwpforms_get_version(), true
		);
	}

	public function script_dependencies( $deps, $forms ) {
		$service = justwpforms_get_integrations()->get_service( 'google-places' );

		if ( ! $service->is_connected() ){
			return $deps;
		}

		$form_controller = justwpforms_get_form_controller();
		$type = 'address';
		$has_autocomplete = false;

		foreach ( $forms as $form ) {
			$parts = array_filter( $form['parts'], function( $part ) use( $type ) {
				return $part['type'] === $type && $part['has_autocomplete'] == 1
						&& $part['mode'] === 'simple';
			} );

			if ( ! empty( $parts ) ){
				$has_autocomplete = true;
				break;
			}
		}

		if ( ! justwpforms_is_preview() && ! $has_autocomplete ) {
			return $deps;
		}

		wp_register_script(
			'justwpforms-google-places',
			justwpforms_get_plugin_url() . 'integrations/services/google-places/assets/js/frontend/google-places.js',
			array( 'justwpforms-part-address' ), justwpforms_get_version(), true
		);

		$deps[] = 'justwpforms-google-places';

		return $deps;
	}

	public function frontend_settings( $settings ) {
		$settings['googlePlaces'] = array(
			'url' => admin_url( 'admin-ajax.php' ),
			'actionAutocomplete' => $this->ajax_action_autocomplete,
		);

		return $settings;
	}

	public function ajax_autocomplete() {
		$results = [];
		$service = justwpforms_get_integrations()->get_service( 'google-places' );

		if ( $service->is_connected() && isset( $_GET['term'] ) ) {
			$results = $service->get_address_suggestions( sanitize_text_field( $_GET['term'] ) );
		}

		wp_send_json( $results );
	}

	// TODO delete after support for migrating old address fields is over.
	public function meta_fields( $fields ){
		$service_fields = array(
			'address_autocomplte_migrated' => array(
				'default' => 0,
				'sanitize' => 'sanitize_text_field',
			),
		);

		$fields = array_merge( $fields, $service_fields );

		return $fields;
	}

	public function migrate_address_mode( $form ) {
		if ( 1 === $form['address_autocomplte_migrated'] ) {
			return $form;
		}

		foreach ( $form['parts'] as $p => $part ) {
			if ( 'address' === $part['type'] && 'autocomplete' === $part['mode'] ){
				$form['parts'][$p]['mode'] = 'simple';
				$form['parts'][$p]['has_autocomplete'] = 1;
			}
		}

		$form['address_autocomplte_migrated'] = 1;

		return $form;
	}

}

if ( ! function_exists( 'justwpforms_get_integration_google_places' ) ):

function justwpforms_get_integration_google_places() {
	return justwpforms_Integration_Google_Places::instance();
}

endif;

justwpforms_get_integration_google_places();
