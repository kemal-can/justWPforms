<div class="wrap">
<h1><?php echo $labels->edit_item; ?></h1>
<?php if ( $message ) : ?>
<div id="message" class="notice notice-<?php echo $message['class']; ?> is-dismissible">
	<p><strong><?php echo $message['text']; ?></strong></p>
	<p>
		<a href="<?php echo esc_url ( admin_url( 'admin.php?page='. $_GET['page'] ) ); ?>">&larr; <?php _e( 'Go to Coupons', 'justwpforms' ); ?></a>
	</p>
</div>
<?php endif; ?>
<div id="ajax-response"></div>
	<form id="edit-justwpforms-coupon" name="edit-justwpforms-coupon" method="post" action="" class="validate">
		<input type="hidden" name="action" value="justwpforms_edit_coupon">
		<input type="hidden" name="screen" value="<?php echo esc_attr( $current_screen->id ); ?>" />
		<input type="hidden" name="ID" value="<?php echo $coupon_id; ?>">
		<?php wp_nonce_field( $post_type . '-nonce', $post_type . '-nonce' ); ?>
		<table class="form-table" role="presentation">
			<tbody>
				<tr class="form-field form-required">
					<th scope="row"><label for="post_title"><?php _e( 'Name', 'justwpforms' ); ?></label></th>
					<td>
						<input name="post_title" id="post_title" type="text" value="<?php echo $coupon['post_title']; ?>" size="40" aria-required="true" />
						<p class="description"><?php _e( 'This is what will be applied by the submitter to receive a discount. The coupon must be unique and contain no spaces.', 'justwpforms'); ?></p>
					</td>
				</tr>
				<tr class="form-field form-required">
					<th scope="row"><label for="discount_type"><?php _e( 'Discount Type', 'justwpforms' ); ?></label></th>
					<td>
						<?php
							$discount_type = $coupon['discount_type'];
						?>
						<span class="justwpforms-buttongroup justwpforms-buttongroup-field_width">
							<label for="discount_type_fixed">
								<input type="radio" id="discount_type_fixed" value="fixed" name="discount_type" <?php echo 'fixed' === $discount_type ? 'checked="checked"' : ''; ?> />
								<span><?php _e( 'Fixed', 'justwpforms' ); ?></span>
							</label>
							<label for="discount_type_percentage">
								<input type="radio" id="discount_type_percentage" value="percentage" name="discount_type" <?php echo 'percentage' === $discount_type ? 'checked="checked"' : ''; ?> />
								<span><?php _e( 'Percentage', 'justwpforms' ); ?></span>
							</label>
						</span>
					</td>
				</tr>
				<tr class="form-field form-required">
					<th scope="row"><label class="labels-dicount_type-fixed" for="discount_amount" <?php echo ( 'percentage' === $discount_type ) ? ' style="display:none;"' : ''; ?>><?php _e( 'Discount Amount', 'justwpforms' ); ?></label>
							<label class="labels-dicount_type-percentage" for="discount_amount" <?php echo ( 'percentage' === $discount_type ) ? ' style="display:block;"' : ''; ?>><?php _e( 'Discount Percentage', 'justwpforms' ); ?></label></th>
					<td>
						<input name="discount_amount" id="discount_amount" type="number" value="<?php echo $coupon['discount_amount']; ?>" size="40" aria-required="true" min="0" <?php echo 'percentage' === $discount_type ? 'max="100"' : ''; ?> />
						<p class="description details-discount_type-fixed" <?php echo ( 'percentage' === $discount_type ) ? ' style="display:none;"' : ''; ?>><?php _e( 'This amount automatically converts to whatever currency the form uses. For example, if this amount is &#8220;5&#8221; and the form uses dollars, the discount is $5. If the form uses euro, the discount is €5.', 'justwpforms' ); ?></p>
						<p class="description details-discount_type-percentage" style="margin: 0;"></p>
					</td>
				</tr>
				<tr class="form-field form-required">
					<th scope="row"><label for="description"><?php _e( 'Description', 'justwpforms' ); ?></label></th>
					<td>
						<textarea name="description" id="description" rows="5" cols="40"><?php echo $coupon['description']; ?></textarea>
						<p class="description"><?php _e( 'This will not be seen by submitters.', 'justwpforms' ); ?></p>
					</td>
				</tr>
			</tbody>
		</table>
		<div class="edit-tag-actions">
			<input type="submit" class="button button-primary" value="Update" />
			<span id="delete-link">
				<a class="delete" href="<?php echo esc_url ( admin_url( wp_nonce_url('admin.php?page='. $_GET['page'] . '&coupon_ID=' . $coupon_id . '&action=justwpforms_delete_coupon', $post_type . '-nonce' ) ) ); ?>">Delete</a>
			</span>

		</div>
	</form>
</div>