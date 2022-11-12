<?php

if ( ! class_exists( 'TTF_Rest_Product' ) ) :

class TTF_Rest_Product {

	public $id = '';
	public $name = '';
	public $slug = '';
	public $version = '';
	public $plan = '';
	public $type = '';
	public $option = '_ttf_product_updater_key_%s';
	public $event_check = '_ttf_product_updater_license_check_%s';

	public $url_oauth = '%s/oauth';
	public $url_request_key = '%s/wp-json/updates/request';
	public $url_register = '%s/wp-json/updates/register';
	public $url_feed = '%s/wp-json/updates/feed';
	public $url_package = '%s/wp-json/updates/package/%s';
	public $url_download = '%s/wp-json/updates/download/%s';
	public $url_license_check = '%s/wp-json/updates/check';

	public function __construct( $args = array() ) {
		$args = wp_parse_args( $args, $this->get_defaults() );

		extract( $args );

		$this->id = $id;
		$this->name = $name;
		$this->slug = $slug;
		$this->version = $version;
		$this->plan = $plan;
		$this->type = $type;
		$this->option = sprintf( $this->option, $this->id );
		$this->event_check = sprintf( $this->event_check, $this->id );

		$this->url_oauth = sprintf( $this->url_oauth, $url );
		$this->url_request_key = sprintf( $this->url_request_key, $url );
		$this->url_register = sprintf( $this->url_register, $url );
		$this->url_feed = sprintf( $this->url_feed, $url );
		$this->url_package = sprintf( $this->url_package, $url, $this->plan );
		$this->url_download = sprintf( $this->url_download, $url, $this->id );
		$this->url_license_check = sprintf( $this->url_license_check, $url );
	}

	public function get_defaults() {
		$defaults = array(
			'id' => '',
			'name' => '',
			'slug' => '',
			'version' => '',
			'plan' => '',
			'type' => '',
			'url' => '',
		);

		return $defaults;
	}

}

endif;

if ( ! class_exists( 'TTF_Product_Rest_Updater' ) ) :

class TTF_Product_Rest_Updater {

	/**
	 *
	 * Ajax actions for registering/deregistering
	 * a license key from the dashboard.
	 *
	 */
	public $action_request_key = 'ttf-product-updater-request-key';
	public $action_authorize = 'ttf-product-updater-authorize';
	public $action_deauthorize = 'ttf-product-updater-deauthorize';

	/**
	 *
	 * Holds product metadata.
	 *
	 */
	public $product;

	public function __construct( $args = array() ) {
		$this->product = new TTF_Rest_Product( $args );

		// Rest actions
		add_action( 'wp_ajax_' . $this->action_request_key, array( $this, 'request_key' ) );
		add_action( 'wp_ajax_' . $this->action_authorize, array( $this, 'authorize' ) );

		// Remote updates handling
		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'pre_set_site_transient_update_plugins' ) );
		add_filter( 'plugins_api', array( $this, 'plugins_api' ), 10, 3 );
		add_filter( 'upgrader_package_options', [ $this, 'upgrader_package_options' ] );
		add_filter( 'upgrader_pre_download', array( $this, 'upgrader_pre_download' ), 10, 3 );
		add_filter( 'http_request_args', array( $this, 'http_request_args' ), 20, 2 );

		// License checks
		if ( ! wp_next_scheduled ( $this->product->event_check ) ) {
			wp_schedule_event( time(), 'daily', $this->product->event_check );
		}

		add_action( $this->product->event_check, array( $this, 'license_check' ) );

		// WordPress 5.8.2 certificate issues patch
		add_filter( 'ttf_product_updater_verify_ssl', '__return_false' );
	}

	public function get_license_key() {
		return get_option( $this->product->option, false );
	}

	public function is_justwpforms_product() {
		$is_justwpforms_product = strpos( $this->product->id, 'justwpforms' ) !== false;

		return $is_justwpforms_product;
	}

	public function is_themefoundry_product() {
		$is_themefoundry_product = ! $this->is_justwpforms_product();

		return $is_themefoundry_product;
	}

	public function has_old_justwpforms_key() {
		$justwpforms_key = get_option( 'ttf_updates_key_justwpforms', true );
		$has_old_justwpforms_key = $justwpforms_key !== true;

		return $has_old_justwpforms_key;
	}

	public function has_old_themefoundry_key() {
		$themefoundry_key = get_option( 'ttf-api-key', true );
		$has_old_themefoundry_key = $themefoundry_key !== true;

		return $has_old_themefoundry_key;
	}

	/**
	 *
	 * Rest methods
	 *
	 */
	private function verifies_ssl() {
		return apply_filters( 'ttf_product_updater_verify_ssl', true, $this->product );
	}

	public function request_key() {
		return;
		if ( ! check_admin_referer( $this->action_request_key ) ) {
			return;
		}

		if ( ! isset( $_POST['product_plan'] ) || empty( $_POST['product_plan'] ) ) {
			return;
		}

		$product_plan = $_POST['product_plan'];

		if ( $this->product->plan !== $product_plan ) {
			return;
		}

		$email = isset( $_POST['email'] ) ? trim( $_POST['email'] ) : '';

		// An email address must be supplied.
		if ( empty( $email ) ) {
			wp_send_json_error( __( 'Please enter a valid email address.', 'ttf-product-updater' ) );
		}

		$response = wp_remote_post( $this->product->url_request_key, array(
			'headers' => array(
				'X-TTF-PRODUCT-PLAN' => $product_plan,
				'X-TTF-PRODUCT-VERSION' => $this->product->version,
				'X-TTF-SITE-URL' => get_site_url(),
			),
			'body' => array(
				'email' => $email,
			),
			'timeout' => 5,
			'sslverify' => $this->verifies_ssl(),
		) );

		$body = wp_remote_retrieve_body( $response );

		// Catch-all, generic error handler.
		if ( ! $body ) {
			wp_send_json_error( __( 'An unexpected error occurred.', 'ttf-product-updater' ) );
		}

		$body = json_decode( $body );

		// Catch-all, generic error handler.
		if ( 'success' !== $body->code ) {
			wp_send_json_error( __( 'An unexpected error occurred.', 'ttf-product-updater' ) );
		}

		// Render the widget with a success notice.
		wp_send_json( array(
			'success' => true,
			'data' => __( 'Registration key sent. Please check your inbox.', 'ttf-product-updater' )
		) );
	}

	public function authorize() {
		if ( ! check_admin_referer( $this->action_authorize ) ) {
			return;
		}

		if ( ! isset( $_POST['product_plan'] ) || empty( $_POST['product_plan'] ) ) {
			return;
		}

		$product_plan = $_POST['product_plan'];

		if ( $this->product->plan !== $product_plan ) {
			return;
		}

		$email = isset( $_POST['email'] ) ? trim( $_POST['email'] ) : '';

		// A license key must be supplied.
		if ( ! isset( $_POST['license_key'] ) || empty( $_POST['license_key'] ) ) {
			wp_send_json_error( __( 'Please enter a registration key.', 'ttf-product-updater' ) );
		}

		$license_key = $_POST['license_key'];

		$response = wp_remote_post( $this->product->url_register, array(
			'headers' => array(
				'X-TTF-LICENSE-KEY' => $license_key,
				'X-TTF-PRODUCT-PLAN' => $product_plan,
				'X-TTF-PRODUCT-VERSION' => $this->product->version,
				'X-TTF-SITE-URL' => get_site_url(),
			),
			'body' => array(
				'email' => $email,
			),
			'timeout' => 5,
			'sslverify' => $this->verifies_ssl(),
		) );

		$body = wp_remote_retrieve_body( $response );

		// Catch-all, generic error handler.
		if ( ! $body ) {
			wp_send_json_error( __( 'An unexpected error occurred.', 'ttf-product-updater' ) );
		}

		$body = json_decode( $body );

		// License key must be valid.
		if ( 'success' !== $body->code ) {
			wp_send_json_error( __( 'The registration key you entered did not appear to be valid.', 'ttf-product-updater' ) );
		}

		// Remove deprecated license keys
		if ( $this->is_justwpforms_product() && $this->has_old_justwpforms_key() ) {
			delete_option( 'ttf_updates_key_justwpforms' );
		}

		// Store the license key.
		update_option( $this->product->option, $license_key );

		// Render the widget with a success notice.
		wp_send_json( array(
			'success' => true,
			'data' => __( 'Successfully registered.', 'ttf-product-updater' )
		) );
	}

	/**
	 *
	 * Fetch a product feed.
	 *
	 */
	public function get_feed() {
		$response = wp_remote_get( $this->product->url_feed, array(
			'headers' => array(
				'X-TTF-PRODUCT-PLAN' => $this->product->plan,
			),
			'timeout' => 5,
			'sslverify' => $this->verifies_ssl(),
		) );

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );

		if ( 200 !== $code ) {
			return false;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ) );

		if ( 'success' !== $body->code ) {
			return false;
		}

		// Prepare feed data for consumption by core.
		$info = $body->data;
		$info->download_link = $info->package;
		$info->sections = (array) $info->sections;

		// Allow injecting icons from the plugin itself.
		$icons = isset( $info->icons ) ? (array) $info->icons : false;
		$icons = apply_filters( 'ttf_product_updater_icons', $icons, $this->product->id );

		if ( $icons ) {
			$info->icons = $icons;
		}

		// Allow injecting banners from the plugin itself.
		$banners = isset( $info->banners ) ? (array) $info->banners : false;
		$banners = apply_filters( 'ttf_product_updater_banners', $banners, $this->product->id );

		if ( $banners ) {
			$info->banners = $banners;
		}

		// Stub translation support.
		if ( isset( $info->translations ) ) {
			$info->translations = (array) $info->translations;
		}

		return $info;
	}

	/**
	 *
	 * Called during core update checks.
	 *
	 * Adds the current plugin to the updates transient,
	 * if a new version is available.
	 *
	 */
	public function pre_set_site_transient_update_plugins( $transient ) {
		if ( empty( $transient ) ) {
			return $transient;
		}

		if ( empty( $transient->checked ) ) {
			$transient->checked = array();
		}

		if ( 'plugin' !== $this->product->type ) {
			return $transient;
		}

		$feed = $this->get_feed();

		// Bail if, for some reason, a feed is unavailable.
		if ( ! $feed ) {
			return $transient;
		}

		// Add the plugin to the updates transient, if a new version is available.
		if ( version_compare( $this->product->version, $feed->version, '<' ) ) {
			$transient->response[$this->product->slug] = $this->get_update( $feed );
		}

		// Add the plugin to the list of plugins checked for updates.
		$transient->checked[$this->product->slug] = $this->product->version;

		return $transient;
	}

	/**
	 *
	 * Called from core's "View version details" links.
	 *
	 * Populates the update's details overlay.
	 *
	 */
	public function plugins_api( $false, $action, $args ) {
		if ( 'plugin_information' !== $action ) {
			return $false;
		}

		if ( $args->slug !== $this->product->id ) {
			return $false;
		}

		$info = $this->get_feed();

		if ( ! $info ) {
			return $false;
		}

		return $info;
	}

	/**
	 *
	 * Called by core's while it gathers update details
	 * before actually performing an update.
	 *
	 * Turns feed details into update details for core's upgrader.
	 *
	 */
	public function get_update( $update_info ) {
		$update = new stdClass();
		$update->slug = $this->product->id;
		$update->plugin = $this->product->slug;
		$update->new_version = $update_info->version;
		$update->url = $update_info->homepage;
		$update->package = $update_info->package;

		if ( isset( $update_info->tested ) ) {
			$update->tested = $update_info->tested;
		}

		if ( isset( $update_info->banners ) ) {
			$update->banners = $update_info->banners;
		}

		if ( isset( $update_info->icons ) ) {
			$update->icons = $update_info->icons;
		}

		if ( isset( $update_info->upgrade_notice ) ) {
			$update->upgrade_notice = $update_info->upgrade_notice;
		}

		return $update;
	}

	/**
	 *
	 * Called by core's upgrader when preparing a package for download.
	 *
	 * If the user has access to a download, this update package is turned
	 * into a download url, otherwise it becomes a placeholder used to display
	 * an expired license message later.
	 *
	 */
	public function upgrader_package_options( $options ) {
		if ( $this->product->url_package === $options['package'] ) {
			$license_key = $this->get_license_key();

			// The license key is missing.
			if ( empty( $license_key ) ) {
				$options['package'] = 'ttf-product-updater-unregistered-' . $this->product->id;

				return $options;
			}

			$response = wp_remote_get( $options['package'], array(
				'headers' => array(
					'X-TTF-LICENSE-KEY' => $this->get_license_key(),
					'X-TTF-PRODUCT-PLAN' => $this->product->plan,
					'X-TTF-PRODUCT-VERSION' => $this->product->version,
					'X-TTF-SITE-URL' => get_site_url(),
				),
				'timeout' => 5,
				'sslverify' => $this->verifies_ssl(),
			) );

			// Default return package, if license checks don't pass.
			$options['package'] = 'ttf-product-updater-expired-' . $this->product->id;

			$code = (int) wp_remote_retrieve_response_code( $response );

			if ( 200 !== $code ) {
				return $options;
			}

			$body = json_decode( wp_remote_retrieve_body( $response ) );

			if ( 'success' !== $body->code ) {
				return $options;
			}

			// License checks passed, return the package download.
			$options['package'] = $this->product->url_download;
		}

		return $options;
	}

	/**
	 *
	 * Called by core's upgrader when downloading a package.
	 *
	 * If the user has access to a download, this lets the request
	 * pass through, otherwise it returns an expired license error.
	 *
	 */
	public function upgrader_pre_download( $reply, $package, $upgrader ) {
		if ( false !== $reply ) {
			return $reply;
		}

		if ( 0 === strpos( $package, 'ttf-product-updater-unregistered-' ) ) {
			return new WP_Error(
				'ttf_updater_license_missing',
				sprintf(
					__( 'Oops! Either your key is incorrect or your membership is deactivated.', 'ttf-product-updater' ),
					$this->product->name
				)
			);
		}

		if ( 0 === strpos( $package, 'ttf-product-updater-expired-' ) ) {
			return new WP_Error(
				'ttf_updater_license_expired',
				sprintf(
					__( 'Oops! Either your key is incorrect or your membership is deactivated.', 'ttf-product-updater' ),
					$this->product->name
				)
			);
		}

		return $reply;
	}

	/**
	 *
	 * Force requests to download URLs to include authentication headers.
	 *
	 */
	public function http_request_args( $args, $url ) {
		if ( $this->product->url_download === $url ) {
			$args['headers'] = wp_parse_args( $args['headers'], array(
				'X-TTF-LICENSE-KEY' => $this->get_license_key(),
				'X-TTF-PRODUCT-PLAN' => $this->product->plan,
				'X-TTF-PRODUCT-VERSION' => $this->product->version,
				'X-TTF-SITE-URL' => get_site_url(),
			) );
		}

		return $args;
	}

	/**
	 *
	 * Poll update server periodically and validate the license.
	 *
	 */
	public function license_check() {
		return;
		$response = wp_remote_get( $this->product->url_license_check, array(
			'headers' => array(
				'X-TTF-LICENSE-KEY' => $this->get_license_key(),
				'X-TTF-PRODUCT-PLAN' => $this->product->plan,
				'X-TTF-PRODUCT-VERSION' => $this->product->version,
				'X-TTF-SITE-URL' => get_site_url(),
			),
			'timeout' => 5,
			'sslverify' => $this->verifies_ssl(),
		) );

		do_action( 'ttf_product_updater_license_check', $this->product->id, $response );
	}

}

endif;
