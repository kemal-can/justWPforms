<div class="<?php justwpforms_the_part_class( $part, $form ); ?>" id="<?php justwpforms_the_part_id( $part, $form ); ?>-part" <?php justwpforms_the_part_data_attributes( $part, $form ); ?>>
	<div class="justwpforms-part-wrap">
		<?php justwpforms_the_part_label( $part, $form ); ?>
		
		<?php justwpforms_print_part_description( $part ); ?>

		<div class="justwpforms-part__el">
			<?php do_action( 'justwpforms_part_input_before', $part, $form ); ?>

			<?php if ( '' !== $part['intent_text'] ) : ?>
			<label class="option-label">
				<input type="checkbox" class="justwpforms-visuallyhidden" id="<?php justwpforms_the_part_id( $part, $form ); ?>" name="<?php justwpforms_the_part_name( $part, $form ); ?>[intent]" value="yes" <?php checked( justwpforms_get_part_value( $part, $form, 'intent' ), 'yes' ); ?> <?php justwpforms_the_part_attributes( $part, $form ); ?>>
				<span class="checkmark"><svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24"><path fill="currentColor" d="M20.285 2l-11.285 11.567-5.286-5.011-3.714 3.716 9 8.728 15-15.285z"/></svg></span>
				<span class="label"><?php echo html_entity_decode( $part['intent_text'] ); ?></span>
			</label>
			<?php endif; ?>

			<?php if ( 'type' === $part['signature_type'] ) : ?>

				<input id="<?php justwpforms_the_part_id( $part, $form ); ?>_signature" type="text" name="<?php justwpforms_the_part_name( $part, $form ); ?>[signature]" value="<?php justwpforms_the_part_value( $part, $form, 'signature' ); ?>" placeholder="<?php echo esc_attr( $part['placeholder'] ); ?>" <?php justwpforms_the_part_attributes( $part, $form ); ?> <?php justwpforms_parts_autocorrect_attribute( $part ); ?> />

			<?php elseif ( 'draw' === $part['signature_type'] ) : ?>

				<?php $signature_path_data = justwpforms_get_part_value( $part, $form, 'signature_path_data' ); ?>

				<input id="<?php justwpforms_the_part_id( $part, $form ); ?>_signature_path_data" type="hidden" name="<?php justwpforms_the_part_name( $part, $form ); ?>[signature_path_data]" value="<?php justwpforms_the_part_value( $part, $form, 'signature_path_data' ); ?>" data-justwpforms-path-data />
				<input id="<?php justwpforms_the_part_id( $part, $form ); ?>_signature_raster_data" type="hidden" name="<?php justwpforms_the_part_name( $part, $form ); ?>[signature_raster_data]" value="<?php justwpforms_the_part_value( $part, $form, 'signature_raster_data' ); ?>" data-justwpforms-raster-data />

				<div class="justwpforms--signature-area--container <?php echo $signature_path_data ? 'drawn' : ''; ?>">
					<div class="justwpforms--signature-area">
						<svg viewBox="<?php justwpforms_the_part_value( $part, $form, 'signature_viewbox' ); ?>" data-justwpforms-name="<?php justwpforms_the_part_name( $part, $form ); ?>[signature_viewbox]" preserveAspectRatio="xMidYMid meet">
							<path d="<?php echo $signature_path_data; ?>" />
						</svg>
						<img />
					</div>
					<button class="justwpforms-button justwpforms--signature-area--start-drawing"><?php echo justwpforms_get_validation_message( 'field_signature_start_drawing_button_label' ); ?></button>
					<div class="justwpforms--signature-area--toolbar">
						<button class="justwpforms-button justwpforms--signature-area--clear-drawing"><?php echo justwpforms_get_validation_message( 'field_signature_clear_button_label' ); ?></button>
						<button class="justwpforms-button justwpforms--signature-area--done-drawing"><?php echo justwpforms_get_validation_message( 'field_signature_done_button_label' ); ?></button>
						<button class="justwpforms-button justwpforms--signature-area--edit-drawing"><?php echo justwpforms_get_validation_message( 'field_signature_start_over_button_label' ); ?></button>
					</div>
				</div>

			<?php endif; ?>

			<?php do_action( 'justwpforms_part_input_after', $part, $form ); ?>

			<?php justwpforms_part_error_message( justwpforms_get_part_name( $part, $form ) ); ?>
		</div>
	</div>
</div>
