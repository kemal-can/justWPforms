<div class="customize-control" id="customize-control-<?php echo $control['field']; ?>">
	<label for="<?php echo $control['field']; ?>" class="customize-control-title"><?php echo $control['label']; ?></label>
	<div data-pointer-target>
		<textarea name="" id="<?php echo $control['field']; ?>" cols="34" rows="4" data-attribute="<?php echo $control['field']; ?>"><%= <?php echo $control['field']; ?> %></textarea>
		<?php if( ! empty( $control['description'] ) ): ?>
		<p class="description"><?php echo $control['description']; ?></p>
		<?php endif; ?>
	</div>
</div>
