<div class="<?php justwpforms_the_part_class( $part, $form ); ?>" id="<?php justwpforms_the_part_id( $part, $form ); ?>-part" <?php justwpforms_the_part_data_attributes( $part, $form ); ?>>
	<div class="justwpforms-part-wrap">
		<?php if ( 'as_placeholder' !== $part['label_placement'] ) : ?>
			<?php justwpforms_the_part_label( $part, $form ); ?>
		<?php endif; ?>

		<?php justwpforms_print_part_description( $part ); ?>

		<div class="justwpforms-part__el">
			<div class="justwpforms-part-phone-wrap">

				<?php do_action( 'justwpforms_part_input_before', $part, $form ); ?>

				<?php if ( 1 === intval( $part['masked'] ) ) : ?>
					<div class="justwpforms-input-country-code">
						<div class="justwpforms-phone-international-wrap"><label class="justwpforms-phone-international-labels"><?php echo $form['phone_label_country_code']; ?></label></div>
						<div class="justwpforms-phone-country-group">
							<div class="justwpforms-input-group with-prefix">
								<div class="justwpforms-input-group__prefix"><span>+</span></div>
								<div class="justwpforms-input">
									<input type="tel" name="<?php justwpforms_the_part_name( $part, $form ); ?>[code]" class="justwpforms-phone-code" value="<?php justwpforms_the_part_value( $part, $form, 'code' ); ?>" <?php justwpforms_the_part_attributes( $part, $form, 'code' ); ?> size="6"/>
								</div>
							</div>
						</div>
					</div>
				<?php endif; ?>

				<div class="justwpforms-input">
					<?php if ( 1 === intval( $part['masked'] ) ) : ?>
						<div class="justwpforms-phone-international-wrap"><label class="justwpforms-phone-international-labels"><?php echo $form['phone_label_number']; ?></label></div>
					<?php endif; ?>
					<input id="<?php justwpforms_the_part_id( $part, $form ); ?>" type="tel" value="<?php justwpforms_the_part_value( $part, $form, 'number' ); ?>" name="<?php justwpforms_the_part_name( $part, $form ); ?>[number]" placeholder="<?php echo esc_attr( $part['placeholder'] ); ?>" <?php justwpforms_the_part_attributes( $part, $form, 'number' ); ?> />
					<?php if ( 'as_placeholder' === $part['label_placement'] ) : ?>
						<?php justwpforms_the_part_label( $part, $form ); ?>
					<?php endif; ?>
				</div>

				<?php do_action( 'justwpforms_part_input_after', $part, $form ); ?>
			</div>

			<?php justwpforms_part_error_message( justwpforms_get_part_name( $part, $form ) ); ?>
		</div>
	</div>
</div>
