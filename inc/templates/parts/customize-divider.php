<script type="text/template" id="customize-justwpforms-divider-template">
	<div class="justwpforms-widget justwpforms-part-widget" data-part-id="<%= instance.id %>">
		<div class="justwpforms-widget-top justwpforms-part-widget-top">
			<div class="justwpforms-part-widget-title-action">
				<button type="button" class="justwpforms-widget-action">
					<span class="toggle-indicator"></span>
				</button>
			</div>
			<div class="justwpforms-widget-title">
				<h3><%= settings.label %><span class="in-widget-title"<% if (!instance.label) { %> style="display: none"<% } %>>: <span><%= (instance.label) ? instance.label : '' %></span></span></h3>
			</div>
		</div>
		<div class="justwpforms-widget-content">
			<div class="justwpforms-widget-form">
				<?php justwpforms_customize_part_width_control(); ?>

				<p>
					<label for="<%= instance.id %>_css_class"><?php _e( 'Additional CSS class(es)', 'justwpforms' ); ?></label>
					<input type="text" id="<%= instance.id %>_css_class" class="widefat title" value="<%- instance.css_class %>" data-bind="css_class" />
				</p>
			</div>

			<div class="justwpforms-widget-actions">
				<a href="#" class="justwpforms-form-part-remove"><?php _e( 'Delete', 'justwpforms' ); ?></a> |
				<a href="#" class="justwpforms-form-part-duplicate"><?php _e( 'Duplicate', 'justwpforms' ); ?></a>
			</div>
		</div>
	</div>
</div>
</script>
