<?php

class justwpforms_Form_Messages {

	private static $instance;

	private $form = null;

	private $default_validation_messages = array();

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		self::$instance->hook();
		self::$instance->define_validation_defaults();

		return self::$instance;
	}

	public function hook() {
		add_filter( 'justwpforms_meta_fields', array( $this, 'meta_fields' ) );
		add_action( 'justwpforms_do_messages_control', array( $this, 'do_control' ), 10, 3 );
	}

	public function get_fields() {

		$fields = array(
			'words_label_min' => array(
				'default' => __( 'Min words', 'justwpforms' ),
				'sanitize' => 'sanitize_text_field',
			),
			'words_label_max' => array(
				'default' => __( 'Max words', 'justwpforms' ),
				'sanitize' => 'sanitize_text_field',
			),
			'characters_label_min' => array(
				'default' => __( 'Min characters', 'justwpforms' ),
				'sanitize' => 'sanitize_text_field',
			),
			'characters_label_max' => array(
				'default' => __( 'Max characters', 'justwpforms' ),
				'sanitize' => 'sanitize_text_field',
			),
			'no_results_label' => array(
				'default' => __( 'Nothing found', 'justwpforms' ),
				'sanitize' => 'sanitize_text_field',
			),
			'number_min_invalid' => array(
				'default' => __( "This number isn't big enough.", 'justwpforms' ),
				'sanitize' => 'sanitize_text_field',
			),
			'number_max_invalid' => array(
				'default' => __( 'This number is too big.', 'justwpforms' ),
				'sanitize' => 'sanitize_text_field',
			),
			'optional_part_label' => array(
				'default' => __( '(optional)', 'justwpforms' ),
				'sanitize' => 'sanitize_text_field',
			),
			'required_field_label' => array(
				'default' => __( '', 'justwpforms' ),
				'sanitize' => 'sanitize_text_field'
			),
			'select_less_choices' => array(
				'default' => __( 'Too many choices are selected.', 'justwpforms' ),
				'sanitize' => 'sanitize_text_field',
			),
			'select_more_choices' => array(
				'default' => __( 'Not enough choices are selected.', 'justwpforms' ),
				'sanitize' => 'sanitize_text_field',
			),
			'submissions_left_label' => array(
				'default' => __( 'Remaining', 'justwpforms' ),
				'sanitize' => 'sanitize_text_field',
			)
		);

		$fields = apply_filters( 'justwpforms_messages_fields', $fields );

		return $fields;
	}

	public function get_controls() {
		$controls = array(
			// control groupings
			1 => array (
				'type' => 'group_start',
				'group_type' => 'group',
				'group_id' => 'messages-view-alerts',
				'group_title' => __( 'Alerts', 'justwpforms' ),
				'group_description' => __( 'These messages are shown to submitters at the very top of the form to communicate the form’s status.', 'justwpforms' ),
			),
			2000 => array (
				'type' => 'group_end',
			),

			2001 => array (
				'type' => 'group_start',
				'group_type' => 'group',
				'group_id' => 'messages-view-buttons',
				'group_title' => __( 'Buttons', 'justwpforms' ),
				'group_description' => __( 'These messages are shown to submitters as they fill out the form to help them trigger an action.', 'justwpforms' ),
			),
			4000 => array (
				'type' => 'group_end',
			),
			4001 => array (
				'type' => 'group_start',
				'group_type' => 'group',
				'group_id' => 'messages-view-errors',
				'group_title' => __( 'Errors', 'justwpforms' ),
				'group_description' => __( 'These messages are shown to submitters when they try to submit but one or more fields has a mistake.', 'justwpforms' ),
			),
			6000 => array (
				'type' => 'group_end',
			),
			6001 => array (
				'type' => 'group_start',
				'group_type' => 'group',
				'group_id' => 'messages-view-hints',
				'group_title' => __( 'Hints', 'justwpforms' ),
				'group_description' => __( 'These messages are shown to submitters as they fill out the form to help them avoid mistakes.', 'justwpforms' ),
			),
			8000 => array (
				'type' => 'group_end',
			),
			4260 => array(
				'type' => 'text',
				'label' => __( "Too many choices are selected", 'justwpforms' ),
				'field' => 'select_less_choices',
			),
			4280 => array(
				'type' => 'text',
				'label' => __( "Not enough choices are selected", 'justwpforms' ),
				'field' => 'select_more_choices',
			),

			// individual controls
			4300 => array(
				'type' => 'text',
				'label' => __( 'Number too small', 'justwpforms' ),
				'field' => 'number_min_invalid',
			),
			4320 => array(
				'type' => 'text',
				'label' => __( 'Number too big', 'justwpforms' ),
				'field' => 'number_max_invalid',
			),
			6020 => array(
				'type' => 'text',
				'label' => __( "Search couldn't find anything", 'justwpforms' ),
				'field' => 'no_results_label',
			),
			6040 => array(
				'type' => 'text',
				'label' => __( 'Minimum characters', 'justwpforms' ),
				'field' => 'characters_label_min',
			),
			6060 => array(
				'type' => 'text',
				'label' => __( 'Maximum characters', 'justwpforms' ),
				'field' => 'characters_label_max',
			),
			6080 => array(
				'type' => 'text',
				'label' => __( 'Minimum words', 'justwpforms' ),
				'field' => 'words_label_min',
			),
			6100 => array(
				'type' => 'text',
				'label' => __( 'Maximum words', 'justwpforms' ),
				'field' => 'words_label_max',
			),
			6010 => array(
				'type' => 'text',
				'label' => __( 'Question is optional', 'justwpforms' ),
				'field' => 'optional_part_label',
			),
			6011 => array(
				'type' => 'text',
				'label' => __( 'Question is required', 'justwpforms' ),
				'field' => 'required_field_label',
			),
			6222 => array(
				'type' => 'text',
				'label' => __( 'Remaining submissions', 'justwpforms' ),
				'field' => 'submissions_left_label',
			)
		);

		$controls = justwpforms_safe_array_merge( array(), $controls );
		$controls = apply_filters( 'justwpforms_messages_controls', $controls );
		ksort( $controls, SORT_NUMERIC );

		return $controls;
	}

	/**
	 * Filter: add fields to form meta.
	 *
	 * @hooked filter justwpforms_meta_fields
	 *
	 * @param array $fields Current form meta fields.
	 *
	 * @return array
	 */
	public function meta_fields( $fields ) {
		$fields = array_merge( $fields, $this->get_fields() );

		return $fields;
	}

	public function do_control( $control, $field, $index ) {
		$type = $control['type'];

		switch( $control['type'] ) {
			case 'text':
				$path = justwpforms_get_core_folder() . '/templates/customize-controls/messages';
				require( "{$path}/{$type}.php" );
				break;
			case 'escaped_text':
				$path = justwpforms_get_core_folder() . '/templates/customize-controls/messages';
				require( "{$path}/{$type}.php" );
				break;
			case 'group_start':
			case 'group_end':
				$path = justwpforms_get_core_folder() . '/templates/customize-controls/setup';
				require( "{$path}/{$type}.php" );
				break;
			default:
				break;
		}
	}

	public function define_validation_defaults(){
		$this->default_validation_messages = justwpforms_validation_messages()->get_default_messages();
	}

	public function get_default_validation_message( $key ) {
		if ( empty( $this->default_validation_messages ) ) {
			$this->define_validation_defaults();
		}

		return $this->default_validation_messages[ $key ];
	}

	public function get_validation_message(  $message, $message_key ) {
		if ( empty( $this->form[ $message_key ] ) ) {
			return $message;
		}

		return $this->form[ $message_key ];
	}

}

if ( ! function_exists( 'justwpforms_get_messages' ) ):

function justwpforms_get_messages() {
	return justwpforms_Form_Messages::instance();
}

endif;

justwpforms_get_messages();
