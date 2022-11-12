<div class="justwpforms-form__part justwpforms-part justwpforms-form-links">
	<?php if ( empty( $form['redirect_url'] ) ) : ?>
	<button type="button" class="justwpforms-text-button justwpforms-print-submission"><?php echo $form['print_submission_link'];?></button>
	<?php else: ?>
	<p class='justwpforms-redirect-notice'><?php echo $form['submission_redirect_notice']; ?></p>
	<button type="button" class="justwpforms-text-button justwpforms-redirect-to-page" data-url="<?php echo $form['redirect_url'];?>"><?php echo $form['redirect_now_link']; ?></button>
	<?php endif; ?>
</div>