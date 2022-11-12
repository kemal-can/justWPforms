<script type="text/template" id="customize-justwpforms-logic-group">
	<fieldset class="justwpforms-conditional__group">
		<% if ( options && 'part' == options.type ) { %>
			<select class="widefat justwpforms-conditional__action" data-show-prefix="<?php _e( 'Show', 'justwpforms' ); ?>" data-hide-prefix="<?php _e( 'Hide', 'justwpforms' ); ?>">
				<option value="" selected disabled><?php _e( 'This field will…', 'justwpforms' ); ?></option>
				<option value="show"><?php _e( 'Show', 'justwpforms' ); ?></option>
				<option value="hide"><?php _e( 'Hide', 'justwpforms' ); ?></option>
			</select>
		<% } else if ( options && 'option' == options.type ) { %>
			<select class="widefat justwpforms-conditional__action" data-show_option-prefix="<?php _e( 'Show', 'justwpforms' ); ?>" data-hide_option-prefix="<?php _e( 'Hide', 'justwpforms' ); ?>">
				<% if ( 'heading' == options.subtype ) { %>
				<option value="" selected disabled><?php _e( 'This heading will…', 'justwpforms' ); ?></option>
				<% } else { %>
				<option value="" selected disabled><?php _e( 'This choice will…', 'justwpforms' ); ?></option>
				<% } %>
				<option value="show_option"><?php _e( 'Show', 'justwpforms' ); ?></option>
				<option value="hide_option"><?php _e( 'Hide', 'justwpforms' ); ?></option>
			</select>
		<% } %>

		<div class="justwpforms-conditional__static">
			<% if ( options && 'set' == options.type ) { %>
			<input type="text" placeholder="<%= options.thenText %>" data-then-value>
			<% } else if ( options && 'select' == options.type ) { %>
			<select data-then-value>
				<option value="" selected disabled><%= options.thenText %></option>
				<% _( options.options ).each( function( option, index ) { %>
					<% if ( 'undefined' !== typeof option.options ) { %>
						<% if ( '' !== option.title ) { %>
						<optgroup label="<%= option.title %>">
						<% } %>
							<% _( option.options ).each( function( suboption, subindex ) { %>
								<option value="<%= suboption.value %>"><%= suboption.label %></option>
							<% } ); %>
						<% if ( '' !== option.title ) { %>
						</optgroup>
						<% } %>
					<% } else { %>
					<option value="<%= index %>"><%= option %></option>
					<% } %>
				<% } ); %>
			</select>
			<% } else if ( options && 'template' == options.type ) { %>
				<% print( options.template( obj ) ); %>
			<% } %>

			<div class="justwpforms-conditional__tools">
				<a href="#" class="justwpforms-conditional__delete"><?php _e( 'Delete', 'justwpforms' ); ?></a> | <a class="justwpforms-conditional__add" href="#"><?php _e( 'Add condition', 'justwpforms' ); ?></a>
			</div>
		</div>
	</fieldset>
</script>
