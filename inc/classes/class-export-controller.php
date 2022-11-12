<?php
class justwpforms_Export_Controller {

	private static $instance;

	private $export_import_action = 'justwpforms_export_import';

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		self::$instance->hook();

		return self::$instance;
	}

	public function hook() {
		add_filter( 'wp_check_filetype_and_ext', array( $this, 'add_xml_mime_type' ), 10, 3 );
		add_action( 'admin_action_justwpforms_export_import', array( $this, 'handle_request' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		add_filter( 'justwpforms_export_page_method', array( $this, 'set_admin_page_method' ) );
		add_filter( 'justwpforms_export_page_url', array( $this, 'set_admin_page_url' ) );
		add_action( 'wp_ajax_justwpforms_import_form', array( $this, 'handle_import_request' ) );
		add_action( 'justwpforms_csv_export_before', array( $this, 'justwpforms_csv_export_before' ) );
	}

	public function add_xml_mime_type( $info, $file, $filename ) {
		if ( isset( $_REQUEST['is_justwpforms_export'] ) ) {
			$extension = pathinfo( $filename, PATHINFO_EXTENSION );

			if ( 'xml' !== $extension ) {
				return $info;
			}

			if ( extension_loaded( 'fileinfo' ) ) {
				$finfo = finfo_open( FILEINFO_MIME_TYPE );
				$mime = finfo_file( $finfo, $file );
				finfo_close( $finfo );

				$xml_mimes = array(
					'text/xml', 'application/xml'
				);

				if ( ! in_array( $mime, $xml_mimes ) ) {
					return $info;
				}
			
				$info = array(
					'ext' => $extension,
					'type' => $mime,
				);
			} else {
				$info = array(
					'ext' => $extension,
					'type' => 'text/xml',
				);
			}
		}

		return $info;
	}

	public function set_admin_page_method( $method ) {
		$method = array( $this, 'settings_page' );

		return $method;
	}

	public function set_admin_page_url( $url ) {
		$url = 'justwpforms-export';

		return $url;
	}

	public function settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'Sorry, you are not allowed to access this page.', 'justwpforms' ) );
		}

		add_filter( 'admin_footer_text', 'justwpforms_admin_footer' );

		require_once( justwpforms_get_include_folder() . '/templates/admin-export.php' );
	}

	public function admin_enqueue_scripts() {
		if ( justwpforms_is_admin_screen( 'justwpforms-settings' ) ) {
			wp_register_script(
				'justwpforms-export',
				justwpforms_get_plugin_url() . 'inc/assets/js/admin/export.js',
				array( 'jquery', 'backbone', 'plupload-handlers' ), justwpforms_get_version(), true
			);

			wp_enqueue_script( 'justwpforms-export' );
		}
	}

	public function handle_request() {
		if ( ! isset( $_REQUEST['justwpforms_export_nonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( $_REQUEST['justwpforms_export_nonce'], $this->export_import_action ) ) {
			return;
		}

		$action_type = sanitize_text_field( $_REQUEST['action_type'] );
		$form_id = ( isset( $_REQUEST['form_id'] ) ) ? intval( $_REQUEST['form_id'] ) : 0;
		$response = '';

		switch ( $action_type ) {
			case 'export_responses':
				$response = $this->export_responses( $form_id );
			break;

			case 'export_form':
				$response = $this->export_form( $form_id );
			break;

			case 'export_form_responses':
				$response = $this->export_form( $form_id, true );
			break;
		}
	}

	public function get_file_name( $form_id, $extension ) {
		$form_title = get_the_title( $form_id );
		$form_title = sanitize_title( $form_title );
		$form_title = "{$form_title}.{$extension}";

		return $form_title;
	}

	public function export_responses( $form_id ) {
		require_once( justwpforms_get_include_folder() . '/classes/class-exporter-csv.php' );

		$filename = $this->get_file_name( $form_id, 'csv' );
		$exporter = new justwpforms_Exporter_CSV( $form_id, $filename );
		$exporter->export();
	}

	public function export_form( $form_id, $include_responses = false ) {
		require_once( justwpforms_get_include_folder() . '/classes/class-exporter-xml.php' );

		$filename = $this->get_file_name( $form_id, 'xml' );
		$exporter = new justwpforms_Exporter_XML( $form_id, $filename );
		$exporter->export( $include_responses );
	}

	public function handle_import_request() {
		$attachment_id = isset( $_REQUEST['attachment_id'] ) ? intval( $_REQUEST['attachment_id'] ) : 0;

		if ( 0 === $attachment_id ) {
			$message = __( 'Upload failed', 'justwpforms' );
			$messages = $this->format_messages( array( $message ) );

			wp_send_json_error( $messages );
		}

		require_once( justwpforms_get_include_folder() . '/classes/class-importer-xml.php' );

		$path = get_attached_file( $attachment_id );
		$importer = new justwpforms_Importer_XML();
		$result = $importer->import( $path );

		wp_delete_attachment( $attachment_id, true );

		if ( is_wp_error( $result ) ) {
			$messages = $result->get_error_messages();
			$messages = $this->format_messages( $messages );

			wp_send_json_error( $messages );
		} else {
			$messages = $importer->get_success_messages();
			$messages = $this->format_messages( $messages );

			wp_send_json_success( $messages );
		}
	}

	private function format_messages( $messages ) {
		$messages = array_map( function( $message ) {
			return "<p>$message</p>";
		}, $messages );
		$messages = implode( '', $messages );

		return $messages;
	}

	public function justwpforms_csv_export_before( $output ) {
		fprintf( $output, chr(0xEF) . chr(0xBB) . chr(0xBF) );
	}

}

if ( ! function_exists( 'justwpforms_get_export_controller' ) ):

function justwpforms_get_export_controller() {
	return justwpforms_Export_Controller::instance();
}

endif;

justwpforms_get_export_controller();
