<script type="text/template" id="justwpforms-customize-toggletip-template">
	<?php include( justwpforms_get_core_folder() . '/templates/customize-form-part-header.php' ); ?>
	<p class="label-field-group">
		<label for="<%= instance.id %>_title"><?php _e( 'Heading', 'justwpforms' ); ?></label>
		<div class="label-group">
			<input type="text" id="<%= instance.id %>_title" class="widefat title" value="<%- instance.label %>" data-bind="label" />
		</div>
	</p>
	<?php do_action( 'justwpforms_part_customize_placeholder_before_options' ); ?>
	<p>
		<label for="<%= instance.id %>_details"><?php _e( 'Text', 'justwpforms' ); ?></label>
		<textarea id="<%= instance.id %>_details" class="wp-editor-area" name="details" data-bind="details"><%= instance.details %></textarea>
	</p>

	<?php do_action( 'justwpforms_part_customize_placeholder_after_options' ); ?>

	<?php do_action( 'justwpforms_part_customize_placeholder_before_advanced_options' ); ?>

	<?php justwpforms_customize_part_width_control(); ?>

	<?php do_action( 'justwpforms_part_customize_placeholder_after_advanced_options' ); ?>

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
