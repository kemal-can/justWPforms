<div class="customize-control customize-control-<?php echo $control['field']; ?> <% if ( <?php echo $control['field']; ?> ) { %>checked<% } %>" data-type="mailchimp-groups" id="customize-control-<?php echo $control['field']; ?>">
	<?php do_action( "justwpforms_setup_control_{$control['field']}_before", $control ); ?>

	<p class="customize-control-title"><?php echo $control['label']; ?></p>

	<div class="customize-inside-control-row" style="margin-left: 0" data-pointer-target>
		<div class="customize-control-options">
		</div>
		<p class="description no-groups"><?php echo $control['no_options']; ?></p>
	</div>

	<?php do_action( "justwpforms_setup_control_{$control['field']}_after", $control ); ?>
</div>
