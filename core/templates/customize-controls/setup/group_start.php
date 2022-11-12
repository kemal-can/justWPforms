<?php
	$trigger = '';
	$group_id = '';
	$group_class = 'justwpforms-nested-settings';
	$group_title = isset( $control['group_title'] ) ? $control['group_title'] : '';
	$group_description = isset( $control['group_description'] ) ? $control['group_description'] : '';
	$group_type = isset( $control['group_type'] ) ? $control['group_type'] : '';

	if ( $group_type == 'group') {
		$group_class = 'justwpforms-group-settings';
	}


	if ( isset( $control['group_id'] ) && ! empty( $control['group_id'] ) ) {
		$trigger = 'id="' . $control['group_id'] . '"';
	}

	if ( isset( $control['trigger'] ) && ! empty( $control['trigger'] ) ) {
		$trigger = 'data-trigger="' . $control['trigger'] . '"';
	}
?>
<section class="customize-control-group <?php echo $group_class; ?>" <?php echo $group_id . ' ' . $trigger; ?>>
<?php if ( ! empty( $group_title ) ) : ?>
	<span class="customize-control-title"><?php echo $group_title; ?></span>
<?php endif; ?>
<?php if ( ! empty( $group_description ) ) : ?>
	<p class="justwpforms-group-settings-description"><?php echo $group_description; ?></span>
<?php endif; ?>
