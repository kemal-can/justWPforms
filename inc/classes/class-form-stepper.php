<?php

class justwpforms_Form_Stepper {

	private static $instance;
	private $controller;
	private $session;
	private $steps = null;
	private $breaks = null;
	private $pages = array();
	private $frontend_styles = false;

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		self::$instance->hook();

		return self::$instance;
	}

	public function __construct() {
		$this->form_controller = justwpforms_get_form_controller();
		$this->message_controller = justwpforms_get_message_controller();
		$this->session = justwpforms_get_session();
	}

	public function hook() {
		add_action( 'justwpforms_form_open', array( $this, 'configure_rendering' ) );
		add_action( 'justwpforms_form_open', array( $this, 'render_progress_bar' ), 20, 1 );
		add_filter( 'justwpforms_get_steps', array( $this, 'get_steps' ), 10, 2 );
		add_action( 'justwpforms_step', array( $this, 'do_step' ) );
		add_filter( 'justwpforms_session_unserialize_step', array( $this, 'unserialize_session_step' ), 10, 3 );
		add_filter( 'justwpforms_style_dependencies', array( $this, 'style_dependencies' ), 10, 2 );
		add_filter( 'justwpforms_form_class', array( $this, 'form_html_class' ), 10, 2 );
		add_filter( 'justwpforms_style_controls', array( $this, 'style_controls' ) );
		add_filter( 'justwpforms_style_fields', array( $this, 'style_fields' ) );
		add_action( 'justwpforms_customize_enqueue_scripts', array( $this, 'customize_enqueue_scripts' ) );

		// Part shuffling
		add_filter( 'justwpforms_get_form_parts', array( $this, 'get_form_parts' ), 10, 2 );

		// Back-compat
		add_action( 'justwpforms_form_updated', array( $this, 'form_updated' ) );
	}

	public function configure_pages( $form ) {
		$this->breaks = justwpforms_get_page_breaks( $form );
		// Apply conditional logic
		$form = justwpforms_get_conditional_controller()->get( $form, $_REQUEST );
		$page_id = $this->breaks[0];

		foreach ( $form['parts'] as $part ) {
			$part_id = $part['id'];

			if ( 'page_break' === $part['type'] ) {
				$page_id = $part_id;
				$this->pages[$page_id] = array();
			} else {
				$this->pages[$page_id][] = $part;
			}
		}
	}

	public function is_multistep( $form ) {
		$breaks = justwpforms_get_page_breaks( $form );
		$is_multistep = count( $breaks ) > 0;

		return $is_multistep;
	}

	public function get_steps( $steps, $form ) {
		if ( ! $this->is_multistep( $form ) ) {
			return $steps;
		}

		if ( null === $this->steps ) {
			$this->configure_pages( $form );
		};

		$steps = array( 'submit' );

		if ( justwpforms_get_form_setup_upgrade()->requires_confirmation( $form ) ) {
			array_unshift( $steps, 'review' );
		}

		$this->steps = array_merge( $this->breaks, $steps );

		if ( justwpforms_upgrade_get_password_protection()->requires_password( $form ) ) {
			array_unshift( $this->steps, 'password' );
		}

		return $this->steps;
	}

	public function configure_rendering( $form ) {
		if ( ! $this->is_multistep( $form ) ) {
			return;
		}

		add_filter( 'justwpforms_get_form_attributes', array( $this, 'form_attributes' ), 10, 2 );
		add_action( 'justwpforms_part_before', array( $this, 'part_before' ), 0, 2 );
		add_action( 'justwpforms_part_after', array( $this, 'part_after' ), 10, 2 );
		add_action( 'justwpforms_parts_after', array( $this, 'parts_after' ) );
		add_filter( 'justwpforms_get_submit_template_path', array( $this, 'submit_template' ), 10, 2 );
		add_filter( 'justwpforms_form_has_captcha', array( $this, 'toggle_recaptcha' ), 10, 2 );
	}

	public function get_current_break( $form ) {
		$step = justwpforms_get_current_step( $form );
		$break = $this->get_step_break( $form, $step );

		return $break;
	}

	public function render_progress_bar( $form ) {
		if ( ! $this->is_multistep( $form ) || count( justwpforms_get_page_breaks( $form ) ) < 2 ) {
			return;
		}

		if ( justwpforms_is_last_step( $form ) ) {
			return;
		}

		require( justwpforms_get_include_folder() . '/templates/partials/form-steps-progress.php' );
	}

	private function get_break_step( $break, $form ) {
		if ( null === $this->steps ) {
			$this->configure_pages( $form );
		};

		$step = array_search( $break, $this->steps );

		return $step;
	}

	private function get_step_break( $form, $step ) {
		if ( null === $this->breaks ) {
			$this->configure_pages( $form );
		};

		$break = array_search( $step, $this->breaks );

		return $break;
	}

	private function get_part_break( $part ) {
		foreach( $this->pages as $break => $parts ) {
			$part_ids = wp_list_pluck( $parts, 'id' );

			if ( array_search( $part['id'], $part_ids ) !== false ) {
				return $break;
			}
		}

		return false;
	}

	public function form_attributes( $attrs, $form ) {
		if ( ! $this->is_multistep( $form ) ) {
			return $attrs;
		}

		$attrs['novalidate'] = 'true';
		$break = $this->get_current_break( $form );

		if ( false !== $break ) {
			$attrs['data-justwpforms-break'] = "{$break}";
		}

		return $attrs;
	}

	public function part_before( $part, $form ) {
		if ( ! $this->is_multistep( $form ) ) {
			return;
		}

		if ( 'page_break' !== $part['type'] ) {
			return;
		}

		if ( justwpforms_is_preview() ) {
			return;
		}

		$current_step = justwpforms_get_current_step( $form );

		if ( in_array( $current_step, $this->breaks ) ) {
			$this->part_before_edit( $part, $form );
		} else if ( 'review' === $current_step ) {
			$this->part_before_review( $part, $form );
		}
	}

	private function part_before_edit( $part, $form ) {
		if ( justwpforms_get_previous_part( $part, $form ) ) : ?>
		</div>
		<?php endif;

		if ( $part['id'] === justwpforms_get_current_step( $form ) ) : ?>
		<div class="justwpforms-step">
		<?php else : ?>
		<div class="justwpforms-step" style="display: none;">
		<?php endif;
	}

	private function part_before_review( $part, $form ) {
		if ( justwpforms_is_preview() ) {
			return;
		}

		if ( justwpforms_get_previous_part( $part, $form ) ) : ?>
		</div>
		<?php endif; ?>
		<div class="justwpforms-step-preview">
		<?php
	}

	public function part_after( $part, $form ) {
		if ( ! $this->is_multistep( $form ) ) {
			return;
		}

		if ( 'review' !== justwpforms_get_current_step( $form ) ) {
			return;
		}

		$next_part = justwpforms_get_next_part( $part, $form );

		if ( false !== $next_part && 'page_break' !== $next_part['type']  ) {
			return;
		}

		$break = $this->get_part_break( $part );
		$step = $this->get_break_step( $break, $form );
		?>
		<button data-step="-<?php echo $step; ?>" class="submit justwpforms-submit justwpforms-button--submit justwpforms-button--edit"><?php echo $form['edit_button_label']; ?></button>
		<?php
	}

	public function parts_after( $form ) {
		if ( ! $this->is_multistep( $form ) ) {
			return;
		}

		if ( justwpforms_is_preview() ) {
			return;
		}
		?></div><?php
	}

	public function submit_template( $path, $form ) {
		if ( justwpforms_is_preview() ) {
			return $path;
		}

		if ( ! $this->is_multistep( $form ) ) {
			return $path;
		}

		$current_step = justwpforms_get_current_step( $form );

		if ( in_array( $current_step, $this->breaks ) ) {
			$path = justwpforms_get_include_folder() . '/templates/partials/form-submit-stepper.php';
		} else if ( justwpforms_get_form_setup_upgrade()->requires_confirmation( $form ) ) {
			$path = justwpforms_get_include_folder() . '/templates/partials/form-submit-stepper-review.php';
		}

		return $path;
	}

	public function do_step( $form ) {
		if ( ! $this->is_multistep( $form ) ) {
			return;
		}

		$current_step = justwpforms_get_current_step( $form );

		if ( '-' === substr( $this->session->current_step( true ), 0, 1 ) ) {
			$this->step_back( $form );
		} else if ( in_array( $current_step, $this->breaks ) ) {
			$this->step_forward( $form );
		}
	}

	private function step_back( $form ) {
		$current_step = - intval( $this->session->current_step() );
		$current_step = max( 0, min( $current_step, count( $this->breaks ) - 1 ) );
		$this->session->set_step( $current_step );

		// Let current form status pass through as-is
		$this->pass_through_step( $form, $_REQUEST );

		// Render the form
		$response = array();
		$response['html'] = $this->form_controller->render( $form );

		// Send success response
		$this->message_controller->send_json_success( $response, array(), $form );
	}

	private function step_forward( $form ) {
		$current_step = justwpforms_get_current_step( $form );
		$form_id = $form['ID'];
		$submission = $this->validate_step( $form, $_REQUEST );
		$response = array();

		if ( false === $submission ) {
			// Add a general error notice at the top
			$this->session->add_error( $form_id, html_entity_decode( $form['error_message'] ) );

			// Render the form
			$response['html'] = $this->form_controller->render( $form );

			// Send error response
			wp_send_json_error( $response );
		} else {
			// Advance step
			$this->session->next_step();

			// Trigger default submit action if
			// it's the last step
			if ( justwpforms_is_last_step( $form ) ) {
				do_action( 'justwpforms_step', $form );
				return;
			}

			$current_step = justwpforms_get_current_step( $form );

			if ( ! in_array( $current_step, $this->breaks ) ) {
				$form = justwpforms_get_conditional_controller()->get( $form, $_REQUEST );
			}

			// Render the form
			$response['html'] = $this->form_controller->render( $form );

			// Send success response
			$this->message_controller->send_json_success( $response, $submission, $form );
		}
	}

	public function validate_step( $form, $request = array() ) {
		$submission = array();
		$is_valid = true;
		$current_step = justwpforms_get_current_step( $form, true );
		$first_break = $this->breaks[0];
		$validation_step = $this->get_break_step( $first_break, $form );

		foreach( $this->pages as $page => $parts ) {
			$page_is_valid = true;

			foreach( $parts as $part ) {
				$part_id = $part['id'];
				$validated_value = $this->message_controller->validate_part( $form, $part, $request );

				if ( false !== $validated_value ) {
					$string_value = justwpforms_stringify_part_value( $validated_value, $part, $form );
					$submission[$part_id] = $string_value;
				} else {
					$page_is_valid = false;

					// Remove any notices from "future" steps
					// which were already filled, just in case.
					if ( $validation_step > $current_step ) {
						$part_name = justwpforms_get_part_name( $part, $form );
						$this->session->remove_error( $part_name );
					}
				}
			}

			// Stop at the latest, invalid step.
			if ( ! $page_is_valid && $validation_step <= $current_step ) {
				$is_valid = false;
				$current_step = $validation_step;
				$this->session->set_step( $validation_step );
			}

			$validation_step ++;
		}

		return $is_valid ? $submission : false;
	}

	public function pass_through_step( $form, $request = array() ) {
		foreach( $this->pages as $page => $parts ) {
			foreach( $parts as $part ) {
				$part_class = justwpforms_get_part_library()->get_part( $part['type'] );
				$part_name = justwpforms_get_part_name( $part, $form );
				$sanitized_value = $part_class->sanitize_value( $part, $form, $request );
				$this->session->add_value( $part_name, $sanitized_value );
			}
		}
	}

	public function unserialize_session_step( $apply_step, $data, $form ) {
		if ( ! $this->is_multistep( $form ) ) {
			return $apply_step;
		}

		if ( justwpforms_is_stepping() ) {
			return $apply_step;
		}

		justwpforms_get_steps( $form );
		$step = $this->steps[$data['step']];
		$break = $this->get_step_break( $form, $step );
		$apply_step = false !== $break;

		return $apply_step;
	}

	public function toggle_recaptcha( $has_recaptcha, $form ) {
		if ( justwpforms_is_preview() ) {
			return $has_recaptcha;
		}

		if ( ! $this->is_multistep( $form ) ) {
			return $has_recaptcha;
		}

		$step = justwpforms_get_current_step( $form );
		$is_second_last = $this->steps[count( $this->steps ) - 2] === $step;

		return $has_recaptcha && $is_second_last;
	}

	public function customize_enqueue_scripts( $deps ) {
		wp_enqueue_script(
			'justwpforms-progress-bar',
			justwpforms_get_plugin_url() . 'inc/assets/js/customize/progress-bar.js',
			$deps, justwpforms_get_version(), true
		);

		wp_localize_script(
			'justwpforms-progress-bar',
			'_justwpformsProgressBarSettings',
			array(
				'i18n' => array(
					'first_label' => __( '', 'justwpforms' )
				)
			)
		);
	}

	public function style_fields( $fields ) {
		$fields = array_merge( $fields, $this->get_styles() );

		return $fields;
	}

	public function get_styles() {
		$styles = array(
			'color_multistep_info_text_color' => array(
				'default' => '#000000',
				'target' => 'css_var',
				'variable' => '--justwpforms-color-multistep-info-text-color',
				'sanitize' => 'sanitize_text_field',
			),
			'color_multistep_back_link' => array(
				'default' => '#000000',
				'target' => 'css_var',
				'variable' => '--justwpforms-color-multistep-info-back-color',
				'sanitize' => 'sanitize_text_field'
			),
			'color_multistep_back_link_hover' => array(
				'default' => '#000000',
				'target' => 'css_var',
				'variable' => '--justwpforms-color-multistep-info-back-color-hover',
				'sanitize' => 'sanitize_text_field'
			),
		);

		return $styles;
	}

	public function style_controls( $controls ) {
		$style_controls = array(
			9020 => array(
				'type' => 'divider',
				'id' => 'multistep',
				'label' => __( 'Multi Step', 'justwpforms' )
			),
			9022 => array(
				'type' => 'color',
				'label' => __( 'Text', 'justwpforms' ),
				'field' => 'color_multistep_info_text_color',
			),
			9023 => array(
				'type' => 'color',
				'label' => __( 'Back link', 'justwpforms' ),
				'field' => 'color_multistep_back_link'
			),
			9024 => array(
				'type' => 'color',
				'label' => __( 'Back link on focus', 'justwpforms' ),
				'field' => 'color_multistep_back_link_hover'
			),
		);

		$controls = justwpforms_safe_array_merge( $controls, $style_controls );

		return $controls;
	}

	public function style_dependencies( $deps, $forms ) {
		$is_multistep = false;
		$form_controller = justwpforms_get_form_controller();

		foreach ( $forms as $form ) {
			if ( $this->is_multistep( $form ) ) {
				$is_multistep = true;
				break;
			}
		}

		if ( ! justwpforms_is_preview() && ! $is_multistep ) {
			return $deps;
		}

		wp_register_style(
			'justwpforms-steps',
			justwpforms_get_plugin_url() . 'inc/assets/css/frontend/steps.css',
			array(), justwpforms_get_version()
		);

		$deps[] = 'justwpforms-steps';

		return $deps;
	}

	public function form_html_class( $class, $form ) {
		if ( $this->is_multistep( $form ) ) {
			$class[] = 'justwpforms-form--multistep';
		}

		return $class;
	}

	public function get_form_parts( $parts, $form ) {
		if ( is_customize_preview() ) {
			return $parts;
		}

		if ( ! justwpforms_get_form_property( $form, 'shuffle_parts' ) ) {
			return $parts;
		}

		if ( ! $this->is_multistep( $form ) ) {
			return $parts;
		}

		$shuffled = array();
		$shuffler = justwpforms_upgrade_get_shuffle_parts();

		foreach ( $parts as $part ) {
			if ( 'page_break' !== $part['type'] ) {
				continue;
			}

			$part_id = $part['id'];
			$shuffled[] = $part;
			$page_parts = $shuffler->shuffle_form_parts( $this->pages[$part_id] );

			foreach( $page_parts as $page_part ) {
				$shuffled[] = $page_part;
			}
		}

		return $shuffled;
	}

	public function form_updated( $form ) {
		if ( justwpforms_meta_exists( $form['ID'], 'next_button_label' ) ) {
			delete_post_meta( $form['ID'], '_justwpforms_next_button_label' );
		}
	}

}

if ( ! function_exists( 'justwpforms_get_stepper' ) ) :

function justwpforms_get_stepper() {
	return justwpforms_Form_Stepper::instance();
}

endif;

justwpforms_get_stepper();
