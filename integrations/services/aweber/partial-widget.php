<?php
$service = justwpforms_get_integrations()->get_service( 'aweber' );
$credentials = $service->get_credentials();
$code_verifier = $service->generate_code_verifier();
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
				<div class="oauth-flow">
					<input type="hidden" name="credentials[aweber][code_verifier]" value="<?php echo $code_verifier; ?>" />
					<?php
					$label_link = ' (<a href="' . $service->get_authorize_url( $code_verifier ) . '" target="_blank">' . __( 'get your code', 'justwpforms' ) . justwpforms_the_external_link_icon( false ) . '</a>)';
					justwpforms_credentials_input(
						$service->id,
						'verification_code',
						__( 'Verification code', 'justwpforms' ) . $label_link,
						''
					);
					?>
				</div>
				<div class="oauth-connected">
					<p>
						<?php _e( 'Successfully connected', 'justwpforms' ); ?>. <a href="<?php echo $service->get_authorize_url( $code_verifier ); ?>" target="_blank"><?php _e( 'Authenticate again', 'justwpforms' ); ?></a>
					</p>
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
