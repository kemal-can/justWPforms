<?php

class justwpforms_Message_Admin {

	/**
	 * The singleton instance.
	 *
	 * @since 1.0
	 *
	 * @var justwpforms_Message_Admin
	 */
	private static $instance;

	/**
	 * The form the form filter is pointing to.
	 *
	 * @since 1.0
	 *
	 * @var array
	 */
	private $current_form;

	/**
	 * The name of the Column Count option in the
	 * Screen Options tab.
	 *
	 * @since 1.0
	 *
	 * @var string
	 */
	private $column_count_option = 'justwpforms-message-admin-col-count';

	/**
	 * The default amount of rows to show.
	 *
	 * @var int
	 */
	private $row_count = 20;

	/**
	 * The default amount of columns to show.
	 *
	 * @var int
	 */
	private $column_count = 1;


	/**
	 * The default amount of parts per submission to show.
	 *
	 * @var int
	 */
	private $parts_per_submission = 10;

	private $filter_status = 'activity_status';

	private $mark_action = 'justwpforms_mark_response';

	/**
	 * The singleton constructor.
	 *
	 * @since 1.0
	 *
	 * @return justwpforms_Message_Admin
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		self::$instance->hook();

		return self::$instance;
	}

	/**
	 * Register hooks.
	 *
	 * @since 1.0
	 *
	 * @return void
	 */
	public function hook() {
		$controller = justwpforms_get_message_controller();
		$this->post_type = $controller->post_type;

		add_action( 'parse_request', array( $this, 'parse_request' ) );
		add_action( 'admin_head', array( $this, 'output_styles' ) );
		add_filter( 'post_updated_messages', array( $this, 'post_updated_messages' ) );
		add_filter( 'bulk_post_updated_messages', array( $this, 'bulk_post_updated_messages' ), 10, 2 );
		add_action( 'load-edit.php', array( $this, 'define_screen_settings' ) );
		add_action( 'load-edit.php', array( $this, 'handle_delete_all_spam' ) );
		add_filter( 'screen_settings', array( $this, 'render_screen_settings' ), 10, 2 );
		add_filter( "manage_{$this->post_type}_posts_columns", array( $this, 'column_headers' ), PHP_INT_MAX );
		add_filter( "manage_edit-{$this->post_type}_sortable_columns", array( $this, 'sortable_columns' ), PHP_INT_MAX );
		add_action( "manage_{$this->post_type}_posts_custom_column", array( $this, 'column_content' ), 10, 2 );
		add_filter( 'list_table_primary_column', array( $this, 'table_primary_column' ), 10, 2 );
		add_filter( "views_edit-{$this->post_type}", array( $this, 'table_views' ) );
		add_filter( 'post_date_column_status', array( $this, 'post_date_column_status' ) );
		add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ) );
		add_filter( 'post_class', array( $this, 'post_class' ), 10, 3 );
		add_action( 'restrict_manage_posts', array( $this, 'restrict_manage_posts' ), PHP_INT_MAX );
		add_action( 'manage_posts_extra_tablenav', array( $this, 'manage_posts_extra_tablenav' ) );
		add_filter( "bulk_actions-edit-{$this->post_type}", array( $this, 'bulk_actions' ) );
		add_filter( "handle_bulk_actions-edit-{$this->post_type}", array( $this, 'handle_bulk_actions' ), 10, 3 );
		add_filter( 'post_row_actions', array( $this, 'row_actions' ), 10, 2 );
		add_action( 'edit_form_after_title', array( $this, 'edit_screen' ) );
		add_filter( 'admin_footer_text', 'justwpforms_admin_footer' );
		add_action( 'admin_notices', array( $this, 'print_activity_title_with_form_link' ) );
		add_action( 'admin_notices', array( $this, 'print_notices' ) );
		add_filter( 'justwpforms_dashboard_data', array( $this, 'dashboard_data' ) );
		add_filter( 'admin_title', array( $this, 'admin_title' ), 10, 2 );
	}

	/**
	 * Action: set the current form and form ids
	 * depending on the value of the form filter.
	 *
	 * @since 1.0
	 *
	 * @hooked action parse_request
	 *
	 * @return void
	 */
	public function parse_request() {
		$form_id = isset( $_GET['form_id'] ) ? intval( $_GET['form_id'] ) : 0;

		if ( $form_id ) {
			$this->current_form = justwpforms_get_form_controller()->get( $form_id );
		}
	}

	/**
	 * Action: output styles in the admin header of the Messages screen.
	 *
	 * @since 1.0
	 *
	 * @hooked action admin_head
	 *
	 * @return void
	 */
	public function output_styles() {
		global $pagenow;

		$post_type = justwpforms_get_message_controller()->post_type;

		if ( 'edit.php' === $pagenow ) : ?>
		<style>
		#adv-settings fieldset {
			display: none;
		}
		#adv-settings fieldset:first-child {
			display: block;
		}
		#adv-settings fieldset:first-child label:first-of-type {
			display: none;
		}
		</style>
		<?php endif;
	}

	/**
	 * Filter: tweak the text of the message post actions admin notices.
	 *
	 * @since 1.0
	 *
	 * @hooked filter post_updated_messages
	 *
	 * @param array $messages The messages configuration.
	 *
	 * @return array
	 */
	public function post_updated_messages( $messages ) {
		$post_type = justwpforms_get_message_controller()->post_type;
		$permalink = get_permalink();
		$preview_url = get_preview_post_link();
		$view_form_link_html = sprintf(
			' <a href="%1$s">%2$s</a>',
			esc_url( $permalink ),
			__( 'Edit submission' )
		);
		$preview_post_link_html = sprintf(
			' <a target="_blank" href="%1$s">%2$s</a>',
			esc_url( $preview_url ),
			__( 'Preview submission' )
		);

		$messages[$post_type] = array(
			'',
			__( 'Submission updated.' ) . $view_form_link_html,
			__( 'Custom field updated.' ),
			__( 'Custom field deleted.' ),
			__( 'Submission updated.' ),
			isset($_GET['revision']) ? sprintf( __( 'Submission restored to revision from %s.' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			__( 'Submission published.' ) . $view_form_link_html,
			__( 'Submission saved.' ),
			__( 'Submission submitted.' ),
			__( 'Submission scheduled.' ),
			__( 'Submission draft updated.' ) . $preview_post_link_html,
		);

		return $messages;
	}

	/**
	 * Filter: tweak the text of the message post
	 * bulk actions admin notices.
	 *
	 * @since 1.0
	 *
	 * @hooked filter bulk_post_updated_messages
	 *
	 * @param array $messages The messages configuration.
	 * @param int   $count    The amount of posts for each bulk action.
	 *
	 * @return array
	 */
	public function bulk_post_updated_messages( $messages, $count ) {
		$post_type = justwpforms_get_message_controller()->post_type;

		$messages[$post_type] = array(
			'updated'   => _n( '%s submission updated.', '%s submissions updated.', $count['updated'] ),
			'locked'    => _n( '%s submission not updated, somebody is editing it.', '%s submissions not updated, somebody is editing them.', $count['locked'] ),
			'deleted'   => _n( '%s submission permanently deleted.', '%s submissions permanently deleted.', $count['deleted'] ),
			'trashed'   => _n( '%s submission moved to the Trash.', '%s submissions moved to the Trash.', $count['trashed'] ),
			'untrashed' => _n( '%s submission restored from the Trash.', '%s submissions restored from the Trash.', $count['untrashed'] ),
		);

		return $messages;
	}

	/**
	 * Action: configure additional options for the Screen Options tab.
	 *
	 * @since 1.0
	 *
	 * @hooked action load-edit.php
	 *
	 * @return void
	 */
	public function define_screen_settings() {
		$screen = get_current_screen();
		$post_type = justwpforms_get_message_controller()->post_type;
		$user_id = get_current_user_id();

		$row_count_option = 'edit_' . $post_type . '_per_page';

		if ( isset( $_REQUEST[$row_count_option] ) ) {
			$row_count = max( intval( $_REQUEST[$row_count_option] ), 1 );
			update_user_option( $user_id, $row_count_option, $row_count, true );
		}

		$parts_per_submission_option = 'edit_' . $post_type . '_parts_per_submission';

		if ( isset( $_REQUEST[$parts_per_submission_option] ) ) {
			$parts_per_submission = max( intval( $_REQUEST[$parts_per_submission_option] ), 1 );
			update_user_option( $user_id, $parts_per_submission_option, $parts_per_submission, true );
		}

		$row_count = get_user_option( $row_count_option, $user_id );
		$parts_per_submission = get_user_option( $parts_per_submission_option, $user_id );
		$row_count = ( false !== $row_count ) ? $row_count : $this->row_count;
		$parts_per_submission = ( false !== $parts_per_submission ) ? $parts_per_submission : $this->parts_per_submission;
		$this->row_count = max( intval( $row_count ), 1 );
		$this->parts_per_submission = max( intval( $parts_per_submission ), 1 );
	}

	/**
	 * Filter: output additional options in the Screen Options tab.
	 *
	 * @since 1.0
	 *
	 * @hooked filter screen_settings
	 *
	 * @param array     $settings The currently configured options.
	 * @param WP_Screen $count    The current screen object.
	 *
	 * @return void
	 */
	public function render_screen_settings( $settings, $screen ) {
		$post_type = justwpforms_get_message_controller()->post_type;
		global $mode;

		if ( 'edit-' . $post_type !== $screen->id ) {
			return $settings;
		}

		ob_start();
		?>
		<fieldset style="display: block;" class="justwpforms-activity-settings-parts screen-options">
			<legend><?php _e( 'Fields', 'justwpforms' ); ?></legend>
			<label for=""><?php _e( 'Number of fields per submission:', 'justwpforms' ); ?></label>
			<input type="number" min="1" max="999" maxlength="3" name="edit_<?php echo esc_attr( $post_type ); ?>_parts_per_submission" value="<?php echo esc_attr( $this->parts_per_submission ); ?>">
			<input type="hidden" name="wp_screen_options[option]" value="<?php echo esc_attr( "edit_{$post_type}_parts_per_submission" ); ?>">
			<input type="hidden" name="wp_screen_options[value]" value="10">
		</fieldset>
		<fieldset style="display: block;" class="justwpforms-activity-settings-pagination screen-options">
			<legend><?php _e( 'Pagination', 'justwpforms' ); ?></legend>
			<label for=""><?php _e( 'Number of items per page:', 'justwpforms' ); ?></label>
			<input type="number" min="1" max="999" maxlength="3" name="edit_<?php echo esc_attr( $post_type ); ?>_per_page" value="<?php echo esc_attr( $this->row_count ); ?>">
		</fieldset>
		<fieldset style="display: block;" class="justwpforms-activity-settings-view-mode screen-options metabox-prefs view-mode">
			<legend><?php _e( 'View mode', 'justwpforms' ); ?></legend>
			<label for="justwpforms-list-view-mode">
				<input id="justwpforms-list-view-mode" type="radio" name="mode" value="list" <?php checked( 'list', $mode ); ?> />
				<?php _e( 'Compact view', 'justwpforms' ); ?>
			</label>
			<label for="justwpforms-excerpt-view-mode">
				<input id="justwpforms-excerpt-view-mode" type="radio" name="mode" value="excerpt" <?php checked( 'excerpt', $mode ); ?> />
				<?php _e( 'Extended view', 'justwpforms' ); ?>
			</label>
		</fieldset>
		<?php
		return ob_get_clean();
	}

	public function handle_delete_all_spam() {
		global $wpdb;

		if ( isset( $_REQUEST['delete_all_spam'] ) ) {

			check_admin_referer( 'bulk-destroy-spam', '_destroy_spam_nonce');
			$deleted = 0;
			$spam_status = 2;

			$spam_ids = $wpdb->get_col( $wpdb->prepare("
				SELECT p.ID FROM $wpdb->posts p
				JOIN $wpdb->postmeta pm ON (
					p.ID = pm.post_id
					AND pm.meta_key = '_justwpforms_read'
					AND pm.meta_value = %d ) WHERE p.post_type = 'justwpforms-message'
					", $spam_status ) );

			foreach ( $spam_ids as $spam_id ) { // Check the permissions on each.
				if ( ! current_user_can( 'justwpforms_manage_forms', $spam_id ) ) {
					continue;
				}

				if ( isset( $_REQUEST['delete_all_spam'] ) ) {
					if ( wp_delete_post( $spam_id ) ) {
						$deleted++;
					}
				}
			}

			if ( 0 < $deleted ) {
				justwpforms_submission_counter()->update_counters();

				$redirect_to = remove_query_arg( array( 'delete_all_spam' ), wp_get_referer() );
				$redirect_to = add_query_arg( 'deleted_spam', $deleted, $redirect_to );
				wp_safe_redirect( $redirect_to );
				exit;
			}

		}

		if ( isset( $_GET['deleted_spam'] ) ) {
			$deleted_spam = $_GET['deleted_spam'];
			if ( 0 < $deleted_spam ) {
				$messages[] = sprintf( _n( '%s spam message permanently deleted.', '%s spam messages permanently deleted.', $deleted_spam ), $deleted_spam );
				echo '<div id="moderated" class="updated notice is-dismissible"><p>' . implode( "<br/>\n", $messages ) . '</p></div>';
			}
		}

	}

	public function print_notices() {
		$messages = array();

		if ( isset( $_GET['activities_marked_spam'] ) && ! empty( $_GET['activities_marked_spam'] ) ) {
			$count = intval( $_GET['activities_marked_spam'] );
			$message = _n( 'submission marked as spam.', 'submissions marked as spam.', $count, 'justwpforms' );

			if ( isset( $_GET['activity_ids'] ) ) {
				$restore_link = add_query_arg( array(
					'_wpnonce' => wp_create_nonce( 'bulk-posts' ),
					'post_type' => justwpforms_get_message_controller()->post_type,
					'action' => 'mark_not_spam',
					'ids' => $_GET['activity_ids'],
				), admin_url( 'edit.php' ) );

				$message = $message . sprintf(
					" <a href=\"%s\">%s</a>",
					$restore_link,
					__( 'Undo', 'justwpforms' )
				);
			}

			$message = "{$count} {$message}";
			$messages[] = $message;
		} else if ( isset( $_GET['activities_marked_not_spam'] ) && ! empty( $_GET['activities_marked_not_spam'] ) ) {
			$count = intval( $_GET['activities_marked_not_spam'] );
			$message = _n( 'submission restored from spam.', 'submissions restored from spam.', $count, 'justwpforms' );

			$message = "{$count} {$message}";
			$messages[] = $message;
		} else if ( isset( $_GET['activities_marked_read'] ) && ! empty( $_GET['activities_marked_read'] ) ) {
			$count = intval( $_GET['activities_marked_read'] );
			$message = _n( 'submission marked as read.', 'submissions marked as read.', $count, 'justwpforms' );

			$message = "{$count} {$message}";
			$messages[] = $message;
		}

		if ( ! empty( $messages ) ) {
		?>
			<div id="message" class="updated notice is-dismissible"><p><?php echo join( '<br>', $messages ); ?></p></div>
			<script type="text/javascript" charset="utf-8">
			if ( window.history ) {
				var url = new URL( document.location );

				url.searchParams.delete( 'activities_marked_spam' );
				url.searchParams.delete( 'activities_marked_not_spam' );
				url.searchParams.delete( 'activity_ids' );

				window.history.replaceState( null, '', url.href );
			}
			</script>
		<?php
		}

		$_SERVER['REQUEST_URI'] = remove_query_arg(
			array( 'activities_marked_spam', 'activities_marked_not_spam', 'activities_marked_read' ),
			$_SERVER['REQUEST_URI']
		);
	}

	public function dashboard_data( $data ) {
		$controller = justwpforms_get_message_controller();
		$notices = array();

		$notices[$controller->action_mark_spam] = __( 'Submission marked as spam', 'justwpforms' );
		$notices[$controller->action_trash] = __( 'Submission moved to the Trash', 'justwpforms' );
		$notices['noActivity'] = __( 'No submissions found', 'justwpforms' );
		$notices['noActivityTrash'] = __( 'No submissions found in Trash', 'justwpforms' );
		$notices['undo'] = __( 'Undo', 'justwpforms' );

		$data['messageAdminNotices'] = $notices;
		return $data;
	}

	public function admin_title( $admin_title, $title ) {
		$screen = get_current_screen();

		if ( $this->post_type === $screen->post_type && 'edit' === $screen->base ) {
			$admin_title = justwpforms_get_message_controller()->get_admin_title();
		}

		return $admin_title;
	}

	/**
	 * Filter: output table views links above table.
	 *
	 * @hooked filter views_edit-justwpforms-message
	 *
	 * @param array     $views Currently configured views.
	 *
	 * @return void
	 */
	public function table_views( $default_views ) {
		$message_controller = justwpforms_get_message_controller();
		$post_type = $message_controller->post_type;
		$counters = justwpforms_submission_counter()->get_totals();
		$link_format = '<a href="%s" class="%s">%s <span class="count">(%s)</span></a>';

		$all_link = add_query_arg( array(
			'post_type' => $post_type,
		), 'edit.php' );
		$all_class = 'current';

		if ( isset( $_GET['post_status'] ) && 'all' !== $_GET['post_status'] ) {
			$all_class = '';
		}

		$unread_link = add_query_arg( array(
			'post_type' => $post_type,
			'activity_status' => 'unread'
		), 'edit.php' );
		$unread_class = '';

		$read_link = add_query_arg( array(
			'post_type' => $post_type,
			'activity_status' => 'read'
		), 'edit.php' );
		$read_class = '';

		$spam_link = add_query_arg( array(
			'post_type' => $post_type,
			'activity_status' => 'spam'
		), 'edit.php' );
		$spam_class = '';

		$trash_link = add_query_arg( array(
			'post_type' => $post_type,
			'post_status' => 'trash'
		), 'edit.php' );
		$trash_class = '';

		if ( isset( $_GET['activity_status'] ) ) {
			$all_class = '';

			switch ( $_GET['activity_status'] ) {
				case 'unread':
					$unread_class = 'current';
					break;
				case 'read':
					$read_class = 'current';
					break;
				case 'spam':
					$spam_class = 'current';
					break;
			}
		}

		if ( isset( $_GET['post_status'] ) && ( 'trash' === $_GET['post_status'] ) ) {
			$trash_class = 'current';
		}

		$count_total = isset( $counters['total'] ) ? $counters['total'] : 0;
		$views_all = sprintf(
			$link_format, $all_link, $all_class,
			__( 'All', 'justwpforms' ),
			$count_total
		);

		$count_unread = isset( $counters['unread'] ) ? $counters['unread'] : 0;
		$views_unread = sprintf(
			$link_format, $unread_link, $unread_class,
			__( 'Unread', 'justwpforms' ),
			$count_unread
		);

		$count_read = isset( $counters['read'] ) ? $counters['read'] : 0;
		$views_read = sprintf(
			$link_format, $read_link, $read_class,
			__( 'Read', 'justwpforms' ),
			$count_read
		);

		$count_spam = isset( $counters['spam'] ) ? $counters['spam'] : 0;
		$views_spam = sprintf(
			$link_format, $spam_link, $spam_class,
			__( 'Spam', 'justwpforms' ),
			$count_spam
		);

		$count_trash = isset( $counters['trash'] ) ? $counters['trash'] : 0;
		$views_trash = sprintf(
			$link_format, $trash_link, $trash_class,
			__( 'Trash', 'justwpforms' ),
			$count_trash
		);

		$views = array(
			'all' => $views_all,
			'unread' => $views_unread,
			'read' => $views_read,
			'spam' => $views_spam,
			'trash' => $views_trash,
		);

		return $views;
	}

	public function table_primary_column( $default, $screen_id ) {
		$default = 'submission';

		return $default;
	}

	public function get_column_parts( $parts ) {
		$parts = array_filter( $parts, function( $part ) {
			return apply_filters( 'justwpforms_message_part_visible', true, $part );
		} );
		$parts = array_values( $parts );

		return $parts;
	}

	/**
	 * Filter: filter the column headers for the
	 * All Messages admin screen table.
	 *
	 * @since 1.0
	 *
	 * @hooked filter manage_justwpforms-message_posts_columns
	 *
	 * @param array $columns  The original table headers.
	 *
	 * @return array          The filtered table headers.
	 */
	public function column_headers( $columns ) {
		$cb_column = $columns['cb'];
		$columns = array( 'cb' => $cb_column );

		$forms = justwpforms_get_form_controller()->get();
		$part_lists = wp_list_pluck( $forms, 'parts' );
		$part_lists = array_map( array( $this, 'get_column_parts' ), $part_lists );
		$part_counts = array_map( 'count', $part_lists );

		$columns['submission'] = __( 'Submission', 'justwpforms' );
		$columns['form'] = __( 'Submitted to', 'justwpforms' );
		$columns['datetime'] = __( 'Submitted on', 'justwpforms' );

		if ( ! $this->current_form ) {
			return $columns;
		}

		$parts = $this->get_column_parts( $this->current_form['parts'] );

		/**
		 * Filter the column headers of responses admin table.
		 *
		 * @since 1.4.5
		 *
		 * @param array  $columns Current column headers.
		 *
		 * @return array
		 */
		$columns = apply_filters( 'justwpforms_manage_response_column_headers', $columns );

		return $columns;
	}

	public function sortable_columns( $columns ) {
		$columns['form'] = 'form';
		$columns['datetime'] = 'datetime';

		return $columns;
	}

	/**
	 * Filter: output the columns content for the
	 * All Messages admin screen table.
	 *
	 * @since 1.0
	 *
	 * @hooked filter manage_justwpforms-message_posts_custom_column
	 *
	 * @param array      $column   The current column header.
	 * @param int|string $id       The current message post object ID.
	 *
	 * @return void
	 */
	public function column_content( $column, $id ) {
		$message = justwpforms_get_message_controller()->get( $id );
		$form_controller = justwpforms_get_form_controller();
		$form = $form_controller->get( $message['form_id'] );

		if ( ! $form ) {
			echo '';

			return;
		}

		$email_part = $form_controller->get_first_part_by_type( $form, 'email' );
		$email_value = '';

		if ( $email_part ) {
			$email_value = justwpforms_get_message_part_value( $message['parts'][$email_part['id']], $email_part );
		}

		switch( $column ) {
			case 'form':
				$form_html = '';

				if ( $form ) {
					$messages_url = admin_url( "/edit.php?post_type=justwpforms-message&form_id={$form['ID']}" );
					$form_html = '<div class="response-links">';

					if ( 'publish' === $form['post_status'] && current_user_can( 'justwpforms_manage_forms' ) ) {
						$form_html .= sprintf(
							'<a href="%s" class="%s">%s</a>',
							justwpforms_get_form_edit_link( $form['ID'] ),
							'comments-edit-item-link',
							justwpforms_get_form_title( $form )
						);
					} else {
						$form_html .= "<b>" . justwpforms_get_form_title( $form ) . "</b>";
					}

					$referral_link = justwpforms_get_meta( $id, 'client_referer', true );

					if ( $referral_link ) {
						$form_html .= sprintf(
							'<a href="%s" class="%s">%s</a>',
							$referral_link,
							'comments-view-item-link',
							__( 'View Referral', 'justwpforms' )
						);
					}

					$form_html .= justwpforms_read_unread_badge( $form['ID'] );
					$form_html .= '</div>';
				}

				echo $form_html;
				break;

			case 'unique_id':
				echo $message['tracking_id'];
				break;

			case 'datetime':
				$submitted = sprintf(
					__( '%1$s at %2$s' ),
					get_post_time( __( 'Y/m/d' ), false, $id ),
					get_post_time( __( 'g:i a' ), false, $id )
				);

				echo $submitted;
				break;

			case 'submission':
				$avatar = get_avatar( $email_value, 32 );

				$content = sprintf(
					'<div class="author-info">%s —</div>',
					$avatar
				);

				if ( ! empty( $email_value ) ) {
					$content = sprintf(
						'<div class="author-info">%s<a href="mailto:%s">%s</a></div>',
						$avatar,
						$email_value,
						$email_value
					);
				}

				$parts = $this->get_column_parts( $form['parts'] );
				$parts = array_slice( $parts, 0, $this->parts_per_submission );

				$content .= '<div class="submission-data">';

				foreach ( $parts as $part ) {
					$part_id = $part['id'];
					$label = justwpforms_get_part_label( $part );
					$value = justwpforms_get_message_part_value( $message['parts'][$part_id], $part, 'admin-column' );

					$content .= "{$label}: {$value}" . "<br>";
				}

				$content .= '</div>';

				echo $content;
				break;

			default:
				if ( $form ) {
					$column_index = preg_match( '/column_(\d+)?/', $column, $matches );

					if ( $column_index ) {
						$column_index = intval( $matches[1] );
					}

					$parts = $this->get_column_parts( $form['parts'] );

					if ( count( $parts ) > $column_index ) {
						$part = $parts[$column_index];
						$part_id = $part['id'];

						if ( isset( $message['parts'][$part_id] ) ) {
							echo justwpforms_get_message_part_value( $message['parts'][$part_id], $part, 'admin-column' );
						}
					}
				}
				break;
		}
	}

	/**
	 * Filter: silence the standard date column content.
	 *
	 * @since 1.0
	 *
	 * @hooked filter post_date_column_status
	 *
	 * @return void
	 */
	public function post_date_column_status() {
		return '';
	}

	public function pre_get_posts( $query ) {
		if ( ! is_admin() ) {
			return;
		}

		if ( ! $query->is_main_query() ) {
			return;
		}

		$post_type = $query->get( 'post_type' );
		$controller = justwpforms_get_message_controller();

		if ( $post_type !== $controller->post_type ) {
			return;
		}

		$query_vars = &$query->query_vars;
		$meta_query = array();

		// Only responses bound to the selected form, if one's selected.
		if ( $this->current_form ) {
			$form_clause = array();
			$form_clause['key'] = '_justwpforms_form_id';
			$form_clause['value'] = $this->current_form['ID'];
			$form_clause['compare'] = '=';
			$meta_query['form_clause'] = $form_clause;
		}

		// Only responses that aren't spam,
		// if not explicitly requested.
		$read_clause = array(
			'key' => '_justwpforms_read',
			'compare' => '!=',
			'value' => 2,
			'type' => 'NUMERIC',
		);

		if ( isset( $_GET[$this->filter_status] ) ) {
			switch( $_GET[$this->filter_status] ) {
				case 'unread':
					$read_clause['compare'] = '=';
					$read_clause['value'] = '';
					$read_clause['type'] = 'CHAR';
					break;
				case 'read':
					$read_clause['compare'] = '=';
					$read_clause['value'] = 1;
					$read_clause['type'] = 'NUMERIC';
					break;
				case 'spam':
					$read_clause['compare'] = '=';
					$read_clause['value'] = 2;
					$read_clause['type'] = 'NUMERIC';
					break;
			}
		}

		if ( ! isset( $_GET['post_status'] ) || ( 'trash' !== $_GET['post_status'] ) ) {
			$meta_query['read_clause'] = $read_clause;
		}

		if ( ! isset( $_GET['post_status'] ) || 'all' === $_GET['post_status'] ) {
			$query_vars['post_status'] = array( 'publish', 'draft' );
		}

		$query_vars['meta_query'] = $meta_query;

		// Handle search query
		if ( $query->is_search ) {
			$term = $query_vars['s'];

			if ( '' !== $term ) {
				$metas = $controller->search_metas( $term );

				if ( count( $metas ) > 0 ) {
					$post_ids = wp_list_pluck( $metas, 'post_id' );
					$query_vars['post__in'] = $post_ids;
					$query_vars['s'] = '';

					// Pass the term to template anyway.
					add_filter( 'get_search_query', function() use ( $term ) {
						return $term;
					} );
				}
			}
		}

		// Handle sorting
		$orderby = $query->get( 'orderby');

		if ( 'form' === $orderby ) {
			$query->set( 'meta_key', '_justwpforms_form_id' );
			$query->set( 'orderby', 'meta_value_num' );
		}
	}

	/**
	 * Filter: add custom HTML classes to message entries
	 * in the All Form admin screen table to represent
	 * read/unread status.
	 *
	 * @since 1.0
	 *
	 * @hooked filter post_date_column_status
	 *
	 * @param array      $class   Array of post classes.
	 * @param array      $classes Array of additional post classes.
	 * @param int|string $id      The message post object ID.
	 *
	 * @return array
	 */
	public function post_class( $class, $classes, $id ) {
		$message = justwpforms_get_message_controller()->get( $id );

		if ( ! $message['read'] ) {
			$classes[] = 'justwpforms-message-unread';
		}

		return $classes;
	}

	/**
	 * Action: output the Form filter dropdown
	 * above the All Messages admin screen table.
	 *
	 * @since 1.0
	 *
	 * @hooked action restrict_manage_posts
	 *
	 * @return void
	 */
	public function restrict_manage_posts( $post_type ) {
		if ( justwpforms_get_message_controller()->post_type === $post_type ) {
			// Remove any previous output.
			ob_clean();

			global $wp_list_table;

			if ( $wp_list_table->has_items() ) {
				$forms = justwpforms_get_form_controller()->get();
				$form_id = isset( $_GET['form_id'] ) ? intval( $_GET['form_id'] ) : '';

				// Preserve submission status parameter
				if ( isset( $_GET[$this->filter_status] ) ) : ?>
					<input type="hidden" name="<?php echo $this->filter_status; ?>" value="<?php echo $_GET[$this->filter_status]; ?>" />
				<?php endif; ?>

				<select name="form_id" id="">
					<option value=""><?php _e( 'All forms', 'justwpforms' ); ?></option>
					<?php foreach ( $forms as $form ) : ?>
						<option value="<?php echo esc_attr( $form['ID'] ); ?>" <?php selected( $form_id, $form['ID'] ); ?>><?php echo justwpforms_get_form_title( $form ); ?></option>
					<?php endforeach; ?>
				</select>

				<?php
			}

			/**
			* Output additional content in the
			* responses admin table filters area.
			*
			* @since 1.4.5
			*
			* @param string $post_type Response post type.
			*
			* @return void
			*/
			do_action( 'justwpforms_restrict_manage_responses', $post_type );
		}
	}

	public function manage_posts_extra_tablenav( ) {
		global $wp_list_table;
		$status_filter = isset( $_GET['activity_status'] ) ? $_GET['activity_status'] : '';
		if ( ( 'spam' === $status_filter ) && $wp_list_table->has_items() && current_user_can( 'justwpforms_manage_forms' ) ) {
			wp_nonce_field( 'bulk-destroy-spam', '_destroy_spam_nonce' );
			submit_button( __( 'Empty Spam', 'justwpforms' ), 'apply', 'delete_all_spam', false );
		}
	}

	/**
	 * Filter: add custom bulk actions for the
	 * All Messages admin screen table.
	 *
	 * @since 1.0
	 *
	 * @hooked filter bulk_actions-edit-justwpforms-message
	 *
	 * @param array $actions Original bulk actions.
	 *
	 * @return array
	 */
	public function bulk_actions( $actions ) {
		$mark_unread = array( 'mark_unread' => __( 'Mark as unread', 'justwpforms' ) );
		$mark_read = array( 'mark_read' => __( 'Mark as read', 'justwpforms' ) );
		$mark_spam = array( 'mark_spam' => __( 'Mark as spam', 'justwpforms' ) );
		$mark_not_spam = array( 'mark_not_spam' => __( 'Not spam', 'justwpforms' ) );
		$trash = array( 'trash' => __( 'Move to Trash', 'justwpforms' ) );
		$untrash = array( 'untrash' => __( 'Restore', 'justwpforms' ) );
		$delete = array( 'delete' => __( 'Delete permanently', 'justwpforms' ) );

		if ( ! isset( $_GET[$this->filter_status] ) && ! isset( $_GET['post_status'] ) ) {
			$actions = array_merge(
				$mark_unread,
				$mark_read,
				$mark_spam,
				$trash
			);
		} elseif ( isset( $_GET['post_status'] ) && 'trash' === $_GET['post_status'] ) {
			$actions = array_merge(
				$mark_spam,
				$untrash,
				$delete
			);
		} elseif ( isset( $_GET[$this->filter_status] ) && 'unread' === $_GET[$this->filter_status] ) {
			$actions = array_merge(
				$mark_read,
				$mark_spam,
				$trash
			);
		} elseif ( isset( $_GET[$this->filter_status] ) && 'read' === $_GET[$this->filter_status] ) {
			$actions = array_merge(
				$mark_unread,
				$mark_spam,
				$trash
			);
		} elseif ( isset( $_GET[$this->filter_status] ) && 'spam' === $_GET[$this->filter_status] ) {
			$actions = array_merge(
				$mark_not_spam,
				$delete
			);
		} else {
			$actions = array_merge(
				$mark_unread,
				$mark_read,
				$mark_spam,
				$trash
			);
		}

		return $actions;
	}

	/**
	 * Filter: handle messages custom bulk actions.
	 *
	 * @since 1.0
	 *
	 * @hooked filter handle_bulk_actions-edit-justwpforms-message
	 *
	 * @param string $redirect_to The url to redirect to
	 *                            after actions have been handled.
	 * @param string $action      The current bulk action.
	 * @param array  $ids         The array of message post object IDs.
	 *
	 * @return string
	 */
	public function handle_bulk_actions( $redirect_to, $action, $ids ) {
		$keys_to_remove = array( 'activities_marked_spam', 'activities_marked_not_spam' );
		$redirect_to = remove_query_arg( $keys_to_remove, $redirect_to );

		switch( $action ) {
			case 'mark_read':
				foreach ( $ids as $id ) {
					justwpforms_update_meta( $id, 'read', 1 );
				}

				$redirect_to = add_query_arg( array(
					'activities_marked_read' =>  count( $ids ),
				), $redirect_to );

				break;
			case 'mark_unread':
				foreach ( $ids as $id ) {
					justwpforms_update_meta( $id, 'read', '' );
				}

				break;
			case 'mark_spam':
				foreach ( $ids as $id ) {
					$current_status = justwpforms_get_meta( $id, 'read', true );

					if ( 2 !== $current_status ) {
						justwpforms_update_meta( $id, 'previously_read', $current_status );
					}

					justwpforms_update_meta( $id, 'read', 2 );
					wp_untrash_post( $id );
				}

				$redirect_to = add_query_arg( array(
					'activities_marked_spam' =>  count( $ids ),
					'activity_ids' => join( ',', $ids )
				), $redirect_to );

				break;
			case 'mark_not_spam':
				foreach ( $ids as $id ) {
					$status = '';

					if( justwpforms_meta_exists( $id, 'previously_read' ) ) {
						$status = justwpforms_get_meta( $id, 'previously_read', true );
					}

					justwpforms_update_meta( $id, 'read', $status );
				}

				$redirect_to = add_query_arg( array(
					'activities_marked_not_spam' => count( $ids ),
				), $redirect_to );

				break;
		}

		justwpforms_submission_counter()->update_counters();

		return $redirect_to;
	}

	/**
	 * Filter: filter the row actions contents for the
	 * All Messages admin screen table.
	 *
	 * @since 1.0
	 *
	 * @hooked filter post_row_actions
	 *
	 * @param array   $actions The original array of action contents.
	 * @param WP_Post $post    The current post object.
	 *
	 * @return array           The filtered array of action contents.
	 */
	public function row_actions( $actions, $post ) {
		$controller = justwpforms_get_message_controller();
		$post_type = $controller->post_type;

		if ( $post->post_type !== $post_type ) {
			return $actions;
		}

		$url_edit = get_edit_post_link( $post->ID );
		$url = admin_url( 'admin-ajax.php' );
		$url_mark_as_read = add_query_arg( 'status', 'read', $url );
		$url_mark_as_unread = add_query_arg( 'status', 'unread', $url );

		// Edit
		$link_edit = sprintf(
			'<a href="%1$s">%2$s</a>',
			get_edit_post_link( $post->ID ),
			__( 'View', 'justwpforms' )
		);

		// Mark as spam/not spam
		$url_mark_as_spam = wp_nonce_url( add_query_arg( array(
			'post' => $post->ID,
			'action' => $controller->action_mark_spam,
		), admin_url( 'admin-ajax.php' ) ),  $controller->action_mark_spam . '-' . $post->ID );

		$url_mark_as_not_spam = wp_nonce_url( add_query_arg( array(
			'post' => $post->ID,
			'action' => $controller->action_mark_not_spam,
		), admin_url( 'admin-ajax.php' ) ),  $controller->action_mark_not_spam . '-' . $post->ID );

		$link_mark_as_spam = sprintf(
			'<a href="#" data-href="%1$s" data-undo="%2$s">%3$s</a>',
			$url_mark_as_spam,
			$url_mark_as_not_spam,
			__( 'Spam', 'justwpforms' )
		);

		$link_mark_as_not_spam = sprintf(
			'<a href="#" data-href="%1$s">%2$s</a>',
			$url_mark_as_not_spam,
			__( 'Not Spam', 'justwpforms' )
		);

		// Mark as read/unread
		$url_mark_as_read = wp_nonce_url( add_query_arg( array(
			'post' => $post->ID,
			'action' => $controller->action_mark_read,
		), admin_url( 'admin-ajax.php' ) ),  $controller->action_mark_read . '-' . $post->ID );

		$link_mark_as_read = sprintf(
			'<a href="#" data-href="%1$s">%2$s</a>',
			$url_mark_as_read,
			__( 'Mark as Read', 'justwpforms' )
		);

		$url_mark_as_unread = wp_nonce_url( add_query_arg( array(
			'post' => $post->ID,
			'action' => $controller->action_mark_unread,
		), admin_url( 'admin-ajax.php' ) ),  $controller->action_mark_unread . '-' . $post->ID );

		$link_mark_as_unread = sprintf(
			'<a href="#" data-href="%1$s">%2$s</a>',
			$url_mark_as_unread,
			__( 'Mark as Unread', 'justwpforms' )
		);

		// Trash/restore/delete
		$url_trash = wp_nonce_url( add_query_arg( array(
			'post' => $post->ID,
			'action' => $controller->action_trash,
		), admin_url( 'admin-ajax.php' ) ),  $controller->action_trash . '-' . $post->ID );

		$url_restore = wp_nonce_url( add_query_arg( array(
			'post' => $post->ID,
			'action' => $controller->action_restore,
		), admin_url( 'admin-ajax.php' ) ),  $controller->action_restore . '-' . $post->ID );

		$url_delete = wp_nonce_url( add_query_arg( array(
			'post' => $post->ID,
			'action' => $controller->action_delete,
		), admin_url( 'admin-ajax.php' ) ),  $controller->action_delete . '-' . $post->ID );

		$link_trash = sprintf(
			'<a href="#" data-href="%1$s" data-undo="%2$s">%3$s</a>',
			$url_trash,
			$url_restore,
			__( 'Trash', 'justwpforms' )
		);

		$link_restore = sprintf(
			'<a href="#" data-href="%1$s">%2$s</a>',
			$url_restore,
			__( 'Restore', 'justwpforms' )
		);

		$link_delete = sprintf(
			'<a href="#" data-href="%1$s">%2$s</a>',
			$url_delete,
			__( 'Delete Permanently', 'justwpforms' )
		);

		$actions = array();
		$response_status = justwpforms_get_meta( $post->ID, 'read', true );
		$status_filter = isset( $_GET['activity_status'] ) ? $_GET['activity_status'] : '';

		if ( 'trash' !== $post->post_status ) {
			if ( 1 == $response_status ) {
				if ( '' === $status_filter ) {
					$actions['justwpforms-mark_read'] = $link_mark_as_read;
				}

				$actions['justwpforms-mark_unread'] = $link_mark_as_unread;
				$actions['edit'] = $link_edit;
				$actions['justwpforms-mark_spam'] = $link_mark_as_spam;
				$actions['justwpforms-trash'] = $link_trash;
			} else if ( 2 == $response_status ) {
				$actions['justwpforms-mark_not_spam'] = $link_mark_as_not_spam;
				$actions['justwpforms-delete'] = $link_delete;
			} else if ( '' === $response_status ) {
				$actions['justwpforms-mark_read'] = $link_mark_as_read;

				if ( '' === $status_filter ) {
					$actions['justwpforms-mark_unread'] = $link_mark_as_unread;
				}

				$actions['edit'] = $link_edit;
				$actions['justwpforms-mark_spam'] = $link_mark_as_spam;
				$actions['justwpforms-trash'] = $link_trash;
			}
		} else {
			$actions['justwpforms-mark_spam'] = $link_mark_as_spam;
			$actions['justwpforms-restore'] = $link_restore;
			$actions['justwpforms-delete'] = $link_delete;
		}

		// Hide the "view" link if this activity is orphan.
		$message = justwpforms_get_message_controller()->get( $post->ID );
		$form_controller = justwpforms_get_form_controller();
		$form = $form_controller->get( $message['form_id'] );

		if ( ! $form ) {
			unset( $actions['edit'] );
		}

		return $actions;
	}

	/**
	 * Action: output custom markup for the
	 * Message Edit admin screen.
	 *
	 * @since 1.0
	 *
	 * @hooked action edit_form_after_title
	 *
	 * @param WP_Post $post The message post object.
	 *
	 * @return void
	 */
	public function edit_screen( $post ) {
		global $message, $form;

		$message = justwpforms_get_message_controller()->get( $post->ID );
		$form = justwpforms_get_form_controller()->get( $message['form_id'] );
		$this->setup_message_navigation( $post->ID, $form['ID'] );

		require_once( justwpforms_get_include_folder() . '/templates/admin-message-edit.php' );
	}

	private function export_csv( $ids = array() ) {
		global $wpdb;

		$this->parse_request();

		if ( ! $this->current_form ) {
			return;
		}

		require_once( justwpforms_get_include_folder() . '/classes/class-exporter-csv.php' );

		$exporter = new justwpforms_Exporter_CSV( $this->current_form['ID'], 'messages.csv' );
		$exporter->export( $ids );
	}

	public function print_activity_title_with_form_link() {
		$post_type = ( isset( $_GET['post_type'] ) ) ? $_GET['post_type'] : '';

		if ( $this->post_type !== $post_type ) {
			return;
		}

		if ( ! isset( $_GET['form_id'] ) || empty( $_GET['form_id'] ) ) {
			return;
		}

		$form = justwpforms_get_form_controller()->get( intval( $_GET['form_id'] ) );

		if ( ! $form ) {
			return;
		}

		$title = sprintf(
			'<h1 class="wp-heading-inline justwpforms-activity-title">%s "<a href="%s">%s</a>"</h1>',
			__( 'Submissions to', 'justwpforms' ),
			justwpforms_get_form_edit_link( $form['ID'] ),
			justwpforms_get_form_title( $form )
		);

		echo $title;
	}
}

/**
 * Initialize the justwpforms_Message_Admin class immediately.
 */
justwpforms_Message_Admin::instance();
