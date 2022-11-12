<li class="justwpforms-attachment-item" data-attachment-id="<?php echo $attachment_id; ?>">
	<div class="justwpforms-attachment-item__col justwpforms-attachment-item__col--main">
		<span class="justwpforms-attachment-item__name"><?php echo $attachment_name; ?></span>
		<span class="justwpforms-attachment-item__size"><?php echo size_format( $attachment_size ); ?></span>
	</div>
	<div class="justwpforms-attachment-item__col">
		<button type="button" class="justwpforms-text-button justwpforms-attachment-link justwpforms-delete-attachment"><?php echo $form['file_upload_delete_label']; ?></button>
	</div>
	<div>
		<input type="hidden" name="<?php justwpforms_the_part_name( $part, $form ); ?>[<?php echo $i; ?>][id]" value="<?php echo $attachment_id; ?>" class="justwpforms-attachment-input__id" />
		<input type="hidden" name="<?php justwpforms_the_part_name( $part, $form ); ?>[<?php echo $i; ?>][name]" value="<?php echo $attachment_name; ?>" class="justwpforms-attachment-input__name" />
		<input type="hidden" name="<?php justwpforms_the_part_name( $part, $form ); ?>[<?php echo $i; ?>][size]" value="<?php echo $attachment_size; ?>" class="justwpforms-attachment-input__size" />
	</div>
</li>