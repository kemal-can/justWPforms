<?php $screen = get_current_screen()->id; ?>
<div class="wrap" id="justwpforms-integrations-screen">
	<h1><?php _e( 'Integrations', 'justwpforms' ); ?></h1>

	<div id="justwpforms-integration-toolbar" class="media-toolbar wp-filter">

		<div class="media-toolbar-secondary">
			<select id="justwpforms-integration-filters" class="attachment-filters">
				<option value=""><?php _e( 'All integrations', 'justwpforms' ); ?></option>
				<option value="analytics"><?php _e( 'Analytics and insights', 'justwpforms' ); ?></option>
				<option value="antispam"><?php _e( 'Anti-spam and validation', 'justwpforms' ); ?></option>
				<option value="automation"><?php _e( 'Automation services', 'justwpforms' ); ?></option>
				<option value="email"><?php _e( 'Email marketing', 'justwpforms' ); ?></option>
				<option value="payments"><?php _e( 'Payment processing', 'justwpforms' ); ?></option>
			</select>
		</div>

		<div class="media-toolbar-primary search-form">
			<label for="media-search-input" class="media-search-input-label"><?php _e( 'Search', 'justwpforms' ); ?></label>
			<input type="search" id="justwpforms-search-input" class="search">
		</div>
	</div>

	<div id="dashboard-widgets-wrap" class="justwpforms-admin-widgets">
		<div id="justwpforms-integrations-results-wrap">
			<p id="justwpforms-no-integrations-found"><?php _e( 'No integrations found.', 'justwpforms' ); ?></p>
			<div id="justwpforms-integrations-results" class="metabox-holder">
				<div id="postbox-container-1" class="postbox-container"></div>
				<div id="postbox-container-2" class="postbox-container"></div>
				<div id="postbox-container-3" class="postbox-container"></div>
				<div id="postbox-container-4" class="postbox-container"></div>
			</div>
		</div>

		<div id="dashboard-widgets" class="metabox-holder">
			<div id="postbox-container-1" class="postbox-container">
			<?php do_meta_boxes( $screen, 'normal', '' ); ?>
			</div>
			<div id="postbox-container-2" class="postbox-container">
			<?php do_meta_boxes( $screen, 'side', '' ); ?>
			</div>
			<div id="postbox-container-3" class="postbox-container">
			<?php do_meta_boxes( $screen, 'column3', '' ); ?>
			</div>
			<div id="postbox-container-4" class="postbox-container">
			<?php do_meta_boxes( $screen, 'column4', '' ); ?>
			</div>
		</div>
		<?php
		wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false );
		wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false );
		?>
	</div>
</div>
