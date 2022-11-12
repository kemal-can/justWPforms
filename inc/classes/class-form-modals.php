<?php

class justwpforms_Form_Modals {

	/**
	 * The singleton instance.
	 *
	 * @var justwpforms_Form_Modals
	 */
	private static $instance;

	private $controller;

	private $frontend_styles = false;

	private $current_modals = array();

	private $action = 'justwpforms-fetch-modal-form';

	/**
	 * The singleton constructor.
	 *
	 * @return justwpforms_Form_Modals
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
		$this->controller = justwpforms_get_form_controller();
		$post_type = $this->controller->post_type;

		add_filter( 'justwpforms_meta_fields', array( $this, 'meta_fields' ) );
		add_filter( 'justwpforms_setup_controls', array( $this, 'setup_controls' ) );
		add_filter( 'justwpforms_style_controls', array( $this, 'style_controls' ) );
		add_action( 'justwpforms_do_setup_control', array( $this, 'do_deprecated_control' ), 10, 3 );
		add_action( 'justwpforms_customize_enqueue_scripts', array( $this, 'customize_enqueue_scripts' ) );
		add_filter( 'justwpforms_form_class', array( $this, 'form_html_class' ), 10, 2 );
		add_filter( 'justwpforms_get_shortcode', array( $this, 'get_shortcode' ), 10, 2 );
		add_filter( 'justwpforms_dashboard_form_fields', array( $this, 'dashboard_form_fields' ) );
		add_filter( 'justwpforms_dashboard_data', array( $this, 'dashboard_data' ) );
		add_action( 'wp_head', array( $this, 'wp_head' ) );
		add_action( 'wp_print_footer_scripts', array( $this, 'print_footer_scripts' ) );
		add_action( 'wp_ajax_' . $this->action, array( $this, 'ajax_fetch_form' ) );
		add_action( 'wp_ajax_nopriv_' . $this->action, array( $this, 'ajax_fetch_form' ) );

		// Navigation menus
		add_filter( 'pre_get_posts', array( $this, 'menu_items_search_filter_posts' ) );
		add_filter( 'nav_menu_meta_box_object', array( $this, 'menu_items_box_arguments' ) );
		add_filter( 'wp_setup_nav_menu_item', array( $this, 'setup_nav_menu_item' ) );
		add_filter( 'customize_nav_menu_available_items', array( $this, 'customize_menu_available_items' ), 10, 4 );
		add_filter( 'customize_nav_menu_available_item_types', array( $this, 'customize_menu_available_item_types' ) );
	}

	public function get_fields() {
		$fields = array(
			'modal' => array(
				'default' => 0,
				'sanitize' => 'justwpforms_sanitize_checkbox',
			),
		);

		return $fields;
	}

	public function get_styles() {
		$styles = array(
			'modal_max_width' => array(
				'default' => 800,
				'unit' => 'px',
				'min' => 300,
				'max' => 1400,
				'step' => 10,
				'target' => 'modal_css_var',
				'variable' => '--justwpforms-modal-max-width',
				'sanitize' => 'intval'
			),
			'modal_form_padding' => array(
				'default' => '',
				'options' => array(
					'' => __( 'Default', 'justwpforms' ),
					'justwpforms-modal--padding-narrow' => __( 'Narrow', 'justwpforms' ),
					'justwpforms-modal--padding-wide' => __( 'Wide', 'justwpforms' )
				),
				'target' => 'modal_class',
				'sanitize' => 'sanitize_text_field'
			),
			'modal_border_radius' => array(
				'default' => '',
				'options' => array(
					'' => __( 'Square', 'justwpforms' ),
					'justwpforms-modal--border-radius-round' => __( 'Round', 'justwpforms' )
				),
				'target' => 'modal_class',
				'sanitize' => 'sanitize_text_field'
			),
			'color_modal_bg' => array(
				'default' => '#ffffff',
				'target' => 'modal_css_var',
				'variable' => '--justwpforms-modal-bg-color',
				'sanitize' => 'sanitize_text_field'
			),
			'color_modal_overlay_bg' => array(
				'default' => '#000000',
				'format' => 'rgba',
				'alpha' => '.75',
				'target' => 'modal_css_var',
				'variable' => '--justwpforms-modal-overlay-bg-color',
				'sanitize' => 'sanitize_text_field'
			),
		);

		return $styles;
	}

	public function meta_fields( $fields ) {
		$fields = array_merge( $fields, $this->get_fields() );
		$fields = array_merge( $fields, $this->get_styles() );

		return $fields;
	}

	public function setup_controls( $controls ) {
		$setup_controls = array(
			1590 => array(
				'field' => 'modal',
				'label' => __( 'Open in overlay window', 'justwpforms' ),
				'type' => 'modal-checkbox'
			),
		);

		$controls = justwpforms_safe_array_merge( $controls, $setup_controls );

		return $controls;
	}

	public function style_controls( $controls ) {
		$style_controls = array(
			8000 => array(
				'type' => 'divider',
				'id' => 'overlay',
				'label' => __( 'Overlay', 'justwpforms' )
			),
			8100 => array(
				'type' => 'range',
				'label' => __( 'Maximum width', 'justwpforms' ),
				'field' => 'modal_max_width'
			),
			8200 => array(
				'type' => 'buttonset',
				'label' => __( 'Padding', 'justwpforms' ),
				'field' => 'modal_form_padding'
			),
			8300 => array(
				'type' => 'buttonset',
				'label' => __( 'Border radius', 'justwpforms' ),
				'field' => 'modal_border_radius'
			),
			8400 => array(
				'type' => 'color',
				'label' => __( 'Box background', 'justwpforms' ),
				'field' => 'color_modal_bg'
			),
			8500 => array(
				'type' => 'color',
				'label' => __( 'Screen background', 'justwpforms' ),
				'field' => 'color_modal_overlay_bg'
			),
		);

		$controls = justwpforms_safe_array_merge( $controls, $style_controls );

		return $controls;
	}

	public function do_deprecated_control( $control, $field, $index ) {
		$type = $control['type'];

		switch( $type ) {
			case 'modal-checkbox':
				$form = justwpforms_customize_get_current_form();

				if ( $form[ 'modal' ] == 1 ) {
					$path = justwpforms_get_core_folder() . '/templates/customize-controls/setup';
					$type = str_replace( 'modal-', '', $type );

					require( "{$path}/{$type}.php" );
				}

				break;
		}
	}

	public function customize_enqueue_scripts( $deps ) {
		wp_enqueue_script(
			'justwpforms-modals',
			justwpforms_get_plugin_url() . 'inc/assets/js/customize/modal.js',
			$deps, justwpforms_get_version(), true
		);
	}

	public function form_html_class( $class, $form ) {
		if ( justwpforms_get_form_property( $form, 'modal' ) ) {
			$class[] = 'justwpforms-form--modal';
		}

		return $class;
	}

	public function get_shortcode( $shortcode, $id ) {
		$form = $this->controller->get( $id );

		if ( justwpforms_get_form_property( $form, 'modal' ) ) {
			$shortcode = $this->get_modal_link( $id );
		}

		return $shortcode;
	}

	public function dashboard_form_fields( $fields ) {
		$fields[] = 'modal';

		return $fields;
	}

	public function get_modal_link( $id = 'ID' ) {
		$link = '<a href="#justwpforms-modal" data-form-id="' . $id . '">' . __( 'Open Form', 'justwpforms' ) . '</a>';

		return $link;
	}

	public function dashboard_data( $data ) {
		$data['modalLink'] = $this->get_modal_link();

		return $data;
	}

	public function wp_head() {
		$forms = $this->controller->get();

		if ( ! is_array( $forms ) ) {
			return;
		}

		foreach ( $forms as $form ) {
			if ( justwpforms_get_form_property( $form, 'modal' ) ) {
				$this->current_modals[] = $form;
			}
		}

		if ( ! justwpforms_is_preview() && empty( $this->current_modals ) ) {
			return;
		}

		wp_enqueue_style(
			'justwpforms-modals',
			justwpforms_get_plugin_url() . 'inc/assets/css/frontend/modal.css?',
			array(), justwpforms_get_version()
		);

		foreach( $this->current_modals as $form ) {
			justwpforms_the_modal_styles( $form );
		}

		wp_register_script(
			'justwpforms-modals',
			justwpforms_get_plugin_url() . 'inc/assets/js/frontend/modal.js',
			array( 'jquery' ), justwpforms_get_version(), true
		);

		wp_localize_script(
			'justwpforms-modals',
			'_justwpformsModalSettings',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'action' => $this->action,
			)
		);

		wp_enqueue_script( 'justwpforms-modals' );
	}

	public function modal_html_styles( $form = array() ) {
		$fields = $this->get_styles();
		$styles = array_filter( $fields, array( $this, 'is_css_var_field' ) );

		return $styles;
	}

	public function is_css_var_field( $field ) {
		return 'modal_css_var' === $field['target'];
	}

	public function modal_html_class( $form = array() ) {
		$fields = $this->get_styles();
		$class = array_filter( $fields, array( $this, 'is_class_field' ) );
		$class = array_intersect_key( $form, $class );
		$class = array_values( $class );

		return $class;
	}

	public function is_class_field( $field ) {
		return 'modal_class' === $field['target'];
	}

	public function print_footer_scripts() {
		if ( empty( $this->current_modals ) ) {
			return;
		}

		foreach( $this->current_modals as $form ) {
			require( justwpforms_get_include_folder() . '/templates/partials/form-modal.php' );
		}
	}

	public function ajax_fetch_form() {
		$html = '';

		if ( ! isset( $_GET['form_id'] ) || empty( $_GET['form_id'] ) ) {
			wp_die( $html );
		}

		$form_id = $_GET['form_id'];
		$form = $this->controller->get( $form_id );

		if ( ! $form ) {
			wp_die( $html );
		}

		$html = $this->controller->render( $form, justwpforms_Form_Assets::MODE_COMPLETE );

		wp_die( $html );
	}

	public function menu_items_search_filter_posts( $query ) {
		if ( ! isset( $_POST['action'] ) || 'menu-quick-search' !== $_POST['action'] ) {
			return $query;
		}

		$post_type = justwpforms_get_form_controller()->post_type;

		if ( ! isset( $query->query_vars['post_type'] ) || $post_type !== $query->query_vars['post_type'] ) {
			return $query;
		}

		$query->set( 'meta_key', '_justwpforms_modal' );
		$query->set( 'meta_value', 1 );
	}

	public function menu_items_box_arguments( $post_type ) {
		$post_type_name = justwpforms_get_form_controller()->post_type;

		if ( $post_type_name !== $post_type->name ) {
			return $post_type;
		}

		$post_type->_default_query['meta_key'] = '_justwpforms_modal';
		$post_type->_default_query['meta_value'] = 1;
		$post_type->labels->name = 'justwpforms';

		return $post_type;
	}

	public function setup_nav_menu_item( $item ) {
		$form_controller = justwpforms_get_form_controller();
		$post_type = $form_controller->post_type;

		if ( $post_type !== $item->object ) {
			return $item;
		}

		$form = $form_controller->get( $item->object_id );

		if ( ! $form ) {
			$item->_invalid = true;
		}

		if ( 'publish' !== $form['post_status'] ) {
			$item->_invalid = true;
		}

		$is_overlay = justwpforms_get_form_property( $form, 'modal' );
		$is_overlay = justwpforms_is_truthy( $is_overlay );

		if ( ! $is_overlay ) {
			$item->_invalid = true;
		}

		$item->url = '#justwpforms-' . $item->object_id;

		return $item;
	}

	public function customize_menu_available_items( $items, $type, $object, $page ) {
		if ( justwpforms_get_form_controller()->post_type !== $object ) {
			return $items;
		}

		$posts = get_posts( array(
			'numberposts' => 10,
			'offset' => 10 * $page,
			'orderby' => 'date',
			'order' => 'DESC',
			'post_type' => $object,
			'meta_key' => '_justwpforms_modal',
			'meta_value' => '1',
		) );

		$items = array_map( function( $post ) {
			$post_title = $post->post_title;

			if ( '' === $post_title ) {
				$post_title = sprintf( __( '#%d (no title)' ), $post->ID );
			}

			$item = array(
				'id' => "post-{$post->ID}",
				'title' => html_entity_decode( $post_title, ENT_QUOTES, get_bloginfo( 'charset' ) ),
				'type' => 'post_type',
				'type_label' => get_post_type_object( $post->post_type )->labels->singular_name,
				'object' => $post->post_type,
				'object_id' => intval( $post->ID ),
				'url' => get_permalink( intval( $post->ID ) ),
			);

			return $item;
		}, $posts );

		return $items;
	}

	public function customize_menu_available_item_types( $types ) {
		$post_type = justwpforms_get_form_controller()->post_type;

		foreach( $types as $t => $type ) {
			if ( $post_type === $type['object'] ) {
				$types[$t]['title'] = 'justwpforms';
			}
		}

		return $types;
	}

}

if ( ! function_exists( 'justwpforms_get_modals' ) ):

function justwpforms_get_modals() {
	return justwpforms_Form_Modals::instance();
}

endif;

justwpforms_get_modals();
