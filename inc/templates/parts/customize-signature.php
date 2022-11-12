<script type="text/template" id="justwpforms-customize-signature-template">
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
	<p style="display: <%= ( 'type' === instance.signature_type ) ? 'block' : 'none' %>">
		<label for="<%= instance.id %>_placeholder"><?php _e( 'Placeholder', 'justwpforms' ); ?></label>
		<input type="text" id="<%= instance.id %>_placeholder" class="widefat title" value="<%- instance.placeholder %>" data-bind="placeholder" />
	</p>
	<p>
		<label for="<%= instance.id %>_description"><?php _e( 'Hint', 'justwpforms' ); ?></label>
		<textarea id="<%= instance.id %>_description" data-bind="description"><%= instance.description %></textarea>
	</p>

	<?php do_action( 'justwpforms_part_customize_legal_before_options' ); ?>

	<% if ( '' !== instance.intent_text ) { %>
	<p>
		<div class="wp-editor-container">
			<textarea id="<%= instance.id %>_intent_text" rows="5" name="intent_text" data-bind="intent_text" class="widefat"><%= instance.intent_text %></textarea>
		</div>
	</p>
	<% } %>

	<% if ( instance.signature_type !== 'draw' ) { %>
	<p>
		<label for="<%= instance.id %>_signature_type"><?php _e( 'Method', 'justwpforms' ); ?></label>
		<select id="<%= instance.id %>_signature_type" name="signature_type" data-bind="signature_type" class="widefat">
			<option value="type"<%= (instance.signature_type == 'type') ? ' selected' : '' %>><?php _e( 'Type', 'justwpforms' ); ?></option>
			<option value="draw"<%= (instance.signature_type == 'draw') ? ' selected' : '' %>><?php _e( 'Draw', 'justwpforms' ); ?></option>
		</select>
	</p>
	<% } %>
	<p>
		<label>
			<input type="checkbox" class="checkbox" value="1" <% if ( instance.required ) { %>checked="checked"<% } %> data-bind="required" /> <?php _e( 'Require an answer', 'justwpforms' ); ?>
		</label>
	</p>

	<?php do_action( 'justwpforms_part_customize_legal_after_options' ); ?>

	<?php do_action( 'justwpforms_part_customize_legal_before_advanced_options' ); ?>

	<?php justwpforms_customize_part_width_control(); ?>

	<?php do_action( 'justwpforms_part_customize_legal_after_advanced_options' ); ?>

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
