<?php
class justwpforms_Admin_Pages_Controller {

	private static $instance;

	private $manage_sections_states_action = 'justwpforms-manage-admin-sections-states';
	private $section_states_meta_key = 'justwpforms-settings-sections-states';

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		self::$instance->hook();

		return self::$instance;
	}

	public function hook() {
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		add_action( 'wp_ajax_' . $this->manage_sections_states_action, array( $this, 'manage_sections_states' ) );
	}

	public function admin_enqueue_scripts() {
		wp_enqueue_script(
			'justwpforms-admin-pages-screen',
			justwpforms_get_plugin_url() . 'inc/assets/js/admin/admin-pages.js',
			array(), justwpforms_get_version(), true
		);

		wp_localize_script(
			'justwpforms-admin-pages-screen',
			'_justwpformsAdminPagesConfig',
			array(
				'manage_sections_state_nonce' => wp_create_nonce( $this->manage_sections_states_action )
			)
		);
	}

	public function manage_sections_states() {
		if ( ! isset( $_REQUEST['nonce'] ) ) {
			wp_send_json_error();
		}

		if ( ! wp_verify_nonce( $_REQUEST['nonce'], $this->manage_sections_states_action ) ) {
			wp_send_json_error();
		}

		$section_id = sanitize_text_field( $_REQUEST['section_id'] );
		$section_state = sanitize_text_field( $_REQUEST['section_state'] );
		$current_user_id = get_current_user_id();
		$user_sections_states = $this->get_user_sections_states( $current_user_id );
		$section_key = array_search( $section_id, $user_sections_states );

		$user_sections_states[$section_id] = $section_state;

		update_user_meta(
			$current_user_id,
			$this->section_states_meta_key,
			$user_sections_states
		);

		wp_send_json_success();
	}

	public function get_user_sections_states( $user_id = 0 ) {
		if ( 0 === $user_id ) {
			$user_id = get_current_user_id();
		}

		$sections_states = get_user_meta(
			$user_id,
			$this->section_states_meta_key,
			true
		);

		if ( empty( $sections_states ) ) {
			$sections_states = array();
		}

		return $sections_states;
	}

}

if ( ! function_exists( 'justwpforms_get_admin_pages_controller' ) ):

function justwpforms_get_admin_pages_controller() {
	return justwpforms_Admin_Pages_Controller::instance();
}

endif;

justwpforms_get_admin_pages_controller();
