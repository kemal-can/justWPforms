<script id="justwpforms-customize-mailchimp-groups-template" type="text/template">
	<% _( groups ).each( function( group, index ) { %>
		<h4><%= group.title %></h4>

		<% if ( 'dropdown' === group.type ) { %>
		<select class="widefat">
			<option value="" disabled selected><?php _e( 'Select group', 'justwpforms' ); ?></option>
			<% _( group.options ).each( function( option, index ) { %>
			<option value="<%= option.value %>"<%= ( -1 !== value.indexOf( option.value ) ) ? ' selected' : '' %>><%= option.label %></option>
			<% } ); %>
		</select>
		<% } %>

		<% if ( 'radio' === group.type || 'hidden' === group.type ) { %>
			<% _( group.options ).each( function( option, index ) { %>
			<div>
				<label>
					<input type="radio" name="<%= group.id %>" value="<%= option.value %>"<%= ( -1 !== value.indexOf( option.value ) ) ? ' checked' : '' %>> <%= option.label %>
				</label>
			</div>
			<% } ); %>
		<% } %>

		<% if ( 'checkboxes' === group.type ) { %>
			<% _( group.options ).each( function( option, index ) { %>
			<div>
				<label>
					<input type="checkbox" name="<%= group.id %>" value="<%= option.value %>"<%= ( -1 !== value.indexOf( option.value ) ) ? ' checked' : '' %>> <%= option.label %>
				</label>
			</div>
			<% } ); %>
		<% } %>
	<% } ); %>
</script>
