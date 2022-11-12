<div class="<?php justwpforms_the_part_class( $part, $form ); ?>" id="<?php justwpforms_the_part_id( $part, $form ); ?>-part" <?php justwpforms_the_part_data_attributes( $part, $form ); ?>>
	<div class="justwpforms-part-wrap">
		<?php
		if ( ! empty( $part['label'] ) ) {
			justwpforms_the_part_label( $part, $form );
		}
		?>

		<?php justwpforms_print_part_description( $part ); ?>

		<div class="justwpforms-part__el">
			<?php do_action( 'justwpforms_part_input_before', $part, $form ); ?>

			<?php
			$placeholder_text = $part['placeholder_text'];
			$placeholder_text = html_entity_decode( $placeholder_text );
			$placeholder_text = wp_unslash( $placeholder_text );
			$placeholder_text = do_shortcode( $placeholder_text );

			echo $placeholder_text;
			?>

			<?php do_action( 'justwpforms_part_input_after', $part, $form ); ?>
		</div>
	</div>
</div>
