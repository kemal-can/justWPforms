<div class="<?php justwpforms_the_part_class( $part, $form ); ?>" id="<?php justwpforms_the_part_id( $part, $form ); ?>-part" <?php justwpforms_the_part_data_attributes( $part, $form ); ?>>
	<div class="justwpforms-part-wrap">
		<?php justwpforms_the_part_label( $part, $form ); ?>

		<?php
			$options = justwpforms_get_part_options( $part['options'], $part, $form );
			$value = justwpforms_get_part_value( $part, $form );
			$default_label = ( '' !== $value ) ? $options[$value]['label'] : '';
			$placeholder_text = $part['placeholder'];
		?>

		<div class="justwpforms-part__el">
			<?php do_action( 'justwpforms_part_input_before', $part, $form ); ?>
			<div class="justwpforms-custom-select">
				<div class="justwpforms-part__select-wrap">
					<select name="<?php justwpforms_the_part_name( $part, $form ); ?>" data-serialize class="justwpforms-select" required>
							<option disabled hidden <?php echo ( $value === '' ) ? ' selected' : ''; ?> value='' class="justwpforms-placeholder-option"><?php echo $placeholder_text; ?></option>
						<?php foreach ( $options as $index => $option ) : ?>
						<?php
							$option_value = isset( $option['value'] ) ? $option['value'] : $index;
							$submissions_left_label = isset( $option['submissions_left_label'] ) ? ' ' . $option['submissions_left_label'] : '';
							$selected = ( $value != '' && $value == $option_value ) ? ' selected' : '';
						?>
							<option value="<?php echo $option_value; ?>" <?php echo $selected; ?>><?php echo esc_attr( $option['label'] ); ?><?php echo $submissions_left_label; ?></option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>

			<?php do_action( 'justwpforms_part_input_after', $part, $form ); ?>

			<?php justwpforms_print_part_description( $part ); ?>
			<?php justwpforms_part_error_message( justwpforms_get_part_name( $part, $form ) ); ?>
		</div>
	</div>
</div>
