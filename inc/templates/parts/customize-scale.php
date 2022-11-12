
<script type="text/template" id="justwpforms-customize-scale-template">
	<?php include( justwpforms_get_core_folder() . '/templates/customize-form-part-header.php' ); ?>
	<p class="label-field-group">
		<label for="<%= instance.id %>_title"><?php _e( 'Label', 'justwpforms' ); ?></label>
		<div class="label-group">
			<input type="text" id="<%= instance.id %>_title" class="widefat title" value="<%- instance.label %>" data-bind="label" />
			<div class="justwpforms-buttongroup">
				<label for="<%= instance.id %>-label_placement-show">
					<input type="radio" id="<%= instance.id %>-label_placement-show" value="show" name="<%= instance.id %>-label_placement" data-bind="label_placement" <%= ( instance.label_placement == 'show' ) ? 'checked' : '' %> />
					<span><?php _e( 'Show', 'justwpforms' ); ?></span>
				</label>
				<label for="<%= instance.id %>-label_placement-hidden">
					<input type="radio" id="<%= instance.id %>-label_placement-hidden" value="hidden" name="<%= instance.id %>-label_placement" data-bind="label_placement" <%= ( instance.label_placement == 'hidden' ) ? 'checked' : '' %> />
					<span><?php _e( 'Hide', 'justwpforms' ); ?></span>
				</label>
 			</div>
		</div>
	</p>
	<p>
		<label for="<%= instance.id %>_description"><?php _e( 'Hint', 'justwpforms' ); ?></label>
		<textarea id="<%= instance.id %>_description" data-bind="description"><%= instance.description %></textarea>
	</p>

	<?php do_action( 'justwpforms_part_customize_scale_before_options' ); ?>

	<div class="min-max-wrapper">
		<p>
			<label for="<%= instance.id %>_max_value"><?php _e( 'Min number', 'justwpforms' ); ?></label>
			<input type="number" id="<%= instance.id %>_max_value" class="widefat title" value="<%= instance.min_value %>" data-bind="min_value" />
		</p>
		<p>
			<label for="<%= instance.id %>_max_value"><?php _e( 'Max number', 'justwpforms' ); ?></label>
			<input type="number" id="<%= instance.id %>_max_value" class="widefat title" value="<%= instance.max_value %>" data-bind="max_value" />
		</p>
	</div>

	<?php do_action( 'justwpforms_part_customize_scale_after_options' ); ?>

	<?php do_action( 'justwpforms_part_customize_scale_before_advanced_options' ); ?>

	<p>
		<label>
			<input type="checkbox" class="checkbox" value="1" <% if ( instance.multiple ) { %>checked="checked"<% } %> data-bind="multiple" /> <?php _e( 'Allow range select', 'justwpforms' ); ?>
		</label>
	</p>
	<div class="justwpforms-nested-settings" data-trigger="multiple" style="margin-bottom: 1em; display: <%= ( instance.multiple ) ? 'flex' : 'none' %>">
		<div class="min-max-wrapper">
			<p>
				<label for="<%= instance.id %>_default_range_from"><?php _e( 'Default min number', 'justwpforms' ); ?></label>
				<input type="number" id="<%= instance.id %>_default_range_from" class="widefat title" value="<%= instance.default_range_from %>" data-bind="default_range_from" />
			</p>
			<p>
				<label for="<%= instance.id %>_default_range_to"><?php _e( 'Default max number', 'justwpforms' ); ?></label>
				<input type="number" id="<%= instance.id %>_default_range_to" class="widefat title" value="<%= instance.default_range_to %>" data-bind="default_range_to" />
			</p>
		</div>
	</div>
	<p class="scale-single-options" style="display: <%= ( instance.multiple ) ? 'none' : 'block' %>">
		<label for="<%= instance.id %>_default_value"><?php _e( 'Default value', 'justwpforms' ); ?></label>
		<input type="number" id="<%= instance.id %>_default_value" class="widefat title" value="<%= instance.default_value %>" data-bind="default_value" />
	</p>
	<p>
		<label for="<%= instance.id %>_min_label"><?php _e( 'Min number label', 'justwpforms' ); ?></label>
		<input type="text" id="<%= instance.id %>_min_label" class="widefat title" value="<%- instance.min_label %>" data-bind="min_label" />
	</p>
	<p>
		<label for="<%= instance.id %>_max_label"><?php _e( 'Max number label', 'justwpforms' ); ?></label>
		<input type="text" id="<%= instance.id %>_max_label" class="widefat title" value="<%- instance.max_label %>" data-bind="max_label" />
	</p>
	<p>
		<label for="<%= instance.id %>_step"><?php _e( 'Step Interval', 'justwpforms' ); ?></label>
		<input type="number" id="<%= instance.id %>_step" class="widefat title" value="<%= instance.step %>" data-bind="step" />
	</p>

	<?php do_action( 'justwpforms_part_customize_scale_after_advanced_options' ); ?>
	
	<p>
		<label>
			<input type="checkbox" class="checkbox" value="1" <% if ( instance.required ) { %>checked="checked"<% } %> data-bind="required" /> <?php _e( 'Require an answer', 'justwpforms' ); ?>
		</label>
	</p>

	<?php justwpforms_customize_part_width_control(); ?>

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
