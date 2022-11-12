<div class="justwpforms-form justwpforms-form--scheduled <?php justwpforms_the_form_class( $form ); ?>" id="<?php justwpforms_the_form_container_id( $form ); ?>">
	<?php do_action( 'justwpforms_form_before', $form ); ?>
 
	<form id="<?php justwpforms_the_form_id( $form ); ?>" <?php justwpforms_the_form_attributes( $form ); ?>>
		<?php do_action( 'justwpforms_form_open', $form ); ?>

		<div class="justwpforms-flex">
			<?php justwpforms_message_notices( $form['ID'] ); ?>

			<p><?php echo html_entity_decode( $form['scheduled_message'] ); ?></p>
		</div>

		<?php do_action( 'justwpforms_form_close', $form ); ?>
	</form>
	
	<?php do_action( 'justwpforms_form_after', $form ); ?>
</div>