<div class="<?php justwpforms_the_part_class( $part, $form ); ?>" id="<?php justwpforms_the_part_id( $part, $form ); ?>-part" <?php justwpforms_the_part_data_attributes( $part, $form ); ?>>
	<div class="justwpforms-part-wrap">
		<?php justwpforms_the_part_label( $part, $form ); ?>

		<?php justwpforms_print_part_description( $part ); ?>

		<div class="justwpforms-part__el">
			<?php
			$start = intval( $part['min_value'] );
			$end   = intval( $part['max_value'] );
			$value = justwpforms_get_part_value( $part, $form );
			?>

			<?php if ( $start < $end ) : ?>
				<div class="justwpforms-likert-scale-label justwpforms-likert-scale-label--small justwpforms-likert-scale-label--min"><?php echo $part['min_label']; ?></div>

				<div class="justwpforms-likert-scale">
					<?php for ( $i = $start; $i <= $end; $i++ ) : ?>
						<label>
							<input type="radio" class="justwpforms-visuallyhidden" name="<?php justwpforms_the_part_name( $part, $form ); ?>" value="<?php echo $i; ?>" <?php checked( $value, $i, true ); ?>>
							<span class="justwpforms-likert-scale__label"><?php echo $i; ?></span>
						</label>
					<?php endfor; ?>
				</div>

				<div class="justwpforms-likert-scale-label justwpforms-likert-scale-label--small justwpforms-likert-scale-label--max"><?php echo $part['max_label']; ?></div>

				<div class="justwpforms-likert-scale-labels">
					<span class="justwpforms-likert-scale-label justwpforms-likert-scale-label--min justwpforms-likert-scale-labels__label--min"><?php echo $part['min_label']; ?></span>
					<span class="justwpforms-likert-scale-label justwpforms-likert-scale-label--max justwpforms-likert-scale-labels__label--max"><?php echo $part['max_label']; ?></span>
				</div>
			<?php endif; ?>

			<?php do_action( 'justwpforms_part_input_after', $part, $form ); ?>
			<?php justwpforms_part_error_message( justwpforms_get_part_name( $part, $form ) ); ?>
		</div>
	</div>
</div>
