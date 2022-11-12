<?php

class justwpforms_Form_Styles_Upgrade {

	private static $instance;

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		self::$instance->hook();

		return self::$instance;
	}

	public function hook() {
		add_filter( 'justwpforms_style_fields', array( $this, 'get_fields' ) );
		add_filter( 'justwpforms_style_controls', array( $this, 'get_controls' ) );
	}

	public function get_fields( $fields ) {
		$styles_fields = array(
      'color_choice_checkmark_bg' => array(
				'default' => '#ffffff',
				'sanitize' => 'sanitize_text_field',
				'target' => 'css_var',
				'variable' => '--justwpforms-color-choice-checkmark-bg',
			),
			'color_choice_checkmark_bg_focus' => array(
				'default' => '#000000',
				'sanitize' => 'sanitize_text_field',
				'target' => 'css_var',
				'variable' => '--justwpforms-color-choice-checkmark-bg-focus',
			),
			'color_choice_checkmark_color' => array(
				'default' => '#ffffff',
				'sanitize' => 'sanitize_text_field',
				'target' => 'css_var',
				'variable' => '--justwpforms-color-choice-checkmark-color',
			),
			'color_rating_star' => array(
				'default' => '#cccccc',
				'sanitize' => 'sanitize_text_field',
				'target' => 'css_var',
				'variable' => '--justwpforms-color-rating',
			),
			'color_rating_star_hover' => array(
				'default' => '#000000',
				'sanitize' => 'sanitize_text_field',
				'target' => 'css_var',
				'variable' => '--justwpforms-color-rating-hover',
			),
			'color_divider_hr' => array(
				'default' => '#cccccc',
				'sanitize' => 'sanitize_text_field',
				'target' => 'css_var',
				'variable' => '--justwpforms-color-divider-hr',
			),
		);

		$fields = array_merge( $fields, $styles_fields );

		return $fields;
	}

	public function get_controls( $controls ) {
		$styles_controls = array(
      4400 => array(
        'type' => 'divider',
        'label' => __( 'Checkboxes & Radios', 'justwpforms' ),
        'id' => 'checkboxes-radios',
      ),
      4500 => array(
        'type' => 'color',
        'label' => __( 'Background', 'justwpforms' ),
        'field' => 'color_choice_checkmark_bg',
      ),
      4600 => array(
        'type' => 'color',
        'label' => __( 'Background on focus', 'justwpforms' ),
        'field' => 'color_choice_checkmark_bg_focus',
      ),
      4700 => array(
        'type' => 'color',
        'label' => __( 'Checkmark', 'justwpforms' ),
        'field' => 'color_choice_checkmark_color',
      ),
      4800 => array(
        'type' => 'divider',
        'label' => __( 'Rating', 'justwpforms' ),
        'id' => 'rating',
      ),
			4900 => array(
				'type' => 'color',
				'label' => __( 'Rating star color', 'justwpforms' ),
				'field' => 'color_rating_star',
			),
			5000 => array(
				'type' => 'color',
				'label' => __( 'Rating star color on hover', 'justwpforms' ),
				'field' => 'color_rating_star_hover',
			),
			5750 => array(
				'type' => 'divider',
				'label' => __( 'Separators', 'justwpforms' ),
				'id' => 'dividers',
			),
			5751 => array(
				'type' => 'color',
				'label' => __( 'Color', 'justwpforms' ),
				'field' => 'color_divider_hr',
			),
		);

		$controls = justwpforms_safe_array_merge( $controls, $styles_controls );

		return $controls;
	}

}

if ( ! function_exists( 'justwpforms_get_styles_upgrade' ) ):

function justwpforms_get_styles_upgrade() {
	return justwpforms_Form_Styles_Upgrade::instance();
}

endif;

justwpforms_get_styles_upgrade();
