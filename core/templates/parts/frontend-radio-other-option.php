<?php
$value = justwpforms_get_part_value( $part, $form );
$checked = false;
$text_value = '';

if ( is_array( $value ) ) {
	if ( 999 === $value[0] ) {
		$checked = true;

		if ( isset( $value[1] ) ) {
			$text_value = $value[1];
		}
	}
}

$checkmark_content = '<span class="justwpforms-radio-circle"></span>';

?>
<div class="justwpforms-part__option justwpforms-part-option justwpforms-part-option--other" id="<?php echo $part['id']; ?>_other">
	<label class="option-label">
		<input type="radio" class="justwpforms-visuallyhidden" name="<?php justwpforms_the_part_name( $part, $form ); ?>" value="999" <?php echo ( $checked ) ? 'checked="checked"' : ''; ?> <?php justwpforms_the_part_attributes( $part, $form ); ?>>
		<span class="checkmark"><?php echo $checkmark_content; ?></span>
		<span class="label" id="hf-label-<?php justwpforms_the_part_name( $part, $form ); ?>"><?php echo $part['other_option_label']; ?></span>
	</label>
	<input type="text" name="<?php justwpforms_the_part_name( $part, $form ); ?>" placeholder="<?php echo $part['other_option_placeholder']; ?>" aria-labelledby="hf-label-<?php justwpforms_the_part_name( $part, $form ); ?>" value="<?php echo $text_value; ?>" class="<?php echo ( $checked ) ? 'hf-show' : ''; ?>" <?php justwpforms_the_part_attributes( $part, $form ); ?> <?php justwpforms_parts_autocorrect_attribute( $part ); ?> />
</div>
