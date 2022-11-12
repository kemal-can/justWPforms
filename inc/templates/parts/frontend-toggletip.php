<div class="<?php justwpforms_the_part_class( $part, $form ); ?>" id="<?php justwpforms_the_part_id( $part, $form ); ?>-part" <?php justwpforms_the_part_data_attributes( $part, $form ); ?>>
	<div class="justwpforms-part-wrap">
		<div class="justwpforms-part__el">
			<?php do_action( 'justwpforms_part_input_before', $part, $form ); ?>
			<?php
			$toggletip_text = $part['details'];
			$toggletip_text = html_entity_decode( $toggletip_text );
			$toggletip_text = wp_unslash( $toggletip_text );
			$toggletip_text = do_shortcode( $toggletip_text );
			?>
			<details class="justwpforms-toggletip-details">
				<summary class="justwpforms-toggletip-summary"><u><?php echo esc_html( $part['label'] ); ?></u></summary>
				<div class="justwpforms-toggletip-text"><?php echo $toggletip_text; ?></div>
			</details>
			<?php do_action( 'justwpforms_part_input_after', $part, $form ); ?>
		</div>
	</div>
</div>
