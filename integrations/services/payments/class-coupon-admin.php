<?php
class justwpforms_Coupon_Admin {

	private static $instance;
	private $list_table;

	public $action_add = 'justwpforms_add_coupon';
	public $action_edit = 'justwpforms_edit_coupon';
	public $action_delete = 'justwpforms_delete_coupon';
	public $action_ajax_delete = 'justwpforms_ajax_delete_coupon';
	public $action_bulk_delete = 'justwpforms_bulk_delete_coupon';
	public $action_inline_update = 'justwpforms-inline-save-coupon';
	public $screen_id = 'forms_page_justwpforms-coupon';
	public $controller = null;

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		self::$instance->hook();

		return self::$instance;
	}

	public function hook() {
		add_action( 'admin_init', array( $this, 'do_coupon_actions' ), 10 );
		add_action( 'admin_head', array( $this, 'output_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		add_action( 'wp_ajax_' . $this->action_add, array( $this, 'ajax_add_coupon' ) );
		add_action( 'wp_ajax_' . $this->action_inline_update, array( $this, 'ajax_inline_update_coupon' ) );
		add_action( 'wp_ajax_' . $this->action_ajax_delete, array( $this, 'ajax_delete_coupon' ) );

		add_filter( 'justwpforms_coupons_page_url', array( $this, 'page_url' ) );
		add_filter( 'justwpforms_coupons_page_method', array( $this, 'set_admin_page_method' ) );
		add_filter( 'load-' . $this->screen_id, array( $this, 'add_screen_options' ) );
		add_filter( 'admin_title', array( $this, 'admin_title' ) );
		add_filter( "manage_{$this->screen_id}_columns", array( $this, 'column_headers' ) );

		$this->controller = justwpforms_get_coupon_controller();
	}

	public function page_url( $url ) {
		$url = $this->controller->post_type;

		return $url;
	}

	public function set_admin_page_method() {
		return array( $this, 'coupons_page' );
	}

	public function do_coupon_actions() {
		if ( ! class_exists( 'WP_List_Table' ) ) {
		    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
		}

		require_once( justwpforms_get_integrations_folder() . '/services/payments/class-coupon-list-table.php' );

		$this->list_table = new justwpforms_Coupon_List_Table();

		switch( $this->list_table->current_action() ) {
			case $this->action_edit:
				$message = $this->update_coupon();
				$location = remove_query_arg( 'action', wp_get_referer() );
				$location = add_query_arg( 'message', $message );

				wp_safe_redirect(  $location );
				exit;

				break;

			case $this->action_delete:
				$this->delete_coupon();
				$location = remove_query_arg( array( 'coupon_ID', 'action' ), wp_get_referer() );

				wp_safe_redirect(  $location );
				exit;
				break;

			case $this->action_bulk_delete:
				$message = $this->bulk_delete_coupons();
				$location = remove_query_arg( 'action', wp_get_referer() );
				$location = add_query_arg( 'message', $message );

				wp_safe_redirect(  $location );
				exit;
				break;
		}

		if ( isset( $_GET['coupon_ID'] ) ) {
			$coupon = $this->controller->get( $_GET['coupon_ID'] );

			if ( ! $coupon ) {
				wp_die( __( 'You attempted to edit a coupon that does not exist. Perhaps it was deleted?', 'justwpforms' ) );
			}
		}		
	}

	public function output_styles() {
		$screen = get_current_screen();

		if ( $screen->id === $this->screen_id ) : ?>
		<style>
		#adv-settings fieldset:first-child label:first-of-type {
			display: none;
		}
		</style>
		<?php endif;
	}

	public function get_notice_message( $slug ) {
		$notices = array(
			'justwpforms_coupon_already_exists' => array(
				'text' => __( 'Coupon name you\'re trying to update already exists.', 'justwpforms' ),
				'class' => 'error',
			),
			'justwpforms_coupon_not_updated' => array(
				'text' => __( 'Coupon not updated.', 'justwpforms' ),
				'class' => 'error',
			),
			'justwpforms_coupon_updated' => array(
				'text' => __( 'Coupon updated.', 'justwpforms' ),
				'class' => 'success',
			),
			'justwpforms_coupons_deleted' => array(
				'text' => __( 'Coupons deleted.', 'justwpforms' ),
				'class' => 'success',
			),
		);

		return $notices[$slug];
	}

	public function coupons_page() {
		wp_enqueue_script('dashboard');
		add_filter( 'admin_footer_text', 'justwpforms_admin_footer' );
		
		$current_screen = get_current_screen();
		$coupon_object = $this->controller->get_post_object();
		$post_type = $this->controller->post_type;
		$labels = $coupon_object->labels;
		$coupon_id = empty( $_REQUEST['coupon_ID'] ) ? 0 : $_REQUEST['coupon_ID'];
		$message = isset( $_REQUEST['message'] ) ? $this->get_notice_message( $_REQUEST['message'] ) : null;

		if ( 0 == $coupon_id ) {
			$this->list_table->prepare_items();
			$wp_list_table = $this->list_table;

			require_once( justwpforms_get_integrations_folder() . '/services/payments/templates/admin-coupon-list.php' );
		} else {
			$coupon = $this->controller->get( $coupon_id );

			require_once( justwpforms_get_integrations_folder() . '/services/payments/templates/admin-coupon-edit.php' );
		}
	}

	public function admin_title( $admin_title ) {
		if ( justwpforms_is_admin_screen( $this->controller->post_type ) ) {
			if ( ! empty( $_REQUEST['coupon_ID'] ) ) {
				$before_title = $this->controller->get_post_object()->labels->edit_item;
				$after_title = sprintf( __( '&lsaquo; %s &#8212; WordPress' ), get_bloginfo( 'name' ) );

				$admin_title = "{$before_title} {$after_title}";
			}
		}

		return $admin_title;
	}

	public function column_headers( $columns ) {
		if ( ! isset( $_REQUEST['coupon_ID'] ) ) {
			$columns['description'] = 'Description';
			$columns['redemptions'] = 'Redemptions';
		}

		return $columns;
	}

	public function admin_enqueue_scripts() {
		if ( ! justwpforms_is_admin_screen( $this->controller->post_type ) ) {
			return;
		}

		wp_enqueue_script(
			'justwpforms-admin-coupon-inline-edit',
			justwpforms_get_plugin_url() . 'integrations/services/payments/assets/js/admin/inline-edit-coupon.js',
			array( 'jquery' ), justwpforms_get_version(), true
		);
		wp_enqueue_script(
			'justwpforms-admin-coupon',
			justwpforms_get_plugin_url() . 'integrations/services/payments/assets/js/admin/coupon.js',
			array( 'jquery', 'justwpforms-admin-coupon-inline-edit' ), justwpforms_get_version(), true
		);
	}

	public function add_screen_options( $show ) {
		if ( ! isset( $_REQUEST['coupon_ID'] ) ) {
			add_screen_option(
				'per_page',
				array(
					'default' => 20,
					'option'  => 'edit_' . $this->controller->post_type . '_per_page',
				)
			);
		}
	}

	public function update_coupon() {
		check_admin_referer( $this->controller->post_type . '-nonce', $this->controller->post_type . '-nonce' );

		$_POST['post_title'] = preg_replace( '/\s+/', '', $_POST['post_title'] );
		$return_data = array();
		$admin_notices = justwpforms_get_admin_notices();
		$coupon = get_post( $_POST['ID'] );
		$message = 'justwpforms_coupon_updated';

		if ( $coupon && $coupon->post_title != $_POST['post_title'] ) {
			$coupon_check = get_page_by_title( $_POST['post_title'], OBJECT, $this->controller->post_type );
			
			if ( $coupon_check ) {
				$message = 'justwpforms_coupon_already_exists';
				
				return $message;
			}
		}

		$result = $this->controller->update( $_POST );

		if ( ! $result || is_wp_error( $result ) ) {
			$message = 'justwpforms_coupon_not_updated';
			
			return $message;
		}

		return $message;
	}
	public function delete_coupon() {
		check_admin_referer( $this->controller->post_type . '-nonce' );

		if ( isset( $_GET['coupon_ID'] ) ) {
			$coupon = $this->controller->get( $_GET['coupon_ID'] );

			if ( ! $coupon ) {
				wp_die( __( 'You attempted to edit a coupon that does not exist. Perhaps it was deleted?', 'justwpforms' ) );
			}

			$this->controller->delete( $_GET['coupon_ID'] );
		}
	}

	public function bulk_delete_coupons() {
		check_admin_referer( $this->controller->post_type . '-nonce', $this->controller->post_type . '-nonce' );

		$message = 'justwpforms_coupons_deleted';

		if ( isset( $_POST['delete_coupons'] ) ) {
			$coupon_ids = ( array ) $_REQUEST['delete_coupons'];
			
			foreach ( $coupon_ids as $id ) {
				$this->controller->delete( $id );
			}

		}

		return $message;
	}

	public function ajax_add_coupon() {
		check_ajax_referer( $this->controller->post_type . '-nonce', $this->controller->post_type . '-nonce' );

		$x = new WP_Ajax_Response();
		$message = '';

		if ( ! isset( $_POST['post_title'] ) || '' === $_POST['post_title'] ) {

			$x->add(
				array(
					'what' => $this->controller->post_type,
					'data' => new WP_Error( 'error-field', __( 'A name is required for this coupon.', 'justwpforms' ) ),
				)
			);

			$x->send();
		}

		if ( ! isset( $_POST['discount_amount'] )  || '' === $_POST['discount_amount'] ) {

			$x->add(
				array(
					'what' => $this->controller->post_type,
					'data' => new WP_Error( 'error-field', __( 'A discount amount is required for this coupon.', 'justwpforms' ) ),
				)
			);

			$x->send();
		}

		$_POST['post_title'] = preg_replace( '/\s+/', '', $_POST['post_title'] );


		$coupon = get_page_by_title( $_POST['post_title'], OBJECT, $this->controller->post_type );

		if ( $coupon ) {
			$x->add(
				array(
					'what' => $this->controller->post_type,
					'data' => new WP_Error( 'error-exists', __( 'This coupon already exists.', 'justwpforms' ) ),
				)
			);
		} else {
			$result = $this->controller->update( $_POST );

			if ( ! $result || is_wp_error( $result ) ) {
				$message = __( "An error has occurred. Coupon can't be added", 'justwpforms' );
				$error_code = 'error';

				if ( is_wp_error( $result ) && $result->get_error_message() ) {
					$message = $result->get_error_message();
				}

				if ( is_wp_error( $result ) && $result->get_error_code() ) {
					$error_code = $result->get_error_code();
				}

				$x->add(
					array(
						'what' => $this->controller->post_type,
						'data' => new WP_Error( $error_code, $message ),
					)
				);
			}

			$coupon = get_post( $result );
			$message = __( 'Coupon added.', 'justwpforms');

			require_once( justwpforms_get_integrations_folder() . '/services/payments/class-coupon-list-table.php' );
			$wp_list_table = new justwpforms_Coupon_List_Table();

			ob_start();
			$wp_list_table->single_row( $coupon );
			$row = ob_get_clean();

			$x->add(
				array(
					'what' => 'justwpforms_add_coupon',
					'data' => $message,
					'supplemental' => array (
						'coupon_row' => $row,
						'notice' => $message,
					)
				)
			);
		}

		$x->send();
	}

	public function ajax_inline_update_coupon(){
		check_ajax_referer( 'justwpformscouponinlineedit', '_inline_edit' );

		if ( ! isset( $_POST['ID'] ) || ! (int) $_POST['ID'] ) {
			wp_die( -1 );
		}

		$id = (int) $_POST['ID'];
		$_POST['post_title'] = preg_replace( '/\s+/', '', $_POST['post_title'] );

		if ( '' === $_POST['post_title'] ) {
			wp_die( __( 'A name is required for this coupon.' ) );
		}

		if ( '' === $_POST['discount_amount'] ) {
			wp_die( __( 'A discount amount is required for this coupon.' ) );
		}

		$return_data = array();
		$proceed_update = true;

		$coupon = get_post( $id );

		if ( $coupon && $coupon->post_title != $_POST['post_title'] ) {
			$coupon_check = get_page_by_title( $_POST['post_title'], OBJECT, $this->controller->post_type );
			if ( $coupon_check ) {
				wp_die( __( 'Name already existed' ) );
			}
		}


		if ( $proceed_update ) {
			$result = $this->controller->update( $_POST );

			if ( ! $result || is_wp_error( $result ) ) {
				wp_die( __( 'Coupon not updated.' ) );
			} else {
				require_once( justwpforms_get_integrations_folder() . '/services/payments/class-coupon-list-table.php' );

				$wp_list_table = new justwpforms_Coupon_List_Table();
				$coupon = get_post( $id );
				$wp_list_table->single_row( $coupon, 0 );
			}
		}

		wp_die();
	}

	public function ajax_delete_coupon() {
		check_ajax_referer( $this->controller->post_type . '-nonce' );

		if ( isset ( $_REQUEST['coupon_ID'] ) && 0 != $_REQUEST['coupon_ID'] ) {
			$post_id = $_REQUEST['coupon_ID'];

			$result = $this->controller->delete( $post_id );

			if ( $result ) {
				wp_die( 1 );
			} else {
				wp_die( 0 );
			}
		}

		wp_die( 0 );
	}

}

justwpforms_Coupon_Admin::instance();
