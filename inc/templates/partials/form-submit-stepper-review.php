<?php
	$submit_button_extra_class = '';
	if( justwpforms_get_form_property( $form, 'add_submit_button_class' ) == 1 ) {
		$submit_button_extra_class = justwpforms_get_form_property( $form, 'submit_button_html_class' );
	} ?>
<div class="justwpforms-form__part justwpforms-part justwpforms-part--submit">
	<?php do_action( 'justwpforms_form_submit_before', $form ); ?>
	<button type="submit" class="justwpforms-submit justwpforms-button--submit <?php echo $submit_button_extra_class; ?>" data-step="<?php echo justwpforms_get_last_step( $form, true ); ?>"><?php echo esc_attr( justwpforms_get_form_property( $form, 'submit_button_label' ) ); ?></button>
	<?php do_action( 'justwpforms_form_submit_after', $form ); ?>
</div>
