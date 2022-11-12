<?php
$controller = justwpforms_get_form_controller();
$controls = justwpforms_get_styles()->get_controls();
?>

<script type="text/template" id="justwpforms-form-style-template">
    <div class="justwpforms-stack-view justwpforms-style-view">
        <ul class="justwpforms-form-widgets justwpforms-style-controls">
		<?php
		$c = 0;
		foreach( $controls as $control ) {
			$field = isset( $control['field'] ) ?
				$controller->get_field( $control['field'] ) : '';
			do_action( 'justwpforms_do_style_control', $control, $field, $c );
			$c ++;
		}
		?>
		</ul>
    </div>
</script>