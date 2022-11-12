<?php
$value = justwpforms_get_part_value( $part, $form );
$array_values = array();
$text_value = '';
$checked = false;

if ( is_array( $value ) ) {
	$array_values = array_filter( $value, 'is_array' );
}

foreach ( $array_values as $index => $array ) {
	if ( 999 === $array[0] ) {
		$checked = true;

		if ( isset( $array[1] ) ) {
			$text_value = $array[1];
		}
	}
}

$checkmark_content = '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24"><path fill="currentColor" d="M20.285 2l-11.285 11.567-5.286-5.011-3.714 3.716 9 8.728 15-15.285z"/></svg>';

?>
<div class="justwpforms-part__option justwpforms-part-option justwpforms-part-option--other" id="<?php echo $part['id']; ?>_other">
	<label class="option-label">
		<input type="checkbox" class="justwpforms-visuallyhidden" name="<?php justwpforms_the_part_name( $part, $form ); ?>[]" <?php echo ( $checked ) ? 'checked="checked"' : ''; ?> value="999" data-serialize <?php justwpforms_the_part_attributes( $part, $form ); ?>>
		<span class="checkmark"><?php echo $checkmark_content; ?></span>
		<span class="label" id="hf-label-<?php justwpforms_the_part_name( $part, $form ); ?>"><?php echo $part['other_option_label']; ?></span>
	</label>
	<input <?php echo ( $checked ) ? 'class="hf-show"' : ''; ?> type="text" aria-labelledby="hf-label-<?php justwpforms_the_part_name( $part, $form ); ?>" placeholder="<?php echo $part['other_option_placeholder']; ?>" value="<?php echo $text_value; ?>" <?php justwpforms_the_part_attributes( $part, $form ); ?> <?php justwpforms_parts_autocorrect_attribute( $part ); ?> />
</div>
