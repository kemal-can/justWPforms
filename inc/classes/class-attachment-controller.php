<?php

class justwpforms_Attachment_Controller {

	/**
	 * The singleton instance.
	 *
	 * @var justwpforms_Attachment_Controller
	 */
	private static $instance;

	/**
	 * Whether or not frontend styles were loaded.
	 */
	private $frontend_styles = false;

	public $part_parameter = 'justwpforms_part_id';

	public $action = 'justwpforms_upload';

	public $action_delete = 'justwpforms_delete_file';

	public $schedule_remove_unassigned = 'justwpforms_remove_unassigned_attachments';

	/**
	 * The singleton constructor.
	 *
	 * @return justwpforms_Attachment_Controller
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
	 * @return void
	 */
	public function hook() {
		add_action( 'wp_ajax_' . $this->action, array( $this, 'ajax_handle_upload' ) );
		add_action( 'wp_ajax_nopriv_' . $this->action, array( $this, 'ajax_handle_upload' ) );
		add_action( 'wp_ajax_' . $this->action_delete, array( $this, 'ajax_handle_delete' ) );
		add_action( 'wp_ajax_nopriv_' . $this->action_delete, array( $this, 'ajax_handle_delete' ) );
		add_filter( 'justwpforms_frontend_settings', array( $this, 'attachment_settings' ), 10, 1 );
		add_filter( $this->action . '_prefilter', array( $this, 'file_filter' ) );
		add_action( 'justwpforms_response_created', array( $this, 'assign_attachments' ), 10, 2 );
		add_action( 'justwpforms_draft_created', array( $this, 'assign_attachments' ), 10, 2 );
		add_action( 'justwpforms_draft_updated', array( $this, 'assign_attachments' ), 10, 2 );
		add_action( 'justwpforms_response_created', array( $this, 'assign_signatures' ), 10, 2 );
		add_action( $this->schedule_remove_unassigned, array( $this, 'remove_unassigned_attachments' ) );
		add_action( 'justwpforms_before_delete_response', array( $this, 'before_delete_response' ) );
		add_filter( 'ajax_query_attachments_args', array( $this, 'handle_attachments_media_overlay' ) );
		add_filter( 'pre_get_posts', array( $this, 'handle_attachments_media_list' ) );
		add_action( 'wp_enqueue_media', array( $this, 'enqueue_media' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		add_action( 'delete_attachment', array( $this, 'cache_deleted_attachments' ) );
		add_action( 'restrict_manage_posts', array( $this, 'additional_media_filters'), PHP_INT_MAX );
	}

	public function get_post_fields() {
		$fields = array(
			'post_mime_type' => '',
			'guid' => '',
			'post_parent' => 0,
			'post_title' => '',
			'post_content' => '',
			'post_excerpt' => '',
		);

		return $fields;
	}

	public function get_meta_fields() {
		$fields = array(
			'form_id' => '',
			'part_id' => '',
			'hash_id' => '',
		);

		return $fields;
	}

	public function validate( $request, $part ) {
		// Mime checks
		$extensions = justwpforms_explode_value( $part['allowed_file_extensions'] );

		$mimes = array_map( 'justwpforms_get_file_mime', $extensions );
		$mimes = call_user_func_array( 'array_merge', $mimes );

		// Strictly limit allowed mimes
		add_filter( 'upload_mimes', function( $t ) use( $mimes ) {
			return $mimes;
		} );

		$overrides = array(
			'test_form' => false,
			'action' => $this->action,
			'test_type' => true,
		);

		$file_data = wp_handle_upload( $request, $overrides );

		if ( isset( $file_data['error'] ) ) {
			return new WP_Error( 'upload_error', $file_data['error'] );
		}

		// Max file size
		$max_file_size = isset( $part['max_file_size'] ) ? $part['max_file_size'] : '';
		$max_file_size = floatval( $part['max_file_size'] ) * 1024 * 1024;
		$file_size = $request['size'];

		if ( $file_size > $max_file_size ) {
			return new WP_Error( 'upload_error', justwpforms_get_validation_message( 'file_size_too_big' ) );
		}

		// Append pretty name
		$file_data['pretty_name'] = $request['pretty_name'];

		return $file_data;
	}

	public function validate_base64( $file, $part ) {
		$overrides = array(
			'test_form' => false,
			'test_type' => true,
			'mimes' => array( 'png' => 'image/png' ),
		);

		$temp_file_name = wp_tempnam();
		$temp_file = file_put_contents( $temp_file_name, file_get_contents( $file ) );
		$file_name = str_replace( 'tmp', 'png', basename( $temp_file_name ) );

		if ( apply_filters( 'justwpforms_optimize_signature_images', true ) ) {
			require_once ABSPATH . WPINC . '/class-wp-image-editor.php';
			require_once ABSPATH . WPINC . '/class-wp-image-editor-imagick.php';

			if ( WP_Image_Editor_Imagick::test() ) {
				$image = new imagick( $temp_file_name );
				$image->quantizeImage( 255, Imagick::COLORSPACE_RGB, 0, false, false );
				$image->writeImage( "$temp_file_name" );
			}
		}

		$file_data = array(
			'name' => $file_name,
			'type' => 'image/png',
			'tmp_name' => $temp_file_name,
			'error' => 0,
			'size' => $temp_file,
		);

		$file_data = wp_handle_sideload( $file_data, $overrides );

		if ( isset( $file_data['error'] ) ) {
			return new WP_Error( 'upload_error', $file_data['error'] );
		}

		// Append pretty name
		$file_data['pretty_name'] = $file_name;

		// Delete temporary file
		@unlink( $temp_file_name );

		return $file_data;
	}

	public function file_filter( $request ) {
		$request['pretty_name'] = $request['name'];
		$request['name'] = $this->hash_file_name( $request );

		return $request;
	}

	public function hash_file_name( $request ) {
		$ext = pathinfo( $request['name'], PATHINFO_EXTENSION );
		$name = md5( $request['tmp_name'] );
		$name = "$name.$ext";

		return $name;
	}

	public function hash_id( $request ) {
		$ext = pathinfo( $request['name'], PATHINFO_EXTENSION );
		$name = md5( $request['tmp_name'] );
		$name = "$name.$ext";

		return $name;
	}

	public function to_array( $attachment ) {
		$attachment_array = $attachment->to_array();

		return $attachment_array;
	}

	public function create( $file_data, $form, $part ) {
		$name = $file_data['pretty_name'];
		$url = $file_data['url'];
		$type = $file_data['type'];
		$file = $file_data['file'];
		$ext  = pathinfo( $name, PATHINFO_EXTENSION );
		$name = wp_basename( $name, ".$ext" );
		$title = sanitize_text_field( $name );
		$form_id = $form['ID'];
		$part_id = $part['id'];
		$hash_id = md5( $url );

		$post_fields = wp_parse_args( array(
			'post_mime_type' => $type,
			'guid' => $url,
			'post_title' => $title,
		), $this->get_post_fields() );

		$meta_fields = wp_parse_args( array(
			'form_id' => $form_id,
			'part_id' => $part_id,
			'hash_id' => $hash_id,
			'upload_draft' => 1,
		), $this->get_meta_fields() );
		$meta_fields = justwpforms_prefix_meta( $meta_fields );

		$attachment_data = array_merge( $post_fields, array(
			'meta_input' => $meta_fields
		) );

		// Disable thumbnail generation
		add_filter( 'intermediate_image_sizes_advanced', function( $sizes ) {
			$sizes = array();

			return $sizes;
		} );

		$attachment_id = wp_insert_attachment( $attachment_data, $file, 0, true );

		if ( is_wp_error( $attachment_id ) ) {
			return $attachment_id;
		}

		$attachment_metadata = wp_generate_attachment_metadata( $attachment_id, $file );
		wp_update_attachment_metadata( $attachment_id,  $attachment_metadata );

		return $hash_id;
	}

	public function create_from_base64( $base64, $form, $part ) {
		// Check file data
		if ( strpos( $base64, 'data:image/png;base64' ) === false ) {
			return new WP_Error( 'invalid_data' );
		}

		// Load function definitions (normally available only in wp-admin scope)
		require_once( ABSPATH . 'wp-admin/includes/file.php' );
		require_once( ABSPATH . 'wp-admin/includes/image.php' );

		// Validate upload
		$file = $this->validate_base64( $base64, $part );

		if ( is_wp_error( $file ) ) {
			return $file;
		}

		$hash_id = $this->create( $file, $form, $part );

		return $hash_id;
	}

	public function get( $args = array() ) {
		$args = wp_parse_args( $args, array(
			'form_id' => '',
		) );

		$query_params = array(
			'post_type' => 'attachment',
			'posts_per_page' => -1,
		);

		// Response
		if ( isset( $args['response_id'] ) ) {
			$query_params['post_parent'] = $args['response_id'];
		}

		// Hash
		if ( isset( $args['hash_id'] ) ) {
			$query_params['meta_query'] =
				isset( $query_params['meta_query'] ) ?
				$query_params['meta_query'] : array();

			$hash_ids =
				is_array( $args['hash_id'] ) ?
				$args['hash_id'] : array( $args['hash_id'] );

			$query_params['meta_query']['hash_clause'] = array(
				'key' => '_justwpforms_hash_id',
				'value' => $hash_ids,
				'compare' => 'IN',
			);
		}

		// Form
		$query_params['meta_query'] =
			isset( $query_params['meta_query'] ) ?
			$query_params['meta_query'] : array();

		$clause = array(
			'key' => '_justwpforms_form_id',
		);

		if ( ! empty( $args['form_id'] ) ) {
			$clause['value'] = $args['form_id'];
		} else {
			$clause['compare'] = 'EXISTS';
		}

		$query_params['meta_query']['form_clause'] = $clause;

		// Part
		if ( isset( $args['part_id'] ) ) {
			$query_params['meta_query'] =
				isset( $query_params['meta_query'] ) ?
				$query_params['meta_query'] : array();

			$query_params['meta_query']['part_clause'] = array(
				'key' => '_justwpforms_part_id',
				'value' => $args['part_id'],
			);
		}

		if ( isset( $query_params['meta_query'] )
			&& 1 < count( array_keys( $query_params['meta_query'] ) ) ) {

			$query_params['relation'] = 'AND';
		}

		// Date
		if ( isset( $args['date'] ) ) {
			$query_params['date_query'] = array(
				'after' => $args['date'],
			);
		}

		$attachments = get_posts( $query_params );
		$attachments = array_map( array( $this, 'to_array'), $attachments );

		foreach( $attachments as &$attachment ) {
			$attachment['form_id'] = justwpforms_get_meta( $attachment['ID'], 'form_id', true );
			$attachment['hash_id'] = justwpforms_get_meta( $attachment['ID'], 'hash_id', true );
		}

		return $attachments;
	}

	public function ajax_handle_upload() {
		$message_controller = justwpforms_get_message_controller();

		// Check form_id and part_id parameter
		if ( ! isset ( $_REQUEST[$message_controller->form_parameter] )
			|| ! isset ( $_REQUEST[$this->part_parameter] ) ) {
			wp_send_json_error( null, 400 );
		}

		$form_id = intval( $_REQUEST[$message_controller->form_parameter] );
		$part_id = $_REQUEST[$this->part_parameter];

		// Check file list
		if ( ! isset( $_FILES ) || ! isset( $_FILES['file'] ) ) {
			wp_send_json_error( null, 400 );
		}

		$form_controller = justwpforms_get_form_controller();
		$form = $form_controller->get( $form_id );
		$part = $form_controller->get_part_by_id( $form, $part_id );

		// Check form and part
		if ( ! $form || ! $part || 'attachment' !== $part['type'] ) {
			wp_send_json_error( null, 400 );
		}

		// Validate upload
		$file = $this->validate( $_FILES['file'], $part );

		if ( is_wp_error( $file ) ) {
			wp_send_json_error( $file, 415 );
		}

		$attachment_id = $this->create( $file, $form, $part );

		// Check upload
		if ( is_wp_error( $attachment_id ) ) {
			wp_send_json_error( $attachment_id, 415 );
		}

		wp_send_json_success( $attachment_id );
	}

	public function ajax_handle_delete() {
		$attachments = $this->get( array( 'hash_id' => $_REQUEST[ 'hash_id'] ) );

		if( ! empty( $attachments ) ) {
			wp_delete_attachment( $attachments[0]['ID'], true );
		}
	}

	public function attachment_settings( $data ) {
		$data['fileUploadAction'] = $this->action;
		$data['fileDeleteAction'] = $this->action_delete;

		return $data;
	}

	public function assign_attachments( $response_id, $form ) {
		foreach ( $form['parts'] as $part ) {
			if ( 'attachment' === $part['type'] ) {
				$response = justwpforms_get_message_controller()->get( $response_id );
				$part_id = $part['id'];

				if ( isset( $response['parts'][$part_id] ) ) {
					$hash_ids = maybe_unserialize( $response['parts'][$part_id] );

					if ( empty( $hash_ids ) ) {
						continue;
					}

					$attachments = $this->get( array(
						'hash_id' => $hash_ids,
					) );

					foreach ( $attachments as $attachment ) {
						wp_update_post( array(
							'ID' => $attachment['ID'],
							'post_parent' => $response_id
						) );

						delete_post_meta( $attachment['ID'], '_justwpforms_upload_draft');
					}
				}
			}
		}
	}

	public function assign_signatures( $response_id, $form ) {
		foreach ( $form['parts'] as $part ) {
			if ( 'signature' === $part['type'] && 'draw' === $part['signature_type'] ) {
				$response = justwpforms_get_message_controller()->get( $response_id );
				$part_id = $part['id'];

				if ( isset( $response['parts'][$part_id] ) ) {
					$part_value = maybe_unserialize( $response['parts'][$part_id] );
					$signature_raster_data = $part_value['signature_raster_data'];
					$signature_hash_id = $this->create_from_base64( $signature_raster_data, $form, $part );

					$attachments = $this->get( array(
						'hash_id' => $signature_hash_id,
					) );

					foreach ( $attachments as $attachment ) {
						wp_update_post( array(
							'ID' => $attachment['ID'],
							'post_parent' => $response_id
						) );

						delete_post_meta( $attachment['ID'], '_justwpforms_upload_draft' );

						// Cleanup signature data
						$part_value['signature_path_data'] = '';
						$part_value['signature_raster_data'] = '';
						// Append signature hash_id
						$part_value['signature_hash_id'] = $signature_hash_id;

						justwpforms_update_meta( $response_id, $part_id, $part_value );
					}
				}
			}
		}
	}

	public function remove_unassigned_attachments() {
		$attachments = $this->get( array(
			'response_id' => 0,
			'form_id' => '',
			'date' => '1 hour ago',
		) );

		foreach ( $attachments as $attachment ) {
			wp_delete_attachment( $attachment['ID'], true );
		}
	}

	public function cache_deleted_attachments( $post_id ) {
		$post = get_post( $post_id );

		if ( ! $post ) {
			return;
		}

		$response_id = $post->post_parent;

		if ( 0 === $response_id ) {
			return;
		}

		$response_post = get_post( $response_id );

		if ( $response_post->post_type != justwpforms_get_message_controller()->post_type ) {
			return;
		}

		$file = get_attached_file( $post_id );

		if ( ! file_exists( $file ) ) {
			return;
		}

		$file_name = $post->post_title;
		$file_type = wp_check_filetype( $file );
		$file_extension = $file_type['ext'];
		$file_size = size_format( filesize( $file ), 2 );
		$file_url = wp_get_attachment_url( $post_id );

		$attachment_data = array(
			'file_name' => $file_name,
			'file_extension' => $file_extension,
			'file_size' => $file_size,
			'url' => $file_url,
		);

		$attachments = justwpforms_get_meta( $response_id, 'deleted_attachments', true );
		$attachments = $attachments ? $attachments : array();
		$attachments[] = $attachment_data;

		justwpforms_update_meta( $response_id, 'deleted_attachments', $attachments );

	}

	public function before_delete_response( $response_id ) {
		$attachments = $this->get( array(
			'response_id' => $response_id,
		) );

		foreach ( $attachments as $attachment ) {
			wp_delete_attachment( $attachment['ID'], true );
		}
	}

	public function handle_attachments_media_overlay( $args ) {
		if ( ! is_admin() ) {
			return;
		}

		$args['meta_query']['form_clause'] = array (
			'key' => '_justwpforms_upload_draft',
			'compare' => 'NOT EXISTS'
		);

		$hide_attachments = apply_filters( 'justwpforms_hide_attachments', false );

		if ( $hide_attachments ) {
			$args['meta_key'] = '_justwpforms_hash_id';
			$args['meta_compare'] = 'NOT EXISTS';

			return $args;
		}

		$query = isset( $_REQUEST['query'] ) ? $_REQUEST['query'] : array();

		if ( isset( $query['post_mime_type'] ) && 'justwpforms' === $query['post_mime_type'] ) {
			$args['meta_key'] = '_justwpforms_hash_id';
			$args['meta_compare'] = 'EXISTS';
			unset( $args['post_mime_type'] );
		}

		$forms_filter = isset( $query['justwpforms_form_id'] ) ? $query['justwpforms_form_id'] : '';

		if ( '' !== $forms_filter ) {
			$args['meta_query']['form_filter_clause'] = array (
				'key' => '_justwpforms_form_id',
				'value' => $forms_filter,
			);
			unset( $args['justwpforms_form_id'] );
		}

		return $args;
	}

	public function handle_attachments_media_list( $query ) {
		if ( ! is_admin() ) {
			return;
		}

		if ( ! $query->is_main_query() ) {
			return;
		}

		$screen = get_current_screen();

		if ( ! $screen || 'upload' !== $screen->id || 'attachment' !== $screen->post_type ) {
			return;
		}

		$query->set( 'meta_query', array(
			'form_clause' => array(
				'key' => '_justwpforms_upload_draft',
				'compare' => 'NOT EXISTS',
			),
		) );

		$hide_attachments = apply_filters( 'justwpforms_hide_attachments', false );

		if ( $hide_attachments ) {
			$query->set( 'meta_key', '_justwpforms_hash_id' );
			$query->set( 'meta_compare', 'NOT EXISTS' );

			return;
		}

		$filter = isset( $_REQUEST['attachment-filter'] ) ? $_REQUEST['attachment-filter'] : '';

		if ( 'justwpforms' === $filter ) {
			$query->set( 'meta_key', '_justwpforms_hash_id' );
			$query->set( 'meta_compare', 'EXISTS' );
		}

		$forms_filter = isset( $_REQUEST['forms-filter'] ) ? $_REQUEST['forms-filter'] : '';

		if ( '' !== $forms_filter ) {
			$query->set( 'meta_query', array(
				'form_filter_clause' => array(
					'key' => '_justwpforms_form_id',
					'value' => $forms_filter,
				),
			) );
		}
	}

	private function get_script_settings() {
		$selected = (
			isset( $_GET['attachment-filter'] )
			&& 'justwpforms' === $_GET['attachment-filter']
		);

		$settings = array(
			'label' => __( 'Submissions', 'justwpforms' ),
			'selected' => $selected,
		);

		$forms = justwpforms_get_form_controller()->get();

		$form_list = array_map( function( $form ) {
			return [
				'id' => $form['ID'],
				'name' => justwpforms_get_form_title( $form )
			];
		}, $forms );

		$settings['forms'] = $form_list;
		return $settings;
	}

	public function enqueue_media() {
		wp_register_script(
			'justwpforms-admin-media-gallery-grid',
			justwpforms_get_plugin_url() . 'inc/assets/js/admin/media-gallery-grid.js',
			array( 'media-editor', 'media-views' ), justwpforms_get_version(), true
		);

		wp_localize_script(
			'justwpforms-admin-media-gallery-grid',
			'_justwpformsAdminMediaSettings', $this->get_script_settings()
		);

		wp_enqueue_script( 'justwpforms-admin-media-gallery-grid' );
	}

	public function admin_enqueue_scripts( $hook ) {
		if ( 'upload.php' !== $hook ) {
			return;
		}

		$mode  = get_user_option( 'media_library_mode', get_current_user_id() );

		if ( 'list' !== $mode ) {
			return;
		}

		$count = count( $this->get() );

		if ( $count < 1 ) {
			return;
		}

		wp_register_script(
			'justwpforms-admin-media-gallery-list',
			justwpforms_get_plugin_url() . 'inc/assets/js/admin/media-gallery-list.js',
			array( 'jquery' ), justwpforms_get_version(), true
		);

		wp_localize_script(
			'justwpforms-admin-media-gallery-list',
			'_justwpformsAdminMediaSettings', $this->get_script_settings()
		);

		wp_enqueue_script( 'justwpforms-admin-media-gallery-list' );
	}

	public function additional_media_filters( $post_type ) {
		if ( ! is_admin() ) {
			return;
		}

		$screen = get_current_screen();

		if ( ! $screen || 'upload' !== $screen->id ) {
			return;
		}

		$forms_filter = isset( $_REQUEST['forms-filter'] ) ? $_REQUEST['forms-filter'] : '';
		$forms = justwpforms_get_form_controller()->get();
		?>
		<select name='forms-filter' id='forms-filter' class=''>
			<option value=''><?php _e( 'All forms', 'justwpforms' ); ?></option>
		<?php foreach ( $forms as $form ) : ?>
			<option value='<?php echo esc_attr( $form['ID'] ); ?>' <?php selected( $forms_filter, $form['ID'] ); ?>><?php echo justwpforms_get_form_title( $form ); ?></option>
		<?php endforeach; ?>
		</select>
		<?php
	}

}

if ( ! function_exists( 'justwpforms_get_attachment_controller' ) ):
/**
 * Get the justwpforms_Attachment_Controller class instance.
 *
 * @since 1.0
 *
 * @return justwpforms_Attachment_Controller
 */
function justwpforms_get_attachment_controller() {
	return justwpforms_Attachment_Controller::instance();
}

endif;

/**
 * Initialize the justwpforms_Attachment_Controller class immediately.
 */
justwpforms_get_attachment_controller();
