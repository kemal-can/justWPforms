<div class="<?php justwpforms_the_part_class( $part, $form ); ?>" id="<?php justwpforms_the_part_id( $part, $form ); ?>-part" <?php justwpforms_the_part_data_attributes( $part, $form ); ?>>
	<div class="justwpforms-part-wrap">
		<?php justwpforms_the_part_label( $part, $form ); ?>

		<?php justwpforms_print_part_description( $part ); ?>

		<?php do_action( 'justwpforms_part_input_before', $part, $form ); ?>

		<div class="justwpforms-part__el">
			<?php
			$columns = justwpforms_get_part_options( $part['columns'], $part, $form );
			$columns_num = max( count( $columns ), 1 );
			?>
			<div class="justwpforms-table">
				<div class="justwpforms-table__row justwpforms-table__row--head">
					<div class="justwpforms-table__cell" style="width: <?php echo 100 / $columns_num; ?>%"></div>
					<?php
					foreach( $columns as $column ) : ?>
						<div class="justwpforms-table__cell justwpforms-table__cell--column-title" id="<?php echo esc_attr( $column['id'] ); ?>" style="width: <?php echo 100 / $columns_num; ?>%">
							<span><?php echo esc_attr( $column['label'] ); ?></span>
						</div>
					<?php endforeach; ?>
				</div>
			<?php
			$rows = justwpforms_get_part_options( $part['rows'], $part, $form );

			foreach( $rows as $row ) : ?>
				<div class="justwpforms-table__row justwpforms-table__row--body" id="<?php echo esc_attr( $row['id'] ); ?>">
					<div class="justwpforms-table__cell justwpforms-table__cell--row-title" style="width: <?php echo 100 / $columns_num; ?>%">
						<span class="justwpforms-table__row-label"><?php echo esc_attr( $row['label'] ); ?></span>
					</div>
					<?php foreach( $columns as $c => $column ) : ?>
						<?php 
						$value = justwpforms_get_part_value( $part, $form, $row['id'] ); 
						$column_classes = '';

						if ( '' != $column['limit_submissions_amount'] && 0 == $column['submissions_left'] ) {
							$column_classes .= ' disabled-option';
						}
						?>
						<div class="justwpforms-table__cell<?php echo $column_classes; ?>" style="width: <?php echo 100 / $columns_num; ?>%">
							<div class="justwpforms-table__cell--column-title justwpforms-table__cell--column-title-sm"><?php echo esc_attr( $column['label'] ); ?></div>
							<label class="option-label">
							<?php if ( ! $part['allow_multiple_selection'] ) : ?>
								<?php $checked = ! empty( $column['label'] ) ? checked( $value, $c, false ) : ''; ?>
								<input type="radio" class="justwpforms-visuallyhidden" name="<?php justwpforms_the_part_name( $part, $form ); ?>[<?php echo esc_attr( $row['id'] ); ?>]" value="<?php echo $c; ?>" <?php echo $checked; ?> <?php justwpforms_the_part_attributes( $part, $form ); ?> <?php echo ( empty( $column['submissions_left'] ) && '' != $column['limit_submissions_amount'] ) ? 'disabled' : '' ?>>
								<span class="checkmark"><span class="justwpforms-radio-circle"></span></span>
							<?php else: ?>
								<?php
								$value = justwpforms_get_part_value( $part, $form, $row['id'], array() );
								$checked = in_array( $c, $value ) ? 'checked="checked"' : '';
								?>
								<input type="checkbox" class="justwpforms-visuallyhidden" name="<?php justwpforms_the_part_name( $part, $form ); ?>[<?php echo esc_attr( $row['id'] ); ?>][]" value="<?php echo $c; ?>" <?php echo $checked; ?> <?php justwpforms_the_part_attributes( $part, $form ); ?> <?php echo ( empty( $column['submissions_left'] ) && '' != $column['limit_submissions_amount'] ) ? 'disabled' : '' ?>>
								<span class="checkmark"><svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24"><path fill="currentColor" d="M20.285 2l-11.285 11.567-5.286-5.011-3.714 3.716 9 8.728 15-15.285z"/></svg></span>
							<?php endif; ?>
							</label>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endforeach; ?>
			</div>

			<?php do_action( 'justwpforms_part_input_after', $part, $form ); ?>
		</div>

		<?php justwpforms_part_error_message( justwpforms_get_part_name( $part, $form ) ); ?>
	</div>
</div>
