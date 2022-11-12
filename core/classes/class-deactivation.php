<?php

class justwpforms_Deactivation {
	
	private static $instance;

	public $modal_action = 'justwpforms-deactivate-modal-submit';
	public $option = '_justwpforms_cleanup_on_deactivate';

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		self::$instance->hook();

		return self::$instance;
	}

	public function hook() {
		add_action( 'admin_init', [ $this, 'register_modals' ] );
		add_filter( 'justwpforms_dashboard_modal_settings', [ $this, 'get_dashboard_modal_settings' ] );
		add_action( 'wp_ajax_' . $this->modal_action, [ $this, 'deactivate_modal_submit' ] );
	}

	public function cleanup_on_deactivation() {
		$cleanup = get_option( $this->option, false );

		return $cleanup;
	}

	public function register_modals() {
		$modals = justwpforms_get_dashboard_modals();

		$modals->register_modal( 'deactivate', [
			'classes' => 'justwpforms-modal__frame--deactivate'
		] );
	}

	public function get_dashboard_modal_settings( $settings ) {
		$settings['deactivateModalAction'] = $this->modal_action;
		$settings['deactivateModalNonce'] = wp_create_nonce( $this->modal_action );
		
		return $settings;
	}

	public function deactivate_modal_submit() {
		if ( ! check_ajax_referer( $this->modal_action ) ) {
			return;
		}

		$keep_data = isset( $_POST['keep_data'] ) ? $_POST['keep_data'] : 'yes';
		
		update_option( $this->option, 'no' === $keep_data );
	}

}

if ( ! function_exists( 'justwpforms_get_deactivation' ) ) :

function justwpforms_get_deactivation() {
	return justwpforms_Deactivation::instance();
}

endif;

justwpforms_get_deactivation();