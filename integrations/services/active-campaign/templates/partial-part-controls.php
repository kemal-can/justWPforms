<% if ( '<?php echo $this->service->id; ?>' == justwpforms.form.get( 'active_email_service' ) ) { %>
<p>
	<label for="<%= instance.id %>_active_campaign_field"><?php _e( 'Map field to ActiveCampaign field', 'justwpforms' ); ?></label>
	<select class="widefat" data-bind="active_campaign_field">
		<option value="" selected><?php _e( '— Select —', 'justwpforms' ); ?></option>
		<%
		var supported = _justwpformsActiveCampaignSettings.mappings[instance.type];
		var list_id = justwpforms.form.get( 'active_campaign_list' );
		var fields = _justwpformsActiveCampaignSettings.fields[list_id];

		if ( fields ) {
			fields.forEach( function( field ) { %>
				<% if ( supported.indexOf( field.type ) >= 0 ) { %>
				<option value="<%= field.id %>" <% if ( instance.active_campaign_field == field.id ) { %>selected<% } %>><%= field.name %></option>
				<% }; %>
			<% } ); %>
		<% } %>
	</select>
</p>
<% } %>
