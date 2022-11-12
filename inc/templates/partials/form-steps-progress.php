<?php if ( ! justwpforms_is_preview() ) : ?>
	<div class="justwpforms-form__progress justwpforms-form-progress">
		<?php
		  $form_controller = justwpforms_get_form_controller();
		  $steps = $form_controller->get_parts_by_type( $form, 'page_break' );
		  $total_steps = count( $steps );
		  $step_index = justwpforms_get_current_page_break( $form, true );
		  $current_part_step = $steps[ $step_index ];
			$multistep_back_label = $form['multi_step_back_label'];
			$multi_step_current_page_label = $form['multi_step_current_page_label'];

			$submitted_forms = $step_index;
		?>

		<div class="justwpforms-flex justwpforms-step_information_wrapper">
			<div class="justwpforms-message-notice justwpforms-step-wrapper-notice">
				<?php if ( $step_index + 1 > 1  ) : ?>
					<button type="button" data-step="-<?php echo ( $step_index - 1 ); ?>" class="submit justwpforms-submit justwpforms-button--submit justwpforms-back-step"><?php echo $multistep_back_label; ?></button>
				<?php endif; ?>
				<span class="justwpforms-form-progress__step-index justwpforms-form-progress__step-title"><?php echo sprintf( __( '%s %s/%s: %s', 'justwpforms' ), $multi_step_current_page_label, ( $step_index + 1 ),  $total_steps, $current_part_step['label'] ); ?></span>
			</div>
		</div>

	</div>
<?php endif; ?>
