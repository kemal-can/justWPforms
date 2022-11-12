<div class="justwpforms-form <?php justwpforms_the_form_class( $form ); ?>" id="<?php justwpforms_the_form_container_id( $form ); ?>">
	<?php do_action( 'justwpforms_form_before', $form ); ?>
	<?php justwpforms_message_notices( $form['ID'] ); ?>

	<form action="<?php justwpforms_form_action( $form['ID'] ); ?>" id="<?php justwpforms_the_form_id( $form ); ?>" method="post" <?php justwpforms_the_form_attributes( $form ); ?>>
		<?php do_action( 'justwpforms_form_open', $form ); ?>

		<?php justwpforms_action_field(); ?>
		<?php justwpforms_client_referer_field( $form['ID'] ); ?>
		<?php justwpforms_form_field( $form['ID'] ); ?>
		<?php justwpforms_step_field( $form ); ?>

		<div class="justwpforms-flex">
			<?php justwpforms_honeypot( $form ); ?>
			<?php $parts = apply_filters( 'justwpforms_get_form_parts', $form['parts'], $form ); ?>
			<?php do_action( 'justwpforms_parts_before', $form ); ?>
			<?php foreach ( $parts as $part ) {
				justwpforms_the_form_part( $part, $form );
			} ?>
			<?php do_action( 'justwpforms_parts_after', $form ); ?>
			<?php justwpforms_submit( $form ); ?>
		</div>

		<?php do_action( 'justwpforms_form_close', $form ); ?>
	</form>

	<?php do_action( 'justwpforms_form_after', $form ); ?>
</div>
