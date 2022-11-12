<?php

class justwpforms_Dashboard_Modals {

	private static $instance;

	private $modals = array();

	public $dismiss_action = 'justwpforms-modal-dismiss';

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		self::$instance->hook();

		return self::$instance;
	}

	public function hook() {
		add_action( "wp_ajax_{$this->dismiss_action}", [ $this, 'dismiss_modal' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_scripts' ] );
		add_filter( 'justwpforms_customize_enqueue_scripts', [ $this, 'customize_enqueue_scripts' ] );
		add_action( 'in_admin_footer', [ $this, 'output_modal_area' ] );
		add_action( 'customize_controls_print_footer_scripts', [ $this, 'output_modal_area' ] );
	}

	public function get_modal_defaults() {
		$defaults = array(
			'classes' => '',
			'dismissible' => false,
		);

		return $defaults;
	}

	public function register_modal( $id, $settings = array() ) {
		if ( $this->is_dismissed( $id ) ) {
			return;
		}

		$settings = wp_parse_args( $settings, $this->get_modal_defaults() );

		$this->modals[$id] = $settings;
	}

	public function dismiss_modal() {
		if ( ! isset( $_POST['id'] ) ) {
			die( '' );
		}

		$id = $_POST['id'];

		if ( ! isset( $this->modals[$id] ) ) {
			die( '' );
		}

		if ( ! $this->is_dismissible( $id ) ) {
			die( '' );
		}

		update_option( "justwpforms_modal_dismissed_{$id}", true );

		do_action( 'justwpforms_modal_dismissed', $id );

		die( '' );
	}

	public function is_dismissible( $id ) {
		$settings = $this->modals[$id];
		$dismissible = isset( $settings['dismissible'] ) ? $settings['dismissible'] : false;

		return $dismissible;
	}

	public function is_dismissed( $id ) {
		return get_option( "justwpforms_modal_dismissed_{$id}", false );
	}

	public function admin_enqueue_scripts() {
		return;
		$asset_file = require( justwpforms_get_include_folder() . '/assets/jsx/build/admin/dashboard-modals.asset.php' );
		$dependencies = array_merge( $asset_file['dependencies'], array( 'justwpforms-admin' ) );

		wp_enqueue_script(
			'justwpforms-dashboard-modals',
			justwpforms_get_plugin_url() . 'inc/assets/jsx/build/admin/dashboard-modals.js',
			$dependencies, $asset_file['version'], true
		);

		wp_register_style(
			'justwpforms-dashboard-modals-core',
			justwpforms_get_plugin_url() . 'core/assets/css/dashboard-modals.css',
			array( 'wp-components' ), justwpforms_get_version()
		);

		wp_enqueue_style(
			'justwpforms-dashboard-modals',
			justwpforms_get_plugin_url() . 'inc/assets/css/dashboard-modals.css',
			array( 'justwpforms-dashboard-modals-core' ), justwpforms_get_version()
		);

		wp_localize_script( 'justwpforms-dashboard-modals', '_justwpformsDashboardModalsSettings', $this->get_script_settings() );
	}

	public function customize_enqueue_scripts() {
		return;
		$asset_file = require( justwpforms_get_include_folder() . '/assets/jsx/build/admin/dashboard-modals.asset.php' );

		wp_enqueue_script(
			'justwpforms-dashboard-modals',
			justwpforms_get_plugin_url() . 'inc/assets/jsx/build/admin/dashboard-modals.js',
			$asset_file['dependencies'], $asset_file['version'], true
		);

		wp_register_style(
			'justwpforms-dashboard-modals-core',
			justwpforms_get_plugin_url() . 'core/assets/css/dashboard-modals.css',
			array( 'wp-components' ), justwpforms_get_version()
		);

		wp_enqueue_style(
			'justwpforms-dashboard-modals',
			justwpforms_get_plugin_url() . 'inc/assets/css/dashboard-modals.css',
			array( 'justwpforms-dashboard-modals-core' ), justwpforms_get_version()
		);

		wp_localize_script( 'justwpforms-dashboard-modals', '_justwpformsDashboardModalsSettings', $this->get_script_settings() );

		$deps[] = 'justwpforms-dashboard-modals';

		return $deps;
	}

	public function get_script_settings() {
		$settings = array(
			'actionModalDismiss' => $this->dismiss_action,
			'pluginURL' => justwpforms_get_plugin_url(),
		);

		$settings = apply_filters( 'justwpforms_dashboard_modal_settings', $settings );

		return $settings;
	}

	public function output_modal_area() {
	?>
	<div id="justwpforms-modals-area"></div>
	<?php
	}

}

if ( ! function_exists( 'justwpforms_get_dashboard_modals' ) ):

function justwpforms_get_dashboard_modals() {
	return justwpforms_Dashboard_Modals::instance();
}

endif;

justwpforms_get_dashboard_modals();