<?php
$form_controller = justwpforms_get_form_controller();
$message_controller = justwpforms_get_message_controller();
$action_type = ( isset( $_GET['action_type'] ) ) ? esc_attr( $_GET['action_type'] ) : '';
?>

<?php
$form_classes = array(
	'justwpforms-export-section__form'
);

switch( $action_type ) {
	case 'import':
		$form_classes[] = 'doing-action-import';
		break;
}
?>
<p><?php _e( 'Transfer forms and submissions to and from sites.', 'justwpforms' ); ?></p>
<form class="<?php echo join( ' ', $form_classes ); ?>" action="<?php echo admin_url( 'admin.php' ); ?>" id="justwpforms-export-form" method="post">
	<input type="hidden" name="action" value="justwpforms_export_import">
	<?php wp_nonce_field( 'justwpforms_export_import', 'justwpforms_export_nonce' ); ?>

	<div id="justwpforms-export-select-action">
		<label for="justwpforms-export-select-action-select"><?php _e( 'Action', 'justwpforms' ); ?>:</label>
		<select name="action_type" id="justwpforms-export-select-action-select">
			<option value="" selected>— <?php _e( 'Select', 'justwpforms' ); ?> —</option>
			<option value="import" <?php selected( $action_type, 'import' ); ?>><?php _e( 'Import data (.xml)', 'justwpforms' ); ?></option>
			<option value="export_responses" <?php selected( $action_type, 'export_responses' ); ?>><?php _e( 'Export submissions to spreadsheet (.csv)', 'justwpforms' ); ?></option>
			<option value="export_form" <?php selected( $action_type, 'export_form' ); ?>><?php _e( 'Export a form (.xml)', 'justwpforms' ); ?></option>
			<option value="export_form_responses" <?php selected( $action_type, 'export_form_responses' ); ?>><?php _e( 'Export a form and its submissions (.xml)', 'justwpforms' ); ?></option>
		</select>
	</div>

	<div id="justwpforms-export-select-form">
		<label for="justwpforms-export-select-form-select"><?php _e( 'Form', 'justwpforms' ); ?>:</label>
		<select name="form_id" id="justwpforms-export-select-form-select">
			<option value="" data-has-responses selected><?php _e( '— Select —', 'justwpforms' ); ?>
			<?php
			$forms = $form_controller->get();
			$submission_counter = justwpforms_submission_counter();

			foreach ( $forms as $form ) {
				$submissions_total = justwpforms_get_meta( $form['ID'], $submission_counter->key_count_submission_total, true );
				?>
				<option value="<?php echo esc_attr( $form['ID'] ); ?>"<?php echo ( ! empty( $submissions_total ) ) ? ' data-has-responses' : ''; ?>><?php echo justwpforms_get_form_property( $form, 'post_title' ); ?></option>
				<?php
			}
			?>
		</select>
	</div>

	<div id="justwpforms-export-section-import">
		<div id="justwpforms-export-import-form">
			<div class="media-upload-form type-form validate">
				<?php wp_enqueue_script( 'plupload' ); ?>
				<?php wp_enqueue_script( 'plupload-handlers' ); ?>

				<p>
					<label for="upload"><?php _e( 'Choose a file from your computer', 'justwpforms' ); ?>:</label>
					(<?php _e( 'Maximum size', 'justwpforms' ); ?>: <?php echo size_format( wp_max_upload_size() ); ?>)
					<input type="file" name="file-import-stub" size="25" id="justwpforms-export-import-upload-stub" accept=".xml">
				</p>
				<?php media_upload_form(); ?>
			</div>
		</div>

		<div id="justwpforms-import-status">
			<div class="static-messages">
				<p><?php _e( 'Importing file…', 'justwpforms' ); ?></p>
			</div>
			<div class="runtime-messages"></div>
		</div>

		<div id="justwpforms-import-result">
			<div id="justwpforms-import-messages">
			</div>
		</div>

		<input type="hidden" id="justwpforms-import-attachment-id">
	</div>

	<?php
	$button_labels = array(
		'export' => __( 'Download Export File', 'justwpforms' ),
		'import' => __( 'Upload File and Import', 'justwpforms' ),
	);

	$button_default_label = ( isset( $button_labels[$action_type] ) ) ? $button_labels[$action_type] : __( 'Submit', 'justwpforms' );
	?>
	<button type="submit" class="button button-primary" data-label-export_responses="<?php echo $button_labels['export']; ?>" data-label-export_form="<?php echo $button_labels['export']; ?>" data-label-export_form_responses="<?php echo $button_labels['export']; ?>" data-label-import="<?php echo $button_labels['import']; ?>"><?php echo $button_default_label; ?></button>
	<p class="reset">
		<a href="<?php echo admin_url( '/admin.php?page=justwpforms-export&action_type=import' ); ?>"><?php _e( 'Retry', 'justwpforms' ); ?></a>
	</p>
	<p class="import-more">
		<a href="<?php echo admin_url( '/admin.php?page=justwpforms-settings&action_type=import' ); ?>"><?php _e( 'Import another file', 'justwpforms' ); ?></a>
	</p>
</form>
