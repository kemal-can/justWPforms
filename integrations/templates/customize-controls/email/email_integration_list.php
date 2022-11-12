<?php
$services = justwpforms_get_integrations()->get_service_group('email');
?>
<div class="customize-control customize-control-select" id="customize-control-<?php echo $control['field']; ?>" data-value="<%= <?php echo $control['field']; ?> %>">
	<?php do_action( "justwpforms_setup_control_{$control['field']}_before", $control ); ?>

	<label for="<?php echo $control['field']; ?>" class="customize-control-title">Connect with</label>
	<select name="<?php echo $control['field']; ?>" id="<?php echo $control['field']; ?>" data-attribute="<?php echo $control['field']; ?>" data-pointer-target>
		<option value="" <% if ( '' === <?php echo $control['field']; ?> ) { %>selected="selected"<% } %>>Don’t use any service</option>
		<?php foreach( $services as $service ) : ?>
			<?php if( $service->is_connected() ) : ?>
				<option value="<?php echo $service->id; ?>" <% if ( '<?php echo $service->id; ?>' === <?php echo $control['field']; ?> ) { %>selected="selected"<% } %>><?php echo $service->label; ?></option>
			<?php endif; ?>
		<?php endforeach; ?>
	</select>

	<?php do_action( "justwpforms_setup_control_{$control['field']}_after", $control ); ?>
</div>
