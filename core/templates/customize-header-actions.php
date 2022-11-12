<script type="text/template" id="justwpforms-customize-header-actions">
	<div id="justwpforms-save-button-wrapper" class="customize-save-button-wrapper">
		<%
		var buttonLabels = {
			"saveNew" : "<?php _e( 'Save', 'justwpforms' ); ?>",
			"saveExisting" : "<?php _e( 'Update', 'justwpforms' ); ?>",
			"savedNew" : "<?php _e( 'Saved', 'justwpforms' ); ?>",
			"savedExisting" : "<?php _e( 'Updated', 'justwpforms' ); ?>"
		};

		var saveLabel = buttonLabels.saveNew;
		var savedLabel = buttonLabels.savedNew;

		if ( ! isNewForm ) {
			saveLabel = buttonLabels.saveExisting;
			savedLabel = buttonLabels.savedExisting;
		}
		%>
		<button id="justwpforms-save-button" class="button-primary button" aria-label="<%= saveLabel %>" aria-expanded="false" disabled="disabled" data-text-saved="<%= savedLabel %>" data-text-default="<%= saveLabel %>"><%= saveLabel %></button>
	</div>
	<a href="<?php echo esc_url( $wp_customize->get_return_url() ); ?>" id="justwpforms-close-link" data-message="<?php _e( 'The changes you made will be lost if you navigate away from this page.', 'justwpforms' ); ?>">
		<span class="screen-reader-text"><?php _e( 'Close', 'justwpforms' ); ?></span>
	</a>

	<div id="justwpforms-steps-nav">
	</div>
</script>
