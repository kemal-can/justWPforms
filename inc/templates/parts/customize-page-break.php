<script type="text/template" id="customize-justwpforms-page-break-template">
	<div class="justwpforms-widget justwpforms-part-widget" data-part-type="<%= instance.type %>" data-part-id="<%= instance.id %>">
		<div class="justwpforms-widget-top<%= ( ! instance.is_first ) ? ' justwpforms-part-widget-top' : '' %>">
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
				<p>
					<label for="<%= instance.id %>_title"><?php _e( 'Name', 'justwpforms' ); ?></label>
					<input type="text" id="<%= instance.id %>_title" class="widefat title" value="<%- instance.label %>" data-bind="label" />
				</p>
				<p class="justwpforms-goto_next_page">
					<label for="<%= instance.id %>_continue_button_label"><?php _e( '’Continue’ label', 'justwpforms' ); ?></label>
					<input type="text" id="<%= instance.id %>_continue_button_label" class="widefat title" value="<%- instance.continue_button_label %>" data-bind="continue_button_label" />
				</p>
				<p>
					<label for="<%= instance.id %>_css_class"><?php _e( 'Additional CSS class(es)', 'justwpforms' ); ?></label>
					<input type="text" id="<%= instance.id %>_css_class" class="widefat title" value="<%- instance.css_class %>" data-bind="css_class" />
				</p>
				</p>
			</div>

			<div class="justwpforms-widget-actions">
				<% if ( ! instance.is_first ) { %>
				<a href="#" class="justwpforms-form-part-remove"><?php _e( 'Delete', 'justwpforms' ); ?></a> |
				<a href="#" class="justwpforms-form-part-duplicate"><?php _e( 'Duplicate', 'justwpforms' ); ?></a>
				<% } %>
			</div>
		</div>
	</div>
</div>
</script>
