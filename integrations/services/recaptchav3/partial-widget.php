<?php

$integrations = justwpforms_get_integrations();
$service = $integrations->get_service( 'recaptchav3' );
$credentials = $service->get_credentials();
$scores = array( '0.1', '0.2', '0.3', '0.4', '0.5', '0.6', '0.7', '0.8', '0.9', '1.0' );
?>
<div id="justwpforms-service-<?php echo $service->id; ?>" class="justwpforms-service-integration">
	<div class="widget-content <?php if ( $service->is_connected() ) : ?>authenticated<?php endif; ?>">
		<div class="mode-group">
 			<label for="credentials[recaptchav3][min_score]"><?php _e( 'Minimum accepted score', 'justwpforms' ); ?></label>
 			<div class="justwpforms-buttongroup">
 				<?php foreach( $scores as $score ) : ?>
 					<label for="v3_score_<?php echo $score; ?>">
 						<input type="radio" id="v3_score_<?php echo $score; ?>" value="<?php echo $score; ?>" name="credentials[recaptchav3][min_score]" <?php echo ( $score == $credentials['min_score'] ) ? 'checked' : ''; ?>/>
 						<span><?php echo $score; ?></span>
 					</label>
				<?php endforeach; ?>
 			</div>
 		</div>

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
