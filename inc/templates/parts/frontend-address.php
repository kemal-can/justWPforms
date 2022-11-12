<div class="<?php justwpforms_the_part_class( $part, $form ); ?>" id="<?php justwpforms_the_part_id( $part, $form ); ?>-part" <?php justwpforms_the_part_data_attributes( $part, $form ); ?>>
	<div class="justwpforms-part-wrap">
		<?php if ( 'as_placeholder' !== $part['label_placement'] ) : ?>
			<?php justwpforms_the_part_label( $part, $form ); ?>
		<?php endif; ?>

		<?php justwpforms_print_part_description( $part ); ?>

		<div class="justwpforms-part__el">
			<?php do_action( 'justwpforms_part_input_before', $part, $form ); ?>

			<?php if ( 'simple' === $part['mode'] ) : ?>
				<div class="justwpforms-part-el-wrap">
					<?php if ( 1 == $part['has_autocomplete'] ) : ?>
					<div class="justwpforms-part__dummy-input">
						<input type="hidden" name="<?php justwpforms_the_part_name( $part, $form ); ?>[full]" value="<?php justwpforms_the_part_value( $part, $form, 'full' ); ?>" data-serialize />
					<?php endif; ?>
						<?php if ( 1 == $part['has_geolocation'] ) : ?>
							<div class="justwpforms-input-group with-suffix">
						<?php endif; ?>
						<?php if ( 1 == $part['has_autocomplete'] ) : ?>
						<div class="justwpforms-input">
							<input id="<?php justwpforms_the_part_id( $part, $form ); ?>" name="<?php justwpforms_the_part_id( $part, $form ); ?>_full_dummy_<?php echo time(); ?>" class="justwpforms-part--address__autocomplete address-full" type="text" value="<?php justwpforms_the_part_value( $part, $form, 'full' ); ?>" placeholder="<?php echo esc_attr( $part['placeholder'] ); ?>" autocomplete="none" <?php justwpforms_the_part_attributes( $part, $form, 'full' ); ?> <?php justwpforms_parts_autocorrect_attribute( $part ); ?> />
						</div>
						<?php else: ?>
						<div class="justwpforms-input">
							<input id="<?php justwpforms_the_part_id( $part, $form ); ?>" name="<?php justwpforms_the_part_name( $part, $form ); ?>[full]" class="address-full" type="text" value="<?php justwpforms_the_part_value( $part, $form, 'full' ); ?>" placeholder="<?php echo esc_attr( $part['placeholder'] ); ?>" <?php justwpforms_the_part_attributes( $part, $form, 'full' ); ?> <?php justwpforms_parts_autocorrect_attribute( $part ); ?> />
						</div>
						<?php endif; ?>


						<?php if ( 1 == $part['has_geolocation'] ) : ?>
							<div class="justwpforms-input-group__suffix justwpforms-input-group__suffix--button">
								<?php justwpforms_geolocation_button( $part ); ?>
							</div>

						</div><!-- /.justwpforms-input-group -->
						<?php endif; ?>

						<?php if ( 'as_placeholder' === $part['label_placement'] ) : ?>
							<?php justwpforms_the_part_label( $part, $form ); ?>
						<?php endif; ?>

						<?php justwpforms_select( array(), $part, $form ); ?>
					<?php if ( 1 == $part['has_autocomplete'] ) : ?>
					</div>
					<?php endif; ?>
				</div>
			<?php elseif ( 'country' === $part['mode'] ) : ?>
				<div class="justwpforms-part-el-wrap">
					<div class="justwpforms-part__dummy-input">
						<input type="hidden" name="<?php justwpforms_the_part_name( $part, $form ); ?>[country]" value="<?php justwpforms_the_part_value( $part, $form, 'country' ); ?>" data-serialize />

						<?php if ( 1 == $part['has_geolocation'] ) : ?>
							<div class="justwpforms-input-group with-suffix">
						<?php endif; ?>

						<div class="justwpforms-input">
							<input id="<?php justwpforms_the_part_id( $part, $form ); ?>" name="<?php justwpforms_the_part_id( $part, $form ); ?>_country_dummy_<?php echo time(); ?>" class="justwpforms-part--address__autocomplete address-country" type="text" value="<?php justwpforms_the_part_value( $part, $form, 'country' ); ?>" placeholder="<?php _e( 'Country', 'justwpforms' ); ?>" autocomplete="off" <?php justwpforms_the_part_attributes( $part, $form, 'country' ); ?> <?php justwpforms_parts_autocorrect_attribute( $part ); ?> />
						</div>

						<?php if ( 1 == $part['has_geolocation'] ) : ?>
							<div class="justwpforms-input-group__suffix justwpforms-input-group__suffix--button">
								<?php justwpforms_geolocation_button( $part ); ?>
							</div>

						</div><!-- /.justwpforms-input-group -->
						<?php endif; ?>

						<?php justwpforms_select( array(), $part, $form ); ?>

						<?php if ( 'as_placeholder' === $part['label_placement'] ) : ?>
							<?php justwpforms_the_part_label( $part, $form ); ?>
						<?php endif; ?>
					</div>
				</div>
			<?php else: ?>
				<div class="justwpforms-part-el-wrap">
					<div class="justwpforms-part__dummy-input">
						<input type="hidden" name="<?php justwpforms_the_part_name( $part, $form ); ?>[country]" value="<?php justwpforms_the_part_value( $part, $form, 'country' ); ?>" data-serialize />

						<?php if ( 1 == $part['has_geolocation'] ) : ?>
							<div class="justwpforms-input-group with-suffix">
						<?php endif; ?>

						<div class="justwpforms-input">
							<input id ="<?php justwpforms_the_part_id( $part, $form ); ?>" name="<?php justwpforms_the_part_name( $part, $form ); ?>_country_dummy_<?php echo time(); ?>" class="justwpforms-part--address__autocomplete address-country" type="text" value="<?php justwpforms_the_part_value( $part, $form, 'country' ); ?>" placeholder="<?php _e( 'Country', 'justwpforms' ); ?>" autocomplete="off" <?php justwpforms_the_part_attributes( $part, $form, 'country' ); ?> <?php justwpforms_parts_autocorrect_attribute( $part ); ?> />
						</div>

						<?php if ( 1 == $part['has_geolocation'] ) : ?>
							<div class="justwpforms-input-group__suffix justwpforms-input-group__suffix--button">
								<?php justwpforms_geolocation_button( $part ); ?>
							</div>

						</div><!-- /.justwpforms-input-group -->
						<?php endif; ?>

						<?php justwpforms_select( array(), $part, $form ); ?>

						<?php if ( 'as_placeholder' === $part['label_placement'] ) : ?>
							<?php justwpforms_the_part_label( $part, $form ); ?>
						<?php endif; ?>
					</div>

					<input name="<?php justwpforms_the_part_name( $part, $form ); ?>[city]" class="address-city" type="text" value="<?php justwpforms_the_part_value( $part, $form, 'city' ); ?>" placeholder="<?php _e( 'City', 'justwpforms' ); ?>" <?php justwpforms_the_part_attributes( $part, $form, 'city' ); ?> <?php justwpforms_parts_autocorrect_attribute( $part ); ?> />
				</div>
			<?php endif; ?>

			<?php do_action( 'justwpforms_part_input_after', $part, $form ); ?>

			<?php justwpforms_part_error_message( justwpforms_get_part_name( $part, $form ) ); ?>
		</div>
	</div>
</div>
