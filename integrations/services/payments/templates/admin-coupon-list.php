<div class="wrap nosubsub">
	<h1 class="wp-heading-inline"><?php echo $labels->name;?></h1>
	<hr class="wp-header-end">
	<?php if ( $message ) : ?>
	<div id="message" class="notice notice-<?php echo $message['class']; ?> is-dismissible">
		<p><?php echo $message['text']; ?></p>
	</div>
	<?php endif; ?>
	<div id="ajax-response"></div>
	<div id="col-container" class="wp-clearfix">
		<div id="col-left">
			<div class="col-wrap">
				<div class="form-wrap">
					<h2><?php echo $labels->add_new_item; ?></h2>
					<form id="add-justwpforms-coupon" name="add-justwpforms-coupon" method="post" action="" class="validate">
						<input type="hidden" name="action" value="justwpforms_add_coupon" />
						<input type="hidden" name="screen" value="<?php echo esc_attr( $current_screen->id ); ?>" />
						<input type="hidden" name="ID" value="<?php echo $coupon_id; ?>" />
						<?php wp_nonce_field( $post_type . '-nonce', $post_type . '-nonce' ); ?>
						<div class="form-field form-required">
							<label for="post_title"><?php _e( 'Name', 'justwpforms' ); ?></label>
							<input name="post_title" id="post_title" type="text" value="" size="40" aria-required="true" />
							<p><?php _e( 'This is what will be applied by the submitter to receive a discount. The coupon must be unique and contain no spaces.', 'justwpforms'); ?></p>
						</div>
						<div class="form-field form-required">
							<label for=""><?php _e( 'Discount Type', 'justwpforms' ); ?></label>
							<span class="justwpforms-buttongroup justwpforms-buttongroup-field_width">
								<label for="discount_type_fixed">
									<input type="radio" id="discount_type_fixed" value="fixed" name="discount_type" checked="" />
									<span><?php _e( 'Fixed', 'justwpforms' ); ?></span>
								</label>
								<label for="discount_type_percentage">
									<input type="radio" id="discount_type_percentage" value="percentage" name="discount_type" />
									<span><?php _e( 'Percentage', 'justwpforms' ); ?></span>
								</label>
							</span>
						</div>
						<div class="form-field form-required">
							<label class="labels-dicount_type-fixed" for="discount_amount"><?php _e( 'Discount Amount', 'justwpforms' ); ?></label>
							<label class="labels-dicount_type-percentage" for="discount_amount"><?php _e( 'Discount Percentage', 'justwpforms' ); ?></label>
							<input name="discount_amount" id="discount_amount" type="number" value="" size="40" aria-required="true" min="0" />
							<p class="details-discount_type-fixed"><?php _e( 'This amount automatically converts to whatever currency the form uses. For example, if this amount is &#8220;5&#8221; and the form uses dollars, the discount is $5. If the form uses euro, the discount is €5.', 'justwpforms' ); ?></p>
						</div>
						<div class="form-field">
							<label for="description"><?php _e( 'Description', 'justwpforms' ); ?></label>
							<textarea name="description" id="description" rows="5" cols="40"></textarea>
							<p><?php _e( 'This will not be seen by submitters.', 'justwpforms' ); ?></p>
						</div>
						<p class="submit">
							<input type="submit" name="submit" id="submit" class="button button-primary" value="<?php echo $labels->add_new_item; ?>" /><span class="spinner"></span>
						</p>
					</form>
				</div>
			</div>
		</div>
		<div id="col-right">
			<div class="col-wrap">
				<form id="coupons-filter" method="post">
				<?php echo wp_nonce_field( $post_type . '-nonce', $post_type . '-nonce' ); ?>
				<?php $wp_list_table->views(); ?>
				<?php $wp_list_table->display(); ?>
				</form>
				<?php $wp_list_table->inline_edit(); ?>
			</div>
		</div>
	</div>
</div>