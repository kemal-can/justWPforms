<?php
class justwpforms_Integrations_Page_Controller {
	private static $instance;

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		self::$instance->hook();

		return self::$instance;
	}

	public function hook() {
		add_filter( 'justwpforms_integrations_page_method', array( $this, 'set_admin_page_method' ) );
		add_filter( 'justwpforms_integrations_page_url', array( $this, 'set_admin_page_url' ) );
		add_action( 'justwpforms_add_meta_boxes', array( $this, 'set_metaboxes' ) );
	}

	public function set_metaboxes() {
		$screen = get_plugin_page_hookname( plugin_basename( $this->set_admin_page_url() ), 'justwpforms' );
		$integrations = justwpforms_get_integrations();

		foreach ( $integrations->get_service_group( 'email' ) as $service ) {
			if ( ! $service->display_widget ) {
				continue;
			}

			$metabox_id = "justwpforms-integrations-widget-{$service->id}";

			add_meta_box(
				$metabox_id,
				$service->label, array( $this, 'integrations_metabox_callback' ),
				$screen, 'normal', 'default',
				array( 'service' => $service )
			);

			add_filter( "postbox_classes_{$screen}_{$metabox_id}", function( $classes ) use( $service ) {
				$classes[] = 'justwpforms-integrations-widget';
				$classes[] = "justwpforms-integrations-widget-group-{$service->group}";

				return $classes;
			} );
		}

		$service = $integrations->get_service( 'recaptcha' );
		
		if ( $service->display_widget ) {
			$metabox_id = "justwpforms-integrations-widget-{$service->id}";

			add_meta_box( 
				$metabox_id,
				__( 'reCAPTCHA', 'justwpforms' ),
				array( $this, 'antispam_metabox_callback' ),
				$screen, 'side' 
			);

			add_filter( "postbox_classes_{$screen}_{$metabox_id}", function( $classes ) use( $service ) {
				$classes[] = 'justwpforms-integrations-widget';
				$classes[] = "justwpforms-integrations-widget-group-{$service->group}";

				return $classes;
			} );
		}

		foreach ( $integrations->get_service_group( 'payments' ) as $service ) {
			if ( ! $service->display_widget ) {
				continue;
			}

			$metabox_id = "justwpforms-integrations-widget-{$service->id}";

			add_meta_box(
				$metabox_id,
				$service->label, array( $this, 'integrations_metabox_callback' ),
				$screen, 'side', 'default',
				array( 'service' => $service )
			);

			add_filter( "postbox_classes_{$screen}_{$metabox_id}", function( $classes ) use( $service ) {
				$classes[] = 'justwpforms-integrations-widget';
				$classes[] = "justwpforms-integrations-widget-group-{$service->group}";

				return $classes;
			} );
		}

		foreach ( $integrations->get_service_group( 'automation' ) as $service ) {
			if ( ! $service->display_widget ) {
				continue;
			}

			$metabox_id = "justwpforms-integrations-widget-{$service->id}";

			add_meta_box(
				$metabox_id,
				$service->label, array( $this, 'integrations_metabox_callback' ),
				$screen, 'column3', 'default',
				array( 'service' => $service )
			);

			add_filter( "postbox_classes_{$screen}_{$metabox_id}", function( $classes ) use( $service ) {
				$classes[] = 'justwpforms-integrations-widget';
				$classes[] = "justwpforms-integrations-widget-group-{$service->group}";

				return $classes;
			} );
		}

		foreach ( $integrations->get_service_group( 'analytics' ) as $service ) {
			if ( ! $service->display_widget ) {
				continue;
			}

			$metabox_id = "justwpforms-integrations-widget-{$service->id}";

			add_meta_box(
				'justwpforms-integrations-widget-' . $service->id,
				$service->label, array( $this, 'integrations_metabox_callback' ),
				$screen, 'column4', 'default',
				array( 'service' => $service )
			);

			add_filter( "postbox_classes_{$screen}_{$metabox_id}", function( $classes ) use( $service ) {
				$classes[] = 'justwpforms-integrations-widget';
				$classes[] = "justwpforms-integrations-widget-group-{$service->group}";

				return $classes;
			} );
		}
	}

	public function antispam_metabox_callback(  ) {
		require( justwpforms_get_integrations_folder() . '/templates/admin-antispam-integrations.php' );
	}

	public function integrations_metabox_callback( $post, $metabox ) {
		$service = $metabox['args']['service'];

		$service->admin_widget();
	}

	public function set_admin_page_method() {
		return array( $this, 'integrations_page' );
	}

	public function set_admin_page_url() {
		return 'justwpforms-integrations';
	}

	public function integrations_page() {
		wp_enqueue_script('dashboard');
		add_filter( 'admin_footer_text', 'justwpforms_admin_footer' );

		require_once( justwpforms_get_integrations_folder() . '/templates/admin-integrations.php' );
	}

}

if ( ! function_exists( 'justwpforms_get_integrations_page_controller' ) ):

function justwpforms_get_integrations_page_controller() {
	return justwpforms_Integrations_Page_Controller::instance();
}

endif;

justwpforms_get_integrations_page_controller();
