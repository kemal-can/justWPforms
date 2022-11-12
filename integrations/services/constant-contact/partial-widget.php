<?php
$service = justwpforms_get_integrations()->get_service( 'constant-contact' );
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
				<div class="justwpforms-clipboard-field">
					<label for="constant-contact-redirect-uri"><?php _e( 'Redirect URI', 'justwpforms' ); ?></label>
					<div class="justwpforms-clipboard-field-input-wrapper">
						<input type="text" name="constant-contact-redirect-uri" readonly value="<?php echo $service->redirect_uri; ?>" />
						<div class="justwpforms-clipboard">
							<button type="button" class="button justwpforms-clipboard__button" data-value="<?php echo $service->redirect_uri; ?>"><?php _e( 'Copy to clipboard', 'justwpforms' ); ?></button>
							<span aria-hidden="true" class="hidden"><?php _e( 'Copied!', 'justwpforms' ); ?></span>
						</div>
					</div>
				</div>
				<?php
				justwpforms_credentials_input(
					$service->id,
					'client_id',
					__( 'API key', 'justwpforms' ),
					$credentials['client_id']
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
				<p>
					<input type="text" value="<?php echo $service->redirect_uri; ?>" id="constant-contact-redirect-url" style="position: absolute; left: -9999px;">
				</p>
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
