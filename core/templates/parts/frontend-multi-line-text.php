<div class="<?php justwpforms_the_part_class( $part, $form ); ?>" id="<?php justwpforms_the_part_id( $part, $form ); ?>-part" <?php justwpforms_the_part_data_attributes( $part, $form ); ?>>
	<div class="justwpforms-part-wrap">
		<?php if ( 'as_placeholder' !== $part['label_placement'] ) : ?>
			<?php justwpforms_the_part_label( $part, $form ); ?>
		<?php endif; ?>

		<?php justwpforms_print_part_description( $part ); ?>

		<div class="justwpforms-part__el">
			<?php do_action( 'justwpforms_part_input_before', $part, $form ); ?>

			<?php
			$textarea_rows = apply_filters( 'justwpforms_long_text_field_rows', 5, $part, $form );
			?>
			<textarea id="<?php justwpforms_the_part_id( $part, $form ); ?>" name="<?php justwpforms_the_part_name( $part, $form ); ?>" rows="<?php echo $textarea_rows; ?>" placeholder="<?php echo esc_attr( $part['placeholder'] ); ?>" <?php justwpforms_the_part_attributes( $part, $form ); ?> <?php justwpforms_parts_autocorrect_attribute( $part ); ?> ><?php justwpforms_the_part_value( $part, $form ); ?></textarea>
			<?php if ( 'as_placeholder' === $part['label_placement'] ) : ?>
				<?php justwpforms_the_part_label( $part, $form ); ?>
			<?php endif; ?>

			<?php do_action( 'justwpforms_part_input_after', $part, $form ); ?>

			<?php justwpforms_part_error_message( justwpforms_get_part_name( $part, $form ) ); ?>
		</div>
	</div>
</div>
