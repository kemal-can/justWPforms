<script type="text/template" id="justwpforms-customize-date-template">
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

	<?php do_action( 'justwpforms_part_customize_date_before_options' ); ?>

	<p>
		<label for="<%= instance.id %>_date_type"><?php _e( 'Show', 'justwpforms' ); ?></label>
		<select id="<%= instance.id %>_date_type" name="date_type" data-bind="date_type" class="widefat">
			<option value="date"<%= (instance.date_type == 'date') ? ' selected' : '' %>><?php _e( 'Date', 'justwpforms' ); ?></option>
			<option value="datetime"<%= (instance.date_type == 'datetime') ? ' selected' : '' %>><?php _e( 'Date &amp; Time', 'justwpforms' ); ?></option>
			<option value="time"<%= (instance.date_type == 'time') ? ' selected' : '' %>><?php _e( 'Time', 'justwpforms' ); ?></option>
			<option value="month_year"<%= (instance.date_type == 'month_year') ? ' selected' : '' %>><?php _e( 'Month &amp; Year', 'justwpforms' ); ?></option>
			<option value="month"<%= (instance.date_type == 'month') ? ' selected' : '' %>><?php _e( 'Month only', 'justwpforms' ); ?></option>
			<option value="year"<%= (instance.date_type == 'year') ? ' selected' : '' %>><?php _e( 'Year only', 'justwpforms' ); ?></option>
		</select>
	</p>
	<p>
		<label for="<%= instance.id %>_default_datetime"><?php _e( 'Default value', 'justwpforms' ); ?></label>
		<select id="<%= instance.id %>_default_datetime" name="default_datetime" data-bind="default_datetime" class="widefat">
			<option value=""<%= (instance.default_datetime == 'blank') ? ' selected' : '' %>><?php _e( 'Blank', 'justwpforms' ); ?></option>
			<option value="current"<%= (instance.default_datetime == 'current') ? ' selected' : '' %>><?php _e( 'Current date and time', 'justwpforms' ); ?></option>
		</select>
	</p>

	<?php do_action( 'justwpforms_part_customize_date_after_options' ); ?>

	<?php do_action( 'justwpforms_part_customize_date_before_advanced_options' ); ?>

	<div class="date-options" style="display: <%= ( 'month' === instance.date_type || 'time' === instance.date_type ) ? 'none' : 'block' %>">
		<div class="min-max-wrapper">
			<p>
				<label for="<%= instance.id %>_min_year"><?php _e( 'Min year', 'justwpforms' ); ?></label>
				<input type="number" id="<%= instance.id %>_min_year" data-bind="min_year" value="<%= instance.min_year %>">
			</p>
			<p>
				<label for="<%= instance.id %>_max_year"><?php _e( 'Max year', 'justwpforms' ); ?></label>
				<input type="number" id="<%= instance.id %>_max_year" data-bind="max_year" value="<%= instance.max_year %>">
			</p>
		</div>
	</div>
	<div class="time-options" style="display: <%= ( -1 !== instance.date_type.indexOf( 'time' ) ) ? 'block' : 'none' %>">
		<div class="min-max-wrapper">
			<p>
				<label for="<%= instance.id %>_min_hour"><?php _e( 'Min hour', 'justwpforms' ); ?></label>
				<input type="number" id="<%= instance.id %>_min_hour" data-bind="min_hour" min="<%= ( '12' == instance.time_format ) ? 1 : 0 %>" max="<%= ( '12' == instance.time_format ) ? 12 : 23 %>" value="<%= instance.min_hour %>">
			</p>
			<p>
				<label for="<%= instance.id %>_max_hour"><?php _e( 'Max hour', 'justwpforms' ); ?></label>
				<input type="number" id="<%= instance.id %>_max_hour" data-bind="max_hour" min="<%= ( '12' == instance.time_format ) ? 1 : 0 %>" max="<%= ( '12' == instance.time_format ) ? 12 : 23 %>" value="<%= instance.max_hour %>">
			</p>
		</div>
	</div>
	<p class="time-options" style="display: <%= ( -1 !== instance.date_type.indexOf( 'time' ) ) ? 'block' : 'none' %>">
		<label for="<%= instance.id %>_minute_step"><?php _e( 'Minute increments', 'justwpforms' ); ?></label>
		<input type="number" id="<%= instance.id %>_minute_step" min="0" max="30" step="15" data-bind="minute_step" value="<%= instance.minute_step %>">
	</p>
	<p class="time-options" style="display: <%= ( -1 !== instance.date_type.indexOf( 'time' ) ) ? 'block' : 'none' %>">
		<label for="<%= instance.id %>_time_format"><?php _e( 'Convention', 'justwpforms' ); ?></label>
		<select id="<%= instance.id %>_time_format" name="time_format" data-bind="time_format" class="widefat">
			<option value="12"<%= (instance.time_format == '12') ? ' selected' : '' %>><?php _e( '12-hour clock', 'justwpforms' ); ?></option>
			<option value="24"<%= (instance.time_format == '24') ? ' selected' : '' %>><?php _e( '24-hour clock', 'justwpforms' ); ?></option>
		</select>
	</p>
	<p>
		<label>
			<input type="checkbox" class="checkbox" value="1" <% if ( instance.required ) { %>checked="checked"<% } %> data-bind="required" /> <?php _e( 'Require an answer', 'justwpforms' ); ?>
		</label>
	</p>

	<?php justwpforms_customize_part_width_control(); ?>

	<?php do_action( 'justwpforms_part_customize_date_after_advanced_options' ); ?>

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
