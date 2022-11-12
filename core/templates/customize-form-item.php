<script type="text/template" id="justwpforms-form-item-template">
	<li class="customize-control">
		<div class="justwpforms-widget">
			<div class="justwpforms-widget-top justwpforms-part-widget-top">
				<div class="justwpforms-part-widget-title-action">
					<button type="button" class="justwpforms-widget-action">
						<span class="screen-reader-text"><%= post_title %></span>
						<span class="toggle-indicator"></span>
					</button>
				</div>
				<div class="justwpforms-widget-title">
					<h3><%= post_title %></h3>
				</div>
			</div>
			<div class="justwpforms-widget-content">
				<ul class="form-actions">
					<li>
						<a href="#" data-href="form/<%= ID %>/build" class="form-action-link form-action-build-link"><?php _e( 'Add Part', 'justwpforms' ); ?></a>
					</li>
					<li>
						<a href="#" data-href="form/<%= ID %>" class="form-action-link form-action-setup-link"><?php _e( 'Setup', 'justwpforms' ); ?></a>
					</li>
					<li>
						<a href="#" data-href="form/<%= ID %>/style" class="form-action-link form-action-style-link"><?php _e( 'Style', 'justwpforms' ); ?></a>
					</li>
					<li>
						<a href="#" data-href="form/<%= ID %>" class="form-action-link form-action-duplicate-link"><?php _e( 'Duplicate', 'justwpforms' ); ?></a>
					</li>
				</ul>
			</div>
			<div class="justwpforms-widget-footer">
				<div class="justwpforms-widget-actions">
					<a href="#" data-href="form/<%= ID %>/remove" class="justwpforms-form-remove"><?php _e( 'Delete', 'justwpforms' ); ?></a> |
					<a href="#" data-href="form/<%= ID %>" class="justwpforms-form-preview"><?php _e( 'Preview', 'justwpforms' ); ?></a>
				</div>
			</div>
		</div>
	</li>
</script>
