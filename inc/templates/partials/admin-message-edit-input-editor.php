<tr class="justwpforms-message-edit-field-<?php echo $part['type']; ?>">
	<td class="first">
		<label for="content-<?php echo $part['id']; ?>"><?php echo esc_html( justwpforms_get_part_label( $part ) ); ?></label>
	</th>
	<td>
		<input type="hidden" name="parts[<?php echo $part['id']; ?>][type]" value="<?php echo $part['type']; ?>" />
		<?php
		$textarea_name = 'parts[' . $part['id'] . '][value]';
		$value = str_replace( '<br>', "\n", $value );
		$value = wp_unslash( $value );

		wp_editor( $value, 'content-' . $part['id'], array(
			'media_buttons' => false,
			'tinymce' => array(
				'toolbar1' => 'bold,italic,bullist,numlist,blockquote,link,unlink,code,strikethrough,underline,hr',
				'toolbar2' => '',
			),
			'quicktags' => false,
			'statusbar' => false,
			'textarea_rows' => 5,
			'textarea_name' => $textarea_name,
		) ); ?>
	</td>
</tr>
