<div class="<?php justwpforms_the_part_class( $part, $form ); ?>" id="<?php justwpforms_the_part_id( $part, $form ); ?>-part" <?php justwpforms_the_part_data_attributes( $part, $form ); ?>>
	<div class="justwpforms-part-wrap">
		<?php justwpforms_the_part_label( $part, $form ); ?>

		<?php justwpforms_print_part_description( $part ); ?>

		<?php
		$part_name = justwpforms_get_part_name( $part, $form );

		if ( 1 === intval( $part['multiple'] ) ) {
			$part_name = $part_name . '[]';
		}
		?>
		<div class="justwpforms-part__el">
			<div class="justwpforms-part--scale__inputwrap">
				<div class="justwpforms-part--scale__labels">
					<span class="label-min"><?php echo $part['min_label']; ?></span>
					<span class="label-max"><?php echo $part['max_label']; ?></span>
				</div>
				<div class="justwpforms-part--scale__wrap">
					<input id="<?php justwpforms_the_part_id( $part, $form ); ?>"<?php if ( 1 === intval( $part['multiple'] ) ) : ?> multiple<?php endif; ?> type="range" name="<?php echo $part_name; ?>" step="<?php echo esc_attr( $part['step'] ); ?>" min="<?php echo esc_attr( $part['min_value'] ); ?>" max="<?php echo esc_attr( $part['max_value'] ); ?>" value="<?php justwpforms_the_part_value( $part, $form ); ?>" <?php justwpforms_the_part_attributes( $part, $form ); ?> />
					<output for="<?php justwpforms_the_part_id( $part, $form ); ?>"><?php justwpforms_the_part_value( $part, $form ); ?></output>
				<?php if ( 1 === intval( $part['multiple'] ) ) : ?>
					<output for="<?php justwpforms_the_part_id( $part, $form ); ?>_clone"><?php justwpforms_the_part_value( $part, $form ); ?></output>
				<?php endif; ?>
				</div>
			</div>

			<?php do_action( 'justwpforms_part_input_after', $part, $form ); ?>
			<?php justwpforms_part_error_message( justwpforms_get_part_name( $part, $form ) ); ?>
		</div>
	</div>
</div>
