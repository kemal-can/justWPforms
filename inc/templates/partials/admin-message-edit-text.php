<tr>
	<td class="first">
		<label for="<?php echo $part['id']; ?>"><?php echo esc_html( justwpforms_get_part_label( $part ) ); ?></label>
	</td>
	<td>
		<?php justwpforms_the_message_part_value( $value, $part, 'admin-edit' ); ?>
	</td>
</tr>
