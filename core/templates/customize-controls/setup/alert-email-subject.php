<div class="customize-control" id="customize-control-<?php echo $control['field']; ?>">
	<label for="<?php echo $control['field']; ?>" class="customize-control-title"><?php echo $control['label']; ?> </label>
	<input type="text" id="<?php echo $control['field']; ?>" value="<%= <?php echo $control['field']; ?> %>" data-attribute="<?php echo $control['field']; ?>" data-pointer-target />
	<p class="description"><span></span> <?php _e( 'part value is currently used as subject', 'justwpforms' ); ?>
</div>
