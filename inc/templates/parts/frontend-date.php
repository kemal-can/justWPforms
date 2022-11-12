<div class="<?php justwpforms_the_part_class( $part, $form ); ?>" id="<?php justwpforms_the_part_id( $part, $form ); ?>-part" <?php justwpforms_the_part_data_attributes( $part, $form ); ?>>
	<div class="justwpforms-part-wrap">
		<?php $current_timestamp = current_time( 'timestamp', false ); ?>
		<?php if ( 'inside' !== $part['label_placement'] ) : ?>
			<?php justwpforms_the_part_label( $part, $form ); ?>
		<?php endif; ?>

		<?php justwpforms_print_part_description( $part ); ?>

		<div class="justwpforms-part__el">
			<?php do_action( 'justwpforms_part_input_before', $part, $form ); ?>
			<?php
			if ( 'datetime' === $part['date_type'] || 'date' === $part['date_type'] ) {
				if ( 'month_first' === justwpforms_get_site_date_format() ) {
					require( 'frontend-date-month.php' );
					require( 'frontend-date-day.php' );
				} else {
					require( 'frontend-date-day.php' );
					require( 'frontend-date-month.php' );
				}
			}

			if ( 'month_year' === $part['date_type'] || 'month' === $part['date_type'] ) {
				require( 'frontend-date-month.php' );
			}

			if ( 'time' !== $part['date_type'] && 'month' !== $part['date_type'] ) {
				$year_value = ( justwpforms_get_part_value( $part, $form, 'year' ) ) ? justwpforms_get_part_value( $part, $form, 'year' ) : '';

				if ( '' === $year_value && 'current' === $part['default_datetime'] ) {
					$year_value = date( 'Y', $current_timestamp );
				}
			?>
				<div class="justwpforms-part-date__date-input justwpforms-part--date__input-wrap justwpforms-part-date-input--years">
					<div class="justwpforms-custom-select">
						<div class="justwpforms-part__select-wrap">
							<?php
							$placeholder_text = justwpforms_get_datetime_placeholders( 'year' );
							$min_year = $part['min_year'];
							$max_year = $part['max_year'];
							$options = array();

							foreach( range( $min_year, $max_year ) as $year ) {
								$options[] = array(
									'label' => $year,
									'value' => $year,
									'is_default' => ( intval( $year_value ) === $year ),
								);
							}
							?>
							<select name="<?php justwpforms_the_part_name( $part, $form ); ?>[year]" required data-serialize class="justwpforms-select">
								<?php if ( ! empty( $placeholder_text ) ) : ?>
									<option disabled hidden <?php echo ( $year_value === '' ) ? ' selected' : ''; ?> value='' class="justwpforms-placeholder-option"><?php echo $placeholder_text; ?></option>
								<?php endif; ?>
								<?php foreach ( $options as $index => $option ) : ?>
								<?php
									$option_value = isset( $option['value'] ) ? $option['value'] : $index;
									$submissions_left_label = isset( $option['submissions_left_label'] ) ? ' ' . $option['submissions_left_label'] : '';
									$selected = ( $year_value != '' && $year_value == $option_value ) ? ' selected' : '';
								?>
									<option value="<?php echo $option_value; ?>" <?php echo $selected; ?>><?php echo esc_attr( $option['label'] ); ?><?php echo $submissions_left_label; ?></option>
								<?php endforeach; ?>
							</select>
						</div>
					</div>
				</div>
			<?php } ?>
			<?php if ( 'datetime' === $part['date_type'] || 'time' === $part['date_type'] ) : ?>
				<?php
				if ( 12 == $part['time_format'] ) {
					$hour_pattern = '(0[0-9]|1[0-2])';
					$hour_date_string = 'h';
				} else {
					$hour_pattern = '(0[0-9]|1[0-9]|2[0-3])';
					$hour_date_string = 'H';
				}

				$default_hour = sprintf( '%02d', intval( $part['min_hour'] ) );
				$justwpforms_hour_value = ( justwpforms_get_part_value( $part, $form, 'hour' ) ) ? justwpforms_get_part_value( $part, $form, 'hour' ) : '';
				$hour_value = ( '' === $justwpforms_hour_value && 'current' === $part['default_datetime'] ) ? date( $hour_date_string, $current_timestamp ) : $justwpforms_hour_value;
				?>
				<div class="justwpforms-part--date__input-wrap justwpforms-part-date__time-input justwpforms-part-date__time-input--hours">
					<input type="text" name="<?php justwpforms_the_part_name( $part, $form ); ?>[hour]" min="<?php echo $part['min_hour']; ?>" max="<?php echo $part['max_hour']; ?>" maxlength="2" pattern="<?php echo $hour_pattern; ?>" autocomplete="off" value="<?php echo $hour_value; ?>" <?php justwpforms_the_part_attributes( $part, $form ); ?> placeholder="<?php _e( 'Hours', 'justwpforms' ) ?>" />
					<span class="justwpforms-spinner-arrow justwpforms-spinner-arrow--up"></span>
					<span class="justwpforms-spinner-arrow justwpforms-spinner-arrow--down"></span>
				</div>
				<div class="justwpforms-part--date__time-separator">
					<span>:</span>
				</div>
				<?php
				$justwpforms_minute_value = ( justwpforms_get_part_value( $part, $form, 'minute' ) ) ? justwpforms_get_part_value( $part, $form, 'minute' ) : '';

				$minute_value = ( '' === $justwpforms_minute_value && 'current' === $part['default_datetime'] ) ? date( 'i', $current_timestamp ) : $justwpforms_minute_value;
				?>
				<div class="justwpforms-part--date__input-wrap justwpforms-part-date__time-input justwpforms-part-date__time-input--minutes">
					<input type="text" name="<?php justwpforms_the_part_name( $part, $form ); ?>[minute]" min="0" max="59" step="<?php echo $part['minute_step']; ?>" maxlength="2" pattern="([0-5][0-9])" autocomplete="off" value="<?php echo $minute_value; ?>" <?php justwpforms_the_part_attributes( $part, $form ); ?> placeholder="<?php _e( 'Minutes', 'justwpforms' ) ?>" />
					<span class="justwpforms-spinner-arrow justwpforms-spinner-arrow--up"></span>
					<span class="justwpforms-spinner-arrow justwpforms-spinner-arrow--down"></span>
				</div>
				<?php if ( 12 == intval( $part['time_format'] ) ) : ?>
				<?php
				$period_value = justwpforms_get_part_value( $part, $form, 'period' );
				$period_value = ( 'current' === $part['default_datetime'] && '' === $period_value ) ? date( 'A', $current_timestamp ) : $period_value;
				 ?>
				<div class="justwpforms-part--date__input-wrap justwpforms-part-date__time-input justwpforms-part-date__time-input--period">
					<div class="justwpforms-custom-select" data-searchable="false">
						<div class="justwpforms-part__select-wrap">
						<?php $placeholder_text = __( 'Period', 'justwpforms' ); ?>
						<?php
						$options = array(
							array(
								'label' => __( 'AM', 'justwpforms' ),
								'value' => 'AM',
								'is_default' => ( 'AM' === $period_value )
							),
							array(
								'label' => __( 'PM', 'justwpforms' ),
								'value' => 'PM',
								'is_default' => ( 'PM' === $period_value )
							)
						);
						?>
							<select name="<?php justwpforms_the_part_name( $part, $form ); ?>[period]" data-serialize required class="justwpforms-select">
								<?php if ( ! empty( $placeholder_text ) ) : ?>
									<option disabled hidden <?php echo ( $year_value === '' ) ? ' selected' : ''; ?> value='' class="justwpforms-placeholder-option"><?php echo $placeholder_text; ?></option>
								<?php endif; ?>
								<?php foreach ( $options as $index => $option ) : ?>
								<?php
									$option_value = isset( $option['value'] ) ? $option['value'] : $index;
									$submissions_left_label = isset( $option['submissions_left_label'] ) ? ' ' . $option['submissions_left_label'] : '';
									$selected = ( $period_value != '' && $period_value == $option_value ) ? ' selected' : '';
								?>
									<option value="<?php echo $option_value; ?>" <?php echo $selected; ?>><?php echo esc_attr( $option['label'] ); ?><?php echo $submissions_left_label; ?></option>
								<?php endforeach; ?>
							</select>
						</div>
					</div>
				</div>
				<?php endif; ?>
			<?php endif; ?>

			<?php do_action( 'justwpforms_part_input_after', $part, $form ); ?>
		</div>
		<?php justwpforms_part_error_message( justwpforms_get_part_name( $part, $form ) ); ?>
	</div>
</div>
