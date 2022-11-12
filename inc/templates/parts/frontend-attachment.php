<div class="<?php justwpforms_the_part_class( $part, $form ); ?>" id="<?php justwpforms_the_part_id( $part, $form ); ?>-part" <?php justwpforms_the_part_data_attributes( $part, $form ); ?>>
	<div class="justwpforms-attachment justwpforms-part-wrap">
		<?php justwpforms_the_part_label( $part, $form ); ?>

		<?php justwpforms_print_part_description( $part ); ?>

		<div class="justwpforms-part__el">
			<?php do_action( 'justwpforms_part_input_before', $part, $form ); ?>

			<div class="justwpforms-upload-area justwpforms-input-group with-suffix">
				<input type="text" class="justwpforms-visuallyhidden" tabindex="-1" aria-disabled="true">
				<div class="justwpforms-attachment-box">
					<?php $place_holder = $part['placeholder']; ?>
					<div class="justwpforms-attachment-progress" data-type="default"><?php echo empty($part['placeholder']) ? '&nbsp;' : $place_holder; ?></div>
					<div class="justwpforms-attachment-progress" data-type="uploading"><?php _e( 'Uploading…', 'justwpforms' ); ?> (<span>0</span>%)</div>
				</div>
				<div class="justwpforms-input-group__suffix justwpforms-input-group__suffix--button">
					<button type="button" class="justwpforms-plain-button" tabindex="-1"><?php echo $form['file_upload_browse_label']; ?></button>
				</div>
			</div>

			<?php
			$error_svg_icon = '<svg role="img" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M504 256c0 136.997-111.043 248-248 248S8 392.997 8 256C8 119.083 119.043 8 256 8s248 111.083 248 248zm-248 50c-25.405 0-46 20.595-46 46s20.595 46 46 46 46-20.595 46-46-20.595-46-46-46zm-43.673-165.346l7.418 136c.347 6.364 5.609 11.346 11.982 11.346h48.546c6.373 0 11.635-4.982 11.982-11.346l7.418-136c.375-6.874-5.098-12.654-11.982-12.654h-63.383c-6.884 0-12.356 5.78-11.981 12.654z" class=""></path></svg>';
			?>

			<div class="justwpforms-file-notices">
				<div class="justwpforms-part-error-notice" data-error-type="duplicate">
					<p><?php echo $error_svg_icon; ?> <?php echo justwpforms_get_validation_message( 'file_duplicate' ); ?></p>
				</div>
				<div class="justwpforms-part-error-notice" data-error-type="invalid">
					<p><?php echo $error_svg_icon; ?> <?php echo justwpforms_get_validation_message( 'file_invalid' ); ?></p>
				</div>
				<div class="justwpforms-part-error-notice" data-error-type="size">
					<p><?php echo $error_svg_icon; ?> <?php echo justwpforms_get_validation_message( 'file_size_too_big' ); ?></p>
				</div>
			</div>

			<ul class="justwpforms-attachment__list">
				<?php
				$controller = justwpforms_get_attachment_controller();
				$value = justwpforms_get_part_value( $part, $form );
				$max_file_count = intval( $part['max_file_count'] );
				$max_file_count = $max_file_count > 0 ? $max_file_count : count( $value );
				$file_count = 0;

				for( $i = 0; $i < $max_file_count; $i ++ ) {
					$attachment_id = '';
					$attachment_name = '';
					$attachment_size = '';

					if ( isset( $value[$i] ) && ! empty( $value[$i]['id'] ) ) {
						$attachments = $controller->get( array(
							'hash_id' => $value[$i]['id'],
						) );

						if ( 0 !== count( $attachments ) ) {
							$attachment_id = $value[$i]['id'];
							$attachment_name = $value[$i]['name'];
							$attachment_size = $value[$i]['size'];
							$file_count ++;
						}
					}

					include( justwpforms_get_include_folder() . '/templates/parts/frontend-attachment-item-file.php' );
				} ?>
			</ul>

			<script type="text/template" class="item-template">
				<?php
				$attachment_id = '';
				$attachment_name = '';
				$attachment_size = '';
				$i = '#';

				include( justwpforms_get_include_folder() . '/templates/parts/frontend-attachment-item-file.php' ); ?>
			</script>

			<?php if ( $part['max_file_count'] > 0 ): ?>
			<p class="justwpforms-attachment__counter">
				<span class="current"><?php echo $file_count; ?></span>/<span class="total"><?php echo $part['max_file_count']; ?></span> <span class="counter-label-1"><?php echo $form['max_files_uploaded_label']; ?></span></p>
			<?php endif; ?>

			<?php do_action( 'justwpforms_part_input_after', $part, $form ); ?>

			<?php justwpforms_part_error_message( justwpforms_get_part_name( $part, $form ) ); ?>
		</div>
	</div>
</div>
