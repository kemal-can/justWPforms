<?php
$service = justwpforms_get_integrations()->get_service( 'google-analytics' );
$credentials = $service->get_credentials();
$action = justwpforms_get_integrations()->integrations_action;
$connected = $service->is_connected();
?>
<form class="justwpforms-service hf-ajax-submit">
	<div class="justwpforms-integrations-notices"><?php do_action( 'justwpforms_integrations_print_notices' ); ?></div>
	<div class="widget-content <?php if ( $service->is_connected() ) : ?>authenticated<?php endif; ?>">
		<?php wp_nonce_field( $action ); ?>
		<input type="hidden" name="action" value="<?php echo $action; ?>">
		<input type="hidden" name="service" value="<?php echo $service->id; ?>">
		<div id="justwpforms-service-<?php echo $service->id; ?>" class="justwpforms-service-integration<?php echo ( $connected ) ? ' enabled' : ''; ?>">
					<input type="hidden" name="services[]" value="<?php echo $service->id; ?>">
						<label for="justwpforms_integrations_google-analytics_tracking_id"><?php _e( 'Tracking ID', 'justwpforms' ); ?></label>
						<div class="hf-pwd">
							<input type="password" class="widefat connected" id="justwpforms_integrations_google-analytics_tracking_id" name="credentials[google-analytics][tracking_id]" value="<?php echo $credentials['tracking_id']; ?>" />
							<button type="button" class="button button-secondary hf-hide-pw hide-if-no-js" data-toggle="0" aria-label="<?php _e( 'Show credentials', 'justwpforms' ); ?>" data-label-show="<?php _e( 'Show credentials', 'justwpforms' ); ?>" data-label-hide="<?php _e( 'Hide credentials', 'justwpforms' ); ?>">
								<span class="dashicons dashicons-visibility" aria-hidden="true"></span>
							</button>
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