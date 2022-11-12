<script type="text/template" id="justwpforms-customize-title-template">
	<?php include( justwpforms_get_core_folder() . '/templates/customize-form-part-header.php' ); ?>
	<p class="label-field-group">
		<label for="<%= instance.id %>_title"><?php _e( 'Label', 'justwpforms' ); ?></label>
		<div class="label-group">
			<input type="text" id="<%= instance.id %>_title" class="widefat title" value="<%- instance.label %>" data-bind="label" />
			<select id="<%= instance.id %>_label_placement" name="label_placement" data-bind="label_placement" class="widefat">
				<option value="show"<%= (instance.label_placement == 'show') ? ' selected' : '' %>><?php _e( 'Show', 'justwpforms' ); ?></option>
				<% if ( 'left' == instance.label_placement ) { %>
					<option value="left" selected><?php _e( 'Left', 'justwpforms' ); ?></option>
				<% } %>
				<% if ( 'below' == instance.label_placement ) { %>
					<option value="below" selected><?php _e( 'Below', 'justwpforms' ); ?></option>
				<% } %>
				<option value="hidden"<%= (instance.label_placement == 'hidden') ? ' selected' : '' %>><?php _e( 'Hide', 'justwpforms' ); ?></option>
			</select>
		</div>
	</p>
	<p class="justwpforms-placeholder-option" style="display: <%= ( 'as_placeholder' !== instance.label_placement ) ? 'block' : 'none' %>">
		<label for="<%= instance.id %>_placeholder"><?php _e( 'Placeholder', 'justwpforms' ); ?></label>
		<input type="text" id="<%= instance.id %>_placeholder" class="widefat title" value="<%- instance.placeholder %>" data-bind="placeholder" />
	</p>
	<p>
		<label for="<%= instance.id %>_description"><?php _e( 'Hint', 'justwpforms' ); ?></label>
		<textarea id="<%= instance.id %>_description" data-bind="description"><%= instance.description %></textarea>
	</p>

	<?php do_action( 'justwpforms_part_customize_title_before_options' ); ?>

	<?php do_action( 'justwpforms_part_customize_title_after_options' ); ?>

	<?php do_action( 'justwpforms_part_customize_title_before_advanced_options' ); ?>

	<p>
		<label>
			<input type="checkbox" class="checkbox" value="1" <% if ( instance.required ) { %>checked="checked"<% } %> data-bind="required" /> <?php _e( 'Require an answer', 'justwpforms' ); ?>
		</label>
	</p>

	<?php justwpforms_customize_part_width_control(); ?>

	<?php do_action( 'justwpforms_part_customize_title_after_advanced_options' ); ?>

	<p>
		<label for="<%= instance.id %>_css_class"><?php _e( 'Additional CSS class(es)', 'justwpforms' ); ?></label>
		<input type="text" id="<%= instance.id %>_css_class" class="widefat title" value="<%- instance.css_class %>" data-bind="css_class" />
	</p>

	<div class="justwpforms-part-logic-wrap">
		<div class="justwpforms-logic-view">
			<?php justwpforms_customize_part_logic(); ?>
		</div>
	</div>

	<?php justwpforms_customize_part_footer(); ?>
</script>
