<div class="<?php justwpforms_the_part_class( $part, $form ); ?>" id="<?php justwpforms_the_part_id( $part, $form ); ?>-part" <?php justwpforms_the_part_data_attributes( $part, $form ); ?>>
	<div class="justwpforms-part-wrap">
		<?php
		if ( ! empty( $part['label'] ) || justwpforms_is_preview() ) {
			justwpforms_the_part_label( $part, $form );
		}
		?>

		<?php justwpforms_print_part_description( $part ); ?>

		<?php if ( 1 ==  $part['required'] ) : ?>
		<?php $has_scrolled = justwpforms_get_part_value( $part, $form ); ?>
			<input type="hidden" name="<?php justwpforms_the_part_name( $part, $form ); ?>" value="<?php echo $has_scrolled; ?>" data-serialize />
		<?php endif; ?>
		<div class="justwpforms-part__el">
			<?php do_action( 'justwpforms_part_input_before', $part, $form ); ?>

			<?php
			$terms_text = $part['terms_text'];
			$terms_text = html_entity_decode( $terms_text );
			$terms_text = wp_unslash( $terms_text );
			$terms_text = do_shortcode( $terms_text );
			?>
			<div class="scrollbox" tabindex="0">
				<div class="content">
					<?php echo $terms_text; ?>
				</div>
			</div>
			<?php do_action( 'justwpforms_part_input_after', $part, $form ); ?>

			<?php justwpforms_part_error_message( justwpforms_get_part_name( $part, $form ) ); ?>
		</div>
	</div>
</div>
