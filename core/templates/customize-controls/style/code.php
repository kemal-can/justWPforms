<li class="customize-control justwpforms-code-control" data-target="<?php echo esc_attr( $field['target'] ); ?>" data-mode="<?php echo $control['mode']; ?>" id="customize-control-<?php echo $control['field']; ?>">
	<?php if ( ! isset( $control['hide_title'] ) || ! $control['hide_title'] ) : ?>
	<label class="customize-control-title" for="<?php echo $control['field']; ?>"><?php echo $control['label']; ?></label>
	<?php endif; ?>
	<div class="customize-control-content" data-pointer-target>
		<textarea class="code" name="<?php echo $control['field']; ?>" id="<?php echo $control['field']; ?>" data-attribute="<?php echo $control['field']; ?>" data-mode="<?php echo $field['mode']; ?>"><%= <?php echo $control['field']; ?> %></textarea>
	</div>
</li>
