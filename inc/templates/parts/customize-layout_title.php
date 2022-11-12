<script type="text/template" id="customize-justwpforms-layout_title-template">
	<div class="justwpforms-widget justwpforms-part-widget" data-part-id="<%= instance.id %>">
		<div class="justwpforms-widget-top justwpforms-part-widget-top">
			<div class="justwpforms-part-widget-title-action">
				<button type="button" class="justwpforms-widget-action">
					<span class="toggle-indicator"></span>
				</button>
			</div>
			<div class="justwpforms-widget-title">
				<h3><%= settings.label %><span class="in-widget-title"<% if (!instance.label) { %> style="display: none"<% } %>>: <span><%= (instance.label) ? instance.label : '' %></span></span></h3>
			</div>
		</div>
		<div class="justwpforms-widget-content">
			<div class="justwpforms-widget-form">
				<p>
					<label for="<%= instance.id %>_title"><?php _e( 'Heading', 'justwpforms' ); ?></label>
					<input type="text" id="<%= instance.id %>_title" class="widefat title" value="<%- instance.label %>" data-bind="label" />
				</p>
				<p>
					<label for="<%= instance.id %>_level"><?php _e( 'Heading level', 'justwpforms' ); ?></label>
					<span class="justwpforms-buttongroup">
					    <?php for ( $i = 1; $i <= 6; $i++ ): ?>
						    <label for="<%= instance.id %>-level-h<?php echo $i; ?>">
						        <input type="radio" id="<%= instance.id %>-level-h<?php echo $i; ?>" value="h<?php echo $i; ?>" name="<%= instance.id %>-level" data-bind="level" <%= ( instance.level == 'h<?php echo $i; ?>' ) ? 'checked' : '' %> />
						        <span><?php _e( 'H' . $i, 'justwpforms' ); ?></span>
						    </label>
					    <?php endfor; ?>
					</span>
				</p>

				<?php justwpforms_customize_part_width_control(); ?>
				
				<p>
					<label for="<%= instance.id %>_css_class"><?php _e( 'Additional CSS class(es)', 'justwpforms' ); ?></label>
					<input type="text" id="<%= instance.id %>_css_class" class="widefat title" value="<%- instance.css_class %>" data-bind="css_class" />
				</p>
			</div>

			<div class="justwpforms-widget-actions">
				<a href="#" class="justwpforms-form-part-remove"><?php _e( 'Delete', 'justwpforms' ); ?></a> |
				<a href="#" class="justwpforms-form-part-duplicate"><?php _e( 'Duplicate', 'justwpforms' ); ?></a>
			</div>
		</div>
	</div>
</div>
</script>
