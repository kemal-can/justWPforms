<?php
class justwpforms_Form_Shuffle_Parts {
	private static $instance;

	public $random_seed = '';

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		self::$instance->hook();

		return self::$instance;
	}

	public function hook() {
		add_action( 'justwpforms_form_open', array( $this, 'output_seed_field' ) );
		add_filter( 'justwpforms_part_options', array( $this, 'shuffle_part_options' ), 10, 3 );
		add_action( 'justwpforms_submission_success', array( $this, 'reset_random_seed' ) );

		$parts_with_choice_shuffle = $this->get_parts_with_choice_shuffle();

		foreach ( $parts_with_choice_shuffle as $part ) {
			add_filter( "justwpforms_part_customize_fields_{$part}", array( $this, 'add_part_fields' ) );
		}
	}

	public function get_parts_with_choice_shuffle() {
		$parts = array(
			'radio',
			'checkbox',
			'select'
		);

		return apply_filters( 'justwpforms_parts_with_choice_shuffle', $parts );

		return $parts;
	}

	public function get_random_seed() {
		if ( '' === $this->random_seed ) {
			$this->random_seed = (
				isset( $_REQUEST['justwpforms_random_seed'] ) ?
				$_REQUEST['justwpforms_random_seed'] :
				justwpforms_random_number()
			);

		}

		return $this->random_seed;
	}

	public function reset_random_seed() {
		$this->random_seed = justwpforms_random_number();
	}

	public function output_seed_field() {
		?>
		<input type="hidden" name="justwpforms_random_seed" value="<?php echo $this->get_random_seed(); ?>" />
		<?php
	}

	public function add_part_fields( $fields ) {
		$fields['shuffle_options'] = array(
			'default' => 0,
			'sanitize' => 'justwpforms_sanitize_checkbox'
		);

		return $fields;
	}

	public function shuffle_part_options( $options, $part, $form ) {
		if ( ! is_customize_preview() && isset( $part['shuffle_options'] ) && 1 === intval( $part['shuffle_options'] ) ) {
			// only shuffle rows in Table part
			if ( 'table' === $part['type'] && $options[0]['type'] === 'column' ) {
				return $options;
			}

			$shuffled = [];
			$option_groups = [];
			$current_group_key = '';

			foreach ( $options as $key => $option ) {
				if ( isset( $option['is_heading'] ) && justwpforms_is_truthy( $option['is_heading'] ) ) {
					$current_group_key = $key;
					continue;
				}

				$option_groups[ $current_group_key ][] = $key;
			}

			foreach ( $option_groups as $group_key => $options_keys ) {
				if ( '' !== $group_key ) {
					$shuffled[ $group_key ] = $options[ $group_key ];
				}

				$options_keys = justwpforms_shuffle_array( $options_keys, $this->get_random_seed() );

				foreach ( $options_keys as $key ) {
					$shuffled[ $key ] = $options[ $key ];
				}
			}

			$options = $shuffled;
		}

		return $options;
	}

}

if ( ! function_exists( 'justwpforms_get_shuffle_parts' ) ) :

function justwpforms_get_shuffle_parts() {
	return justwpforms_Form_Shuffle_Parts::instance();
}

endif;

justwpforms_get_shuffle_parts();
