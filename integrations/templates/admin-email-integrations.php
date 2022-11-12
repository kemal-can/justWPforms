<?php

$integrations = justwpforms_get_integrations();
$service = $integrations->get_service( 'email' );
$action = $integrations->action_update;
$services = $integrations->get_services();
$active_service = $service->get_active_service();
$active_service = $active_service ? $active_service->id : $active_service;
$groups = array();

foreach( $services as $service ) {
	$groups[$service->group][] = $service;
}
?>
<p><?php _e( 'Connect forms with an email service.', 'justwpforms' ); ?></p>
<div class="widget-content has-service-selection <?php if ( $service->is_connected() ) : ?>authenticated<?php endif; ?>" data-active-service="<?php echo $active_service; ?>">
	<div class="justwpforms-settings-notices"></div>

	<form class="justwpforms-service hf-ajax-submit">
		<?php wp_nonce_field( $action ); ?>
		<input type="hidden" name="action" value="<?php echo $action; ?>">
		<input type="hidden" name="group" value="email">

		<p>
			<label for="justwpforms_integrations_email_service"><?php _e( 'Service:', 'justwpforms' ); ?></label>
			<select id="justwpforms_integrations_email_service" name="services[]" class="widefat">
				<option value="">— <?php _e( 'Select', 'justwpforms' ); ?> —</option>

				<?php foreach( $groups['email'] as $sub_service ) : ?>
					<option value="<?php echo $sub_service->id; ?>" <?php selected( $sub_service->id, $active_service ); ?>><?php echo $sub_service->label; ?></option>
				<?php endforeach; ?>
			</select>
		</p>

		<?php
		foreach ( $groups['email'] as $sub_service ) {
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
