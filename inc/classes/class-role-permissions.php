<?php

class justwpforms_Role_Permissions {

	private static $instance;
	private static $hooked = false;

	public $main_capability = 'justwpforms_manage';
	public $form_capability = 'justwpforms_manage_forms';
	public $activity_capability = 'justwpforms_manage_activity';
	public $settings_capability = 'justwpforms_manage_settings';

	public $save_action = 'justwpforms_save_role_permissions';
	public $save_nonce = 'justwpforms-role-permissions';
	public $option = 'justwpforms_role_permissions';

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		self::$instance->hook();

		return self::$instance;
	}

	public function hook() {
		if ( self::$hooked ) {
			return;
		}

		self::$hooked = true;

		add_action( 'wp_ajax_' . $this->save_action, array( $this, 'save_settings' ) );
		add_action( 'init', array( $this, 'set_admin_role_capabilities' ) );
		add_filter( 'justwpforms_main_page_capabilities', array( $this, 'get_main_page_capabilities' ) );
		add_filter( 'justwpforms_forms_page_capabilities', array( $this, 'get_forms_page_capabilities' ) );
		add_filter( 'justwpforms_responses_page_capabilities', array( $this, 'get_responses_page_capabilities' ) );
		add_filter( 'justwpforms_settings_page_capabilities', array( $this, 'get_settings_page_capabilities' ) );
		add_filter( 'map_meta_cap', array( $this, 'map_meta_cap' ), 10, 4 );
		add_action( 'current_screen', array( $this, 'current_screen' ) );
	}

	public function get_defaults() {
		$roles = $this->get_roles();
		$defaults = array();

		foreach( $roles as $role_id => $role ) {
			$defaults[$role_id] = array(
				'allow' => 0,
				'allow_forms' => 0,
				'allow_activity' => 0,
				'allow_settings' => 0,
			);
		}

		return $defaults;
	}

	public function read() {
		$permissions = get_option( $this->option, '' );
		$permissions = wp_parse_args( $permissions, $this->get_defaults() );

		return $permissions;
	}

	public function write( $roles ) {
		$permissions = $this->get_defaults();

		foreach( $roles as $role_id => $role_permissions ) {
			foreach( $role_permissions as $permission => $allowed ) {
				$permissions[$role_id][$permission] = $allowed;
			}
		}

		update_option( $this->option, $permissions );

		foreach( $permissions as $role_id => $role_permissions ) {
			$role = get_role( $role_id );

			if ( ! $role ) {
				continue;
			}

			$allowed = $permissions[$role_id]['allow'];
			$allow_forms = $allowed && $permissions[$role_id]['allow_forms'];
			$allow_activity = $allowed && $permissions[$role_id]['allow_activity'];
			$allow_settings = $allowed && $permissions[$role_id]['allow_settings'];

			if ( $allow_forms ) {
				$role->add_cap( $this->form_capability );
			} else {
				$role->remove_cap( $this->form_capability );
			}

			if ( $allow_activity ) {
				$role->add_cap( $this->activity_capability );
			} else {
				$role->remove_cap( $this->activity_capability );
			}

			if ( $allow_settings ) {
				$role->add_cap( $this->settings_capability );
			} else {
				$role->remove_cap( $this->settings_capability );
			}
		}
	}

	public function get_core_roles() {
		$roles = array( 'editor', 'author', 'contributor', 'subscriber' );

		return $roles;
	}

	public function get_roles() {
		$roles = get_editable_roles();
		unset( $roles['administrator'] );

		$extended_roles = apply_filters( 'justwpforms_extended_privacy_roles', false );

		if ( ! $extended_roles ) {
			$roles = array_intersect_key( $roles, array_flip( $this->get_core_roles() ) );
		}

		return $roles;
	}

	public function save_settings() {
		if ( ! check_ajax_referer( $this->save_action, $this->save_nonce ) ) {
			return;
		}

		$permissions = isset( $_REQUEST['justwpforms_role_permissions'] ) ? $_REQUEST['justwpforms_role_permissions'] : '';
		$this->write( $permissions );

		ob_start();
		require_once( justwpforms_get_include_folder() . '/templates/admin-settings-role-permissions.php' );
		$response = ob_get_clean();

		wp_send_json_success( array(
			'html' => $response,
			'message' => __( 'Changes saved.', 'justwpforms' ),
		) );
	}

	public function set_admin_role_capabilities() {
		$role = get_role( 'administrator' );

		$role->add_cap( $this->form_capability );
		$role->add_cap( $this->activity_capability );
		$role->add_cap( $this->settings_capability );
	}

	public function get_main_page_capabilities() {
		return $this->main_capability;
	}

	public function get_forms_page_capabilities() {
		return $this->form_capability;
	}

	public function get_responses_page_capabilities() {
		return $this->activity_capability;
	}

	public function get_settings_page_capabilities() {
		return $this->settings_capability;
	}

	public function map_meta_cap( $caps, $cap, $user_id, $args ) {
		if ( 'edit_pages' === $cap && $this->is_forms_screen() ) {
			$caps = array( $this->form_capability );
		} else if ( 'edit_pages' === $cap && $this->is_activity_screen() ) {
			$caps = array( $this->activity_capability );
		} else if ( 'edit_post' === $cap && $this->is_forms_screen() ) {
			$caps = array( $this->form_capability );
		} else if ( 'edit_post' === $cap && $this->is_activity_screen() ) {
			$caps = array( $this->activity_capability );
		} else if ( 'edit_others_pages' === $cap && $this->is_activity_edit_screen() ) {
			$caps = array( $this->activity_capability );
		} else if ( 'edit_post' === $cap && $this->is_activity_edit_screen() ) {
			$caps = array( $this->activity_capability );
		} else if ( 'delete_post' === $cap && $this->is_form( $args[0] ) ) {
			$caps = array( $this->form_capability );
		} else if ( 'delete_post' === $cap && $this->is_activity( $args[0] ) ) {
			$caps = array( $this->activity_capability );
		} else if ( 'customize' === $cap && $this->is_form_edit_screen() ) {
			$caps = array( $this->form_capability );
		} else if ( 'customize' === $cap && $this->is_form_preview_frame() ) {
			$caps = array( $this->form_capability );
		} else if ( $this->main_capability === $cap ) {
			if ( current_user_can( $this->form_capability ) ) {
				$caps = array( $this->form_capability );
			} else if ( current_user_can( $this->activity_capability ) ) {
				$caps = array( $this->activity_capability );
			} else if ( current_user_can( $this->settings_capability ) ) {
				$caps = array( $this->settings_capability );
			} else {
				$caps = array();
			}
		}

		return $caps;
	}

	public function current_screen( $screen ) {
		$has_access = true;

		if ( $this->is_forms_screen() && ! current_user_can( $this->form_capability ) ) {
			$has_access = false;
		} else if ( $this->is_form_edit_screen() && ! current_user_can( $this->form_capability ) ) {
			$has_access = false;
		} else if ( $this->is_activity_screen() && ! current_user_can( $this->activity_capability ) ) {
			$has_access = false;
		} else if ( $this->is_activity_edit_screen() && ! current_user_can( $this->activity_capability ) ) {
			$has_access = false;
		} else if ( $this->is_settings_screen() && ! current_user_can( $this->settings_capability ) ) {
			$has_access = false;
		}

		if ( ! $has_access ) {
			wp_die( 'Sorry, you are not allowed to view this item.' );
		}
	}

	public function is_forms_screen() {
		global $pagenow, $typenow;

		$form_post_type = justwpforms_get_form_controller()->post_type;

		if ( 'edit.php' === $pagenow && $form_post_type === $typenow ) {
			return true;
		}

		return false;
	}

	public function is_form( $post_id ) {
		$form_post_type = justwpforms_get_form_controller()->post_type;
		$is_form = $form_post_type === get_post_type( $post_id );

		return $is_form;
	}

	public function is_activity( $post_id ) {
		$message_post_type = justwpforms_get_message_controller()->post_type;
		$is_activity = $message_post_type === get_post_type( $post_id );

		return $is_activity;
	}

	public function is_form_edit_screen() {
		global $pagenow;

		if ( 'customize.php' === $pagenow && justwpforms()->is_customize_mode() ) {
			return true;
		}

		return false;
	}

	public function is_form_preview_frame() {
		if ( isset( $_REQUEST['post_type'] ) 
			&& 'justwpform' === $_REQUEST['post_type'] ) {

			return true;
		}

		if ( isset( $_REQUEST['justwpform'] )
			&& isset( $_REQUEST['customize_messenger_channel'] ) ) {

			return true;
		}

		if ( isset( $_REQUEST['justwpforms'] )
			&& isset( $_REQUEST['wp_customize'] ) ) {

			return true;
		}

		return false;
	}

	public function is_activity_screen() {
		global $pagenow, $typenow;

		$message_post_type = justwpforms_get_message_controller()->post_type;

		if ( 'edit.php' === $pagenow && $message_post_type === $typenow ) {
			return true;
		}

		return false;
	}

	public function is_activity_edit_screen() {
		global $pagenow, $typenow;

		$message_post_type = justwpforms_get_message_controller()->post_type;
		
		if ( isset( $_POST['post_type'] ) && $message_post_type === $_POST['post_type'] ) {
			return true;
		}

		if ( 'post.php' === $pagenow && $message_post_type === $typenow ) {
			return true;
		}

		return false;
	}

	public function is_settings_screen() {
		$screen = get_current_screen();

		if ( ! $screen ) {
			return false;
		}

		if ( justwpforms_is_admin_screen( 'justwpforms-settings' ) ) {
			return true;
		}

		return false;
	}
	
}

if ( ! function_exists( 'justwpforms_get_role_permissions' ) ):

function justwpforms_get_role_permissions() {
	return justwpforms_Role_Permissions::instance();
}

endif;

justwpforms_get_role_permissions();
