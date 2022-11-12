<div class="<?php justwpforms_the_part_class( $part, $form ); ?>" id="<?php justwpforms_the_part_id( $part, $form ); ?>-part" <?php justwpforms_the_part_data_attributes( $part, $form ); ?>>
	<?php
	$input_group = false;
	$has_prefix = ( '' !== $part['prefix'] );
	$has_suffix = ( '' !== $part['suffix'] );
	$early_label = true;

	if ( $has_suffix || $has_prefix ) {
		$input_group = true;
	}

	if ( 'as_placeholder' === $part['label_placement'] ) {
		$early_label = false;
	}
	?>
	<div class="justwpforms-part-wrap">
		<?php if ( $early_label ) : ?>
			<?php justwpforms_the_part_label( $part, $form ); ?>
		<?php endif; ?>

		<?php justwpforms_print_part_description( $part ); ?>

		<div class="justwpforms-part__el">
			<?php do_action( 'justwpforms_part_input_before', $part, $form ); ?>

			<?php if ( $input_group ) : ?>
				<div class="justwpforms-input-group<?php echo ( $has_prefix ) ? ' with-prefix' : ''; ?><?php echo ( $has_suffix ) ? ' with-suffix': '' ?>">
					<?php if ( $has_prefix ) : ?>
						<div class="justwpforms-input-group__prefix">
							<span><?php echo $part['prefix']; ?></span>
						</div>
					<?php endif; ?>
			<?php endif; ?>

			<div class="justwpforms-input">
				<input type="email" name="<?php justwpforms_the_part_name( $part, $form ); ?>" id="<?php justwpforms_the_part_id( $part, $form ); ?>" value="<?php justwpforms_the_part_value( $part, $form ); ?>" placeholder="<?php echo esc_attr( $part['placeholder'] ); ?>" <?php justwpforms_the_part_attributes( $part, $form, 0 ); ?>  <?php justwpforms_parts_autocorrect_attribute( $part ); ?> />
				<?php if ( 'as_placeholder' === $part['label_placement'] ) : ?>
					<?php justwpforms_the_part_label( $part, $form ); ?>
				<?php endif; ?>
			</div>

			<?php if ( $input_group ) : ?>
				<?php if ( $has_suffix ) : ?>
					<div class="justwpforms-input-group__suffix">
						<span><?php echo $part['suffix']; ?></span>
					</div>
				<?php endif; ?>

			</div><!-- /.justwpforms-input-group -->
			<?php endif; ?>

			<?php justwpforms_part_error_message( justwpforms_get_part_name( $part, $form ) ); ?>

			<?php do_action( 'justwpforms_part_input_after', $part, $form ); ?>
		</div>
	</div>
</div>
