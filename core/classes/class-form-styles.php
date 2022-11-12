<?php

class justwpforms_Form_Styles {

	/**
	 * The singleton instance.
	 *
	 * @since 1.0
	 *
	 * @var justwpforms_Form_Styles
	 */
	private static $instance;

	/**
	 * The singleton constructor.
	 *
	 * @since 1.0
	 *
	 * @return justwpforms_Form_Styles
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
	 * @since 1.0.0.
	 *
	 * @return void
	 */
	public function hook() {
		add_filter( 'justwpforms_meta_fields', array( $this, 'meta_fields' ) );
		add_filter( 'justwpforms_form_class', array( $this, 'form_html_class' ), 10, 2 );
		add_filter( 'justwpforms_form_class', array( $this, 'form_html_class_title_display' ), 10, 2 );
		add_filter( 'justwpforms_form_class', array( $this, 'form_html_class_compat' ), PHP_INT_MAX, 2 );
		add_filter( 'justwpforms_get_form_data', array( $this, 'cap_form_width' ) );
		add_action( 'justwpforms_do_style_control', array( $this, 'do_control' ), 10, 3 );
		add_action( 'justwpforms_do_style_control', array( $this, 'do_deprecated_control' ), 10, 3 );
		add_filter( 'justwpforms_form_styles', array( $this, 'filter_obsolete_form_width'), 50, 2 );
	}

	public function get_fields() {
		$fields = array(
			'form_direction' => array(
				'default' => '',
				'options' => array(
					'' => __( 'Left-to-right', 'justwpforms' ),
					'justwpforms-form--direction-rtl' => __( 'Right-to-left', 'justwpforms' )
				),
				'target' => 'form_class',
				'sanitize' => 'sanitize_text_field'
			),
			'form_width' => array(
				'default' => '',
				'unit' => '%',
				'min' => 10,
				'max' => 100,
				'step' => 10,
				'target' => 'css_var',
				'variable' => '--justwpforms-form-width',
				'extra_class' => 'form-width-control',
				'sanitize' => 'sanitize_text_field'
			),
			'form_padding' => array(
				'default' => '',
				'options' => array(
					'' => __( 'Default', 'justwpforms' ),
					'justwpforms-form--padding-narrow' => __( 'Narrow', 'justwpforms' ),
					'justwpforms-form--padding-wide' => __( 'Wide', 'justwpforms' )
				),
				'target' => 'form_class',
				'sanitize' => 'sanitize_text_field'
			),
			'form_hide_on_submit' => array(
				'default' => '',
				'target' => 'form_class',
				'value' => 'justwpforms-form--hide-on-submit',
				'sanitize' => 'sanitize_text_field'
			),
			'form_title' => array(
				'default' => 'justwpforms-form--hide-title',
				'options' => array(
					'' => __( 'Show', 'justwpforms' ),
					'justwpforms-form--hide-title' => __( 'Hide', 'justwpforms' )
				),
				'target' => '',
				'sanitize' => 'sanitize_text_field'
			),
			'form_title_alignment' => array(
				'default' => '',
				'options' => array(
					'' => __( 'Left', 'justwpforms' ),
					'justwpforms-form--title-text-align-center' => __( 'Center', 'justwpforms' ),
					'justwpforms-form--title-text-align-right' => __( 'Right', 'justwpforms' )
				),
				'sanitize' => 'sanitize_text_field',
				'target' => 'form_class'
			),
			'form_title_font_size' => array(
				'default' => 32,
				'unit' => 'px',
				'min' => 16,
				'max' => 52,
				'step' => 1,
				'sanitize' => 'intval',
				'target' => 'css_var',
				'variable' => '--justwpforms-form-title-font-size'
			),
			'part_border' => array(
				'default' => '',
				'options' => array(
					'' => __( 'Show', 'justwpforms' ),
					'justwpforms-form--part-border-off' => __( 'Hide', 'justwpforms' )
				),
				'target' => 'form_class',
				'sanitize' => 'sanitize_text_field'
			),
			'part_border_location' => array(
				'default' => '',
				'options' => array(
					'' => __( 'All sides', 'justwpforms' ),
					'justwpforms-form--part-borders-bottom-only' => __( 'Bottom only', 'justwpforms' )
				),
				'target' => 'form_class',
				'sanitize' => 'sanitize_text_field'
			),
			'part_border_radius' => array(
				'default' => '',
				'options' => array(
					'justwpforms-form--part-border-radius-square' => __( 'Square', 'justwpforms' ),
					'' => __( 'Round', 'justwpforms' ),
					'justwpforms-form--part-border-radius-pill' => __( 'Pill', 'justwpforms' )
				),
				'target' => 'form_class',
				'sanitize' => 'sanitize_text_field'
			),
			'part_outer_padding' => array(
				'default' => '',
				'options' => array(
					'justwpforms-form--part-outer-padding-narrow' => __( 'Narrow', 'justwpforms' ),
					'' => __( 'Default', 'justwpforms' ),
					'justwpforms-form--part-outer-padding-wide' => __( 'Wide', 'justwpforms' )
				),
				'target' => 'form_class',
				'sanitize' => 'sanitize_text_field'
			),
			'part_inner_padding' => array(
				'default' => '',
				'options' => array(
					'justwpforms-form--part-inner-padding-narrow' => __( 'Narrow', 'justwpforms' ),
					'' => __( 'Default', 'justwpforms' ),
					'justwpforms-form--part-inner-padding-wide' => __( 'Wide', 'justwpforms' )
				),
				'target' => 'form_class',
				'sanitize' => 'sanitize_text_field'
			),
			'part_toggle_placeholders' => array(
				'default' => '',
				'value' => 'justwpforms-form--part-placeholder-toggle',
				'sanitize' => 'sanitize_text_field',
				'target' => 'form_class',
			),
			'part_title_alignment' => array(
				'default' => '',
				'options' => array(
					'' => __( 'Left', 'justwpforms' ),
					'justwpforms-form--part-title-text-align-center' => __( 'Center', 'justwpforms' ),
					'justwpforms-form--part-title-text-align-right' => __( 'Right', 'justwpforms' )
				),
				'sanitize' => 'sanitize_text_field',
				'target' => 'form_class'
			),
			'part_title_font_size' => array(
				'default' => 16,
				'unit' => 'px',
				'min' => 13,
				'max' => 30,
				'step' => 1,
				'target' => 'css_var',
				'variable' => '--justwpforms-part-title-font-size',
				'sanitize' => 'sanitize_text_field'
			),
			'part_title_font_weight' => array(
				'default' => '',
				'options' => array(
					'' => __( 'Normal', 'justwpforms' ),
					'justwpforms-form--part-title-font-weight-bold' => __( 'Bold', 'justwpforms' )
				),
				'target' => 'form_class',
				'sanitize' => 'sanitize_text_field'
			),
			'part_title_label_placement' => array (
				'default' => '',
				'options' => array(
					'above' => __( 'Above', 'justwpforms' ),
					'below' => __( 'Below', 'justwpforms' ),
					'left' => __( 'Left', 'justwpforms' ),
					'hidden' => __( 'Hidden', 'justwpforms' ),
				),
				'target' => '',
				'sanitize' => 'sanitize_text_field'
			),
			'part_description_alignment' => array(
				'default' => '',
				'options' => array(
					'' => __( 'Left', 'justwpforms' ),
					'justwpforms-form--part-description-text-align-center' => __( 'Center', 'justwpforms' ),
					'justwpforms-form--part-description-text-align-right' => __( 'Right', 'justwpforms' )
				),
				'sanitize' => 'sanitize_text_field',
				'target' => 'form_class'
			),
			'part_description_font_size' => array(
				'default' => 12,
				'unit' => 'px',
				'min' => 10,
				'max' => 20,
				'step' => 1,
				'target' => 'css_var',
				'variable' => '--justwpforms-part-description-font-size',
				'sanitize' => 'sanitize_text_field'
			),
			'part_value_alignment' => array(
				'default' => '',
				'options' => array(
					'' => __( 'Left', 'justwpforms' ),
					'justwpforms-form--part-value-text-align-center' => __( 'Center', 'justwpforms' ),
					'justwpforms-form--part-value-text-align-right' => __( 'Right', 'justwpforms' )
				),
				'sanitize' => 'sanitize_text_field',
				'target' => 'form_class'
			),
			'part_value_font_size' => array(
				'default' => 16,
				'unit' => 'px',
				'min' => 12,
				'max' => 24,
				'step' => 1,
				'target' => 'css_var',
				'variable' => '--justwpforms-part-value-font-size',
				'sanitize' => 'sanitize_text_field'
			),
			'submit_button_border' => array(
				'default' => '',
				'options' => array(
					'' => __( 'Show', 'justwpforms' ),
					'justwpforms-form--submit-button-border-hide' => __( 'Hide', 'justwpforms' )
				),
				'target' => 'form_class',
				'sanitize' => 'sanitize_text_field'
			),
			'submit_button_border_radius' => array(
				'default' => '',
				'options' => array(
					'justwpforms-form--submit-button-border-radius-square' => __( 'Square', 'justwpforms' ),
					'' => __( 'Round', 'justwpforms' ),
					'justwpforms-form--submit-button-border-radius-pill' => __( 'Pill', 'justwpforms' )
				),
				'target' => 'form_class',
				'sanitize' => 'sanitize_text_field'
			),
			'submit_button_width' => array(
				'default' => '',
				'options' => array(
					'' => __( 'Default', 'justwpforms' ),
					'justwpforms-form--submit-button-fullwidth' => __( 'Full width', 'justwpforms' )
				),
				'target' => 'form_class',
				'sanitize' => 'sanitize_text_field'
			),
			'submit_button_padding' => array(
				'default' => '',
				'options' => array(
					'justwpforms-form--submit-button-padding-narrow' => __( 'Narrow', 'justwpforms' ),
					'' => __( 'Default', 'justwpforms' ),
					'justwpforms-form--submit-button-padding-wide' => __( 'Wide', 'justwpforms' )
				),
				'target' => 'form_class',
				'sanitize' => 'sanitize_text_field'
			),
			'submit_button_font_size' => array(
				'default' => 16,
				'unit' => 'px',
				'min' => 14,
				'max' => 40,
				'step' => 1,
				'target' => 'css_var',
				'variable' => '--justwpforms-submit-button-font-size',
				'sanitize' => 'sanitize_text_field'
			),
			'submit_button_font_weight' => array(
				'default' => '',
				'options' => array(
					'' => __( 'Normal', 'justwpforms' ),
					'justwpforms-form--submit-button-bold' => __( 'Bold', 'justwpforms' )
				),
				'target' => 'form_class',
				'sanitize' => 'sanitize_text_field'
			),
			'submit_button_alignment' => array(
				'default' => '',
				'options' => array(
					'' => __( 'Left', 'justwpforms' ),
					'justwpforms-form--submit-button-align-center' => __( 'Center', 'justwpforms' ),
					'justwpforms-form--submit-button-align-right' => __( 'Right', 'justwpforms' )
				),
				'target' => 'form_class',
				'sanitize' => 'sanitize_text_field'
			),
			'submit_button_part_of_last_input' => array(
				'default' => '',
				'sanitize' => 'sanitize_text_field',
				'target' => 'form_class',
				'value' => 'justwpforms-form--submit-part-of-input',
			),
			'color_primary' => array(
				'default' => '#000000',
				'sanitize' => 'sanitize_text_field',
				'target' => 'css_var',
				'variable' => '--justwpforms-color-primary',
			),
			'color_success_notice' => array(
				'default' => '#ebf9f0',
				'sanitize' => 'sanitize_text_field',
				'target' => 'css_var',
				'variable' => '--justwpforms-color-success-notice',
			),
			'color_success_notice_text' => array(
				'default' => '#1eb452',
				'sanitize' => 'sanitize_text_field',
				'target' => 'css_var',
				'variable' => '--justwpforms-color-success-notice-text'
			),
			'color_error' => array(
				'default' => '#f23000',
				'sanitize' => 'sanitize_text_field',
				'target' => 'css_var',
				'variable' => '--justwpforms-color-error',
			),
			'color_error_notice' => array(
				'default' => '#ffeeea',
				'sanitize' => 'sanitize_text_field',
				'target' => 'css_var',
				'variable' => '--justwpforms-color-error-notice',
			),
			'color_error_notice_text' => array(
				'default' => '#f23000',
				'sanitize' => 'sanitize_text_field',
				'target' => 'css_var',
				'variable' => '--justwpforms-color-error-notice-text',
			),
			'color_part_title' => array(
				'default' => '#000000',
				'sanitize' => 'sanitize_text_field',
				'target' => 'css_var',
				'variable' => '--justwpforms-color-part-title',
			),
			'color_part_text' => array(
				'default' => '#000000',
				'sanitize' => 'sanitize_text_field',
				'target' => 'css_var',
				'variable' => '--justwpforms-color-part-value',
			),
			'color_part_placeholder' => array(
				'default' => '#888888',
				'sanitize' => 'sanitize_text_field',
				'target' => 'css_var',
				'variable' => '--justwpforms-color-part-placeholder',
			),
			'color_part_description' => array(
				'default' => '#454545',
				'sanitize' => 'sanitize_text_field',
				'target' => 'css_var',
				'variable' => '--justwpforms-color-part-description',
			),
			'color_part_border' => array(
				'default' => '#dbdbdb',
				'sanitize' => 'sanitize_text_field',
				'target' => 'css_var',
				'variable' => '--justwpforms-color-part-border',
			),
			'color_part_border_focus' => array(
				'default' => '#7aa4ff',
				'sanitize' => 'sanitize_text_field',
				'target' => 'css_var',
				'variable' => '--justwpforms-color-part-border-focus',
			),
			'color_part_background' => array(
				'default' => '#ffffff',
				'sanitize' => 'sanitize_text_field',
				'target' => 'css_var',
				'variable' => '--justwpforms-color-part-background',
			),
			'color_part_background_focus' => array(
				'default' => '#ffffff',
				'sanitize' => 'sanitize_text_field',
				'target' => 'css_var',
				'variable' => '--justwpforms-color-part-background-focus',
			),
			'color_submit_background' => array(
				'default' => '#000000',
				'sanitize' => 'sanitize_text_field',
				'target' => 'css_var',
				'variable' => '--justwpforms-color-submit-background',
			),
			'color_submit_background_hover' => array(
				'default' => '#000000',
				'sanitize' => 'sanitize_text_field',
				'target' => 'css_var',
				'variable' => '--justwpforms-color-submit-background-hover',
			),
			'color_submit_border' => array(
				'default' => 'transparent',
				'sanitize' => 'sanitize_text_field',
				'target' => 'css_var',
				'variable' => '--justwpforms-color-submit-border',
			),
			'color_submit_text' => array(
				'default' => '#ffffff',
				'sanitize' => 'sanitize_text_field',
				'target' => 'css_var',
				'variable' => '--justwpforms-color-submit-text',
			),
			'color_submit_text_hover' => array(
				'default' => '#ffffff',
				'sanitize' => 'sanitize_text_field',
				'target' => 'css_var',
				'variable' => '--justwpforms-color-submit-text-hover',
			),
			'color_table_row_odd' => array(
				'default' => '#fcfcfc',
				'sanitize' => 'sanitize_text_field',
				'target' => 'css_var',
				'variable' => '--justwpforms-color-table-row-odd',
			),
			'color_table_row_even' => array(
				'default' => '#efefef',
				'sanitize' => 'sanitize_text_field',
				'target' => 'css_var',
				'variable' => '--justwpforms-color-table-row-even',
			),
			'color_table_row_odd_text' => array(
				'default' => '#000000',
				'sanitize' => 'sanitize_text_field',
				'target' => 'css_var',
				'variable' => '--justwpforms-color-table-row-odd-text',
			),
			'color_table_row_even_text' => array(
				'default' => '#000000',
				'sanitize' => 'sanitize_text_field',
				'target' => 'css_var',
				'variable' => '--justwpforms-color-table-row-even-text',
			),
			'color_dropdown_item_bg' => array(
				'default' => '#ffffff',
				'sanitize' => 'sanitize_text_field',
				'target' => 'css_var',
				'variable' => '--justwpforms-color-dropdown-item-bg',
			),
			'color_dropdown_item_text' => array(
				'default' => '#000000',
				'sanitize' => 'sanitize_text_field',
				'target' => 'css_var',
				'variable' => '--justwpforms-color-dropdown-item-text',
			),
			'color_dropdown_item_bg_hover' => array(
				'default' => '#f4f4f5',
				'sanitize' => 'sanitize_text_field',
				'target' => 'css_var',
				'variable' => '--justwpforms-color-dropdown-item-bg-hover',
			),
			'color_dropdown_item_text_hover' => array(
				'default' => '#000000',
				'sanitize' => 'sanitize_text_field',
				'target' => 'css_var',
				'variable' => '--justwpforms-color-dropdown-item-text-hover',
			),
			'additional_css' => array(
				'default' => '',
				'mode' => 'css',
				'target' => 'value',
				'sanitize' => ''
			)
		);

		$fields = apply_filters( 'justwpforms_style_fields', $fields );

		return $fields;
	}

	public function get_controls() {
		$controls = array(
			100 => array(
				'type' => 'divider',
				'label' => __( 'General', 'justwpforms' ),
				'id' => 'general',
			),
			200 => array(
				'type' => 'form-width-range',
				'label' => __( 'Width', 'justwpforms' ),
				'field' => 'form_width'
			),
			300 => array(
				'type' => 'buttonset',
				'label' => __( 'Padding', 'justwpforms' ),
				'field' => 'form_padding',
			),
			400 => array(
				'type' => 'buttonset',
				'label' => __( 'Direction', 'justwpforms' ),
				'field' => 'form_direction'
			),
			600 => array(
				'type' => 'heading',
				'id' => 'colors_general',
				'label' => __( 'Colors', 'justwpforms' )
			),
			700 => array(
				'type' => 'color',
				'label' => __( 'Primary', 'justwpforms' ),
				'field' => 'color_primary',
			),
			800 => array(
				'type' => 'color',
				'label' => __( 'Success message background', 'justwpforms' ),
				'field' => 'color_success_notice',
			),
			801 => array(
				'type' => 'color',
				'label' => __( 'Success message text', 'justwpforms' ),
				'field' => 'color_success_notice_text',
			),
			900 => array(
				'type' => 'color',
				'label' => __( 'Validation message text', 'justwpforms' ),
				'field' => 'color_error',
			),
			901 => array(
				'type' => 'color',
				'label' => __( 'Error message background', 'justwpforms' ),
				'field' => 'color_error_notice',
			),
			902 => array(
				'type' => 'color',
				'label' => __( 'Error message text', 'justwpforms' ),
				'field' => 'color_error_notice_text',
			),
			1000 => array(
				'type' => 'form_title-divider',
				'label' => __( 'Title', 'justwpforms' ),
				'id' => 'form_title',
			),
			1100 => array(
				'type' => 'form_title-buttonset',
				'label' => __( 'Display', 'justwpforms' ),
				'field' => 'form_title',
			),
			1200 => array(
				'type' => 'form_title-buttonset',
				'label' => __( 'Alignment', 'justwpforms' ),
				'field' => 'form_title_alignment'
			),
			1300 => array(
				'type' => 'form_title-range',
				'label' => __( 'Font size', 'justwpforms' ),
				'field' => 'form_title_font_size',
			),
			1400 => array(
				'type' => 'divider',
				'label' => __( 'Field borders & spacing', 'justwpforms' ),
				'id' => 'borders-spacing',
			),
			1500 => array(
				'type' => 'buttonset',
				'label' => __( 'Border', 'justwpforms' ),
				'field' => 'part_border',
			),
			1600 => array(
				'type' => 'buttonset',
				'label' => __( 'Border location', 'justwpforms' ),
				'field' => 'part_border_location',
			),
			1700 => array(
				'type' => 'buttonset',
				'label' => __( 'Border radius', 'justwpforms' ),
				'field' => 'part_border_radius',
			),
			1800 => array(
				'type' => 'buttonset',
				'label' => __( 'Outer spacing', 'justwpforms' ),
				'field' => 'part_outer_padding',
			),
			1900 => array(
				'type' => 'buttonset',
				'label' => __( 'Inner spacing', 'justwpforms' ),
				'field' => 'part_inner_padding',
			),
			2000 => array(
				'type' => 'heading',
				'id' => 'colors_part_borders',
				'label' => __( 'Colors', 'justwpforms' )
			),
			2100 => array(
				'type' => 'color',
				'label' => __( 'Border', 'justwpforms' ),
				'field' => 'color_part_border',
			),
			2200 => array(
				'type' => 'color',
				'label' => __( 'Border on focus', 'justwpforms' ),
				'field' => 'color_part_border_focus',
			),
			2300 => array(
				'type' => 'color',
				'label' => __( 'Background', 'justwpforms' ),
				'field' => 'color_part_background',
			),
			2400 => array(
				'type' => 'color',
				'label' => __( 'Background on focus', 'justwpforms' ),
				'field' => 'color_part_background_focus',
			),
			2500 => array(
				'type' => 'divider',
				'label' => __( 'Field labels & text', 'justwpforms' ),
				'id' => 'labels-text',
			),
			2600 => array(
				'type' => 'checkbox',
				'label' => __( 'Toggle placeholder on field focus', 'justwpforms' ),
				'field' => 'part_toggle_placeholders',
			),
			2700 => array(
				'type' => 'buttonset',
				'label' => __( 'Label alignment', 'justwpforms' ),
				'field' => 'part_title_alignment'
			),
			2800 => array(
				'type' => 'range',
				'label' => __( 'Label font size', 'justwpforms' ),
				'field' => 'part_title_font_size',
			),
			2900 => array(
				'type' => 'buttonset',
				'label' => __( 'Label font weight', 'justwpforms' ),
				'field' => 'part_title_font_weight',
			),
			3000 => array(
				'type' => 'buttonset',
				'label' => __( 'Hint alignment', 'justwpforms' ),
				'field' => 'part_description_alignment'
			),
			3100 => array(
				'type' => 'range',
				'label' => __( 'Hint font size', 'justwpforms' ),
				'field' => 'part_description_font_size',
			),
			3200 => array(
				'type' => 'buttonset',
				'label' => __( 'Placeholder &amp; value alignment', 'justwpforms' ),
				'field' => 'part_value_alignment'
			),
			3300 => array(
				'type' => 'range',
				'label' => __( 'Value font size', 'justwpforms' ),
				'field' => 'part_value_font_size',
			),
			3400 => array(
				'type' => 'heading',
				'id' => 'colors_part_text',
				'label' => __( 'Colors', 'justwpforms' )
			),
			3500 => array(
				'type' => 'color',
				'label' => __( 'Label', 'justwpforms' ),
				'field' => 'color_part_title',
			),
			3600 => array(
				'type' => 'color',
				'label' => __( 'Value', 'justwpforms' ),
				'field' => 'color_part_text',
			),
			3700 => array(
				'type' => 'color',
				'label' => __( 'Placeholder', 'justwpforms' ),
				'field' => 'color_part_placeholder',
			),
			3701 => array(
				'type' => 'color',
				'label' => __( 'Hint', 'justwpforms' ),
				'field' => 'color_part_description',
			),
			3800 => array(
				'type' => 'divider',
				'label' => __( 'Address dropdowns', 'justwpforms' ),
				'id' => 'dropdowns',
			),
			3900 => array(
				'type' => 'heading',
				'id' => 'colors_dropdown_items',
				'label' => __( 'Items', 'justwpforms' )
			),
			4000 => array(
				'type' => 'color',
				'label' => __( 'Background', 'justwpforms' ),
				'field' => 'color_dropdown_item_bg',
			),
			4100 => array(
				'type' => 'color',
				'label' => __( 'Text', 'justwpforms' ),
				'field' => 'color_dropdown_item_text',
			),
			4200 => array(
				'type' => 'color',
				'label' => __( 'Background on focus', 'justwpforms' ),
				'field' => 'color_dropdown_item_bg_hover',
			),
			4300 => array(
				'type' => 'color',
				'label' => __( 'Text focused', 'justwpforms' ),
				'field' => 'color_dropdown_item_text_hover',
			),
			5300 => array(
				'type' => 'divider',
				'label' => __( 'Tables', 'justwpforms' ),
				'id' => 'tables',
			),
			5400 => array(
				'type' => 'color',
				'label' => __( 'Odd row primary', 'justwpforms' ),
				'field' => 'color_table_row_odd',
			),
			5500 => array(
				'type' => 'color',
				'label' => __( 'Odd row secondary', 'justwpforms' ),
				'field' => 'color_table_row_odd_text',
			),
			5600 => array(
				'type' => 'color',
				'label' => __( 'Even row primary', 'justwpforms' ),
				'field' => 'color_table_row_even',
			),
			5700 => array(
				'type' => 'color',
				'label' => __( 'Even row secondary', 'justwpforms' ),
				'field' => 'color_table_row_even_text',
			),
			5800 => array(
				'type' => 'divider',
				'label' => __( 'Submit button', 'justwpforms' ),
				'id' => 'submit',
			),
			5900 => array(
				'type' => 'buttonset',
				'label' => __( 'Border', 'justwpforms' ),
				'field' => 'submit_button_border',
			),
			6000 => array(
				'type' => 'buttonset',
				'label' => __( 'Border radius', 'justwpforms' ),
				'field' => 'submit_button_border_radius',
			),
			6100 => array(
				'type' => 'buttonset',
				'label' => __( 'Width', 'justwpforms' ),
				'field' => 'submit_button_width',
			),
			6200 => array(
				'type' => 'buttonset',
				'label' => __( 'Padding', 'justwpforms' ),
				'field' => 'submit_button_padding',
			),
			6300 => array(
				'type' => 'range',
				'label' => __( 'Font size', 'justwpforms' ),
				'field' => 'submit_button_font_size',
			),
			6400 => array(
				'type' => 'buttonset',
				'label' => __( 'Font weight', 'justwpforms' ),
				'field' => 'submit_button_font_weight',
			),
			6500 => array(
				'type' => 'buttonset',
				'label' => __( 'Alignment', 'justwpforms' ),
				'field' => 'submit_button_alignment',
			),
			6600 => array(
				'type' => 'checkbox',
				'label' => __( 'Make button a field of last input', 'justwpforms' ),
				'field' => 'submit_button_part_of_last_input'
			),
			6700 => array(
				'type' => 'heading',
				'id' => 'colors_submit_button',
				'label' => __( 'Colors', 'justwpforms' )
			),
			6800 => array(
				'type' => 'color',
				'label' => __( 'Background', 'justwpforms' ),
				'field' => 'color_submit_background',
			),
			6900 => array(
				'type' => 'color',
				'label' => __( 'Background on focus', 'justwpforms' ),
				'field' => 'color_submit_background_hover',
			),
			7000 => array(
				'type' => 'color',
				'label' => __( 'Border', 'justwpforms' ),
				'field' => 'color_submit_border',
			),
			7100 => array(
				'type' => 'color',
				'label' => __( 'Text', 'justwpforms' ),
				'field' => 'color_submit_text',
			),
			7200 => array(
				'type' => 'color',
				'label' => __( 'Text on focus', 'justwpforms' ),
				'field' => 'color_submit_text_hover',
			),
		);

		$current_user_id = get_current_user_id();
		$code_editor_mode = 'plain';

		if ( 'true' === get_user_meta( $current_user_id, 'syntax_highlighting', true ) ) {
			$code_editor_mode = 'rich';
		}

		$code_section_description = '<p>' . __( 'Add your own CSS code here to customize the appearance of your form.', 'justwpforms' ) . '</p>' .
		'<p>' . sprintf(
			__( 'For each rule you add, we\'ll prepend your form\'s HTML ID. This makes sure all styles added will only apply to this form. For example %s becomes %s.', 'justwpforms' ),
			'<code>p</code>',
			'<code><%= ( justwpforms.form.get( \'form_id\' ) ) ? justwpforms.form.get( \'html_id\' ) : \'#justwpforms-\'+justwpforms.form.get( \'ID\' ) %> p</code>'
		)
		. '</p>';

		if ( 'rich' === $code_editor_mode ) {
			$code_section_description .= sprintf(
				'<p>' . __( 'The edit field automatically highlights code syntax. You can disable this in your <a href="%s" class="%s" target="_blank">user profile</a> to work in plain text mode.', 'justwpforms' ) . '</p>',
				get_edit_profile_url( $current_user_id ),
				'external external-link'
			);
		}

		$controls[99990] = array(
			'type' => 'additional_css-divider',
			'label' => __( 'Additional CSS', 'justwpforms' ),
			'id' => 'additional_css_divider',
			'class' => "code-editor-mode--{$code_editor_mode}",
			'description' => $code_section_description
		);

		$controls[99991] = array(
			'type' => 'additional_css-code',
			'mode' => $code_editor_mode,
			'hide_title' => true,
			'label' => __( 'Additional CSS', 'justwpforms' ),
			'field' => 'additional_css'
		);

		$controls = justwpforms_safe_array_merge( array(), $controls );
		$controls = apply_filters( 'justwpforms_style_controls', $controls );
		ksort( $controls, SORT_NUMERIC );

		return $controls;
	}

	public function cap_form_width( $form ) {
		$form['form_width'] = preg_replace( '/[^\d*]/', '', $form['form_width'] );
		$form['form_width'] = intval( $form['form_width'] );
		$form['form_width'] = min( $form['form_width'], 100 );

		return $form;
	}

	public function filter_obsolete_form_width( $styles, $form ) {
		$form_width = $form['form_width'];

		if ( justwpforms_is_falsy( $form_width ) || 100 === intval( $form_width ) ) {
			unset( $styles['form_width'] );
		}

		return $styles;
	}

	public function do_control( $control, $field, $index ) {
		$type = $control['type'];
		$path = justwpforms_get_core_folder() . '/templates/customize-controls/style';

		switch( $control['type'] ) {
			case 'divider':
			case 'checkbox':
			case 'range':
			case 'buttonset':
			case 'color':
			case 'text':
			case 'select':
			case 'custom-select':
			case 'heading':
			case 'code':
				require( "{$path}/{$type}.php" );
				break;
			default:
				break;
		}
	}

	public function do_deprecated_control( $control, $field, $index ) {
		$type = $control['type'];
		$path = justwpforms_get_core_folder() . '/templates/customize-controls/style';
		$form = justwpforms_customize_get_current_form();

		switch( $control['type'] ) {
			case 'form-width-range':
				$form_width = $form['form_width'];

				if ( justwpforms_is_falsy( $form_width ) || 100 === intval( $form_width ) ) {
					break;
				}

				require( "{$path}/range.php" );
				break;
			case 'additional_css-divider':
			case 'additional_css-code':
				if ( '' == trim( $form['additional_css'] ) ) {
					break;
				}

				$true_type = str_replace( 'additional_css-', '', $type );

				require( "{$path}/{$true_type}.php" );
				break;
			case 'form_title-divider':
			case 'form_title-buttonset':
			case 'form_title-range':
				if( 'justwpforms-form--hide-title' == $form['form_title'] ) {
					break;
				}

				$true_type = str_replace( 'form_title-', '', $type );

				require( "{$path}/{$true_type}.php" );
				break;
			default:
				break;
		}
	}

	public function is_class_field( $field ) {
		return 'form_class' === $field['target'];
	}

	public function is_css_var_field( $field ) {
		return 'css_var' === $field['target'];
	}

	public function form_html_class( $class, $form ) {
		$class[] = 'justwpforms-styles';

		$fields = $this->get_fields();
		$class_fields = array_filter( $fields, array( $this, 'is_class_field' ) );

		foreach ( $class_fields as $key => $field ) {
			if ( '' !== $form[$key] ) {
				$class[] = $form[$key];
			}
		}

		return $class;
	}

	public function form_html_class_title_display( $class, $form ) {
		if ( justwpforms_is_preview() ) {
			$class[] = $form['form_title'];
		}

		return $class;
	}

	public function form_html_class_compat( $class, $form ) {
		$class = array_unique( $class );
		$class = array_flip( $class );

		if ( isset( $class['standard'] ) ) {
			unset( $class['standard'] );
			$class['justwpforms-part-description-mode-standard'] = '';
		}

		$class = array_flip( $class );

		return $class;
	}

	public function form_html_styles( $form = array() ) {
		$fields = $this->get_fields();
		$styles = array_filter( $fields, array( $this, 'is_css_var_field' ) );

		return $styles;
	}

	public function form_css_vars( $form = array() ) {
		$styles = $this->form_html_styles( $form );
		$variables = wp_list_pluck( $styles, 'variable' );

		return $variables;
	}

	/**
	 * Filter: add fields to form meta.
	 *
	 * @hooked filter justwpforms_meta_fields
	 *
	 * @since 1.3.0.
	 *
	 * @param array $fields Current form meta fields.
	 *
	 * @return array
	 */
	public function meta_fields( $fields ) {
		$fields = array_merge( $fields, $this->get_fields() );

		return $fields;
	}

}

if ( ! function_exists( 'justwpforms_get_styles' ) ):

function justwpforms_get_styles() {
	return justwpforms_Form_Styles::instance();
}

endif;

justwpforms_get_styles();
