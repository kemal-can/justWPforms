<?php

class justwpforms_Privacy_Settings {

	private static $instance;
	private static $hooked = false;

	public $save_action = 'justwpforms_save_privacy_settings';
	public $save_nonce = 'justwpforms-privacy-settings';
	public $option = 'justwpforms_privacy_settings';
	public $schedule_cleanup = 'justwpforms_schedule_privacy_cleanup';

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

		add_action( $this->schedule_cleanup, array( $this, 'cleanup' ) );
		add_filter( 'justwpforms_save_entries', array( $this, 'save_entries' ), 10, 2 );
		add_filter( 'justwpforms_save_user_data', array( $this, 'do_deprecated_save_user_data' ), 10, 2 );

		add_filter( 'justwpforms_meta_fields', array( $this, 'meta_fields' ) );
		add_filter( 'justwpforms_setup_controls', array( $this, 'setup_controls' ) );
		add_action( 'justwpforms_do_setup_control', array( $this, 'do_deprecated_control' ), 10, 4 );
		add_filter( 'justwpforms_get_form_data', array( $this, 'transition_form_privacy_settings'), 99 );
		add_action( 'justwpforms_form_updated', array( $this, 'cleanup_deprecated_meta_fields' ) );

		$this->schedule_cleanup();
	}

	//TODO remove once transition_form_privacy_settings has been removed.
	public function get_defaults() {
		$defaults = array(
			'save_submissions' => 1,
			'delete_submission_after' => 0,
			'delete_submission_days' => 60,
			'preserve_database_data' => 1,
			'save_user_data' => 1,
		);

		return $defaults;
	}

	//TODO remove once transition_form_privacy_settings has been removed.
	public function read( $as_array = false ) {
		$settings = get_option( $this->option, '' );
		$settings = wp_parse_args( $settings, $this->get_defaults() );

		return $settings;
	}

	public function save_entries( $save, $form ) {
		if ( justwpforms_is_falsy( $form['save_submissions'] ) ) {
			$save = false;
		}

		return $save;
	}

	public function do_deprecated_save_user_data( $save, $form ) {
		if ( ! $save ) {
			return $save;
		}

		return true;
	}

	public function schedule_cleanup() {
		if ( ! wp_next_scheduled( $this->schedule_cleanup ) ) {
			wp_schedule_event( time(), 'twicedaily', $this->schedule_cleanup );
		}
	}

	public function cleanup() {
		$form_controller = justwpforms_get_form_controller();
		$forms = $form_controller->get();

		foreach( $forms as $form ) {
			if ( '' === $form['delete_submission_days'] ) {
				continue;
			}

			$this->do_cleanup( $form );
		}
	}

	public function do_cleanup( $form ) {
		$date = '-' . $form['delete_submission_days'] . ' day';

		$args = array(
			'post_type' => justwpforms_get_message_controller()->post_type,
			'post_status' => 'publish',
			'date_query' => array(
				'before' => $date,
			),
			'meta_key' => '_justwpforms_form_id',
			'meta_value' => $form['ID'],
			'posts_per_page' => -1,
			'fields' => 'ids',
		);

		$args = apply_filters( 'justwpforms_cleanup_activity_args', $args, $form );
		$post_ids = get_posts( $args );

		foreach ( $post_ids as $id ) {
			wp_delete_post( $id, true );
		}
	}

	public function get_fields() {
		$fields = array(
			'save_submissions' => array(
				'default' => 1,
				'sanitize' => 'justwpforms_sanitize_checkbox',
			),
			'delete_submission_days' => array(
				'default' => '',
				'sanitize' => 'justwpforms_sanitize_intval_empty',
			),
			'save_user_data' => array(
				'default' => 1,
				'sanitize' => 'justwpforms_sanitize_checkbox',
			),
			// TODO delete once handler for global privacy settings has been complete removed.
			'per_form_privacy_settings' => array(
				'default' => 0,
				'sanitize' => 'justwpforms_sanitize_checkbox',
			),
		);

		return $fields;
	}

	public function meta_fields( $fields ) {
		$fields = array_merge( $fields, $this->get_fields() );

		return $fields;
	}

	public function setup_controls( $controls ) {
		$setup_controls = array(
			5950 => array(
				'type' => 'save_submissions-checkbox',
				'label' => __( 'Store replies and attachments in database', 'justwpforms' ),
				'field' => 'save_submissions',
			),
			5953 => array(
				'type' => 'number',
				'label' => __( "Erase submitter's personal data after set number of days", 'justwpforms' ),
				'field' => 'delete_submission_days',
			),
		);

		$controls = justwpforms_safe_array_merge( $controls, $setup_controls );

		return $controls;
	}

	public function transition_form_privacy_settings( $form ) {
		if ( 0 == intval( $form['ID'] ) ) {
			$form['per_form_privacy_settings'] = 1;
			return $form;
		}

		if ( justwpforms_is_falsy( $form['per_form_privacy_settings'] ) ) {
			$form['per_form_privacy_settings'] = 1;
			$settings = $this->read();

			if( 0 == $settings['save_submissions'] ) {
				$form['save_submissions'] = 0;
			}

			if ( 1 == $settings['delete_submission_after'] ) {
				$form['delete_submission_after'] = 1;
				$form['delete_submission_days'] = $settings['delete_submission_days'];
			}
		}

		if ( isset( $form['delete_submission_after'] ) && justwpforms_is_falsy( $form['delete_submission_after'] ) ) {
			$form['delete_submission_days'] = '';
		}

		return $form;
	}

	public function cleanup_deprecated_meta_fields( $form ) {
		if ( isset( $form['delete_submission_after'] ) ) {
			delete_post_meta( $form['ID'], '_justwpforms_delete_submission_after' );
		}
	}

	public function do_deprecated_control( $control, $field, $index ) {
		$type = $control['type'];
		$path = justwpforms_get_core_folder() . '/templates/customize-controls/setup';

		switch( $type ) {
			case 'save_submissions-checkbox':
				$form = justwpforms_customize_get_current_form();

				if ( justwpforms_is_truthy( $form['save_submissions'] ) ) {
					break;
				}

				$true_type = str_replace( 'save_submissions-', '', $type );

				require( "{$path}/{$true_type}.php" );
				break;
			default:
				break;
		}
	}

}

if ( ! function_exists( 'justwpforms_get_privacy_settings' ) ):

function justwpforms_get_privacy_settings() {
	return justwpforms_Privacy_Settings::instance();
}

endif;

justwpforms_get_privacy_settings();
