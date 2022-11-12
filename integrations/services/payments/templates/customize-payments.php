<script type="text/template" id="justwpforms-customize-payments-template">
	<?php
	$integrations = justwpforms_get_integrations();
	$paypal = $integrations->get_service( 'paypal' );
	$stripe = $integrations->get_service( 'stripe' );
	?>

	<?php include( justwpforms_get_core_folder() . '/templates/customize-form-part-header.php' ); ?>

	<?php do_action( 'justwpforms_part_customize_payments_before_options' ); ?>

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

	<p>
		<label for="<%= instance.id %>_currency"><?php _e( 'Currency', 'justwpforms' ); ?></label>
		<select id="<%= instance.id %>_currency" data-bind="currency" class="widefat">
		<?php
		$currencies = justwpforms_payment_get_currencies( 'stripe' );

		foreach( $currencies as $currency_key => $currency_data ) : ?>
			<option value="<?php echo $currency_key; ?>"<%= (instance.currency == '<?php echo $currency_key; ?>') ? ' selected' : '' %>><?php echo $currency_data['label']; ?></option>
		<?php endforeach; ?>
		</select>
	</p>

	<p class="price-field" style="display: <%= ( instance.show_user_price_field ) ? 'none' : 'block' %>">
		<label for="<%= instance.id %>_price"><?php _e( 'Price', 'justwpforms' ); ?></label>
		<input type="number" min="0" id="<%= instance.id %>_price" class="widefat title" value="<%= instance.price %>" data-bind="price" />
	</p>

	<div class="justwpforms-part-settings-logic-view" data-logic-type="set" data-logic-id="price" data-logic-then-text="<?php _e( 'Then price is…', 'justwpforms' ); ?>" style="display: <%= ( instance.show_user_price_field ) ? 'none' : 'block' %>">
		<?php justwpforms_customize_part_logic(); ?>
	</div>

	<?php do_action( 'justwpforms_part_customize_payments_after_options' ); ?>

	<?php do_action( 'justwpforms_part_customize_payments_before_advanced_options' ); ?>

	<p>
		<label>
			<input type="checkbox" class="checkbox" value="1" <% if ( instance.show_user_price_field ) { %>checked="checked"<% } %> data-bind="show_user_price_field" /> <?php _e( 'Use \'pay what you want\' pricing', 'justwpforms' ); ?>
		</label>
	</p>

	<div class="justwpforms-nested-settings" data-trigger="show_user_price_field" style="display: <%= ( 1 == instance.show_user_price_field ) ? 'block' : 'none' %>">
		<p>
			<label for="<%= instance.id %>_user_price_min"><?php _e( 'Minimum accepted amount', 'justwpforms' ); ?></label>
			<input type="number" id="<%= instance.id %>_user_price_min" class="widefat title" value="<%= instance.user_price_min %>" min="0" data-bind="user_price_min" />
		</p>
		<p>
			<label for="<%= instance.id %>_user_price_placeholder"><?php _e( 'Placeholder', 'justwpforms' ); ?></label>
			<input type="text" id="<%= instance.id %>_user_price_placeholder" class="widefat title" value="<%- instance.user_price_placeholder %>" data-bind="user_price_placeholder" />
		</p>
		<p>
			<label for="<%= instance.id %>_user_price_step"><?php _e( 'Step Interval', 'justwpforms' ); ?></label>
			<input type="number" id="<%= instance.id %>_user_price_step" class="widefat title" value="<%= instance.user_price_step %>" data-bind="user_price_step" />
		</p>
	</div>
	<p>
		<label>
			<input type="checkbox" class="checkbox" value="1" <% if ( instance.accept_coupons ) { %>checked="checked"<% } %> data-bind="accept_coupons" /> <?php _e( 'Accept coupons', 'justwpforms' ); ?>
		</label>
	</p>
	<p>
		<label>
			<input type="checkbox" class="checkbox" value="1" <% if ( instance.required ) { %>checked="checked"<% } %> data-bind="required" /> <?php _e( 'Require an answer', 'justwpforms' ); ?>
		</label>
	</p>
	<p class="justwpforms-buttongroup-wrapper">
		<label for="<%= instance.id %>_width"><?php _e( 'Width', 'justwpforms' ); ?></label>
		<span class="justwpforms-buttongroup justwpforms-buttongroup-field_width">
			<label for="<%= instance.id %>_width_full">
				<input type="radio" id="<%= instance.id %>_width_full" value="full" name="<%= instance.id %>_width" data-bind="width" <%= ( instance.width == 'full' ) ? 'checked' : '' %> />
				<span><?php _e( 'Full', 'justwpforms' ); ?></span>
			</label>

			<label for="<%= instance.id %>_width_half">
				<input type="radio" id="<%= instance.id %>_width_half" value="half" name="<%= instance.id %>_width" data-bind="width" <%= ( instance.width == 'half' ) ? 'checked' : '' %> />
				<span><?php _e( 'Half', 'justwpforms' ); ?></span>
			</label>

			<label for="<%= instance.id %>_width_third">
				<input type="radio" id="<%= instance.id %>_width_third" value="third" name="<%= instance.id %>_width" data-bind="width" <%= ( instance.width == 'third' ) ? 'checked' : '' %>/>
				<span><?php _e( 'Third', 'justwpforms' ); ?></span>
			</label>
			<label for="<%= instance.id %>_width_quarter">
				<input type="radio" id="<%= instance.id %>_width_quarter" value="quarter" data-bind="width" name="<%= instance.id %>_width" <%= ( instance.width == 'quarter' ) ? 'checked' : '' %>/>
				<span><?php _e( 'Quarter', 'justwpforms' ); ?></span>
			</label>

			<label for="<%= instance.id %>_width_auto">
				<input type="radio" id="<%= instance.id %>_width_auto" value="auto" data-bind="width" name="<%= instance.id %>_width" <%= ( instance.width == 'auto' ) ? 'checked' : '' %> />
				<span><?php _e( 'Auto', 'justwpforms' ); ?></span>
			</label>
		</span>
	</p>
	<p class="width-options" style="display: none">
		<label>
			<input type="checkbox" class="checkbox apply-all-check" value="" data-apply-to="width" /> <?php _e( 'Apply to all fields', 'justwpforms' ); ?>
		</label>
	</p>

	<?php do_action( 'justwpforms_part_customize_payments_after_advanced_options' ); ?>

	<p>
		<label for="<%= instance.id %>_css_class"><?php _e( 'Additional CSS class(es)', 'justwpforms' ); ?></label>
		<input type="text" id="<%= instance.id %>_css_class" class="widefat title" value="<%- instance.css_class %>" data-bind="css_class" />
	</p>

	<div class="justwpforms-part-logic-wrap">
		<div class="justwpforms-logic-view">
			<?php justwpforms_customize_part_logic(); ?>
		</div>
	</div>

	<div class="justwpforms-widget-actions">
		<a href="#" class="justwpforms-form-part-remove"><?php _e( 'Delete', 'justwpforms' ); ?></a>
		<a href="#" class="justwpforms-form-part-logic"><?php _e( 'Logic', 'justwpforms' ); ?></a>
	</div>
</script>
