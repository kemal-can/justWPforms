<tr class="justwpforms-message-edit-field-<?php echo $part['type']; ?>">
	<td class="first">
		<label for="<?php echo $part['id']; ?>"><?php echo esc_html( justwpforms_get_part_label( $part ) ); ?></label>
	</td>
	<td>
		<?php $original_value = maybe_unserialize( $value ); ?>
		<input type="text" value="<?php echo 'yes' === $original_value['intent'] ? esc_html( $part['intent_text'] ) : ''; ?>" disabled /><br/><br/>
		<?php if( '' === $original_value['signature_hash_id'] ){
			$value = justwpforms_get_message_part_value( $value, $part, 'admin-edit' );
			$value = wp_unslash( $value );
		?>
			<input type="text" id="<?php echo $part['id']; ?>" value="<?php echo esc_html( $value ); ?>" disabled />
		<?php
		} else {
			justwpforms_the_message_part_value( $value, $part, 'admin-edit' );
		}
		?>
	</td>
</tr>
