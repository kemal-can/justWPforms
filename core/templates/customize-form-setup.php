<?php
$controller = justwpforms_get_form_controller();
$controls = justwpforms_get_setup()->get_controls();
?>

<script type="text/template" id="justwpforms-form-setup-template">
	<div class="justwpforms-stack-view justwpforms-setup-view">
	<?php
	$c = 0;
	foreach( $controls as $control ) {
		$field = false;

		if ( isset( $control['field'] ) ) {
			$field = $controller->get_field( $control['field'] );
		}

		do_action( 'justwpforms_do_setup_control', $control, $field, $c );
		$c ++;
	}
	?>
	</div>
</script>
