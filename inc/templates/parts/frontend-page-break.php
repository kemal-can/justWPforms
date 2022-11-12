<div class="<?php justwpforms_the_part_class( $part, $form ); ?>" id="<?php justwpforms_the_part_id( $part, $form ); ?>-part" <?php justwpforms_the_part_data_attributes( $part, $form ); ?>>
	<div class="justwpforms-part-wrap">
		<?php if ( justwpforms_is_preview() ) : ?>
			<div class="justwpforms-page-break">
				<?php	echo justwpforms_the_part_label( $part, $form ); ?>
			</div>
		<?php endif; ?>
	</div>
</div>
