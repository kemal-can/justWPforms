<?php

$integrations = justwpforms_get_integrations();
$service = $integrations->get_service( 'antispam' );
$action = $integrations->action_update;
$services = $integrations->get_services();
$active_service = $service->get_active_service();
$active_service = $active_service ? $active_service->id : '';
$groups = array();

foreach( $services as $service ) {
	$groups[$service->group][] = $service;
}
?>
<form class="justwpforms-service hf-ajax-submit">
	<div class="widget-content has-service-selection <?php if ( $service->is_connected() ) : ?>authenticated<?php endif; ?>" data-active-service="<?php echo $active_service; ?>">
		<div class="justwpforms-settings-notices"><?php do_action( 'justwpforms_integrations_print_notices' ); ?></div>
		<?php wp_nonce_field( $action ); ?>
		<input type="hidden" name="action" value="<?php echo $action; ?>">
		<input type="hidden" name="group" value="antispam">
		<div class="mode-group">
 			<label for="services[]"><?php _e( 'Service', 'justwpforms' ); ?></label>
 			<div class="justwpforms-buttongroup">
 				<?php foreach( $groups['antispam'] as $sub_service ) : ?>
 					<label for="service_<?php echo $sub_service->id; ?>">
 						<input type="radio" id="service_<?php echo $sub_service->id; ?>" value="<?php echo $sub_service->id; ?>" name="services[]" <?php echo ( $sub_service->id == $active_service ) ? 'checked' : ''; ?>/>
 						<span><?php echo $sub_service->label; ?></span>
 					</label>
				<?php endforeach; ?>
 			</div>
 		</div>
		<?php
		foreach ( $groups['antispam'] as $sub_service ) {
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
	</div>
</form>

