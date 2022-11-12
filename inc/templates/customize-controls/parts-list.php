<div class="customize-control" id="customize-control-<?php echo $control['field']; ?>">
	<div class="customize-password-field-wrap">
		<label for="<?php echo $control['field']; ?>" class="customize-control-title"><?php echo $control['label']; ?>:</label>
		<select id="<?php echo $control['field']; ?>" data-attribute="<?php echo $control['field']; ?>">
			<?php if ( isset( $control['empty_option'] ) ) : ?>
				<option value=""><?php echo $control['empty_option']; ?></option>
			<?php endif; ?>
			<%
			var parts = justwpforms.form.get( 'parts' );

			_( parts.models ).each( function( model ) {
			%>
				<option value="<%= model.get( 'id' ) %>"<%= ( model.get( 'id' ) === <?php echo $control['field']; ?> ) ? ' selected' : '' %>><%= model.get( 'label' ) %></option>
			<%
			} );
			%>
		</select>
	</div>
</div>
