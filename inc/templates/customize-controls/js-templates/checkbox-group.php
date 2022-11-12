<script id="justwpforms-checkbox-group-js-template" type="text/template">
	<% _( data ).each( function( group, index ) { %>
		<h4><%= group.title %></h4>

		<% _( group.options ).each( function( subgroupLabel, subgroupKey ) { %>
			<div>
				<label>
					<input type="checkbox" id="<%= data.field %>_<%= subgroupKey %>" data-attribute="<%= data.field %>" value="<%= subgroupKey %>" data-multiple="true"<%= ( -1 !== justwpforms.form.get( data.field ).indexOf( subgroupKey ) ) ? ' checked': '' %>> <%= subgroupLabel %>
				</label>
			</div>
		<% } ); %>
	<% } ); %>
</script>
