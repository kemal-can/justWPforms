<p>
	<label for="<%= instance.id %>_limit_answer_amount"><?php _e( 'Max times the same answer can be submitted', 'justwpforms' ); ?></label>
	<input type="number" id="<%= instance.id %>_limit_answer_amount" class="widefat title" step="1" value="<%= instance.max_limit_answer %>" data-bind="max_limit_answer" />
</p>
