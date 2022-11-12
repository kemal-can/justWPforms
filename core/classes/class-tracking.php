<?php

class justwpforms_Tracking {

	/**
	 * The singleton instance.
	 *
	 * @since 1.0
	 *
	 * @var justwpforms_Tracking
	 */
	private static $instance;
	/**
	 * The name of the tracking option entry.
	 *
	 * @since 1.0
	 *
	 * @var string
	 */
	public $activation_option = 'justwpforms-tracking';

	/**
	 * The singleton constructor.
	 *
	 * @since 1.0
	 *
	 * @return justwpforms_Tracking
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		self::$instance->hook();

		return self::$instance;
	}

	/**
	 * Register hooks.
	 *
	 * @since 1.0
	 *
	 * @return void
	 */
	public function hook() {
		// noop
	}

	/**
	 * Get the tracking status.
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	public function get_status() {
		$status = get_option( $this->activation_option, array(
			'status' => 0,
		) );

		return $status;
	}

	/**
	 * Update the tracking status.
	 *
	 * @since 1.0
	 *
	 * @param string $status The status counter.
	 * @param string $email  The user email.
	 *
	 * @return void
	 */
	public function update_status( $status, $email = '' ) {
		update_option( $this->activation_option, array(
			'status' => $status,
		) );
	}

	/**
	 * Action: handle the ajax request for the update
	 * of tracking status
	 *
	 * @since 1.0
	 *
	 * @hooked action wp_ajax_justwpforms_update_tracking
	 *
	 * @return void
	 */
	public function ajax_update_tracking() {
		if ( isset( $_REQUEST['status'] ) ) {
			$current_status = $this->get_status();
			$status = $_REQUEST['status'];
			$email = isset( $_REQUEST['email'] ) ? $_REQUEST['email'] : $current_status['email'];

			$this->update_status( $status, $email );
		}

		wp_die( 1 );
	}

}

if ( ! function_exists( 'justwpforms_get_tracking' ) ):
/**
 * Get the justwpforms_Tracking class instance.
 *
 * @since 1.0
 *
 * @return justwpforms_Tracking
 */
function justwpforms_get_tracking() {
	return justwpforms_Tracking::instance();
}

endif;

/**
 * Initialize the justwpforms_Tracking class immediately.
 */
justwpforms_get_tracking();
