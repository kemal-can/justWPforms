<tr class="justwpforms-message-edit-field-<?php echo $part['type']; ?>">
	<td class="first">
		<label for="<?php echo $part['id']; ?>"><?php echo esc_html( justwpforms_get_part_label( $part ) ); ?></label>
	</td>
	<td>
		<?php 
		$value = justwpforms_get_message_part_value( $value, $part, 'admin-edit' );
		$value = wp_unslash( $value );
		?>
		<input type="text" id="<?php echo $part['id']; ?>" value="<?php echo esc_html( $value ); ?>" disabled />
	</td>
</tr>
