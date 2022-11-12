<script type="text/template" id="customize-justwpforms-checkbox-template">
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

	<?php do_action( 'justwpforms_part_customize_checkbox_before_options' ); ?>

	<div class="options">
		<label><?php _e( 'List', 'justwpforms' ); ?>:</label>
		<ul class="option-list"></ul>
		<p class="no-options description customize-control-description"><?php _e( 'It doesn\'t look like your field has any choices yet. Want to add one? Click the "Add Choice" button to start.', 'justwpforms' ); ?></p>
	</div>
	<p class="options-import">
		<label for="<%= instance.id %>_bulk_choices"><?php _e( 'Choices', 'justwpforms' ); ?></label>
		<textarea id="<%= instance.id %>_bulk_choices" class="option-import-area" cols="30" rows="10"></textarea>
		<span class="customize-control-description"><?php _e( 'Type or paste your choices, adding each on a new line.' ); ?></span>
	</p>
	<p class="links mode-manual">
		<a href="#" class="button bulk-options centered"><?php _e( 'Bulk add choices', 'justwpforms' ); ?></a>
		<a href="#" class="button add-heading"><?php _e( 'Add heading', 'justwpforms' ); ?></a>
		<a href="#" class="button add-option centered"><?php _e( 'Add choice', 'justwpforms' ); ?></a>
	</p>
	<p class="links mode-import">
		<a href="#" class="button add-import-options"><?php _e( 'Add choices', 'justwpforms' ); ?></a>
		<a href="#" class="button cancel-import-options"><?php _e( 'Cancel', 'justwpforms' ); ?></a>
	</p>

	<?php do_action( 'justwpforms_part_customize_checkbox_after_options' ); ?>

	<?php do_action( 'justwpforms_part_customize_checkbox_before_advanced_options' ); ?>

	<% if ( instance.other_option ) { %>
		<p>
			<label>
				<input type="checkbox" class="checkbox" value="1" data-bind="other_option" checked /> <?php _e( 'Add \'other\' choice', 'justwpforms' ); ?>
			</label>
		</p>
		<div class="justwpforms-nested-settings" data-trigger="other_option" style="display: <%= ( instance.other_option ) ? 'block' : 'none' %>">
			<p>
				<label for="<%= instance.id %>_other_option_label"><?php _e( '\'Other\' label', 'justwpforms' ); ?></label>
				<input type="text" id="<%= instance.id %>_other_option_label" maxlength="30" class="widefat title" value="<%- instance.other_option_label %>" data-bind="other_option_label" />
			</p>
			<p>
				<label for="<%= instance.id %>_other_option_placeholder"><?php _e( '\'Other\' placeholder', 'justwpforms' ); ?></label>
				<input type="text" id="<%= instance.id %>_other_option_placeholder" maxlength="50" class="widefat title" value="<%- instance.other_option_placeholder %>" data-bind="other_option_placeholder" />
			</p>
		</div>
	<% } %>
	<p>
		<label>
			<input type="checkbox" class="checkbox" value="1" <% if ( instance.shuffle_options ) { %>checked="checked"<% } %> data-bind="shuffle_options" /> <?php _e( 'Shuffle order of choices', 'justwpforms' ); ?>
		</label>
	</p>
	<p>
		<label>
			<input type="checkbox" class="checkbox" value="1" <% if ( instance.limit_choices ) { %>checked="checked"<% } %> data-bind="limit_choices" /> <?php _e( 'Limit choices', 'justwpforms' ); ?>
		</label>
	</p>
	<div class="justwpforms-nested-settings" data-trigger="limit_choices" style="display: <%= ( instance.limit_choices ) ? 'block' : 'none' %>">
		<p>
			<label for="<%= instance.id %>_limit_choices_min"><?php _e( 'Min choices', 'justwpforms' ); ?></label>
			<input type="number" id="<%= instance.id %>_limit_choices_min" class="widefat title justwpforms-checkbox-limit-min" min="1" value="<%= instance.limit_choices_min %>" data-trigger="limit_choices_min" data-bind="limit_choices_min" />
		</p>
		<p>
			<label for="<%= instance.id %>_limit_choices_max"><?php _e( 'Max choices', 'justwpforms' ); ?></label>
			<input type="number" id="<%= instance.id %>_limit_choices_max" class="widefat title justwpforms-checkbox-limit-max" min="1" value="<%= instance.limit_choices_max %>" data-trigger="limit_choices_max" data-bind="limit_choices_max" />
		</p>
	</div>
	<p>
		<label for="<%= instance.id %>_display_type"><?php _e( 'Align choices', 'justwpforms' ); ?></label>
		<span class="justwpforms-buttongroup">
			<label for="<%= instance.id %>-display_type-vertical">
				<input type="radio" id="<%= instance.id %>-display_type-vertical" value="block" name="<%= instance.id %>-display_type" data-bind="display_type" <%= ( instance.display_type == 'block' ) ? 'checked' : '' %> />
				<span><?php _e( 'Vertically', 'justwpforms' ); ?></span>
			</label>
			<label for="<%= instance.id %>-display_type-horizontal">
				<input type="radio" id="<%= instance.id %>-display_type-horizontal" value="inline" name="<%= instance.id %>-display_type" data-bind="display_type" <%= ( instance.display_type == 'inline' ) ? 'checked' : '' %> />
				<span><?php _e( 'Horizontally', 'justwpforms' ); ?></span>
			</label>
		</span>
	</p>
	<p>
		<label>
			<input type="checkbox" class="checkbox" value="1" <% if ( instance.required ) { %>checked="checked"<% } %> data-bind="required" /> <?php _e( 'Require an answer', 'justwpforms' ); ?>
		</label>
	</p>

	<?php justwpforms_customize_part_width_control(); ?>

	<?php do_action( 'justwpforms_part_customize_checkbox_after_advanced_options' ); ?>

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
<script type="text/template" id="customize-justwpforms-checkbox-item-template">
	<li data-option-id="<%= id %>" class="justwpforms-choice-item-widget">
		<div class="justwpforms-part-item-handle">
			<div class="justwpforms-part-item-advanced-option">
				<button type="button" class="justwpforms-advanced-option-action">
					<span class="toggle-indicator"></span>
				</button>
			</div>
			<div class="justwpforms-item-choice-widget-title">
				<h3><?php _e( 'Choice', 'justwpforms' ); ?><span class="choice-in-widget-title">: <span><%= label %></span></span></h3>
			</div>
		</div>
		<div class="justwpforms-part-item-body">
			<div class="justwpforms-part-item-advanced">
				<p>
					<label>
						<?php _e( 'Label', 'justwpforms' ); ?>:
						<input type="text" class="widefat" name="label" value="<%= label %>" data-option-attribute="label">
					</label>
				</p>
				<p>
					<label>
						<?php _e( 'Hint', 'justwpforms' ); ?>:
						<textarea name="description" data-option-attribute="description"><%= description %></textarea>
					</label>
				</p>
				<p>
					<label>
						<?php _e( 'Max times this choice can be submitted', 'justwpforms' ); ?>:
						<input type="number" class="widefat" name="limit_submissions_amount" min="0" value="<%= typeof limit_submissions_amount !== 'undefined' ? limit_submissions_amount : '' %>">
					</label>
				</p>
				<p>
					<label>
						<input type="checkbox" name="is_default" value="1" <% if (is_default == 1) { %> checked="checked"<% } %>> <?php _e( 'Make this choice default', 'justwpforms' ); ?>
					</label>
				</p>
				<div class="justwpforms-part-choice-logic-wrap">
					<div class="justwpforms-logic-view">
						<?php justwpforms_customize_part_choice_logic(); ?>
					</div>
				</div>

				<?php justwpforms_customize_part_choice_footer(); ?>
			</div>
		</div>
	</li>
</script>
<script type="text/template" id="customize-justwpforms-checkbox-item-heading-template">
	<li data-option-id="<%= id %>" class="justwpforms-choice-item-widget" data-is-heading="yes">
		<div class="justwpforms-part-item-handle">
			<div class="justwpforms-part-item-advanced-option">
				<button type="button" class="justwpforms-advanced-option-action">
					<span class="toggle-indicator"></span>
				</button>
			</div>
			<div class="justwpforms-item-choice-widget-title">
				<h3><?php _e( 'Heading', 'justwpforms' ); ?><span class="choice-in-widget-title">: <span><%= label %></span></span></h3>
			</div>
		</div>
		<div class="justwpforms-part-item-body">
			<div class="justwpforms-part-item-advanced">
				<p>
					<label>
						<?php _e( 'Label', 'justwpforms' ); ?>:
						<input type="text" class="widefat" name="label" value="<%= label %>" data-option-attribute="label">
					</label>
				</p>
				<div class="justwpforms-part-choice-logic-wrap">
					<div class="justwpforms-logic-view">
						<?php justwpforms_customize_part_choice_logic(); ?>
					</div>
				</div>

				<?php justwpforms_customize_part_choice_footer(); ?>
			</div>
		</div>
	</li>
</script>
