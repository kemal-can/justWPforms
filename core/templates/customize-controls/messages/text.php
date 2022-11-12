<div class="customize-control" id="customize-control-<?php echo $control['field']; ?>">
	<?php do_action( "justwpforms_setup_control_{$control['field']}_before", $control ); ?>

	<label for="<?php echo $control['field']; ?>" class="customize-control-title"><?php echo $control['label']; ?></label>
	<div class="customize-control-reset-wrap">
		<input type="text" id="<?php echo $control['field']; ?>" value="<%- <?php echo $control['field']; ?> %>" data-attribute="<?php echo $control['field']; ?>" placeholder="<?php echo ( isset( $control['placeholder'] ) ) ? $control['placeholder'] : ''; ?>" data-pointer-target<?php echo ( isset( $control['autocomplete'] ) ) ? ' autocomplete="' . $control['autocomplete'] . '"' : ''; ?> />
		<button type="button" class="reset-default button button-secondary" data-default="<?php echo $field['default']; ?>" data-reset="<?php echo $control['field']; ?>"><?php _e( 'Reset', 'justwpforms' ); ?></button>
	</div>

	<?php do_action( "justwpforms_setup_control_{$control['field']}_after", $control ); ?>
</div>
