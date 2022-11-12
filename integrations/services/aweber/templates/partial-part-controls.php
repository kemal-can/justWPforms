<% if ( '<?php echo $this->service->id; ?>' == justwpforms.form.get( 'active_email_service' ) ) { %>
<p>
	<label for="<%= instance.id %>_aweber_field"><?php _e( 'Map field to AWeber field', 'justwpforms' ); ?></label>
	<select class="widefat justwpforms-client-updated" data-bind="aweber_field" data-source="aweber_list" data-var="_justwpformsAweberSettings" data-var-prop="fields">
		<option value="" selected><?php _e( '— Select —', 'justwpforms' ); ?></option>
	</select>
</p>
<% } %>
