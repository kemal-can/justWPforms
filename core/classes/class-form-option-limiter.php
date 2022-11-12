<?php
class justwpforms_Form_Option_Limiter {

	private static $instance;

	private $counter_key = 'counter_choices';

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		self::$instance->hook();

		return self::$instance;
	}

	public function hook() {
		add_filter( 'justwpforms_part_options', array( $this, 'get_part_options' ), 10, 3 );
		add_action( 'justwpforms_submission_success', array( $this, 'submission_success' ), 10, 3 );
		add_action( 'justwpforms_form_duplicated', array( $this, 'form_duplicated' ) );
		add_filter( 'justwpforms_get_form_data', array( $this, 'transition_deprecated_limit_submissions' ), 99 );
	}

	public function transition_deprecated_limit_submissions( $form ) {
		// Transition to meta-based counters.
		$this->try_migrate_limit_count_options( $form );

		// Zero-out limits if deprecated limit_submissions control is off.
		$supported_parts = $this->get_supported_parts();

		foreach( $form['parts'] as $p => $part ) {
			if ( ! in_array( $part['type'], $supported_parts ) ) {
				continue;
			}

			$options_key = 'options';

			if ( 'table' === $part['type'] ) {
				$options_key = 'columns';
			}

			foreach( $part[$options_key] as $o => $option ) {
				if ( isset( $option['limit_submissions'] ) && '' == $option['limit_submissions'] ) {
					$form['parts'][$p][$options_key][$o]['limit_submissions_amount'] = '';
				}
			}
		}

		return $form;
	}

	public function get_option_fields() {
		$defaults = array(
			'limit_submissions_amount' => '',
			'submissions_left' => 0,
			'submissions_left_label' => '',
		);

		return $defaults;
	}


	public function get_part_options( $options, $part, $form ) {
		$option_defaults = $this->get_option_fields();
		$part_id = $part['id'];

		// Calculate remaining choices
		$options = array_map( function( $option ) use( $option_defaults, $form, $part_id ) {
			$option = wp_parse_args( $option, $option_defaults );

			if ( '' == $option['limit_submissions_amount'] || $option['limit_submissions_amount']  < 0 )  {
				return $option;
			}

			$limit = intval( $option['limit_submissions_amount'] );
			$count = $this->count_by_option( $form['ID'], $part_id, $option['id'] );
			$option['submissions_left'] = $limit - $count;

			$submissions_left_label = $form['submissions_left_label'];

			if ( justwpforms_is_preview() ) {
				$submissions_left_label = sprintf(
					'<span class="justwpforms-submissions-left">%s</span>',
					$submissions_left_label
				);
			}

			$submissions_left = sprintf(
				' <span class="justwpforms-remaining-choice">(%1$s %2$s)</span>',
				$option['submissions_left'], $submissions_left_label
			);

			$option['submissions_left_label'] = $submissions_left;

			return $option;
		}, $options );

		return $options;
	}

	public function submission_success( $submission, $form, $message ) {
		$meta_counters = justwpforms_get_meta( $form['ID'], $this->counter_key, true );

		if ( empty( $meta_counters ) ) {
			$meta_counters = [];
		}

		foreach( $form['parts'] as $part ) {
			if ( ! in_array( $part['type'], $this->get_supported_parts() ) ) {
				continue;
			}

			$options_key = 'options';

			if ( 'table' === $part['type'] ) {
				$options_key = 'columns';
			}

			foreach( $part[$options_key] as $o => $option ) {
				$option = wp_parse_args( $option, $this->get_option_fields() );

				if ( '' == $option['limit_submissions_amount'] ) {
					continue;
				}

				$part_name = justwpforms_get_part_name( $part, $form );

				if ( ! isset( $_REQUEST[$part_name] ) ) {
					continue;
				}

				$request_value = $_REQUEST[$part_name];

				if ( ! is_array( $request_value ) ) {
					$request_value = array( $request_value );
				}

				foreach( $request_value as $submitted_value ) {
					if ( ! is_array( $submitted_value ) ) {
						$submitted_value = array( $submitted_value );
					}

					// Filter out "other" options
					$submitted_value = array_filter( $submitted_value, 'is_numeric' );
					$submitted_value = array_map( 'intval', $submitted_value );

					if ( ! in_array( $o, $submitted_value ) ) {
						continue;
					}

					$choice_id = $this->get_counter_choice_key( $part['id'], $option['id'] );
					$count = 1;

					if ( isset( $meta_counters[ $choice_id ] ) ) {
						$count = $meta_counters[ $choice_id ] + 1;
					}

					$meta_counters[ $choice_id ] = $count;
				}
			}
		}

		justwpforms_update_meta( $form['ID'], $this->counter_key, $meta_counters );
	}

	public function form_duplicated( $form ) {
		if ( justwpforms_meta_exists( $form['ID'], $this->counter_key ) ) {
			justwpforms_delete_meta( $form['ID'], $this->counter_key );
		}
	}

	public function count_by_option( $form_id, $part_id, $option_id ) {
		$count = 0;
		$counters = justwpforms_get_meta( $form_id, $this->counter_key, true );
		$choice_id = $this->get_counter_choice_key( $part_id, $option_id );

		if ( isset( $counters[ $choice_id ] ) ) {
			$count = $counters[ $choice_id ];
		}

		return $count;
	}

	public function get_counter_choice_key( $part_id, $option_id ) {
		return $part_id . '_' . $option_id;
	}

	public function get_supported_parts() {
		$parts = array(
			'radio',
			'checkbox',
			'select',
		);

		$parts = apply_filters( 'justwpforms_limited_options_supported_parts', $parts );

		return $parts;
	}

	public function try_migrate_limit_count_options( $form ) {
		$form_id = $form['ID'];

		if ( 0 == $form_id || justwpforms_meta_exists( $form_id, $this->counter_key ) ) {
			return;
		}

		$parts_with_limits = [];

		foreach( $form['parts'] as $part ) {
			if ( ! in_array( $part['type'], $this->get_supported_parts() ) ) {
				continue;
			}

			$options_key = 'options';

			if ( 'table' === $part['type'] ) {
				$options_key = 'columns';
			}

			foreach( $part[$options_key] as $o => $option ) {
				$option = wp_parse_args( $option, $this->get_option_fields() );

				if ( '' == $option['limit_submissions_amount'] ) {
					continue;
				}

				$part_name = justwpforms_get_part_name( $part, $form );

				if ( ! isset( $parts_with_limits[ $part_name ] ) ) {
					$parts_with_limits[ $part_name ] = [
						'id' => $part['id'],
						'options' => [],
					];
				}

				$parts_with_limits[ $part_name ]['options'][ $option['id'] ] = $o;
			}
		}

		if ( empty ( $parts_with_limits ) ) {
			return;
		}

		$meta_counters = [];
		$message_controller = justwpforms_get_message_controller();

		global $wpdb;

		$messages = $wpdb->get_results( $wpdb->prepare( "
			SELECT f.meta_value 
			FROM $wpdb->postmeta f
			JOIN $wpdb->postmeta p ON f.post_id = p.post_id
			WHERE p.meta_key = '_justwpforms_form_id' 
			AND p.meta_value = %d
			AND f.meta_key = '_justwpforms_request';
		", $form_id ), ARRAY_A );
		
		$messages = array_map( function( $message ) {
			return maybe_unserialize( $message['meta_value'] );
		}, $messages );

		foreach( $messages as $message ) {
			foreach( $parts_with_limits as $part_name => $part ) {
				$request_value = $message;

				if ( ! isset( $request_value[ $part_name ] ) ) {
					continue;
				}

				$request_value = $request_value[ $part_name ];

				if ( ! is_array( $request_value ) ) {
					$request_value = array( $request_value );
				}

				foreach( $part['options'] as $option_id => $o ) {
					foreach( $request_value as $submitted_value ) {
						if ( ! is_array( $submitted_value ) ) {
							$submitted_value = array( $submitted_value );
						}

						// Filter out "other" options
						$submitted_value = array_filter( $submitted_value, 'is_numeric' );
						$submitted_value = array_map( 'intval', $submitted_value );

						if ( ! in_array( $o, $submitted_value ) ) {
							continue;
						}

						$choice_id = $this->get_counter_choice_key( $part['id'], $option_id );
						$count = 1;

						if ( isset( $meta_counters[ $choice_id ] ) ) {
							$count = $meta_counters[ $choice_id ] + 1;
						}

						$meta_counters[ $choice_id ] = $count;
					}
				}
			}
		}

		justwpforms_update_meta( $form_id, $this->counter_key, $meta_counters );

		return $form;
	}

}

if ( ! function_exists( 'justwpforms_upgrade_get_option_limiter' ) ) :

function justwpforms_upgrade_get_option_limiter() {
	return justwpforms_Form_Option_Limiter::instance();
}

endif;

justwpforms_upgrade_get_option_limiter();
