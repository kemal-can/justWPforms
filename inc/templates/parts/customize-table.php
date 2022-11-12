<script type="text/template" id="customize-justwpforms-table-template">
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

	<?php do_action( 'justwpforms_part_customize_table_before_options' ); ?>

	<div class="tab-links">
		<a href="#" data-justwpforms-tab="tab-content-columns" class="active"><?php _e( 'Columns', 'justwpforms' ); ?></a>
		<a href="#" data-justwpforms-tab="tab-content-rows"><?php _e( 'Rows', 'justwpforms' ); ?></a>
	</div>
	<div class="tab-content">
		<div class="tab-content-columns active">
			<div class="options columns">
				<ul class="column-list"></ul>
				<h3><?php _e( 'Columns', 'justwpforms' ); ?></h3>
				<p class="no-options description"><?php _e( 'No columns added yet.', 'justwpforms' ); ?></p>
			</div>
			<div class="options-import" data-type="column">
				<h3><?php _e( 'Choices', 'justwpforms' ); ?></h3>
				<textarea class="option-import-area" data-type="column" cols="30" rows="10" placeholder="<?php _e( 'Type or paste your columns here, adding each on a new line.' ); ?>"></textarea>
			</div>
			<p class="links mode-manual">
				<a href="#" class="button add-column"><?php _e( 'Add column', 'justwpforms' ); ?></a>
				<span class="centered">
					<a href="#" class="import-options" data-type="column"><?php _e( 'Or, bulk add columns', 'justwpforms' ); ?></a>
				</span>
			</p>
			<p class="links mode-import">
				<a href="#" class="button import-column"><?php _e( 'Add columns', 'justwpforms' ); ?></a>
				<span class="centered">
					<a href="#" class="add-options"><?php _e( 'Cancel', 'justwpforms' ); ?></a>
				</span>
			</p>
		</div>
		<div class="tab-content-rows">
			<div class="options rows">
				<ul class="row-list"></ul>
				<h3><?php _e( 'Rows', 'justwpforms' ); ?></h3>
				<p class="no-options description"><?php _e( 'No rows added yet.', 'justwpforms' ); ?></p>
			</div>
			<div class="options-import" data-type="row">
				<h3><?php _e( 'Choices', 'justwpforms' ); ?></h3>
				<textarea class="option-import-area" data-type="row" cols="30" rows="10" placeholder="<?php _e( 'Type or paste your rows here, adding each on a new line.' ); ?>"></textarea>
			</div>
			<p class="links mode-manual">
				<a href="#" class="button add-row"><?php _e( 'Add row', 'justwpforms' ); ?></a>
				<span class="centered">
					<a href="#" class="import-options" data-type="row"><?php _e( 'Or, bulk add rows', 'justwpforms' ); ?></a>
				</span>
			</p>
			<p class="links mode-import">
				<a href="#" class="button import-row"><?php _e( 'Add rows', 'justwpforms' ); ?></a>
				<span class="centered">
					<a href="#" class="add-options"><?php _e( 'Cancel', 'justwpforms' ); ?></a>
				</span>
			</p>
		</div>
	</div>

	<?php do_action( 'justwpforms_part_customize_table_after_options' ); ?>

	<?php do_action( 'justwpforms_part_customize_table_before_advanced_options' ); ?>

	<p>
		<label>
			<input type="checkbox" class="checkbox" value="1" <% if ( instance.allow_multiple_selection ) { %>checked="checked"<% } %> data-bind="allow_multiple_selection" /> <?php _e( 'Allow multiple choices', 'justwpforms' ); ?>
		</label>
	</p>

	<p class="justwpforms-poll-limit-choices-wrap" style="display: <%= ( instance.allow_multiple_selection ) ? 'block' : 'none' %>">
		<label>
			<input type="checkbox" class="checkbox" value="1" <% if ( instance.limit_choices ) { %>checked="checked"<% } %> data-bind="limit_choices" /> <?php _e( 'Limit choices', 'justwpforms' ); ?>
		</label>
	</p>
	<div class="justwpforms-nested-settings" data-trigger="limit_choices" style="display: <%= ( instance.limit_choices ) ? 'block' : 'none' %>">
		<p>
			<label for="<%= instance.id %>_limit_choices_min"><?php _e( 'Min choices', 'justwpforms' ); ?></label>
			<input type="number" id="<%= instance.id %>_limit_choices_min" class="widefat title justwpforms-table-limit-min" min="1" value="<%= instance.limit_choices_min %>" data-trigger="limit_choices_min" data-bind="limit_choices_min" />
		</p>
		<p>
			<label for="<%= instance.id %>_limit_choices_max"><?php _e( 'Max choices', 'justwpforms' ); ?></label>
			<input type="number" id="<%= instance.id %>_limit_choices_max" class="widefat title justwpforms-table-limit-max" min="1" value="<%= instance.limit_choices_max %>" data-trigger="limit_choices_max" data-bind="limit_choices_max" />
		</p>
	</div>
	<p>
		<label>
			<input type="checkbox" class="checkbox" value="1" <% if ( instance.required ) { %>checked="checked"<% } %> data-bind="required" /> <?php _e( 'Require an answer', 'justwpforms' ); ?>
		</label>
	</p>

	<p>
		<label for="<%= instance.id %>_css_class"><?php _e( 'Additional CSS class(es)', 'justwpforms' ); ?></label>
		<input type="text" id="<%= instance.id %>_css_class" class="widefat title" value="<%- instance.css_class %>" data-bind="css_class" />
	</p>

	<?php do_action( 'justwpforms_part_customize_table_after_advanced_options' ); ?>

	<div class="justwpforms-part-logic-wrap">
		<div class="justwpforms-logic-view">
			<?php justwpforms_customize_part_logic(); ?>
		</div>
	</div>

	<?php justwpforms_customize_part_footer(); ?>
</script>
<script type="text/template" id="customize-justwpforms-table-column-template">
	<li data-column-id="<%= id %>">
		<div class="justwpforms-part-item-body">
			<div class="justwpforms-part-item-handle"></div>
			<label>
				<?php _e( 'Label', 'justwpforms' ); ?>:
				<input type="text" class="widefat" name="label" value="<%- label %>" data-option-attribute="label">
			</label>
			<div class="justwpforms-part-item-advanced">
				<p>
					<label>
						<?php _e( 'Max times this choice can be submitted', 'justwpforms' ); ?>:
						<input type="number" class="widefat" name="limit_submissions_amount" min="0" value="<%= typeof limit_submissions_amount !== 'undefined' ? limit_submissions_amount : '' %>">
					</label>
				</p>
				<p>
					<label>
						<input type="checkbox" name="is_default" value="1" class="default-option-switch"<% if (is_default == 1) { %> checked="checked"<% } %>> <?php _e( 'Make this choice default', 'justwpforms' ); ?>
					</label>
				</p>
			</div>
			<div class="option-actions">
				<a href="#" class="delete-column"><?php _e( 'Delete', 'justwpforms' ); ?></a> |
				<a href="#" class="advanced-column"><?php _e( 'More', 'justwpforms' ); ?></a>
			</div>
		</div>
	</li>
</script>
<script type="text/template" id="customize-justwpforms-table-row-template">
	<li data-row-id="<%= id %>">
		<div class="justwpforms-part-item-body">
			<div class="justwpforms-part-item-handle"></div>
			<label>
				<?php _e( 'Label', 'justwpforms' ); ?>:
				<input type="text" class="widefat" name="label" value="<%- label %>" data-option-attribute="label">
			</label>
			<div class="option-actions">
				<a href="#" class="delete-row"><?php _e( 'Delete', 'justwpforms' ); ?></a>
			</div>
		</div>
	</li>
</script>
