<?php
$service = justwpforms_get_integrations()->get_service( 'paypal' );
$credentials = $service->get_credentials();
$action = justwpforms_get_integrations()->integrations_action;
$connected = $service->is_connected();
$mode = $credentials['mode']
?>
<form class="justwpforms-service hf-ajax-submit">
	<div class="justwpforms-integrations-notices"><?php do_action( 'justwpforms_integrations_print_notices' ); ?></div>
	<div class="widget-content">
	<?php wp_nonce_field( $action ); ?>
	<input type="hidden" name="action" value="<?php echo $action; ?>">
	<input type="hidden" name="service" value="<?php echo $service->id; ?>">
	<div id="justwpforms-service-<?php echo $service->id; ?>" class="justwpforms-service-integration<?php echo ( $connected ) ? ' enabled' : ''; ?>" data-active-mode="<?php echo $mode; ?>">
		<div class="mode-group">
 			<label for="credentials[paypal][mode]"><?php _e( 'Mode', 'justwpforms' ); ?></label>
 			<div class="justwpforms-buttongroup">
 				<label for="paypal_live">
 					<input type="radio" id="paypal_live" value="live" name="credentials[paypal][mode]" <?php echo ( 'live' == $mode ) ? 'checked' : ''; ?>/>
 					<span><?php _e( 'Live', 'justwpforms' ); ?></span>
 				</label>
 				<label for="paypal_sandbox">
 					<input type="radio" id="paypal_sandbox" value="sandbox" name="credentials[paypal][mode]" <?php echo ( 'sandbox' == $mode ) ? 'checked' : ''; ?>/>
 					<span><?php _e( 'Sandbox', 'justwpforms' ); ?></span>
 				</label>
 			</div>
 		</div>

		<div class="justwpforms-paypal-block justwpforms-paypal-block-live">
			<div class="widget-content <?php if ( $service->is_connected() ) : ?>authenticated<?php endif; ?>">
				<input type="hidden" name="services[]" value="<?php echo $service->id; ?>">
				<?php
				justwpforms_credentials_input(
					$service->id,
					'client_id',
					__( 'Client ID', 'justwpforms' ),
					$credentials['client_id']
				);
				?>
				<?php
				justwpforms_credentials_input(
					$service->id,
					'client_secret',
					__( 'Client secret', 'justwpforms' ),
					$credentials['client_secret']
				);
				?>
			</div>
		</div>
		<div class="justwpforms-paypal-block justwpforms-paypal-block-sandbox">
			<div class="widget-content <?php if ( $service->is_connected() ) : ?>authenticated<?php endif; ?>">
				<input type="hidden" name="services[]" value="<?php echo $service->id; ?>">
				<?php
				justwpforms_credentials_input(
					$service->id,
					'sandbox_client_id',
					__( 'Client ID', 'justwpforms' ),
					$credentials['sandbox_client_id']
				);
				?>
				<?php
				justwpforms_credentials_input(
					$service->id,
					'sandbox_client_secret',
					__( 'Client secret', 'justwpforms' ),
					$credentials['sandbox_client_secret']
				);
				?>
			</div>
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