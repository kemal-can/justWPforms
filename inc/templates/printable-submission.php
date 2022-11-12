<?php foreach( $form['parts'] as $part ) : ?>

	<?php if ( justwpforms_email_is_part_visible( $part, $form, $message ) ) : ?>

	<?php $label = justwpforms_get_email_part_label( $message, $part, $form ); ?>

	<?php if ( '' !== $label ) : ?>
		<b><?php echo $label; ?></b><br>
	<?php endif; ?>

	<?php echo justwpforms_get_email_part_value( $message, $part, $form, 'admin-email' ); ?>
	<br><br>

	<?php endif; ?>

<?php endforeach; ?>

<b><?php _e( 'IPv4/IPv6', 'justwpforms' ); ?></b><br>
<?php echo justwpforms_get_meta( $message['ID'], 'client_ip', true ); ?>