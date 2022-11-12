<?php

class justwpforms_Conditional_Controller {

	private static $instance;

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		self::$instance->hook();

		return self::$instance;
	}

	public function hook() {
		add_filter( 'justwpforms_meta_fields', array( $this, 'meta_fields' ) );
		add_filter( 'justwpforms_get_form_attributes', array( $this, 'form_attributes' ), 10, 2 );
		add_filter( 'justwpforms_get_form_data', array( $this, 'get_form_data' ) );
		add_action( 'justwpforms_print_frontend_styles', array( $this, 'print_styles' ) );
		add_filter( 'justwpforms_frontend_dependencies', array( $this, 'script_dependencies' ), 10, 2 );
		add_filter( 'justwpforms_frontend_settings', array( $this, 'frontend_settings' ), 10, 2 );
	}

	public function get_fields() {
		$fields = array(
			'conditions' => array(
				'default' => array(),
				'sanitize' => array( $this, 'sanitize_conditions' ),
			),
		);

		return $fields;
	}

	public function meta_fields( $fields ) {
		$fields = array_merge( $fields, $this->get_fields() );

		return $fields;
	}

	public function sanitize_conditions( $conditions ) {
		foreach ( $conditions as $key => $condition ) {
			$if = array_filter( $condition['if'], function( $if ) {
				return ( ! empty( $if['key'] ) && ! is_null( $if['comparison'] ) );
			} );

			$conditions[$key]['if'] = $if;

			if ( empty( $if ) ) {
				unset( $conditions[$key] );
			}
		}

		return $conditions;
	}

	public function has_conditions( $form ) {
		$conditions = justwpforms_get_form_property( $form, 'conditions' );
		$has_conditions = ! empty( $conditions );

		return $has_conditions;
	}

	public function get( $form, $response ) {
		$conditions = justwpforms_get_form_property( $form, 'conditions' );

		if ( empty( $conditions ) ) {
			return $form;
		}

		foreach( $conditions as $condition ) {
			$condition = justwpforms_Condition::from_array( $condition );
			$form = $this->apply_condition( $condition, $response, $form );
		}

		$form['parts'] = array_filter( $form['parts'], function( $part ) {
			$hidden = isset( $part['hidden'] ) && $part['hidden'];
			return ! $hidden;
		} );

		foreach( $form['parts'] as $p => $part ) {
			if ( ! in_array( $part['type'], array( 'radio', 'checkbox', 'select' ) ) ) {
				continue;
			}

			$form['parts'][$p]['options'] = array();

			foreach( $part['options'] as $o => $option ) {
				$hidden = isset( $option['hidden'] ) && $option['hidden'];

				if ( $hidden ) {
					continue;
				}

				$form['parts'][$p]['options'][$o] = $option;
			}
		}

		return $form;
	}

	public function apply_condition( $condition, $response, $form ) {
		$condition->evaluate( $response, $form );
		$callback = $condition->get_callback();

		$form = call_user_func_array(
			array( $this, $callback ),
			array( $form, $condition->result, $condition->key, $condition->args )
		);

		return $form;
	}

	public function show( $form, $result, $key, $args ) {
		$form['parts'] = array_map( function( $part ) use( $key, $result ) {
			if ( $part['id'] === $key ) {
				if ( $result ) {
					$part['hidden'] = false;
				} else if ( ! $result && ! isset( $part['hidden'] ) ) {
					$part['hidden'] = true;
				}
			}

			return $part;
		}, $form['parts'] );

		return $form;
	}

	public function hide( $form, $result, $key, $args ) {
		$form['parts'] = array_map( function( $part ) use( $key, $result ) {
			if ( $part['id'] === $key && $result ) {
				$part['hidden'] = true;
			}

			return $part;
		}, $form['parts'] );

		return $form;
	}

	public function show_option( $form, $result, $key, $args ) {
		foreach( $form['parts'] as $p => $part ) {
			if ( ! in_array( $part['type'], array( 'radio', 'checkbox', 'select' ) ) ) {
				continue;
			}

			$form['parts'][$p]['options'] = array_map( function( $option ) use( $key, $result ) {
				if ( $option['id'] === $key ) {
					if ( $result ) {
						$option['hidden'] = false;
					} else if ( ! $result && ! isset( $option['hidden'] ) ) {
						$option['hidden'] = true;
					}
				}

				return $option;
			}, $part['options'] );
		}

		return $form;
	}

	public function hide_option( $form, $result, $key, $args ) {
		foreach( $form['parts'] as $p => $part ) {
			if ( ! in_array( $part['type'], array( 'radio', 'checkbox', 'select' ) ) ) {
				continue;
			}

			$form['parts'][$p]['options'] = array_map( function( $option ) use( $key, $result ) {
				if ( $option['id'] === $key && $result ) {
					$option['hidden'] = true;
				}

				return $option;
			}, $part['options'] );
		}

		return $form;
	}

	public function set( $form, $result, $key, $args ) {
		if ( ! $result ) {
			return $form;
		}

		$value = ! empty( $args ) ? $args[0] : '';

		if ( isset( $form[$key] ) ) {
			// Form field
			$form[$key] = $value;
		} else {
			$components = explode( ':', $key );
			// Part field
			$part_id = ! empty( $components ) ? $components[0] : '';
			$field = 1 < count( $components ) ? $components[1] : '';
			$part_ids = wp_list_pluck( $form['parts'], 'id' );
			$part_index = array_search( $part_id, $part_ids );

			if ( false === $part_index ) {
				return $form;
			}

			$part = $form['parts'][$part_index];

			if ( ! isset( $part[$field] ) ) {
				return $form;
			}

			$part[$field] = $value;
			$form['parts'][$part_index] = $part;
		}

		return $form;
	}

	public function form_attributes( $attrs, $form ) {
		$step = justwpforms_get_current_step( $form );

		if ( 'review' === $step ) {
			return $attrs;
		}

		if ( $this->has_conditions( $form ) || justwpforms_is_preview() ) {
			$attrs['data-justwpforms-conditionable'] = '';
		}

 		return $attrs;
	}

	public function get_form_data( $form ) {
		if ( isset( $form['conditions'] ) ) {
			$form['conditions'] = array_values( $form['conditions'] );
		}

		return $form;
	}

	public function print_styles( $form ) {
		if ( ! $this->has_conditions( $form ) ) {
			return;
		}

		$conditions = justwpforms_get_form_property( $form, 'conditions' );
		$thens = wp_list_pluck( $conditions, 'then' );
		$thens = array_filter( $thens, function( $then ) {
			$show_hide = in_array( $then['cb'], array( 'show', 'show_option' ) );
			return $show_hide;
		} );

		if ( empty( $thens ) ) {
			return;
		}

		$grouped_thens = array();

		foreach( $thens as $then ) {
			$grouped_thens[$then['key']][] = $then;
		}

		$thens = array_map( function( $then ) {
			return $then[0];
		}, array_values( $grouped_thens ) );

		?>
		<style type="text/css" id="justwpforms-conditional-styles">
		<?php foreach( $thens as $then ) {
			if ( 'show' === $then['cb'] ) {
				$part_id = justwpforms_get_part_id( $then['key'], $form['ID'] );
				echo "#{$part_id}-part { display: none; }\n";	
			} else if ( 'show_option' === $then['cb'] ) {
				$form_id = justwpforms_get_form_id( $form );
				$option_id = $then['key'];
				echo "#{$form_id} #{$option_id}:not(option):not(optgroup) { display: none; }\n";	
			}
		} ?>
		</style>
		<?php
	}

	private function get_frontend_conditions( $forms ) {
		$conditions = wp_list_pluck( $forms, 'conditions', 'ID' );

		foreach( $conditions as $form_id => $form_conditions ) {
			$conditions[$form_id] = $this->filter_frontend_conditions( $form_conditions );
		}

		return $conditions;
	}

	public function filter_frontend_conditions( &$conditions ) {
		$meta = justwpforms_get_form_controller()->get_fields();
		$conditions = array_filter( $conditions, function( $condition ) use( $meta ) {
			$key = $condition['then']['key'];
			$is_public = ! isset( $meta[$key] );

			return $is_public;
		} );
		$conditions = array_values( $conditions );

		return $conditions;
	}

	public function script_dependencies( $deps, $forms ) {
		$forms_with_conditions = array_filter( $forms, array( $this, 'has_conditions' ) );

 		if ( ! justwpforms_is_preview() && empty( $forms_with_conditions ) ) {
			return $deps;
		}

		wp_register_script(
			'justwpforms-conditionals',
			justwpforms_get_plugin_url() . 'inc/assets/js/frontend/conditionals.js',
			array(), justwpforms_get_version(), true
		);

		$deps[] = 'justwpforms-conditionals';

		return $deps;
	}

	public function frontend_settings( $settings, $forms ) {
		$constants = justwpforms_Condition::get_constants();
		$conditions = $this->get_frontend_conditions( $forms );
		$chain_length = apply_filters( 'justwpforms_conditional_logic_chain_length', 5 );

		$settings['conditionals'] = array(
			'constants' => $constants,
			'conditions' => $conditions,
			'chainLength' => $chain_length,
		);

		return $settings;
	}

}

if ( ! function_exists( 'justwpforms_get_conditional_controller' ) ):

function justwpforms_get_conditional_controller() {
	return justwpforms_Conditional_Controller::instance();
}

endif;

justwpforms_get_conditional_controller();
