<tr class="justwpforms-message-edit-field-<?php echo $part['type']; ?>">
	<td class="first">
		<label for="<?php echo $part['id']; ?>"><?php echo esc_html( justwpforms_get_part_label( $part ) ); ?></label>
	</td>
	<td>
		<input type="hidden" name="parts[<?php echo $part['id']; ?>][type]" value="<?php echo $part['type']; ?>" />
		<?php 
		$value = justwpforms_get_message_part_value( $value, $part, 'admin-edit' );
		$value = str_replace( '<br>', "\n", $value );
		$value = wp_unslash( $value );
		?>
		<textarea name="parts[<?php echo $part['id']; ?>][value]" id="<?php echo $part['id']; ?>" rows="6"><?php echo $value; ?></textarea>
	</td>
</tr>
