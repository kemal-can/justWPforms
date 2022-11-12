<div class="customize-control customize-control-buttongroup <% if ( <?php echo $control['field']; ?> ) { %>checked<% } %>" id="customize-control-<?php echo $control['field']; ?>" data-value="<%= <?php echo $control['field']; ?> %>">
	<label class="customize-control-title" for="<?php echo $control['field']; ?>"><?php echo $control['label']; ?></label>
	<div class="justwpforms-buttongroup-wrapper">
		<span class="justwpforms-buttongroup justwpforms-buttongroup-<?php echo $control['label']; ?>">
			<?php foreach( $control['options'] as $id => $label ): ?>
			<label for="<?php echo $control['field']; ?>_<?php echo $label; ?>">
				<input type="radio" id="<?php echo $control['field']; ?>_<?php echo $label; ?>" value="<?php echo $id; ?>" name="<?php echo $control['field']; ?>" data-attribute="<?php echo $control['field']; ?>" <% if ( '<?php echo $id; ?>' === <?php echo $control['field']; ?> ) { %>checked<% } %> />
				<span><?php echo $label; ?></span>
			</label>
			<?php endforeach; ?>
		</span>
	</div>
</div>
