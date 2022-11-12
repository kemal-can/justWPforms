<% if ( '<?php echo $this->service->id; ?>' == justwpforms.form.get( 'active_email_service' ) ) { %>
<p>
	<label for="<%= instance.id %>_constant_contact_field"><?php _e( 'Map field to Constant Contact field', 'justwpforms' ); ?></label>
	<select class="widefat" data-bind="constant_contact_field">
		<option value="" selected><?php _e( '— Select —', 'justwpforms' ); ?></option>
		<%
		var supported = _justwpformsConstantContactSettings.mappings[instance.type];
		var fields = _justwpformsConstantContactSettings.fields;

		fields.forEach( function( field ) { %>
			<% if ( ! field.items ) { %>
				<% if ( supported.indexOf( field.type ) >= 0 ) { %>
				<option value="<%= field.id %>" <% if ( instance.constant_contact_field == field.id ) { %>selected<% } %>><%= field.name %></option>
				<% }; %>
			<% } else { %>
				<optgroup label="<%= field.name %>">
					<% field.items.forEach( function( sub_field ) { %>
						<% if ( supported.indexOf( sub_field.type ) >= 0 ) { %>
						<option value="<%= sub_field.id %>" <% if ( instance.constant_contact_field == sub_field.id ) { %>selected<% } %>><%= sub_field.name %></option>
						<% }; %>
					<% } ); %>
				</optgroup>
			<% } %>
		<% } ); %>
	</select>
</p>
<% } %>
