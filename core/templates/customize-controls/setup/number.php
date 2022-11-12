<div class="customize-control" id="customize-control-<?php echo $control['field']; ?>">
	<label for="<?php echo $control['field']; ?>" class="customize-control-title"><?php echo $control['label']; ?></label>
	<input type="number" id="<?php echo $control['field']; ?>" value="<%= <?php echo $control['field']; ?> %>" data-attribute="<?php echo $control['field']; ?>" min="<?php echo ( isset( $control['min'] ) ) ? $control['min'] : 0; ?>" <?php echo ( isset( $control['max'] ) ) ? ' max="'. $control['max'] .'"' : ''; ?> data-pointer-target />
	<?php if ( isset( $control['description'] ) ): ?>
	<p class="description"><?php echo $control['description']; ?></p>
	<?php endif; ?>
</div>
