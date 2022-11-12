<?php
class justwpforms_Form_Mute_Styles {
	private static $instance;

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		self::$instance->hook();

		return self::$instance;
	}

	public function hook() {
		add_filter( 'justwpforms_style_fields', array( $this, 'add_fields' ), 10, 1 );
		add_filter( 'justwpforms_style_controls', array( $this, 'add_style_controls' ), 10, 1 );
		add_filter( 'justwpforms_form_class', array( $this, 'form_html_class' ), 999, 2 );
		add_action( 'justwpforms_customize_enqueue_scripts', array( $this, 'customize_enqueue_scripts' ) );
		add_action( 'justwpforms_do_style_control', array( $this, 'do_deprecated_control' ), 10, 3 );
	}

	public function are_styles_muted( $form ) {
		if ( justwpforms_get_form_property( $form, 'mute_styles' ) ) {
			return true;
		}
	}

	public function form_html_class( $class, $form ) {
		if ( $this->are_styles_muted( $form ) ) {
			$styles_class = array_search( 'justwpforms-styles', $class );

			if ( false !== $styles_class ) {
				unset( $class[$styles_class] );
			}
		}

		return $class;
	}

	public function add_fields( $fields ) {
		$fields['mute_styles'] = array(
			'default'  => 0,
			'value'   => 1,
			'target' => '',
			'sanitize' => 'justwpforms_sanitize_checkbox'
		);

		return $fields;
	}

	public function add_style_controls( $controls ) {
		$style_controls = array(
			101 => array(
				'field' => 'mute_styles',
				'label' => __( 'Use theme styles', 'justwpforms' ),
				'type' => 'mute-style-checkbox',
			),
		);

		$controls = justwpforms_safe_array_merge( $controls, $style_controls );

		return $controls;
	}

	public function do_deprecated_control( $control, $field, $index ) {
		$type = $control['type'];

		switch( $control['type'] ) {
			case 'mute-style-checkbox':
				$form = justwpforms_customize_get_current_form();

				if ( justwpforms_is_falsy( $form['mute_styles'] ) ) {
					break;
				}

				$path = justwpforms_get_core_folder() . '/templates/customize-controls/style';

				require( "{$path}/checkbox.php" );
				break;
			default:
				break;
		}
	}

	public function customize_enqueue_scripts( $deps ) {
		$form = justwpforms_customize_get_current_form();

		if ( justwpforms_is_truthy( $form['mute_styles'] ) ) {
			wp_enqueue_script(
				'justwpforms-mute-styles',
				justwpforms_get_plugin_url() . 'inc/assets/js/customize/mute-styles.js',
				$deps, justwpforms_get_version(), true
			);
			
			wp_enqueue_style(
				'justwpforms-mute-styles',
				justwpforms_get_plugin_url() . 'inc/assets/css/customize-mute-styles.css',
				array(), justwpforms_get_version()
			);
		}
	}

}

if ( ! function_exists( 'justwpforms_upgrade_get_mute_styles' ) ) :

	function justwpforms_upgrade_get_mute_styles() {
		return justwpforms_Form_Mute_Styles::instance();
	}

endif;

justwpforms_upgrade_get_mute_styles();
