<script type="text/template" id="customize-justwpforms-logic-item">
	<?php
	$condition_constants = justwpforms_Condition::get_constants();
	$and = $condition_constants['AND'];
	?>
	<div class="justwpforms-condition">
		<select class="widefat justwpforms-conditional__operator" style="display: none" disabled>
			<option value="<?php echo $and; ?>"><?php _e( 'And', 'justwpforms' ); ?></option>
		</select>
		<select class="widefat justwpforms-conditional__part"  data-prefix="<?php _e( 'If', 'justwpforms' ); ?> " disabled>
			<option value="" selected disabled><?php _e( 'If field is…', 'justwpforms' ); ?></option>
		</select>
		<select class="widefat justwpforms-conditional__option" data-prefix="<?php _e( 'Is', 'justwpforms' ); ?> " disabled>
			<option value="" selected disabled><?php _e( 'And choice is…', 'justwpforms' ); ?></option>
		</select>
	</div>
</script>
<script type="text/template" id="customize-justwpforms-logic-part-dropdown-template">
	<option value="<%= data.id %>" data-label="<%= ( '' !== data.label ? data.label : _justwpformsSettings.unlabeledFieldLabel ) %>"><%= ( '' !== data.label ? data.label : _justwpformsSettings.unlabeledFieldLabel ) %></option>
</script>
<script type="text/template" id="customize-justwpforms-logic-value-dropdown-template">
	<option value="<%= data.index %>" data-label="<%= data.option.label %>" data-option-id="<%= data.option.id %>"><%= data.option.label %></option>
</script>
