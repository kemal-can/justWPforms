<div class="<?php justwpforms_the_part_class( $part, $form ); ?>" id="<?php justwpforms_the_part_id( $part, $form ); ?>-part" <?php justwpforms_the_part_data_attributes( $part, $form ); ?>>
	<div class="justwpforms-part-wrap">
		<?php justwpforms_the_part_label( $part, $form ); ?>

		<?php justwpforms_print_part_description( $part ); ?>

		<div class="justwpforms-part__el">
			<?php do_action( 'justwpforms_part_input_before', $part, $form ); ?>

			<div class="justwpforms-rating-wrap">
				<?php
				$icons = justwpforms_get_rating_icons( $part );

				switch( $part[ 'rating_type' ] ) {
					case 'yesno':
						require( 'frontend-rating-yesno.php' );
						break;
					case 'scale':
						require( 'frontend-rating-scale.php' );
						break;
				}
				?>
			</div>

			<?php do_action( 'justwpforms_part_input_after', $part, $form ); ?>

		</div>

		<?php justwpforms_part_error_message( justwpforms_get_part_name( $part, $form ) ); ?>
	</div>
</div>
