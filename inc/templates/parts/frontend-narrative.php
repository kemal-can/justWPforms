<div class="<?php justwpforms_the_part_class( $part, $form ); ?>" id="<?php justwpforms_the_part_id( $part, $form ); ?>-part" <?php justwpforms_the_part_data_attributes( $part, $form ); ?>>
	<div class="justwpforms-part-wrap">
		<?php if ( 'as_placeholder' !== $part['label_placement'] ) : ?>
			<?php justwpforms_the_part_label( $part, $form ); ?>
		<?php endif; ?>

		<?php justwpforms_print_part_description( $part ); ?>

		<div class="justwpforms-part__el">
			<?php do_action( 'justwpforms_part_input_before', $part, $form ); ?>

			<p><?php
			$tokens = justwpforms_get_narrative_tokens( $part['format'], true );
			$format = justwpforms_get_narrative_format( $part['format'] );
			$inputs = array();

			foreach ( $tokens as $t => $placeholder ) {
				ob_start(); ?>
				<input id="<?php justwpforms_the_part_id( $part, $form ); ?>" type="text" name="<?php justwpforms_the_part_name( $part, $form ); ?>[]" <?php if ( ! empty( $placeholder ) ) : ?>placeholder="<?php echo esc_html( $placeholder ); ?>" <?php endif; ?> value="<?php justwpforms_the_part_value( $part, $form, $t ); ?>" <?php justwpforms_the_part_attributes( $part, $form, $t ); ?> /><?php
				$input = ob_get_clean();
				$inputs[$t] = $input;
			}

			$part_content = do_shortcode( vsprintf( html_entity_decode( stripslashes( $format ) ), $inputs ) );

			echo $part_content;
			?></p>

			<?php do_action( 'justwpforms_part_input_after', $part, $form ); ?>

			<?php justwpforms_part_error_message( justwpforms_get_part_name( $part, $form ) ); ?>
		</div>
	</div>
</div>
