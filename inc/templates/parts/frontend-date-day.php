<?php
$current_timestamp = current_time( 'timestamp', false );

$day_value = ( justwpforms_get_part_value( $part, $form, 'day' ) ) ? justwpforms_get_part_value( $part, $form, 'day' ) : '';

if ( '' === $day_value && 'current' === $part['default_datetime'] ) {
	$day_value = date( 'j', $current_timestamp );
}
?>
<div class="justwpforms-part-date__date-input justwpforms-part--date__input-wrap justwpforms-part-date-input--days">
	<div class="justwpforms-custom-select" data-searchable="true">
		<div class="justwpforms-part__select-wrap">
			<?php
			$placeholder_text = justwpforms_get_datetime_placeholders( 'day' );
			$options = array();
			$days = justwpforms_get_days();

			foreach( $days as $i ) {
				$options[] = array(
					'label' => $i,
					'value' => $i,
					'is_default' => ( intval( $day_value ) === $i )
				);
			}
			$is_searchable = count( $options ) > 5;
			$is_searchable = apply_filters( 'justwpforms_is_dropdown_searchable', $is_searchable, $part, $form );
			?>

			<select name="<?php justwpforms_the_part_name( $part, $form ); ?>[day]" data-serialize required class="justwpforms-select">
				<?php if ( ! empty( $placeholder_text ) ) : ?>
					<option disabled hidden <?php echo ( $day_value === '' ) ? ' selected' : ''; ?> value='' class="justwpforms-placeholder-option"><?php echo $placeholder_text; ?></option>
				<?php endif; ?>
				<?php foreach ( $options as $index => $option ) : ?>
				<?php
					$option_value = isset( $option['value'] ) ? $option['value'] : $index;
					$submissions_left_label = isset( $option['submissions_left_label'] ) ? ' ' . $option['submissions_left_label'] : '';
					$selected = ( $day_value != '' && $day_value == $option_value ) ? ' selected' : '';
				?>
					<option value="<?php echo $option_value; ?>" <?php echo $selected; ?>><?php echo esc_attr( $option['label'] ); ?><?php echo $submissions_left_label; ?></option>
				<?php endforeach; ?>
			</select>
		</div>
	</div>
</div>
