<script type="text/template" id="customize-justwpforms-logic-email-part-lists-then-value">
	<select data-then-value>
		<option value="" selected disabled><%= options.thenText %></option>
        <%
        var fields = justwpforms.form.get( 'parts' ).where( { type: 'email' } );

        fields.forEach( function( field ) { %>
        <option value="<%= field.get( 'id' ) %>">"<%= ( '' !== field.get( 'label' ) ? field.get( 'label' ) : _justwpformsSettings.unlabeledFieldLabel ) %>" <?php _e( 'field', 'justwpforms' ); ?></option>
        <% } ); %>

        <option value="all"><?php _e( 'All Email fields', 'justwpforms' ); ?></option>
    </select>
</script>
