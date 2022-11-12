<% if ( '<?php echo $this->service->id; ?>' == justwpforms.form.get( 'active_email_service' ) ) { %>
<p>
	<label for="<%= instance.id %>_mailchimp_field"><?php _e( 'Map field to Mailchimp field', 'justwpforms' ); ?></label>
	<select class="widefat justwpforms-client-updated" data-bind="mailchimp_field" data-source="mailchimp_list" data-var="_justwpformsMailchimpData" data-var-prop="fields">
		<option value="" selected><?php _e( '— Select —', 'justwpforms' ); ?></option>
	</select>
</p>
<% } %>