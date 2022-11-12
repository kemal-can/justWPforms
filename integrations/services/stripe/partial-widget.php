<?php
$service = justwpforms_get_integrations()->get_service( 'stripe' );
$credentials = $service->get_credentials();
$action = justwpforms_get_integrations()->integrations_action;
$connected = $service->is_connected();
$mode = $credentials['mode'];
?>
<form class="justwpforms-service hf-ajax-submit">
	<div class="justwpforms-integrations-notices"><?php do_action( 'justwpforms_integrations_print_notices' ); ?></div>
	<div class="widget-content">
	<?php wp_nonce_field( $action ); ?>
	<input type="hidden" name="action" value="<?php echo $action; ?>">
	<input type="hidden" name="service" value="<?php echo $service->id; ?>">
	<div id="justwpforms-service-<?php echo $service->id; ?>" class="justwpforms-service-integration<?php echo ( $connected ) ? ' enabled' : ''; ?>" data-active-mode="<?php echo $mode; ?>">
		<div class="mode-group">
 			<label for="credentials[stripe][mode]"><?php _e( 'Mode', 'justwpforms' ); ?></label>
 			<div class="justwpforms-buttongroup">
 				<label for="stipe_live">
 					<input type="radio" id="stipe_live" value="live" name="credentials[stripe][mode]" <?php echo ( 'live' == $mode ) ? 'checked' : ''; ?>/>
 					<span><?php _e( 'Live', 'justwpforms' ); ?></span>
 				</label>
 				<label for="stripe_test">
 					<input type="radio" id="stripe_test" value="test" name="credentials[stripe][mode]" <?php echo ( 'test' == $mode ) ? 'checked' : ''; ?>/>
 					<span><?php _e( 'Test', 'justwpforms' ); ?></span>
 				</label>
 			</div>
 		</div>
		<div class="justwpforms-stripe-block justwpforms-stripe-block-live">
			<div class="widget-content <?php if ( $service->is_connected() ) : ?>authenticated<?php endif; ?>">
				<input type="hidden" name="services[]" value="<?php echo $service->id; ?>">
				<?php
				justwpforms_credentials_input(
					$service->id,
					'key',
					__( 'Publishable key', 'justwpforms' ),
					$credentials['key']
				);
				?>
				<?php
				justwpforms_credentials_input(
					$service->id,
					'secret_key',
					__( 'Secret key', 'justwpforms' ),
					$credentials['secret_key']
				);
				?>
			</div>
		</div>
		<div class="justwpforms-stripe-block justwpforms-stripe-block-test">
			<div class="widget-content <?php if ( $service->is_connected() ) : ?>authenticated<?php endif; ?>">
				<input type="hidden" name="services[]" value="<?php echo $service->id; ?>">
				<?php
				justwpforms_credentials_input(
					$service->id,
					'test_key',
					__( 'Publishable key', 'justwpforms' ),
					$credentials['test_key']
				);
				?>
				<?php
				justwpforms_credentials_input(
					$service->id,
					'test_secret_key',
					__( 'Secret key', 'justwpforms' ),
					$credentials['test_secret_key']
				);
				?>
			</div>
		</div>
		<div class="justwpforms-clipboard-field">
			<label for="credentials[stripe][webhook_endpoint_url]"><?php _e( 'Webhook endpoint URL', 'justwpforms' ); ?></label>
			<div class="justwpforms-clipboard-field-input-wrapper">
				<input type="text" readonly value="<?php echo $service->webhook_endpoint_url; ?>" />
				<div class="justwpforms-clipboard">
					<button type="button" class="button justwpforms-clipboard__button" data-value="<?php echo $service->webhook_endpoint_url; ?>"><?php _e( 'Copy to clipboard', 'justwpforms' ); ?></button>
					<span aria-hidden="true" class="hidden"><?php _e( 'Copied!', 'justwpforms' ); ?></span>
				</div>
			</div>
		</div>
		<div class="justwpforms-stripe-webhook-endpoint-secret-key justwpforms-stripe-webhook-endpoint-secret-key-live">
			<?php
			justwpforms_credentials_input(
				$service->id,
				'webhook_endpoint_secret_key',
				__( 'Webhook endpoint secret key', 'justwpforms' ),
				$credentials['webhook_endpoint_secret_key']
			);
			?>
		</div>
		<div class="justwpforms-stripe-webhook-endpoint-secret-key justwpforms-stripe-webhook-endpoint-secret-key-test">
			<?php
			justwpforms_credentials_input(
				$service->id,
				'test_webhook_endpoint_secret_key',
				__( 'Webhook endpoint secret key', 'justwpforms' ),
				$credentials['test_webhook_endpoint_secret_key']
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
