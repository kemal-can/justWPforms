<div class="justwpforms-form justwpforms-form--hide-progress-bar <?php justwpforms_the_form_class( $form ); ?>" id="<?php justwpforms_the_form_container_id( $form ); ?>">
	<?php do_action( 'justwpforms_form_before', $form ); ?>
	
	<form class="justwpforms-form--password-protect" action="<?php justwpforms_form_action( $form['ID'] ); ?>" id="<?php justwpforms_the_form_id( $form ); ?>" method="post" <?php justwpforms_the_form_attributes( $form ); ?>>
		<?php do_action( 'justwpforms_form_open', $form ); ?>

		<?php justwpforms_action_field(); ?>
		<?php justwpforms_form_field( $form['ID'] ); ?>
		<?php justwpforms_step_field( $form ); ?>

		<div class="justwpforms-flex">
			<?php justwpforms_message_notices( $form['ID'] ); ?>
			<?php justwpforms_honeypot( $form ); ?>
			<div class="justwpforms-form__part justwpforms-part justwpforms-part--form-password justwpforms-part--width-auto" data-justwpforms-type="password">
				<div class="justwpforms-part-wrap">
					<div class="justwpforms-part__el">
						<input type="password" name="justwpforms_password" id="justwpforms-<?php echo esc_attr( $form['ID'] ); ?>_password" placeholder="<?php echo $form['password_input_placeholder']; ?>">
					</div>
				</div>
			</div>
			<?php justwpforms_password_submit( $form ); ?>
		</div>

		<?php do_action( 'justwpforms_form_close', $form ); ?>
	</form>

	<?php do_action( 'justwpforms_form_after', $form ); ?>
</div>