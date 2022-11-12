<script type="text/template" id="customize-justwpforms-rank-order-template">
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
	<p>
		<label for="<%= instance.id %>_description"><?php _e( 'Hint', 'justwpforms' ); ?></label>
		<textarea id="<%= instance.id %>_description" data-bind="description"><%= instance.description %></textarea>
	</p>

	<?php do_action( 'justwpforms_part_customize_rank_before_options' ); ?>

	<div class="options">
		<h3><?php _e( 'List', 'justwpforms' ); ?></h3>
		<ul class="option-list"></ul>
		<p class="no-options description customize-control-description"><?php _e( 'No choices added yet.', 'justwpforms' ); ?></p>
	</div>
	<div class="options-import">
		<h3><?php _e( 'Choices', 'justwpforms' ); ?></h3>
		<textarea class="option-import-area" cols="30" rows="10" placeholder="<?php _e( 'Type or paste your choices here, adding each on a new line.' ); ?>"></textarea>
	</div>
	<p class="links mode-manual">
		<a href="#" class="button add-option"><?php _e( 'Add choice', 'justwpforms' ); ?></a>
		<span class="centered">
			<a href="#" class="import-options"><?php _e( 'Or, bulk add choices', 'justwpforms' ); ?></a>
		</span>
	</p>
	<p class="links mode-import">
		<a href="#" class="button import-option"><?php _e( 'Add choices', 'justwpforms' ); ?></a>
		<span class="centered">
			<a href="#" class="add-options"><?php _e( 'Cancel', 'justwpforms' ); ?></a>
		</span>
	</p>

	<?php do_action( 'justwpforms_part_customize_rank_after_options' ); ?>

	<?php do_action( 'justwpforms_part_customize_rank_before_advanced_options' ); ?>
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
	<p>
		<label>
			<input type="checkbox" class="checkbox" value="1" <% if ( instance.shuffle_options ) { %>checked="checked"<% } %> data-bind="shuffle_options" /> <?php _e( 'Shuffle order of choices', 'justwpforms' ); ?>
		</label>
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

	<?php do_action( 'justwpforms_part_customize_rank_after_advanced_options' ); ?>

	<div class="justwpforms-part-logic-wrap">
		<div class="justwpforms-logic-view">
			<?php justwpforms_customize_part_logic(); ?>
		</div>
	</div>

	<?php justwpforms_customize_part_footer(); ?>
</script>
<script type="text/template" id="customize-justwpforms-rank-order-item-template">
	<li data-option-id="<%= id %>">
		<div class="justwpforms-part-item-body">
			<div class="justwpforms-part-item-handle"></div>
			<label>
				<?php _e( 'Label', 'justwpforms' ); ?>:
				<input type="text" class="widefat" name="label" value="<%- label %>" data-option-attribute="label">
			</label>
			<div class="option-actions">
				<a href="#" class="delete-option"><?php _e( 'Delete', 'justwpforms' ); ?></a>
			</div>
		</div>
	</li>
</script>
