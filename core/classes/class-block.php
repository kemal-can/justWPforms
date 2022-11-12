<?php

class justwpforms_Block {

	/**
	 * The singleton instance.
	 *
	 * @var justwpforms_Block
	 */
	private static $instance;

	/**
	 * The singleton constructor.
	 *
	 * @return justwpforms_Block
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		self::$instance->hook();

		return self::$instance;
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function hook() {
		add_action( 'init', array( $this, 'register' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_scripts' ) );
	}

	private function get_attributes() {
		$attributes = array(
			'id' => array(
				'type' => 'string',
			),
			'anchor' => array(
				'type' => 'string',
			)
		);

		return $attributes;
	}

	private function get_properties() {
		$properties = array(
			'title' => __( 'Forms', 'justwpforms' ),
			'description' => __( 'Displays a form.', 'justwpforms' ),
			'category' => 'widgets',
			'icon' => 'feedback',
			'keywords' => array(
				'form', 'contact', 'email',
			),
		);

		return $properties;
	}

	public function register() {
		register_block_type( 'thethemefoundry/justwpforms', array(
			'attributes' => $this->get_attributes(),
			'editor_script' => 'justwpforms-block',
			'render_callback' => array( $this, 'render' ),
		) );
	}

	public function render( $attrs ) {
		$block_classes = isset( $attrs['className'] ) ? trim( $attrs['className'] ) : '';
		$html_id = isset( $attrs['anchor'] ) ? trim( $attrs['anchor'] ) : '';

		if ( isset( $attrs['id'] ) && empty( $attrs['id'] ) ) {
			return;
		}

		if ( '' !== $block_classes ) {
			$block_classes = explode( ' ', $block_classes );

			add_filter( 'justwpforms_form_class', function( $class, $form ) use ( $block_classes ) {
				$class = array_merge( $class, $block_classes );
				return $class;
			}, 10, 2 );
		}

		if ( '' !== $html_id ) {
			add_filter( 'justwpforms_form_id', function( $id ) use ( $html_id ) {
				return $html_id;
			}, 10, 2 );
		}

		return justwpforms()->handle_shortcode( $attrs );
	}

	public function enqueue_scripts() {
		$asset_file = require( justwpforms_get_core_folder() . '/assets/jsx/build/admin/block.asset.php' );

		wp_enqueue_script(
			'justwpforms-block',
			justwpforms_get_plugin_url() . 'core/assets/jsx/build/admin/block.js',
			$asset_file['dependencies']
		);

		$forms = justwpforms_get_form_controller()->get();
		$forms = array_values( wp_list_filter( $forms, array( 'post_status' => 'publish' ) ) );
		$forms = array_map( function ( $form ) {
			return array(
				'ID' => $form['ID'],
				'post_title' => ( justwpforms_get_form_title( $form ) )
			);
		}, $forms );
		$block_properties = $this->get_properties();
		$data = array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'forms' => $forms,
			'block' => $block_properties,
		);

		wp_localize_script( 'justwpforms-block', '_justwpformsBlockSettings', $data );
	}

}

if ( ! function_exists( 'justwpforms_get_block' ) ):
/**
 * Get the justwpforms_Block class instance.
 *
 * @return justwpforms_Block
 */
function justwpforms_get_block() {
	return justwpforms_Block::instance();
}

endif;

/**
 * Initialize the justwpforms_Block class immediately.
 */
justwpforms_get_block();
