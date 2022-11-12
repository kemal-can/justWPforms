<div class="justwpforms-settings-notices"></div>

<form class="hf-ajax-submit">
	<div class="controls">
	<?php
	$validation_messages_controller = justwpforms_validation_messages_upgrade();
	$validation_messages = $validation_messages_controller->get_default_messages();

	foreach ( $validation_messages as $message_key => $message ) :
		$label = __( 'Rewrite', 'justwpforms' ) . ' “' . $message . '“';
		$checked = checked( $validation_messages_controller->is_custom_message( $message_key ), true, false );
	?>
	<div class="control">
		<div class="control__line">
			<input type="checkbox" name="custom_<?php echo esc_attr( $message_key ); ?>" value="1" aria-label="<?php echo esc_attr( $label ); ?>" id="custom_<?php echo esc_attr( $message_key ); ?>" <?php echo $checked; ?>>
			<label for="custom_<?php echo esc_attr( $message_key ); ?>"><?php echo $label; ?></label>

			<div class="nested-input">
				<input type="text" name="<?php echo esc_attr( $message_key ); ?>" value="<?php echo $validation_messages_controller->get_message( $message_key ); ?>" class="widefat">
			</div>
		</div>
	</div>
	<?php endforeach; ?>
	</div>

	<?php wp_nonce_field( 'justwpforms_save_validation_messages', 'justwpforms-validation-messages-nonce' ); ?>
	<input type="hidden" name="action" value="justwpforms_save_validation_messages">

	<div class="alignleft">
		<span class="spinner"></span>
		<input type="submit" class="button button-primary" value="<?php _e( 'Save Changes', 'justwpforms' ); ?>">
	</div>
	<br class="clear">
</form>
