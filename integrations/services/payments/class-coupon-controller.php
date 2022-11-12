<?php

class justwpforms_Coupon_Controller {

	private static $instance;

	public $post_type = 'justwpforms-coupon';
	public $action_apply_coupon = 'justwpforms_apply_coupon';
	public $nonce_ajax_coupons = 'justwpforms_coupons_nonce';


	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		self::$instance->hook();

		return self::$instance;
	}

	public function hook() {
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action( 'delete_post', array( $this, 'delete_post' ) );
		add_action( 'wp_ajax_' . $this->action_apply_coupon, array( $this, 'apply_coupon' ) );
		add_action( 'wp_ajax_nopriv_' . $this->action_apply_coupon, array( $this, 'apply_coupon' ) );
		add_action( 'justwpforms_customize_enqueue_scripts', array( $this, 'customize_enqueue_scripts' ) );
		add_action( 'justwpforms_pending_submission_success', array( $this, 'submission_success' ), 10 );

		add_filter( 'justwpforms_frontend_dependencies', array( $this, 'script_dependencies' ), 10, 2 );
		add_filter( 'justwpforms_frontend_settings', array( $this, 'frontend_settings' ) );
		add_filter( 'justwpforms_messages_fields', array( $this, 'meta_messages_fields' ) );
		add_filter( 'justwpforms_messages_controls', array( $this, 'get_message_controls' ) );
		add_filter( 'justwpforms_validate_part_submission', array( $this, 'validate_part_submission' ), 20, 4 );
	}


	public function register_post_type() {
		$labels = array(
			'name' => __( 'Coupons', 'justwpforms' ),
			'singular_name' => __( 'Coupon', 'justwpforms' ),
			'add_new' => __( 'Add New', 'justwpforms' ),
			'add_new_item' => __( 'Add New Coupon', 'justwpforms' ),
			'edit_item' => __( 'Edit Coupon', 'justwpforms' ),
			'new_item' => __( 'Add New Coupon', 'justwpforms' ),
			'view_item' => __( 'View Coupon', 'justwpforms' ),
			'view_items' => __( 'View Coupons', 'justwpforms' ),
			'update_item' => __( 'Update Coupon', 'justwpforms' ),
			'search_items' => __( 'Search Coupons', 'justwpforms' ),
			'not_found' => __( 'No coupons found.', 'justwpforms' ),
			'not_found_in_trash' => __( 'No coupons found in Trash.', 'justwpforms' ),
			'all_items' => __( 'All Coupons', 'justwpforms' ),
			'menu_name' => __( 'All Coupons', 'justwpforms' ),
		);

		$args = array(
			'labels' => $labels,
			'public' => false,
			'publicly_queryable' => is_customize_preview(),
			'exclude_from_search' => true,
			'show_ui' => false,
			'show_in_menu' => false,
			'query_var' => true,
			'rewrite' => false,
			'capability_type' => 'page',
			'has_archive' => false,
			'hierarchical' => false,
			'can_export' => false,
			'supports' => array( 'author', 'custom-fields' ),
		);


		$args = apply_filters( 'justwpforms_coupon_post_type_args', $args );

		register_post_type( $this->post_type, $args );

		$tracking_status = justwpforms_get_tracking()->get_status();

		if ( 1 === intval( $tracking_status['status'] ) ) {
			flush_rewrite_rules();
		}
	}

	public function get_post_object() {
		return get_post_type_object( $this->post_type );
	}

	public function inject_new_form() {
		global $wp_query;

		if ( ! is_customize_preview() ) {
			return;
		}

		if ( ! isset( $wp_query->query['p'] ) ||
			! isset( $wp_query->query['post_type'] ) ) {
			return;
		}

		$queried_post_type = $wp_query->query['post_type'];
		$queried_post_id = intval( $wp_query->query['p'] );

		if ( $this->post_type !== $queried_post_type || 0 !== $queried_post_id ) {
			return;
		}

		$post = $this->create_virtual();
		$this->inject_virtual_post( $post );
	}


	public function get_post_fields() {
		$fields = array(
			'ID' => array(
				'default' => '0',
				'sanitize' => 'intval',
			),
			'post_title' => array(
				'default' => '',
				'sanitize' => 'sanitize_text_field',
			),
			'post_status' => array(
				'default' => 'publish',
				'sanitize' => 'justwpforms_sanitize_post_status',
			),
			'post_type' => array(
				'default' => $this->post_type,
				'sanitize' => 'sanitize_text_field',
			)
		);

		return $fields;
	}

	public function get_meta_fields() {
		$fields = array(
			'discount_type' => array(
				'default' => 'percentage',
				'sanitize' => 'sanitize_text_field',
			),
			'discount_amount' => array(
				'default' => '',
				'sanitize' => 'justwpforms_sanitize_float',
			),
			'description' => array(
				'default' => '',
				'sanitize' => 'sanitize_text_field',
			),
			'redemptions' => array(
				'default' => 0,
				'sanitize' => 'sanitize_text_field',
			),
		);

		$fields = apply_filters( 'justwpforms_coupon_meta_fields', $fields );

		return $fields;
	}

	public function get_fields( $group = '' ) {
		$fields = array();

		switch ( $group ) {
			case 'post':
				$fields = $this->get_post_fields();
				break;
			case 'meta':
				$fields = $this->get_meta_fields();
				break;
			default:
				$fields = array_merge(
					$this->get_post_fields(),
					$this->get_meta_fields()
				);
				break;
		}

		return $fields;
	}

	public function get_field( $field ) {
		$fields = $this->get_fields();

		if ( isset( $fields[$field] ) ) {
			return $fields[$field];
		}

		return null;
	}

	public function get_defaults( $group = '' ) {
		$defaults = wp_list_pluck( $this->get_fields( $group ), 'default' );

		return $defaults;
	}

	public function get_default( $field ) {
		$defaults = $this->get_defaults();

		if ( isset( $defaults[$field] ) ) {
			return $defaults[$field];
		}

		return null;
	}

	public function validate_field( &$value, $key ) {
		$field = $this->get_field( $key );

		if ( isset( $field['sanitize'] ) && is_callable( $field['sanitize'] ) ) {
			$callback = $field['sanitize'];
			$value = call_user_func( $callback, $value );
		};
	}

	public function validate_fields( $post_data = array() ) {
		$defaults = $this->get_defaults();
		$filtered = array_intersect_key( $post_data, $defaults );
		$validated = wp_parse_args( $post_data, $filtered );
		array_walk( $validated, array( $this, 'validate_field' ) );

		return $validated;
	}

	private function create_virtual() {
		$post_id = 0;
		$defaults = $this->get_defaults();

		$post = new stdClass();
		$post->ID = $post_id;
		$post->post_author = 1;
		$post->post_date = current_time( 'mysql' );
		$post->post_date_gmt = current_time( 'mysql', 1 );
		$post->post_title = $this->get_default( 'post_title' );
		$post->post_content = '';
		$post->post_status = 'publish';
		$post->comment_status = 'closed';
		$post->ping_status = 'closed';
		$post->post_name = '';
		$post->post_type = $this->post_type;
		$post->filter = 'raw';

		$wp_post = new WP_Post( $post );
		wp_cache_add( $post_id, $wp_post, 'posts' );

		return $wp_post;
	}

	private function inject_virtual_post( $post ) {
		global $wp, $wp_query;

		$wp_query->post = $post;
		$wp_query->posts = array( $post );
		$wp_query->queried_object = $post;
		$wp_query->queried_object_id = 0;
		$wp_query->found_posts = 1;
		$wp_query->post_count = 1;
		$wp_query->max_num_pages = 1;
		$wp_query->is_page = false;
		$wp_query->is_singular = true;
		$wp_query->is_single = true;
		$wp_query->is_attachment = false;
		$wp_query->is_archive = false;
		$wp_query->is_category = false;
		$wp_query->is_tag = false;
		$wp_query->is_tax = false;
		$wp_query->is_author = false;
		$wp_query->is_date = false;
		$wp_query->is_year = false;
		$wp_query->is_month = false;
		$wp_query->is_day = false;
		$wp_query->is_time = false;
		$wp_query->is_search = false;
		$wp_query->is_feed = false;
		$wp_query->is_comment_feed = false;
		$wp_query->is_trackback = false;
		$wp_query->is_home = false;
		$wp_query->is_embed = false;
		$wp_query->is_404 = false;
		$wp_query->is_paged = false;
		$wp_query->is_admin = false;
		$wp_query->is_preview = false;
		$wp_query->is_robots = false;
		$wp_query->is_posts_page = false;
		$wp_query->is_post_type_archive = false;

		$GLOBALS['wp_query'] = $wp_query;
		$wp->register_globals();
	}

	public function create() {
		$defaults = $this->get_defaults( 'post' );
		$meta = $this->get_defaults( 'meta' );
		$meta = justwpforms_prefix_meta( $meta );
		$data = array_merge( $defaults, $meta );
		$data = apply_filters( 'justwpforms_create_coupon_data', $data );
		$defaults = array_intersect_key( $data, $defaults );
		$meta = array_intersect_key( $data, $meta );

		$post_data = array_merge( $defaults, array(
			'meta_input' => $meta
		) );

		$result = wp_insert_post( wp_slash( $post_data ), true );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return $result;
	}

	public function do_get( $post_ids = array(), $only_id = false ) {
		$query_params = array(
			'post_type'   => justwpforms_get_coupon_controller()->post_type,
			'post_status' => array( 'publish' ),
			'posts_per_page' => -1,
			'orderby' => 'modified',
			'order' => 'desc',
		);

		$query_params['post__in'] = is_array( $post_ids ) ? $post_ids : array( $post_ids );

		if ( true === $only_id ) {
			$query_params['fields'] = 'ids';
		}

		if ( 0 !== $post_ids ) {
			$coupons = get_posts( $query_params );
		} else {
			$coupons = array( $this->create_virtual() );
		}

		if ( true === $only_id ) {
			return $coupons;
		}

		$coupon_entries = array();

		foreach ( $coupons as $coupon ) {
			$coupon_entries[] = $this->to_array( $coupon );
		}

		if ( ! is_array( $post_ids ) ) {
			if ( count( $coupon_entries ) > 0 ) {
				return $coupon_entries[0];
			} else {
				return false;
			}
		}

		return $coupon_entries;
	}

	public function get( $post_ids = array(), $only_id = false ) {
		$args = md5( serialize( func_get_args() ) );
		$key = "_justwpforms_cache_coupons_get_{$args}";
		$found = false;
		$result = justwpforms_cache_get( $key, $found );

		if ( false === $found ) {
			$result = $this->do_get( $post_ids, $only_id );
			justwpforms_cache_set( $key, $result );
		}

		return $result;
	}

	public function to_array( $coupon ) {
		$coupon_array = $coupon->to_array();

		$defaults = $this->get_defaults( 'meta' );
		$meta = justwpforms_unprefix_meta( get_post_meta( $coupon->ID ) );
		$coupon_array = array_merge( $coupon_array, wp_parse_args( $meta, $defaults ) );

		$coupon_array = apply_filters( 'justwpforms_get_coupon_data', $coupon_array );

		return $coupon_array;
	}

	public function update( $coupon_data = array() ) {
		if ( 'percentage' === $coupon_data['discount_type'] ) {
			$coupon_data['discount_amount'] = min( $coupon_data['discount_amount'], 100 );
		}

		$validated_data = $this->validate_fields( $coupon_data );

		if ( isset( $validated_data['ID'] ) && 0 === $validated_data['ID'] ) {
			$coupon = $this->create();

			if ( is_wp_error( $coupon ) ) {
				return $coupon;
			}

			$validated_data['ID'] = $coupon;
		}

		$post_data = array_intersect_key( $validated_data, $this->get_defaults( 'post' ) );

		$meta_data = array_intersect_key( $validated_data, $this->get_defaults( 'meta' ) );
		$meta_data = apply_filters( 'justwpforms_validate_coupon_meta_data', $meta_data );
		$meta_data = justwpforms_prefix_meta( $meta_data );

		$update_data = array_merge( $post_data, $meta_data );
		$update_data = apply_filters( 'justwpforms_update_coupon_data', $update_data );

		$post_data = array_intersect_key( $update_data, $post_data);
		$meta_data = array_intersect_key( $update_data, $meta_data);

		$update_data = array_merge( $post_data, array(
			'meta_input' => $meta_data
		) );

		$update_data = wp_slash( $update_data );

		$coupon_id = wp_update_post( $update_data, true );

		return $coupon_id;
	}

	public function delete( $coupon_id ) {
		$result = wp_delete_post( $coupon_id, true );

		return $result;
	}

	public function delete_post( $post_id ) {
		$post = get_post( $post_id );

		if ( $this->post_type !== $post->post_type ) {
			return;
		}

		do_action( 'justwpforms_coupon_deleted', $post_id );
	}

	public function customize_enqueue_scripts( $deps ) {
		wp_enqueue_script(
			'justwpforms-coupons',
			justwpforms_get_plugin_url() . 'integrations/services/payments/assets/js/customize-coupons.js',
			$deps, justwpforms_get_version(), true
		);
	}

	public function script_dependencies( $deps, $forms ) {
		$allow_coupons = false;
		$form_controller = justwpforms_get_form_controller();
		$payment_part = null;

		foreach ( $forms as $form ) {
			$payment_part = $form_controller->get_first_part_by_type( $form, 'payments' );

			if ( $payment_part ) {
				if ( justwpforms_is_truthy( $payment_part['accept_coupons'] ) ) {
					$allow_coupons = true;
					break;
				}

			}
		}

		if ( ! justwpforms_is_preview() && ! $allow_coupons ) {
			return $deps;
		}

		if ( $allow_coupons ) {
			wp_register_script(
				'justwpforms-part-coupons',
				justwpforms_get_plugin_url() . 'integrations/services/payments/assets/js/coupons.js',
				array(), justwpforms_get_version(), true
			);

			$deps[] = 'justwpforms-part-coupons';
		}

		return $deps;
	}

	public function frontend_settings( $settings ) {
		$settings['coupons'] = array(
			'action' => $this->action_apply_coupon,
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( $this->nonce_ajax_coupons ),
		);

		return $settings;
	}

	public function apply_coupon() {
		check_ajax_referer( $this->nonce_ajax_coupons, 'nonce' );

		$response = array();

		if ( ! isset( $_POST['coupon'] ) ) {
			wp_die(0);
		}

		if ( ! isset( $_POST['formid'] ) || 0 == $_POST['formid'] ) {
			$response['message'] = __( 'Something went wrong. Please try again later.', 'justwpforms' );

			wp_send_json_error( $response );
		}

		$form_controller = justwpforms_get_form_controller();
		$form = $form_controller->get( $_POST['formid'] );
		$payment_part = $form_controller->get_first_part_by_type( $form, 'payments' );
		$payment_part_name = justwpforms_get_part_name( $payment_part, $form );
		$coupon = get_page_by_title( $_POST['coupon'], OBJECT, $this->post_type );

		if ( $coupon ) {
			if ( $coupon->post_title !== $_POST['coupon'] ) {
				$response['message'] = $this->get_error_notice( $form['coupon_not_allowed'], $payment_part_name );

				wp_send_json_error( $response );
			}

			$amount = $payment_part['price'];
			$currency = $payment_part['currency'];
			$currency_symbol = justwpforms_payment_get_currencies()[ $currency ]['symbol'];

			$coupon = $this->get( $coupon->ID );
			$discount_type = $coupon['discount_type'];
			$discount_amount = $coupon['discount_amount'];

			$discounted = $amount;

			if ( 'percentage' === $discount_type ) {
				$discount_amount = ( $amount / 100 ) * $discount_amount;
			}

			$discounted = $amount - $discount_amount;

			if ( $discounted < 0 ) {
				$discounted = 0;
			}

			$response['coupon'] = $coupon['ID'];
			$response['amount'] = $discounted;
			$response['amount_display'] = $currency_symbol . $discounted;

			wp_send_json_success( $response );
		} else {
			$response['message'] = $this->get_error_notice( $form['coupon_not_allowed'], $payment_part_name );

			wp_send_json_error( $response );
		}

		wp_die(0);
	}

	public function get_error_notice( $message, $part_name ) {
		ob_start();

		$notices = array( array(
			'message' => array(
				'coupon' => $message,
			)
		) );

		justwpforms_the_part_error_message( $notices, $part_name, 'coupon' );

		return ob_get_clean();
	}

	public function meta_messages_fields( $fields ) {
		$fields = array_merge( $fields, $this->get_messages_fields() );

		return $fields;
	}

	public function get_messages_fields() {
		$fields = array(
			'coupon_label' => array(
				'default' => __(  'Coupon', 'justwpforms' ),
				'sanitize' => 'sanitize_text_field',
			),
			'coupon_apply_label' => array(
				'default' => __(  'Apply', 'justwpforms' ),
				'sanitize' => 'sanitize_text_field',
			),
			'coupon_not_allowed' => array(
				'default' => __(  "This coupon isn't allowed.", 'justwpforms' ),
				'sanitize' => 'sanitize_text_field',
			),
		);

		return $fields;
	}

	public function get_message_controls( $controls ) {
		$message_controls = array(
			6168 => array(
				'type' => 'text',
				'label' => __( 'Coupon field label', 'justwpforms' ),
				'field' => 'coupon_label',
			),
			6169 => array(
				'type' => 'text',
				'label' => __( 'Apply coupon button label', 'justwpforms' ),
				'field' => 'coupon_apply_label',
			),
			4051 => array(
				'type' => 'text',
				'label' => __( 'Coupon code invalid', 'justwpforms' ),
				'field' => 'coupon_not_allowed',
			),
		);

		$controls = justwpforms_safe_array_merge( $controls, $message_controls );

		return $controls;
	}

	public function submission_success( $submission_id ) {

		$submission = justwpforms_get_message_controller()->get( $submission_id );
		$form_controller = justwpforms_get_form_controller();
		$form = $form_controller->get( $submission['form_id'] );

		$part = $form_controller->get_first_part_by_type( $form, 'payments' );

		if ( $part && isset( $part['accept_coupons'] ) && 1 == $part['accept_coupons'] ) {
			$field_name = justwpforms_get_part_name( $part, $form );
			$coupon = $submission['request'][ $field_name ]['coupon'];

			$coupon_controller = justwpforms_get_coupon_controller();
			$coupon = $coupon_controller->get( $coupon );

			if ( $coupon ) {
				$coupon['redemptions'] += 1;

				$coupon_controller->update( $coupon );
			}
		}
	}

	public function validate_part_submission( $value, $part, $form, $request ) {
		if ( 'payments' !== $part['type'] || is_wp_error( $value ) ) {
			return $value;
		}

		if ( justwpforms_is_truthy( $part['accept_coupons'] ) ) {
			$part_name = justwpforms_get_part_name( $part, $form );
			$payment_details = $request[ $part_name ];

			$value['price'] = $payment_details['price'];
		}

		return $value;
	}

}

if ( ! function_exists( 'justwpforms_get_coupon_controller' ) ):

function justwpforms_get_coupon_controller() {
	return justwpforms_Coupon_Controller::instance();
}

endif;

justwpforms_get_coupon_controller();
