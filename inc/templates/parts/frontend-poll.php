<?php
$states = justwpforms_get_part_states( justwpforms_get_part_name( $part, $form ) );
$polls_controller = justwpforms_get_polls_controller();
$poll_post = $polls_controller->get_poll_by_id( $form, $part['id'] );
$total_votes = $polls_controller->get_poll_total_votes( $poll_post, $part );
?>

<div class="<?php justwpforms_the_part_class( $part, $form ); ?>" id="<?php justwpforms_the_part_id( $part, $form ); ?>-part" <?php justwpforms_the_part_data_attributes( $part, $form ); ?>>
	<div class="justwpforms-part-wrap">
		<?php justwpforms_the_part_label( $part, $form ); ?>

		<?php justwpforms_print_part_description( $part ); ?>

		<?php do_action( 'justwpforms_part_input_before', $part, $form ); ?>

		<div class="justwpforms-part__el">
			<?php
			$options = justwpforms_get_part_options( $part['options'], $part, $form );
			$value = justwpforms_get_part_value( $part, $form );
			?>

			<?php if ( ! in_array( 'results', $states ) ) : ?>

			<div class="justwpforms-poll-voting">
				<?php
				foreach ( $options as $o => $option ) :
					$checked = false;

					if ( is_string( $value ) ) {
						$checked = ! empty( $option['label'] ) ? checked( $value, $o, false ) : '';
					}

					if ( is_array( $value ) ) {
						$checked = in_array( $o, $value ) ? 'checked="checked"' : '';
					}

					$input_type = 'radio';
					$part_name = justwpforms_get_part_name( $part, $form );

					if ( 1 == intval( $part['allow_multiple'] ) ) {
						$input_type = 'checkbox';
						$part_name = $part_name . '[]';
					}
				?>
					<div class="justwpforms-part__option justwpforms-part-option" id="<?php echo esc_attr( $option['id'] ); ?>">
						<label class="option-label">
							<input type="<?php echo $input_type; ?>" class="justwpforms-visuallyhidden justwpforms-checkbox" name="<?php echo $part_name; ?>" value="<?php echo $o; ?>" data-serialize <?php echo $checked; ?> <?php justwpforms_the_part_attributes( $part, $form ); ?>>
							<span class="checkmark">
								<?php if ( 1 == intval( $part['allow_multiple'] ) ) : ?>
								<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24"><path fill="currentColor" d="M20.285 2l-11.285 11.567-5.286-5.011-3.714 3.716 9 8.728 15-15.285z"/></svg>
								<?php else : ?>
								<span class="justwpforms-radio-circle"></span>
								<?php endif; ?>
							</span>
							<span class="label-wrap">
								<span class="label"><?php echo esc_attr( $option['label'] ); ?></span>
							</span>
						</label>
						<span class="justwpforms-part-option__description"><?php echo esc_attr( $option['description'] ); ?></span>
					</div>
				<?php
				endforeach;
				?>

				<?php do_action( 'justwpforms_part_input_after', $part, $form ); ?>

				<?php if ( 1 == intval( $part['show_results_before_voting'] && 0 < $total_votes ) ) : ?>
					<div class="justwpforms-poll__links">
						<div class="justwpforms-poll__total-votes">
							<svg xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 0 24 24" width="24"><path d="M0 0h24v24H0V0z" fill="none"/><path d="M18 13h-.68l-2 2h1.91L19 17H5l1.78-2h2.05l-2-2H6l-3 3v4c0 1.1.89 2 1.99 2H19c1.1 0 2-.89 2-2v-4l-3-3zm1 7H5v-1h14v1zm-7.66-4.98c.39.39 1.02.39 1.41 0l6.36-6.36c.39-.39.39-1.02 0-1.41L14.16 2.3c-.38-.4-1.01-.4-1.4-.01L6.39 8.66c-.39.39-.39 1.02 0 1.41l4.95 4.95zm2.12-10.61L17 7.95l-4.95 4.95-3.54-3.54 4.95-4.95z"/></svg> <?php echo $total_votes; ?>
						</div>

						<button type="button" class="justwpforms-text-button justwpforms-poll__show-results">
							<svg xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 0 24 24" width="24"><path d="M0 0h24v24H0V0z" fill="none" /><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V5h14v14zM7 10h2v7H7zm4-3h2v10h-2zm4 6h2v4h-2z"/></svg>
							<span><?php echo $form['show_results_label']; ?></span>
						</button>
					</div>
				<?php endif; ?>
			</div>

			<?php endif; ?>

			<?php if ( in_array( 'results', $states ) || 1 == intval( $part['show_results_before_voting'] ) ) : ?>

			<div class="justwpforms-poll-results">
				<?php
				if ( 1 == intval( $part['other_option'] ) ) {
					$options[] = array(
						'id' => 'other',
						'label' => $part['other_option_label']
					);
				}

				$winner_vote = $polls_controller->get_poll_winner_vote( $poll_post, $part );

				$preview_options = array();

				foreach ( $options as $option ) {
					$option_votes      = $polls_controller->get_poll_option_votes( $poll_post, $option );
					$option_percentage = $polls_controller->get_poll_option_votes_percentage( $option_votes, $total_votes );

					$preview_options[$option['id']]               = $option;
					$preview_options[$option['id']]['votes']      = $option_votes;
					$preview_options[$option['id']]['percentage'] = round( $option_percentage );
				}

				array_multisort( array_column( $preview_options, 'votes' ), SORT_ASC, $preview_options );
				?>

				<?php foreach ( $preview_options as $option ) : ?>
					<?php
					$bar_width = 0.25;

					if ( 0 < $option['percentage'] ) {
						$bar_width = $option['percentage'];
					}

					if ( $winner_vote === $option['id'] ) {
						$bar_width = 100;
					}
					?>
					<div class="justwpforms-poll-results__row justwpforms-poll-row">
						<div class="justwpforms-poll-row__track">
							<div class="justwpforms-poll-row__bar<?php echo ( $winner_vote === $option['id'] ) ? ' justwpforms-poll-row__bar--winner' : ''; ?>" style="width: <?php echo $bar_width; ?>%"></div>
						</div>
						<span class="justwpforms-poll-row__votes"><?php echo $option['percentage'] ?>% (<?php echo $option['votes']; ?>)</span> | <span class="justwpforms-poll-row__label"><?php echo $option['label']; ?></span>
					</div>
				<?php endforeach; ?>

				<div class="justwpforms-poll__links">
					<div class="justwpforms-poll__total-votes">
						<svg xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 0 24 24" width="24"><path d="M0 0h24v24H0V0z" fill="none"/><path d="M18 13h-.68l-2 2h1.91L19 17H5l1.78-2h2.05l-2-2H6l-3 3v4c0 1.1.89 2 1.99 2H19c1.1 0 2-.89 2-2v-4l-3-3zm1 7H5v-1h14v1zm-7.66-4.98c.39.39 1.02.39 1.41 0l6.36-6.36c.39-.39.39-1.02 0-1.41L14.16 2.3c-.38-.4-1.01-.4-1.4-.01L6.39 8.66c-.39.39-.39 1.02 0 1.41l4.95 4.95zm2.12-10.61L17 7.95l-4.95 4.95-3.54-3.54 4.95-4.95z"/></svg> <?php echo $total_votes; ?>
					</div>

					<?php if ( ! in_array( 'results', $states ) ) : ?>
						<button type="button" class="justwpforms-text-button justwpforms-poll__back-to-poll">
							<svg xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 0 24 24" width="24"><path d="M0 0h24v24H0V0z" fill="none"/><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V5h14v14zM7 10h2v7H7zm4-3h2v10h-2zm4 6h2v4h-2z"/></svg>
							<span><?php echo $form['back_to_poll_label']; ?></span>
						</button>
					<?php endif; ?>
				</div>
			</div>

			<?php endif; ?>
		</div>

		<?php justwpforms_part_error_message( justwpforms_get_part_name( $part, $form ) ); ?>
	</div>
</div>
