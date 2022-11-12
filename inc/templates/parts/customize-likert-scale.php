
<script type="text/template" id="justwpforms-customize-likert-scale-template">
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
			<input type="number" id="<%= instance.id %>_max_value" min="-10" max="1" class="widefat title" value="<%= instance.min_value %>" data-bind="min_value" />
		</p>
		<p>
			<label for="<%= instance.id %>_max_value"><?php _e( 'Max number', 'justwpforms' ); ?></label>
			<input type="number" id="<%= instance.id %>_max_value" min="0" max="10" class="widefat title" value="<%= instance.max_value %>" data-bind="max_value" />
		</p>
	</div>

	<?php do_action( 'justwpforms_part_customize_scale_after_options' ); ?>

	<?php do_action( 'justwpforms_part_customize_scale_before_advanced_options' ); ?>

	<p>
		<label for="<%= instance.id %>_min_label"><?php _e( 'Min number label', 'justwpforms' ); ?></label>
		<input type="text" id="<%= instance.id %>_min_label" class="widefat title" value="<%= instance.min_label %>" data-bind="min_label" />
	</p>
	<p>
		<label for="<%= instance.id %>_max_label"><?php _e( 'Max number label', 'justwpforms' ); ?></label>
		<input type="text" id="<%= instance.id %>_max_label" class="widefat title" value="<%= instance.max_label %>" data-bind="max_label" />
	</p>
	<p>
		<label>
			<input type="checkbox" class="checkbox" value="1" <% if ( instance.required ) { %>checked="checked"<% } %> data-bind="required" /> <?php _e( 'Require an answer', 'justwpforms' ); ?>
		</label>
	</p>

	<?php do_action( 'justwpforms_part_customize_scale_after_advanced_options' ); ?>

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
