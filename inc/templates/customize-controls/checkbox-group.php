<div class="customize-control customize-control-checkbox-group <% if ( <?php echo $control['field']; ?> ) { %>checked<% } %>" data-type="checkbox-group" data-field="<?php echo $control['field']; ?>" id="customize-control-<?php echo $control['field']; ?>"<?php echo ( isset( $control['fetch'] ) && isset( $control['fetch']['var'] ) ) ? " data-fetch-var=\"{$control['fetch']['var']}\"" : ""; ?><?php echo ( isset( $control['fetch'] ) && isset( $control['fetch']['prop'] ) ) ? " data-fetch-prop=\"{$control['fetch']['prop']}\"" : ""; ?><?php echo ( isset( $control['fetch'] ) && isset( $control['fetch']['bind'] ) ) ? " data-fetch-bind=\"{$control['fetch']['bind']}\"" : ""; ?>>
	<?php do_action( "justwpforms_setup_control_{$control['field']}_before", $control ); ?>

	<p class="customize-control-title"><?php echo $control['label']; ?></p>

	<div class="customize-inside-control-row" style="margin-left: 0" data-pointer-target>
		<?php if ( ! isset( $control['fetch'] ) || 'server' === $control['fetch']['type'] ) : ?>
			<div class="customize-control-options">
				<?php foreach ( $control['groups'] as $group ) : ?>
					<h4><?php echo $group['name']; ?></h4>

					<?php foreach( $group['options'] as $option => $label ) : ?>
						<div>
							<input type="checkbox" id="<?php echo $control['field']; ?>_<?php echo $option; ?>" value="<?php echo $option; ?>" <% if ( -1 !== <?php echo $control['field']; ?>.indexOf( '<?php echo $option; ?>' ) ) { %>checked="checked"<% } %> data-attribute="<?php echo $control['field']; ?>" />
							<label for="<?php echo $control['field']; ?>_<?php echo $option; ?>"><?php echo $label; ?></label>
						</div>
					<?php endforeach; ?>
				<?php endforeach; ?>
			</div>
		<?php else: ?>
			<div class="customize-control-options">
			</div>
			<p class="description no-groups"><?php echo $control['no_options']; ?></p>
		<?php endif; ?>
	</div>

	<?php do_action( "justwpforms_setup_control_{$control['field']}_after", $control ); ?>
</div>
