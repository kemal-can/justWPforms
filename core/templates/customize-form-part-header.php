<div class="justwpforms-widget justwpforms-part-widget" data-part-type="<%= instance.type %>" data-part-id="<%= instance.id %>">
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
