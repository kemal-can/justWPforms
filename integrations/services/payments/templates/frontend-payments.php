<div class="<?php justwpforms_the_part_class( $part, $form ); ?>" id="<?php justwpforms_the_part_id( $part, $form ); ?>-part" <?php justwpforms_the_part_data_attributes( $part, $form ); ?>>
	<div class="justwpforms-part justwpforms-form__part">
		<div class="justwpforms-part-wrap">
			<?php justwpforms_the_part_label( $part, $form ); ?>

			<?php justwpforms_print_part_description( $part ); ?>

			<?php do_action( 'justwpforms_part_input_before', $part, $form ); ?>

			<?php
			$currency = $part['currency'];
			$currencies = justwpforms_payment_get_currencies();
			$currency_symbol = $currencies[$currency]['symbol'];
			$price_class = 'price';
			$coupon = justwpforms_get_part_value( $part, $form, 'coupon' );
			$discounted_price = '';

			?>

			<div class="justwpforms-part__el">
				<?php if ( justwpforms_is_falsy( $part['show_user_price_field'] ) ) : ?>
					<?php
					if ( justwpforms_is_truthy ( $part['accept_coupons'] ) && '' != $coupon ) {
						$price = $part['price'];
						$price_class .= ' strikethrough';
						$discounted_price = $currency_symbol . justwpforms_get_part_value( $part, $form, 'price' );
					} else {
						$price = justwpforms_get_part_value( $part, $form, 'price' );
						$price = ( ! empty( $price ) ) ? $price : $part['price'];
					}
					?>
					<div class="justwpforms-part justwpforms-form__part justwpforms-payments__price">
						<div class="justwpforms-part-wrap">
							<div class="justwpforms-part__el" id="<?php justwpforms_the_part_id( $part, $form ); ?>_price" data-subpart="price">
								<div class="<?php echo $price_class; ?>"><span><?php echo $currency_symbol; ?></span><strong><?php echo $part['price']; ?></strong></div>
								<?php if ( justwpforms_is_truthy ( $part['accept_coupons'] ) ): ?>
								<div class="discounted-price coupons"><?php echo $discounted_price; ?></div>
								<?php endif; ?>
								<input type="hidden" name="<?php justwpforms_the_part_name( $part, $form ); ?>[price]" value="<?php echo $price; ?>" data-default="<?php echo $part['price']; ?>">
							</div>
						</div>
					</div>
					<?php if ( justwpforms_is_truthy ( $part['accept_coupons'] ) ): ?>
					<div class="justwpforms-part justwpforms-form__part justwpforms-payments__coupon">
						<div class="justwpforms-part__wrap">
							<div class="justwpforms-part display-type--block">
								<label for="<?php justwpforms_the_part_id( $part, $form ); ?>_coupon" class="justwpforms-part__label">
									<span class="label"><?php echo $form['coupon_label']; ?></span>
								</label>
								<div class="justwpforms-part__el">
									<div class="justwpforms-input">
										<input id="justwpforms_payment_coupon" type="text" name="justwpforms_payment_coupon" value=""  />
										<button id="justwpforms_coupon_apply" class="justwpforms-button"><?php echo $form['coupon_apply_label']; ?></button>
										<input type="hidden" name="<?php justwpforms_the_part_name( $part, $form ); ?>[coupon]" value="<?php justwpforms_the_part_value( $part, $form, 'coupon' ); ?>" data-default="">
									</div>
									<div class="justwpforms-coupon-notice"></div>
								</div>
							</div>
						</div>
					</div>

					<?php endif; ?>
				<?php else : ?>
					<div class="justwpforms-part justwpforms-form__part justwpforms-payments__user-price">
						<div class="justwpforms-part-wrap">
							<div class="justwpforms-part__el" data-subpart="user_price">
								<label for="<?php justwpforms_the_part_id( $part, $form ); ?>_user_price" class="justwpforms-part__label">
									<span class="label"><?php echo $form['user_price_label']; ?></span>
								</label>

								<div class="justwpforms-input-group with-prefix">
									<div class="justwpforms-input-group__prefix">
										<span><?php echo $currency_symbol; ?></span>
									</div>

									<div class="justwpforms-input">
										<input id="<?php justwpforms_the_part_id( $part, $form ); ?>_user_price" type="number" min="<?php echo $part['user_price_min']; ?>" step="<?php echo $part['user_price_step']; ?>" name="<?php justwpforms_the_part_name( $part, $form ); ?>[price]" value="<?php echo justwpforms_get_part_value( $part, $form, 'price' ); ?>" placeholder="<?php echo $part['user_price_placeholder']; ?>" <?php justwpforms_the_part_attributes( $part, $form, 'price' ); ?> />
									</div>
								</div>
							</div>
						</div>
					</div>
				<?php endif; ?>

				<?php if ( justwpforms_payment_part_has_credit_card( $part, $form ) && justwpforms_payment_part_has_paypal( $part, $form ) ) : ?>
				<div class="justwpforms-part justwpforms-form__part justwpforms-payments__payment-method-choice">
					<div class="justwpforms-part__wrap">
						<?php $payment_method = justwpforms_get_part_value( $part, $form, 'payment_method' ); ?>
						<div class="justwpforms-part justwpforms-part--choice justwpforms-part-options-width--full display-type--block" data-subpart="payment_method">
							<label for="<?php justwpforms_the_part_id( $part, $form ); ?>_payment_method" class="justwpforms-part__label">
								<span class="label"><?php echo $form['payment_method_choice_label']; ?></span>
							</label>
							<div class="justwpforms-part__el">
								<div class="justwpforms-part__option justwpforms-part-option justwpforms-payments__choice-stripe">
									<label class="option-label">
										<input type="radio" class="justwpforms-visuallyhidden" name="<?php justwpforms_the_part_name( $part, $form ); ?>[payment_method]" value="stripe" <?php checked( $payment_method, 'stripe' ); ?>>
										<span class="checkmark">
											<span class="justwpforms-radio-circle"></span>
										</span>
										<span class="label-wrap">
											<span class="label"><?php echo $form['stripe_option_label']; ?></span>
										</span>
									</label>
								</div>
								<div class="justwpforms-part__option justwpforms-part-option justwpforms-payments__choice-paypal">
									<label class="option-label">
										<input type="radio" class="justwpforms-visuallyhidden" name="<?php justwpforms_the_part_name( $part, $form ); ?>[payment_method]" value="paypal" <?php checked( $payment_method, 'paypal' ); ?>>
										<span class="checkmark">
											<span class="justwpforms-radio-circle"></span>
										</span>
										<span class="label-wrap">
											<span class="label"><?php echo $form['paypal_option_label']; ?></span>
										</span>
									</label>
								</div>
							</div>
						</div>
					</div>
				</div>
				<?php elseif ( justwpforms_payment_part_has_credit_card( $part, $form ) ) : ?>
					<input type="hidden" name="<?php justwpforms_the_part_name( $part, $form ); ?>[payment_method]" value="stripe" />
				<?php elseif ( justwpforms_payment_part_has_paypal( $part, $form ) ) : ?>
					<input type="hidden" name="<?php justwpforms_the_part_name( $part, $form ); ?>[payment_method]" value="paypal" />
				<?php endif; ?>

				<?php do_action( 'justwpforms_part_input_after', $part, $form ); ?>
			</div>

			<?php 
			$part_name = justwpforms_get_part_name( $part, $form );
			$notices = justwpforms_get_session()->get_messages( $part_name );

			if ( empty( $notices ) ) {
				$notices[] = array(
					'message' => array(
						'realtime' => '',
					),
				);
			};

			justwpforms_the_part_error_message( $notices, $part_name, 'realtime' );
			?>
		</div>
	</div>
</div>
