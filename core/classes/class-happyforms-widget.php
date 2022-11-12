<?php
class justwpforms_Widget extends WP_Widget {

	/**
	 * Widget constructor.
	 *
	 * @since 1.0
	 *
	 */
	function __construct() {
		parent::__construct(
			'justwpforms_widget',
			__( 'Forms', 'justwpforms' ),
			array(
				'description' => __( 'Displays a form.', 'justwpforms' ),
				'show_instance_in_rest' => true,
			)
		);
	}

	/**
	 * Render the widget.
	 *
	 * @since 1.0
	 *
	 * @param array $args     The widget configuration.
	 * @param array $instance The widget instance attributes.
	 *
	 */
	public function widget( $args, $instance ) {
		$default_instance = array(
			'title' => '',
			'form_id' => '',
		);

		$forms = justwpforms_get_form_controller()->get();

		if ( ! empty( $forms ) ) {
			$default_instance['form_id'] = $forms[0]['ID'];
		}

		$instance = wp_parse_args( $instance, $default_instance );

		$title = ( isset( $instance['title'] ) ) ? $instance['title'] : '';
		$form_id = ( isset( $instance['form_id'] ) ) ? $instance['form_id'] : '';

		$title = apply_filters( 'widget_title', $title );

		echo $args[ 'before_widget' ];

		if ( !empty( $title ) ) {
			echo $args[ 'before_title' ] . $title . $args[ 'after_title' ];
		}

		if ( !empty( $form_id ) && intval( $form_id ) ) {
			echo do_shortcode( '[justwpforms id="'. $form_id .'"]' );
		}

		echo $args[ 'after_widget' ];
	}

	/**
	 * Render the configuration form.
	 *
	 * @since 1.0
	 *
	 * @param array $instance The widget instance data.
	 *
	 * @return void
	 */
	public function form( $instance ) {
		$default_instance = array(
			'title' => '',
			'form_id' => '',
		);

		$instance = wp_parse_args( $instance, $default_instance );
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'justwpforms' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $instance['title'] ); ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'form_id' ); ?>"><?php _e( 'Form:', 'justwpforms' ); ?></label>
			<select class="widefat" id="<?php echo $this->get_field_id( 'form_id' ); ?>" name="<?php echo $this->get_field_name( 'form_id' ); ?>">
				<?php
				$forms = justwpforms_get_form_controller()->get();
				$forms = array_values( wp_list_filter( $forms, array( 'post_status' => 'publish' ) ) );

				foreach ( $forms as $form ) {
					echo '<option value="'. $form['ID'] .'" '. selected( (int) $instance['form_id'] === (int) $form['ID'] ) .'">'. justwpforms_get_form_title( $form ) .'</option>';
				}
				?>
			</select>
		</p>
		<?php
	}

	/**
	 * Update the widget attributes.
	 *
	 * @since 1.0
	 *
	 * @param array $old Previous widget instance attributes.
	 * @param array $new New widget instance attributes.
	 *
	 * @return array
	 *
	 */
	public function update( $new, $old ) {
		$instance = array();
		$instance['title'] = ( !empty( $new[ 'title' ] ) ) ? esc_attr( $new[ 'title' ] ) : '';
		$instance['form_id'] = ( !empty( $new[ 'form_id' ] ) ) ? intval( $new[ 'form_id' ] ) : '';

		return $instance;
	}
}
