<?php

$integrations = justwpforms_get_integrations();
$service = $integrations->get_service( 'recaptcha' );
$credentials = $service->get_credentials();
?>
<div id="justwpforms-service-<?php echo $service->id; ?>" class="justwpforms-service-integration">
	<div class="widget-content <?php if ( $service->is_connected() ) : ?>authenticated<?php endif; ?>">
		<?php
		justwpforms_credentials_input(
			$service->id,
			'site',
			__( 'Site key', 'justwpforms' ),
			$credentials['site']
		);
		?>
		<?php
		justwpforms_credentials_input(
			$service->id,
			'secret',
			__( 'Secret key', 'justwpforms' ),
			$credentials['secret']
		);
		?>
	</div>
</div>
