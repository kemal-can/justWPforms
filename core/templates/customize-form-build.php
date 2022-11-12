<script type="text/template" id="justwpforms-form-build-template">
	<div class="justwpforms-stack-view">
		<div class="customize-control">
			<input type="text" name="post_title" value="<%- post_title %>" id="justwpforms-form-name" placeholder="<?php _e( 'Add title', 'justwpforms' ); ?>">
		</div>

		<div class="customize-control">
			<div class="justwpforms-parts-placeholder">
				<p><?php _e( 'It doesn\'t look like your form has any fields yet. Want to add one?
Click the "Add a Field" button to start.', 'justwpforms' ); ?></p>
			</div>
			<div class="justwpforms-form-widgets"></div>
			<button type="button" class="button add-new-widget justwpforms-add-new-part"><?php _e( 'Add a Field', 'justwpforms' ); ?></button>
		</div>
	</div>
</script>
