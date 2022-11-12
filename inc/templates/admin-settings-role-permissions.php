<?php
$role_permissions = justwpforms_get_role_permissions();
$action = $role_permissions->save_action;
$nonce = $role_permissions->save_nonce;
$roles = $role_permissions->get_roles();
$permissions = $role_permissions->read();
?>
<div>
	<div class="justwpforms-settings-notices"></div>

	<form class="hf-ajax-submit">
		<?php wp_nonce_field( $action, $nonce ); ?>
		<input type="hidden" name="action" value="<?php echo $action; ?>">

		<p><?php _e( 'Manage users\' access to forms, submissions and settings per role.', 'justwpforms' ); ?></p>

		<div class="controls">
			<?php foreach( $roles as $role_id => $role ) : ?>
			<div class="control">
				<div class="control__line">
					<input type="checkbox" name="justwpforms_role_permissions[<?php echo $role_id; ?>][allow]" id="<?php echo "{$role_id}_allow"; ?>" value="1" <?php checked( $permissions[$role_id]['allow'], 1 ); ?>>
					<label for="<?php echo "{$role_id}_allow"; ?>"><?php printf( __( '%s role', 'justwpforms' ), translate_user_role( $role['name'] ) ); ?></label>
					<div class="nested-input">
						<div class="control">
							<div class="control__line">
								<input type="checkbox" name="justwpforms_role_permissions[<?php echo $role_id; ?>][allow_forms]" value="1" id="<?php echo $role_id; ?>_allow_forms" <?php checked( $permissions[$role_id]['allow_forms'], true ); ?>>
								<label for="<?php echo $role_id; ?>_allow_forms"><?php _e( 'Allow access to forms', 'justwpforms' ); ?></label>
							</div>
						</div>
						<div class="control">
							<div class="control__line">
								<input type="checkbox" name="justwpforms_role_permissions[<?php echo $role_id; ?>][allow_activity]" value="1" id="<?php echo $role_id; ?>_allow_activity" <?php checked( $permissions[$role_id]['allow_activity'], true ); ?>>
								<label for="<?php echo $role_id; ?>_allow_activity"><?php _e( 'Allow access to submissions', 'justwpforms' ); ?></label>
							</div>
						</div>
						<div class="control">
							<div class="control__line">
								<input type="checkbox" name="justwpforms_role_permissions[<?php echo $role_id; ?>][allow_settings]" value="1" id="<?php echo $role_id; ?>_allow_settings" <?php checked( $permissions[$role_id]['allow_settings'], true ); ?>>
								<label for="<?php echo $role_id; ?>_allow_settings"><?php _e( 'Allow access to settings (excludes Role Capabilities)', 'justwpforms' ); ?></label>
							</div>
						</div>
					</div>
				</div>
			</div>
			<?php endforeach; ?>
		</div>

		<div class="alignleft">
			<span class="spinner"></span>
			<input type="submit" class="button button-primary" value="<?php _e( 'Save Changes', 'justwpforms' ); ?>">
		</div>
		<br class="clear">
	</form>
</div>
