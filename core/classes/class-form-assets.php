<?php

class justwpforms_Form_Assets {

	private static $instance;
	private static $hooked = false;

	const MODE_NONE = 0;
	const MODE_ADMIN = 1;
	const MODE_BLOCK = 2;
	const MODE_CUSTOMIZER = 4;
	const MODE_COMPLETE = 8;

	private $forms = array();

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

		add_filter( 'justwpforms_frontend_dependencies', array( $this, 'frontend_dependencies' ) );
		add_action( 'wp_print_footer_scripts', array( $this, 'wp_print_footer_scripts' ), 0 );
		add_action( 'elementor/theme/after_do_popup', array( $this, 'elementor_popup_compatibility' ) );
	}

	public function output_frontend_styles( $form ) {
		$output = apply_filters( 'justwpforms_enqueue_style', true );

		if ( ! $output ) {
			return;
		}

		justwpforms_the_form_styles( $form );
		justwpforms_additional_css( $form );
	}

	public function output_admin_styles( $form ) {
		?>
		<link rel="stylesheet" type="text/css" href="<?php echo justwpforms_get_plugin_url() . 'core/assets/css/no-interaction.css'; ?>">
		<?php
	}

	private function get_frontend_script_url() {
		$script_url = justwpforms_get_plugin_url() . 'bundles/js/frontend.js';
		
		if ( ! justwpforms_concatenate_scripts() ) {
			$script_url = justwpforms_get_plugin_url() . 'inc/assets/js/frontend.js';
		}

		return $script_url;
	}

	private function get_frontend_script_dependencies( $forms ) {
		wp_register_script( 'justwpforms-settings', '', array(), justwpforms_get_version(), true );

		$settings = apply_filters( 'justwpforms_frontend_settings', array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' )
		), $forms );

		wp_localize_script( 'justwpforms-settings', '_justwpformsSettings', $settings );

		$dependencies = array( 'jquery', 'justwpforms-settings' );

		if ( ! justwpforms_concatenate_scripts() ) {
			$dependencies = apply_filters(
				'justwpforms_frontend_dependencies',
				$dependencies, $forms
			);
		}

		return $dependencies;
	}

	public function output_frontend_scripts( $form ) {
		if ( wp_doing_ajax() ) {
			global $wp_scripts;

			add_filter( 'script_loader_tag', function( $tag, $handle, $src ) {
				return '';
			}, 10, 3 );

			$dependencies = $this->get_frontend_script_dependencies( [ $form ] );
			$script_url = $this->get_frontend_script_url();

			wp_register_script(
				'justwpforms-frontend',
				$script_url,
				$dependencies, justwpforms_get_version(), true
			);

			wp_scripts()->all_deps( 'justwpforms-frontend' );

			$queue = array();

			foreach ( wp_scripts()->to_do as $handle ) {
				$dep = wp_scripts()->registered[ $handle ];

				if ( $dep->src ) {
					$queue[] = array(
						'id' => $handle,
						'src' => $dep->src
					);
				}

				wp_scripts()->print_extra_script( $handle );
			}

			wp_scripts()->print_extra_script( 'justwpforms-frontend' );
			?>
			<script type="text/javascript">
				var queue = <?php echo json_encode( $queue ); ?>;
				var loaded = 0;

				queue = queue.filter( function( item ) {
					if ( document.querySelector( 'script[id="' + item.id + '-js"]' ) ) {
						return false;
					}

					return true;
				} );

				function enqueueNextScript() {
					if ( queue.length === 0 ) {
						jQuery( '.justwpforms-form' ).justwpform();
						return;
					}

					var item = queue.shift();
					var script = document.createElement( 'script' );
					script.id = item.id + '-js';
					script.src = item.src;

					script.addEventListener( 'load', function() {
						enqueueNextScript();
					} );

					document.body.appendChild( script );
				}

				enqueueNextScript();
			</script>
			<?php

			do_action( 'justwpforms_print_scripts', [ $form ] );
		} else {
			$this->forms[] = $form;
		}
	}

	public function output( $form, $mode = self::MODE_COMPLETE ) {
		switch( $mode ) {
			case self::MODE_NONE:
				break;
			case self::MODE_ADMIN:
			case self::MODE_BLOCK:
				$this->output_frontend_styles( $form );
				$this->output_admin_styles( $form );
				break;
			case self::MODE_CUSTOMIZER:
				$this->output_frontend_styles( $form );
				$this->output_admin_styles( $form );
				$this->output_frontend_scripts( $form );
				break;
			case self::MODE_COMPLETE:
				$this->output_frontend_styles( $form );
				$this->output_frontend_scripts( $form );
				break;
		}
	}

	public function print_frontend_styles( $form ) {
		if ( ! justwpforms_concatenate_styles() ) {
			wp_register_style(
				'justwpforms-color',
				justwpforms_get_frontend_stylesheet_url( 'color.css' ),
				array(), justwpforms_get_version()
			);

			$dependencies = apply_filters(
				'justwpforms_style_dependencies',
				array( 'justwpforms-color' ), [ $form ]
			);

			wp_register_style(
				'justwpforms-layout',
				justwpforms_get_frontend_stylesheet_url( 'layout.css' ),
				$dependencies, justwpforms_get_version()
			);

			$dependencies[] = 'justwpforms-layout';

			global $wp_styles;

			foreach( $dependencies as $dependency ) {
				if ( isset( $wp_styles->registered[$dependency] ) ) {
					$stylesheet_url = $wp_styles->registered[$dependency]->src;
					?><link rel="stylesheet" property="stylesheet" href="<?php echo $stylesheet_url; ?>" /><?php
				}
			}
		} else {
			?>
			<link rel="stylesheet" property="stylesheet" href="<?php echo justwpforms_get_plugin_url() . 'bundles/css/frontend.css'; ?>" />
			<?php
		}

		do_action( 'justwpforms_print_frontend_styles', $form );
	}

	public function frontend_dependencies( $deps ) {
		wp_register_script(
			'justwpforms-md5',
			justwpforms_get_plugin_url() . 'core/assets/js/lib/md5.min.js',
			array(), justwpforms_get_version(), true
		);

		wp_register_script(
			'justwpforms-antispam',
			justwpforms_get_plugin_url() . 'core/assets/js/frontend/antispam.js',
			array( 'justwpforms-md5' ), justwpforms_get_version(), true
		);

		$deps[] = 'justwpforms-antispam';

		return $deps;
	}

	public function wp_print_footer_scripts() {
		if ( empty( $this->forms ) ) {
			return;
		}

		$dependencies = $this->get_frontend_script_dependencies( $this->forms );
		$script_url = $this->get_frontend_script_url();

		wp_enqueue_script(
			'justwpforms-frontend',
			$script_url,
			$dependencies, justwpforms_get_version(), true
		);
		
		do_action( 'justwpforms_print_scripts', $this->forms );
	}

	public function elementor_popup_compatibility() {
	?>
	<script type="text/javascript">
	( function( $ ) {
		$( function() {
	        $( document ).on( 'elementor/popup/show', () => {
	            if ( $.fn.justwpform ) {
					$( '.justwpforms-form' ).justwpform();
				}
	        } );
	    } );
	} )( jQuery );
	</script>
	<?php
	}

}

if ( ! function_exists( 'justwpforms_get_form_assets' ) ):

function justwpforms_get_form_assets() {
	return justwpforms_Form_Assets::instance();
}

endif;

justwpforms_get_form_assets();
