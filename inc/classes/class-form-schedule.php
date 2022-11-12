<?php

class justwpforms_Form_Schedule {

	/**
	 * The singleton instance.
	 *
	 * @var justwpforms_Form_Schedule
	 */
	private static $instance;

	private $output_datetime_format = 'Y-m-d H:i:s';

	private $input_datetime_format = 'm/d/Y g:i A';

	private $frontend_styles = false;

	/**
	 * The singleton constructor.
	 *
	 * @return justwpforms_Form_Schedule
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
		add_filter( 'justwpforms_meta_fields', array( $this, 'meta_fields' ) );
		add_filter( 'justwpforms_setup_controls', array( $this, 'setup_controls' ) );
		add_filter( 'justwpforms_messages_controls', array( $this, 'messages_controls' ) );
		add_action( 'justwpforms_do_setup_control', array( $this, 'do_control' ), 10, 3 );
		add_filter( 'justwpforms_get_form_data', array( $this, 'get_form_data' ) );
		add_action( 'justwpforms_customize_enqueue_scripts', array( $this, 'customize_enqueue_scripts' ) );
		add_filter( 'justwpforms_validate_submission', array( $this, 'validate_submission' ), 10, 3 );
		add_filter( 'justwpforms_form_template_path', array( $this, 'form_template_path' ), 10, 3 );
		add_filter( 'justwpforms_messages_fields', array( $this, 'meta_messages_fields' ) );
		add_filter( 'justwpforms_style_dependencies', array( $this, 'style_dependencies' ), 10, 2 );
		add_action( 'justwpforms_do_setup_control', array( $this, 'do_deprecated_control' ), 10, 4 );
		add_action( 'justwpforms_do_messages_control', array( $this, 'do_deprecated_control_messages' ), 10, 3 );

	}

	public function get_fields() {
		$fields = array(
			'schedule_visibility' => array(
				'default' => 0,
				'sanitize' => 'justwpforms_sanitize_checkbox',
			),
			'schedule_from_datetime' => array(
				'default' => '',
				'sanitize' => array( $this, 'sanitize_datetime' ),
			),
			'schedule_to_datetime' => array(
				'default' => '',
				'sanitize' => array( $this, 'sanitize_datetime' ),
			),
		);

		return $fields;
	}
	public function meta_messages_fields( $fields ) {
		$messages_fields = array(
			'scheduled_message' => array(
				'default' => __( "This form isn’t scheduled to show at the moment.",
			'justwpforms' ),
				'sanitize' => 'sanitize_text_field',
			),
		);

		$fields = array_merge( $fields, $messages_fields );

		return $fields;
	}

	public function meta_fields( $fields ) {
		$fields = array_merge( $fields, $this->get_fields() );

		return $fields;
	}

	public function setup_controls( $controls ) {
		$setup_controls = array(
			3000 => array(
				'field' => 'schedule_visibility',
				'label' => __( 'Schedule visibility', 'justwpforms' ),
				'type' => 'schedule_visibility_checkbox'
			),
			3001 => array(
				'type' => 'group_start',
				'trigger' => 'schedule_visibility'
			),
			3002 => array(
				'field' => 'schedule_from_datetime',
				'label' => __( 'Open form', 'justwpforms' ),
				'type' => 'schedule_visibility_datetime'
			),
			3003 => array(
				'field' => 'schedule_to_datetime',
				'label' => __( 'Close form', 'justwpforms' ),
				'type' => 'schedule_visibility_datetime'
			),
			3005 => array(
				'type' => 'group_end'
			)
		);

		$controls = justwpforms_safe_array_merge( $controls, $setup_controls );

		return $controls;
	}

	public function messages_controls( $controls ) {
		$message_controls = array(
			80 => array(
				'type' => 'schedule_visibility_text',
				'label' => __( "Form isn't scheduled to show now", 'justwpforms' ),
				'field' => 'scheduled_message',
			),
		);

		$controls = justwpforms_safe_array_merge( $controls, $message_controls );

		return $controls;
	}

	public function do_control( $control, $field, $index ) {
		$type = $control['type'];

		if ( 'datetime' === $type ) {
			require( justwpforms_get_include_folder() . '/templates/customize-controls/datetime.php' );
		}
	}

	public function get_form_data( $data ) {
		$from_datetime = $data['schedule_from_datetime'];
		$from_datetime = $this->parse_datetime( $from_datetime );
		$data['schedule_from_datetime'] = $from_datetime;
		$to_datetime = $data['schedule_to_datetime'];
		$to_datetime = $this->parse_datetime( $to_datetime );
		$data['schedule_to_datetime'] = $to_datetime;

		return $data;
	}

	public function get_empty_datetime() {
		$datetime = array(
			'date' => '',
			'time' => '',
			'period' => 'AM',
		);

		return $datetime;
	}

	public function parse_datetime( $datetime ) {
		if ( '' === $datetime ) {
			return $this->get_empty_datetime();
		}

		$datetime = DateTime::createFromFormat( $this->output_datetime_format, $datetime );

		if ( ! $datetime ) {
			return $this->get_empty_datetime();
		}

		$date = $datetime->format( 'm/d/Y' );
		$time = $datetime->format( 'h:i' );
		$period = $datetime->format( 'A' );

		$datetime = array(
			'date' => $date,
			'time' => $time,
			'period' => $period,
		);

		return $datetime;
	}

	public function sanitize_datetime( $datetime ) {
		$values = array_values( $datetime );
		$nonempty_values = array_filter( $values );

		if ( count( $values ) !== count( $nonempty_values ) ) {
			return '';
		}

		$datetime = wp_parse_args( $datetime, $this->get_empty_datetime() );
		$date = explode( '/', $datetime['date'] );
		$time = explode( ':', $datetime['time'] );
		$period = $datetime['period'];

		if ( 3 !== count( $date ) || 2 !== count( $time ) ) {
			return '';
		}

		$datetime = array();
		$datetime['month'] = sprintf( '%02d', intval( $date[0] ) );
		$datetime['day'] = sprintf( '%02d', intval( $date[1] ) );
		$datetime['year'] = sprintf( '%04d', intval( $date[2] ) );
		$datetime['hour'] = sprintf( '%02d', intval( $time[0] ) );
		$datetime['minute'] = sprintf( '%02d', intval( $time[1] ) );
		$datetime['period'] = $period;
		$datetime = vsprintf( '%s/%s/%s %s:%s %s', array_values( $datetime ) );
		$datetime = DateTime::createFromFormat( $this->input_datetime_format, $datetime );

		if ( $datetime ) {
			return $datetime->format( $this->output_datetime_format );
		}

		return '';
	}

	public function customize_enqueue_scripts( $deps ) {
		wp_enqueue_script(
			'justwpforms-schedule',
			justwpforms_get_plugin_url() . 'inc/assets/js/customize/schedule.js',
			$deps, justwpforms_get_version(), true
		);
	}

	public function is_restricted( $form ) {
		$restricted = false;

		if ( ! justwpforms_get_form_property( $form, 'scheduled_message' ) ) {
			return $restricted;
		}

		$now = current_time( 'mysql' );
		$now = DateTime::createFromFormat( $this->output_datetime_format, $now );
		$from_datetime = justwpforms_get_meta( $form['ID'], 'schedule_from_datetime', true );

		if ( $from_datetime ) {
			$from_datetime = DateTime::createFromFormat( $this->output_datetime_format, $from_datetime );
			$restricted = $restricted || ( $now < $from_datetime );
		}

		$to_datetime = justwpforms_get_meta( $form['ID'], 'schedule_to_datetime', true );

		if ( $to_datetime ) {
			$to_datetime = DateTime::createFromFormat( $this->output_datetime_format, $to_datetime );
			$restricted = $restricted || ( $now > $to_datetime );
		}

		$restricted = ( 1 === intval( justwpforms_get_meta( $form['ID'], 'schedule_visibility', true ) ) && $restricted );

		return $restricted;
	}

	public function validate_submission( $is_valid, $request, $form ) {
		if ( $this->is_restricted( $form ) ) {
			$is_valid = false;
		}

		return $is_valid;
	}

	public function form_template_path( $template_path, $form ) {
		if ( $this->is_restricted( $form ) ) {
			$template_path = justwpforms_get_include_folder() . '/templates/single-form-scheduled.php';
		}

		return $template_path;
	}

	public function style_dependencies( $deps, $forms ) {
		$is_restricted = false;
		$form_controller = justwpforms_get_form_controller();

		foreach ( $forms as $form ) {
			if ( $this->is_restricted( $form ) ) {
				$is_restricted = true;
				break;
			}
		}

		if ( ! justwpforms_is_preview() && ! $is_restricted ) {
			return $deps;
		}

		wp_register_style(
			'justwpforms-schedule',
			justwpforms_get_plugin_url() . 'inc/assets/css/frontend/schedule.css',
			array(), justwpforms_get_version()
		);

		$deps[] = 'justwpforms-schedule';

		return $deps;
	}

	public function do_deprecated_control( $control, $field, $index ) {
		$type = $control['type'];
		$path = justwpforms_get_core_folder() . '/templates/customize-controls/setup';
		$inc_path = justwpforms_get_include_folder() . '/templates/customize-controls';
		$form = justwpforms_customize_get_current_form();

		switch( $type ) {
			case 'schedule_visibility_checkbox':

				if ( justwpforms_is_falsy( $form['schedule_visibility'] ) ) {
					break;
				}

				require( "{$path}/checkbox.php" );
				break;
			case 'schedule_visibility_datetime':

				if ( justwpforms_is_falsy( $form['schedule_visibility'] ) ) {
					break;
				}

				require( "{$inc_path}/datetime.php" );
				break;
			default:
				break;
		}
	}

	public function do_deprecated_control_messages( $control, $field, $index ) {
		$type = $control['type'];
		$form = justwpforms_customize_get_current_form();
		$path = justwpforms_get_core_folder() . '/templates/customize-controls/messages';

		switch( $type ) {
			case 'schedule_visibility_text':

				if ( justwpforms_is_falsy( $form['schedule_visibility'] ) ) {
					break;
				}

				require( "{$path}/text.php" );
				break;
			default:
				break;
		}
	}

}

if ( ! function_exists( 'justwpforms_get_schedule' ) ):

function justwpforms_get_schedule() {
	return justwpforms_Form_Schedule::instance();
}

endif;

justwpforms_get_schedule();
