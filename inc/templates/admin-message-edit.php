<?php
global $message, $form;

if ( ! $form ) {
	return;
}
?>

<?php do_action( 'justwpforms_message_edit_screen_before' ); ?>

<div id="namediv" class="stuffbox">
	<div class="inside">
		<h2 class="edit-comment-author"><?php _e( 'Submission', 'justwpforms' ); ?></h2>
		<fieldset>
			<legend class="screen-reader-text"><?php _e( 'Submission', 'justwpforms' ); ?></legend>
			<table class="form-table editcomment justwpforms-edit-message-table" role="presentation">
				<tbody>
				<?php
				$conditional_controller = justwpforms_get_conditional_controller();

				if ( $conditional_controller->has_conditions( $form ) ) {
					$form = $conditional_controller->get( $form, $message['request'] );
				}

				foreach ( $form['parts'] as $p => $part ) {
					$value = $message['parts'][$part['id']];
					do_action( 'justwpforms_message_edit_field', $value, $part, $message, $form );
				}
				?>
				<?php if ( intval( $form['unique_id'] ) ): ?>
				<?php do_action( 'justwpforms_message_edit_field', $message['tracking_id'], array(
					'type' => 'tracking_id',
					'label' => __( 'Tracking number', 'justwpforms' ),
				), $message, $form ); ?>
				<?php endif; ?>
				</tbody>
			</table>
		</fieldset>
	</div>
</div>

<?php do_action( 'justwpforms_message_edit_screen_after' ); ?>
