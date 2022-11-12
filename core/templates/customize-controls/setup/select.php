<div class="customize-control customize-control-select" id="customize-control-<?php echo $control['field']; ?>" data-value="<%= <?php echo $control['field']; ?> %>">
	<?php do_action( "justwpforms_setup_control_{$control['field']}_before", $control ); ?>

	<label for="<?php echo $control['field']; ?>" class="customize-control-title"><?php echo $control['label']; ?></label>
	<select name="<?php echo $control['field']; ?>" id="<?php echo $control['field']; ?>" data-attribute="<?php echo $control['field']; ?>" data-pointer-target>
	<?php if ( isset( $control['placeholder'] ) ) : ?>
		<option value="" selected><?php echo $control['placeholder']; ?></option>
	<?php endif; ?>
	<?php foreach ( $control['options'] as $option => $value ) : ?>
		<?php if ( is_array( $value ) ) : ?>
		<?php if ( '' !== $option ) : ?>
		<optgroup label="<?php echo $option; ?>">
		<?php endif; ?>
			<?php foreach ( $value as $val ) : ?>
			<option value="<?php echo $val['value']; ?>" <% if ( '<?php echo $val['value']; ?>' === <?php echo $control['field']; ?> ) { %>selected="selected"<% } %>><?php echo $val['label']; ?></option>
			<?php endforeach; ?>
		<?php if ( '' !== $option ) : ?>
		</optgroup>
		<?php endif; ?>
		<?php else : ?>
		<option value="<?php echo $option; ?>" <% if ( '<?php echo $option; ?>' === <?php echo $control['field']; ?> ) { %>selected="selected"<% } %>><?php echo $value; ?></option>
		<?php endif; ?>
	<?php endforeach; ?>
	</select>

	<?php do_action( "justwpforms_setup_control_{$control['field']}_after", $control ); ?>
</div>
