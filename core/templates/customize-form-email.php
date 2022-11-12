<?php
$controller = justwpforms_get_form_controller();
$controls = justwpforms_get_email()->get_controls();
?>

<script type="text/template" id="justwpforms-form-email-template">
	<div class="justwpforms-stack-view justwpforms-email-view">
	<?php
	$c = 0;
	foreach( $controls as $control ) {
		$field = false;

		if ( isset( $control['field'] ) ) {
			$field = $controller->get_field( $control['field'] );
		}

		do_action( 'justwpforms_do_email_control', $control, $field, $c );
		$c ++;
	}
	?>
	</div>
</script>
