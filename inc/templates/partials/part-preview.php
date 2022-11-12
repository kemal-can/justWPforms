<?php $visible = apply_filters( 'justwpforms_message_part_visible', true, $part ); ?>
<div class="justwpforms-form__part justwpforms-part-preview" <?php if ( ! $visible ) : ?>style="display: none;"<?php endif; ?>>
	<label class="justwpforms-part__label">
		<span class="label"><?php echo esc_html( $part['label'] ); ?></span>
	</label>
	<div class="justwpforms-part__el-preview justwpforms-part__el-preview__<?php echo $part['type']; ?>"><?php justwpforms_the_part_preview_value( $part, $form ); ?></div>
	<div class="justwpforms-hide">