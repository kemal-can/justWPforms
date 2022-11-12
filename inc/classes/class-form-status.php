<?php

class justwpforms_Form_Status {

	/**
	 * The singleton instance.
	 *
	 * @var justwpforms_Form_Status
	 */
	private static $instance;

	public $status_action = 'justwpforms_form_status';

	/**
	 * The singleton constructor.
	 *
	 * @return justwpforms_Form_Status
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		self::$instance->hook();

		return self::$instance;
	}

	/**
	 * Hook into WordPress.
	 *
	 * @return void
	 */
	public function hook() {
		add_filter( 'justwpforms_validate_submission', array( $this, 'validate_submission' ), 10, 3 );
		add_action( 'init', array( $this, 'register_post_status' ) );
		add_action( 'load-edit.php', array( $this, 'handle_action' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
	}

	public function register_post_status() {
		$post_type = justwpforms_get_form_controller()->post_type;
		$label = __( 'Archive', 'justwpforms' );

		register_post_status(
			'archive',
			array(
				'label' => $label,
				'label_count' => _n_noop( "{$label} <span class=\"count\">(%s)</span>", "{$label} <span class=\"count\">(%s)</span>" ),
				'public' => false,
				'post_type' => array( $post_type ),
				'exclude_from_search' => true,
				'show_in_admin_all_list' => false,
				'show_in_admin_status_list' => true,
			)
		);
	}

	public function is_archived( $form = array() ) {
		if ( 'archive' === justwpforms_get_form_property( $form, 'post_status' ) ) {
			return true;
		}

		return false;
	}

	public function validate_submission( $is_valid, $request, $form ) {
		if ( $this->is_archived( $form ) ) {
			$is_valid = false;
		}

		return $is_valid;
	}

	public function handle_action() {
		if ( ! isset( $_GET['action'] )
			|| $this->status_action !== $_GET['action']
			|| ! isset( $_GET['_wpnonce'] )
			|| ! isset( $_GET['status'] ) ) {

			return;
		}

		$nonce = $_GET['_wpnonce'];
		$status = $_GET['status'];

		if ( ! isset( $_GET['form_ids'] ) ) {
			return;
		}

		$form_ids = $_GET['form_ids'];

		if ( ! wp_verify_nonce( $nonce, "{$this->status_action}-{$form_ids}" ) ) {
			return;
		}

		$form_ids_array = explode( ',', $form_ids );

		$form_controller = justwpforms_get_form_controller();
		$form = $form_controller->get( $form_ids_array );

		if ( ! $form ) {
			return;
		}

		if ( ! in_array( $status, array( 'publish', 'archive' ) ) ) {
			return;
		}

		$redirect_url = admin_url( "edit.php?post_type={$form_controller->post_type}" );

		switch ( $status ) {
			case 'archive':
				$post_status = 'archive';

				$redirect_url = remove_query_arg( 'restored', $redirect_url );
				$redirect_url = add_query_arg( array(
					'all_posts' => 1,
					'form_ids' => $form_ids,
					'archived' => count( $form_ids_array )
				), $redirect_url );

				break;
			case 'publish':
				$post_status = 'publish';

				if ( isset( $_GET['restored'] ) ) {
					$query_args = array(
						'form_ids' => $form_ids,
						'restored' => count( $form_ids_array )
					);
				} else if ( isset( $_GET['untrashed'] ) ) {
					$query_args = array(
						'form_ids' => $form_ids,
						'untrashed' => count( $form_ids_array )
					);
				}

				if ( ! isset( $_GET['undo'] ) ) {
					$query_args['post_status'] = 'archive';
				}

				$redirect_url = remove_query_arg( 'archived', $redirect_url );
				$redirect_url = add_query_arg( $query_args, $redirect_url );

				break;
		}

		foreach ( $form_ids_array as $form_id ) {
			wp_update_post( array(
				'ID' => $form_id,
				'post_status' => $post_status
			) );
		}

		wp_redirect( $redirect_url );
		exit;
	}

	public function admin_enqueue_scripts() {
		if ( ! isset( $_GET['post_status'] ) || 'archive' !== $_GET['post_status'] ) {
			return;
		}

		wp_localize_script(
			'justwpforms-admin-upgrade',
			'_justwpformsFormStatusSettings',
			array(
				'labels' => array(
					'not_found_in_archive' => __( 'No forms found in Archive.', 'justwpforms' )
				)
			)
		);
	}

}

if ( ! function_exists( 'justwpforms_get_form_status' ) ):

function justwpforms_get_form_status() {
	return justwpforms_Form_Status::instance();
}

endif;

justwpforms_get_form_status();
