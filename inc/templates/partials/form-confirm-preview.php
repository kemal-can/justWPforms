<div class="justwpforms-form__part justwpforms-part justwpforms-part--submit">
	<button class="submit justwpforms-submit justwpforms-button--submit justwpforms-button--edit"><?php echo $form['edit_button_label']; ?></button>
	<button type="submit" class="justwpforms-submit justwpforms-button--submit" data-step="<?php echo justwpforms_get_last_step( $form, true ); ?>"><?php echo esc_attr( justwpforms_get_form_property( $form, 'submit_button_label' ) ); ?></button>
</div>
