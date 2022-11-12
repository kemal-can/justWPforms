<?php
class justwpforms_Settings_Page_Controller {

	private static $instance;

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		self::$instance->hook();

		return self::$instance;
	}

	public function hook() {
		add_filter( 'justwpforms_settings_page_method', array( $this, 'set_admin_page_method' ) );
		add_filter( 'justwpforms_settings_page_url', array( $this, 'set_admin_page_url' ) );
		add_action( 'justwpforms_add_meta_boxes', array( $this, 'set_metaboxes' ) );
	}

	public function export_metabox_callback(  ) {
		require( justwpforms_get_include_folder() . '/templates/admin-settings-export.php' );
	}

	public function permissions_metabox_callback(  ) {
		require( justwpforms_get_include_folder() . '/templates/admin-settings-role-permissions.php' );
	}

	public function set_metaboxes( ) {
		$screen = get_plugin_page_hookname( plugin_basename( $this->set_admin_page_url() ), 'justwpforms' );
		
		add_meta_box( 
			'justwpforms-export-section',
			__( 'Import and Export', 'justwpforms' ),
			array( $this, 'export_metabox_callback' ),
			$screen, 'normal'
		);

		if ( current_user_can( 'manage_options' ) ) {
			add_meta_box( 
				'justwpforms-role_permissions-section',
				__( 'Role Capabilities', 'justwpforms' ),
				array( $this, 'permissions_metabox_callback' ),
				$screen, 'side' 
			);
		}
	}

	public function set_admin_page_method() {
		return array( $this, 'settings_page' );
	}

	public function set_admin_page_url() {
		return 'justwpforms-settings';
	}

	public function settings_page() {
		wp_enqueue_script('dashboard');
		add_filter( 'admin_footer_text', 'justwpforms_admin_footer' );

		require_once( justwpforms_get_include_folder() . '/templates/admin-settings.php' );
	}

}

if ( ! function_exists( 'justwpforms_get_settings_page_controller' ) ):

function justwpforms_get_settings_page_controller() {
	return justwpforms_Settings_Page_Controller::instance();
}

endif;

justwpforms_get_settings_page_controller();
