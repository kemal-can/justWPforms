<ul class="justwpforms-custom-select-dropdown">
	<li class="justwpforms-custom-select-dropdown__placeholder" data-value="" data-label=""><?php echo $placeholder_value; ?></li>
	<?php foreach ( $options as $index => $option ) : ?>
		<li <?php echo ( isset( $option['id'] ) ) ? 'data-option-id="'. esc_attr( $option['id'] ) .'" ' : ''; ?>data-value="<?php echo ( isset( $option['value'] ) ) ? $option['value'] : $index; ?>" data-label="<?php echo esc_attr( $option['label'] ); ?>" class="justwpforms-dropdown-item justwpforms-custom-select-dropdown__item" <?php echo ( empty( $option['submissions_left'] ) && '' != $option['limit_submissions_amount'] ) ? 'data-select-disabled="1"' : ''; ?>><?php echo esc_attr( $option['label'] ); ?> <?php echo ( isset( $option['submissions_left_label'] ) ) ? $option['submissions_left_label'] : ''; ?></li>
	<?php endforeach; ?>
 	<?php if ( count( $options ) > 5 ) : ?>
		<li class="justwpforms-custom-select-dropdown__not-found"><?php echo esc_attr( $form['no_results_label'] ); ?></li>
	<?php endif; ?>
</ul>
