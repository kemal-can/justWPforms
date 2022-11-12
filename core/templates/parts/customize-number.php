<script type="text/template" id="justwpforms-customize-number-template">
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
	<p class="justwpforms-placeholder-option" style="display: <%= ( 'as_placeholder' !== instance.label_placement ) ? 'block' : 'none' %>">
		<label for="<%= instance.id %>_placeholder"><?php _e( 'Placeholder', 'justwpforms' ); ?></label>
		<input type="text" id="<%= instance.id %>_placeholder" class="widefat title" value="<%- instance.placeholder %>" data-bind="placeholder" />
	</p>
	<p class="justwpforms-default-value-option">
		<label for="<%= instance.id %>_default_value"><?php _e( 'Prefill', 'justwpforms' ); ?></label>
		<input type="number" id="<%= instance.id %>_default_value" class="widefat title default_value" value="<%- instance.default_value %>" data-bind="default_value" />
	</p>
	<p>
		<label for="<%= instance.id %>_description"><?php _e( 'Hint', 'justwpforms' ); ?></label>
		<textarea id="<%= instance.id %>_description" data-bind="description"><%= instance.description %></textarea>
	</p>

	<?php do_action( 'justwpforms_part_customize_number_before_options' ); ?>

	<p>
		<label for="<%= instance.id %>_min_value"><?php _e( 'Min number', 'justwpforms' ); ?></label>
		<input type="number" id="<%= instance.id %>_min_value" class="widefat title" value="<%= instance.min_value %>" data-bind="min_value" />
	</p>
	<p>
		<label for="<%= instance.id %>_max_value"><?php _e( 'Max number', 'justwpforms' ); ?></label>
		<input type="number" id="<%= instance.id %>_max_value" class="widefat title" value="<%= instance.max_value %>" data-bind="max_value" />
	</p>
	<p>
		<label for="<%= instance.id %>_step"><?php _e( 'Step Interval', 'justwpforms' ); ?></label>
		<input type="number" id="<%= instance.id %>_step" class="widefat title" value="<%= instance.step %>" data-bind="step" />
	</p>

	<?php do_action( 'justwpforms_part_customize_number_after_options' ); ?>

	<?php do_action( 'justwpforms_part_customize_number_before_advanced_options' ); ?>

	<p>
		<label>
			<input type="checkbox" name="masked" class="checkbox" value="1" <% if ( instance.masked ) { %>checked="checked"<% } %> data-bind="masked" /> <?php _e( 'Use number separators', 'justwpforms' ); ?>
		</label>
	</p>
	<div class="justwpforms-nested-settings mask-wrapper number-options number-options--numeric" data-trigger="masked" style="display: <%= (instance.masked == 1) ? 'flex' : 'none' %>">
		<p>
			<label for="<%= instance.id %>_mask_numeric_thousands_delimiter"><?php _e( 'Grouping', 'justwpforms' ); ?></label>
			<input type="text" id="<%= instance.id %>_mask_numeric_thousands_delimiter" class="widefat title" value="<%- instance.mask_numeric_thousands_delimiter %>" data-bind="mask_numeric_thousands_delimiter" />
		</p>
		<p>
			<label for="<%= instance.id %>_mask_numeric_decimal_mark"><?php _e( 'Decimal', 'justwpforms' ); ?></label>
			<input type="text" id="<%= instance.id %>_mask_numeric_decimal_mark" class="widefat title" value="<%- instance.mask_numeric_decimal_mark %>" data-bind="mask_numeric_decimal_mark" />
		</p>
	</div>
	<p>
		<label for="<%= instance.id %>_mask_numeric_prefix"><?php _e( 'Prefix', 'justwpforms' ); ?></label>
		<input type="text" id="<%= instance.id %>_mask_numeric_prefix" class="widefat title" value="<%- instance.mask_numeric_prefix %>" data-bind="mask_numeric_prefix" maxlength="50" />
	</p>
	<p>
		<label for="<%= instance.id %>_mask_numeric_suffix"><?php _e( 'Suffix', 'justwpforms' ); ?></label>
			<input type="text" id="<%= instance.id %>_mask_numeric_suffix" class="widefat title" value="<%- instance.mask_numeric_suffix %>" data-bind="mask_numeric_suffix" maxlength="50" />
	</p>
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

	<?php do_action( 'justwpforms_part_customize_number_after_advanced_options' ); ?>

	<div class="justwpforms-part-logic-wrap">
		<div class="justwpforms-logic-view">
			<?php justwpforms_customize_part_logic(); ?>
		</div>
	</div>

	<?php justwpforms_customize_part_footer(); ?>
</script>
