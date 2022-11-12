<?php

$selected = false;
$text_value = '';
$value = justwpforms_get_part_value( $part, $form );

if ( is_array( $value ) ) {
	if ( 999 === $value[0] ) {
		$selected = true;

		if ( isset( $value[1] ) ) {
			$text_value = $value[1];
		}
	}
}


?>

<div class="justwpforms-part__option justwpforms-part-option justwpforms-part-option--other" id="<?php echo $part['id']; ?>_other">
	<input type="text" name="<?php justwpforms_the_part_name( $part, $form ); ?>" placeholder="<?php echo $part['other_option_placeholder']; ?>" aria-labelledby="hf-label-<?php justwpforms_the_part_name( $part, $form ); ?>" value="<?php echo $text_value; ?>" class="justwpforms-select-dropdown-other <?php echo ( $selected ) ? 'hf-show' : ''; ?>" <?php justwpforms_the_part_attributes( $part, $form ); ?> <?php justwpforms_parts_autocorrect_attribute( $part ); ?> />
</div>
