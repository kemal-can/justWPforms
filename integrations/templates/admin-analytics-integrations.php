<?php

$integrations = justwpforms_get_integrations();
$service = $integrations->get_service( 'analytics' );
$action = $integrations->action_update;
$services = $integrations->get_services();
$groups = array();

foreach( $services as $service ) {
	$groups[$service->group][] = $service;
}
?>
<p><?php _e( 'Connect forms with analytics services.', 'justwpforms' ); ?></p>
<div class="widget-content <?php if ( $service->is_connected() ) : ?>authenticated<?php endif; ?>">
	<div class="justwpforms-settings-notices"></div>

	<form class="justwpforms-service hf-ajax-submit">
		<?php wp_nonce_field( $action ); ?>
		<input type="hidden" name="action" value="<?php echo $action; ?>">
		<input type="hidden" name="group" value="analytics">

		<?php
		foreach ( $groups['analytics'] as $sub_service ) {
			$sub_service->admin_widget();
		}
		?>

		<div class="widget-control-actions">
			<div class="alignleft">
				<span class="spinner"></span>
				<input type="submit" class="connected button button-primary widget-control-save right" value="<?php _e( 'Save Changes', 'justwpforms' ); ?>">
			</div>
			<br class="clear" />
		</div>
	</form>
</div>
