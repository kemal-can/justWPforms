<div class="justwpforms-payments-service justwpforms-payments-service--stripe">
	<div class="justwpforms-part justwpforms-form__part justwpforms-payments__stripe-el justwpforms-payments__card">
		<div class="justwpforms-part-wrap">
			<div class="justwpforms-part__el">
				<label for="<?php justwpforms_the_part_id( $part, $form ); ?>_card" class="justwpforms-part__label justwpforms-stripe-card-label">
					<span class="label"><?php echo $form['card_label']; ?></span>
				</label>
				<div>
					<label for="<?php justwpforms_the_part_id( $part, $form ); ?>_stripe_card" class="justwpforms-part__label justwpforms-stripe-card-number-label">
						<span class="label"><?php echo $form['card_number_label']; ?></span>
					</label>
					<div id="<?php justwpforms_the_part_id( $part, $form ); ?>_stripe_card" class="stripe-element stripe-card" data-value="8888 8888 8888 8888"></div>
				</div>
				<div>
					<label for="<?php justwpforms_the_part_id( $part, $form ); ?>_stripe_card_expiry" class="justwpforms-part__label justwpforms-stripe-card-expiry-label">
						<span class="label"><?php echo $form['card_expiry_label']; ?></span>
					</label>
					<div id="<?php justwpforms_the_part_id( $part, $form ); ?>_stripe_card_expiry" class="stripe-element stripe-card-expiry" data-value="88 / 88"></div>
				</div>
				<div>
					<label for="<?php justwpforms_the_part_id( $part, $form ); ?>_stripe_card_cvc" class="justwpforms-part__label justwpforms-stripe-card-cvc-label">
						<span class="label"><?php echo $form['card_cvc_label']; ?></span>
					</label>
					<div id="<?php justwpforms_the_part_id( $part, $form ); ?>_stripe_card_cvc" class="stripe-element stripe-card-cvc" data-value="8888"></div>
				</div>
			</div>
		</div>
	</div>
	<input type="hidden" name="<?php justwpforms_the_part_name( $part, $form ); ?>[filled]" class="credit-card-filled" value="<?php echo justwpforms_the_part_value( $part, $form, 'filled' ); ?>">
</div>