<?php
$service = justwpforms_get_integrations()->get_service( 'convertkit' );
$credentials = $service->get_credentials();
$action = justwpforms_get_integrations()->integrations_action;
?>
<form class="justwpforms-service hf-ajax-submit">
	<div class="justwpforms-integrations-notices"><?php do_action( 'justwpforms_integrations_print_notices' ); ?></div>
	<div class="widget-content">
		<?php wp_nonce_field( $action ); ?>
		<input type="hidden" name="action" value="<?php echo $action; ?>">
		<input type="hidden" name="service" value="<?php echo $service->id; ?>">

		<div id="justwpforms-service-<?php echo $service->id; ?>" class="justwpforms-service-integration">
			<div class="widget-content <?php if ( $service->is_connected() ) : ?>authenticated<?php endif; ?>">
				<?php
				justwpforms_credentials_input(
					$service->id,
					'key',
					__( 'API key', 'justwpforms' ),
					$credentials['key']
				);
				?>
				<?php
				justwpforms_credentials_input(
					$service->id,
					'client_secret',
					__( 'API secret', 'justwpforms' ),
					$credentials['client_secret']
				);
				?>
			</div>
		</div>
		<div class="widget-control-actions">
			<div class="alignleft">
				<span class="spinner"></span>
				<input type="submit" class="connected button button-primary widget-control-save right" value="<?php _e( 'Save Changes', 'justwpforms' ); ?>">
			</div>
			<br class="clear" />
		</div>
	</div>
</form>
