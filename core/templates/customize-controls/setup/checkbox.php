<div class="customize-control customize-control-checkbox <% if ( <?php echo $control['field']; ?> ) { %>checked<% } %>" id="customize-control-<?php echo $control['field']; ?>">
	<div class="customize-inside-control-row" data-pointer-target>
		<input type="checkbox" id="<?php echo $control['field']; ?>" value="1" <% if ( <?php echo $control['field']; ?> ) { %>checked="checked"<% } %> data-attribute="<?php echo $control['field']; ?>" />
		<label for="<?php echo $control['field']; ?>"><?php echo $control['label']; ?></label>
	</div>
</div>
