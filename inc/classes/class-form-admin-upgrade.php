<?php

class justwpforms_Form_Admin_Upgrade {

	private static $instance;

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		self::$instance->hook();

		return self::$instance;
	}

	public function hook() {
		$post_type = justwpforms_get_form_controller()->post_type;

		add_filter( "views_edit-{$post_type}", array( $this, 'order_views' ) );
		add_filter( 'justwpforms_manage_form_column_headers', array( $this, 'column_headers' ) );
		add_action( "manage_{$post_type}_posts_custom_column", array( $this, 'column_content' ), 10, 2 );
		add_filter( "bulk_actions-edit-{$post_type}", array( $this, 'bulk_actions' ) );
		add_filter( "handle_bulk_actions-edit-{$post_type}", array( $this, 'handle_bulk_actions' ), 10, 3 );
		add_filter( 'post_row_actions', array( $this, 'row_actions' ), 10, 2 );
		add_filter( 'pre_trash_post', array( $this, 'pre_trash_delete_post' ), 20, 2 );
		add_filter( 'pre_delete_post', array( $this, 'pre_trash_delete_post' ), 20, 2 );
		add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ) );
		add_action( 'admin_notices', array( $this, 'print_notices' ) );
		add_filter( "manage_edit-{$post_type}_sortable_columns", array( $this, 'sortable_columns' ) );
	}

	public function column_headers( $headers ) {
		$messages_header = sprintf(
			'<span class="vers comment-grey-bubble" title="%s"><span class="screen-reader-text">%s</span></span>',
			__( 'Submissions', 'justwpforms' ),
			__( 'Submissions', 'justwpforms' )
		);
		$messages_header = array( 'messages' => $messages_header );
		$before = array_slice( $headers, 0, -1, true );
		$after = array_slice( $headers, -1, 1, true );
		$headers = $before + $messages_header + $after;

		return $headers;
	}

	public function column_content( $column, $id ) {
		switch ( $column ) {
			case 'messages':
				echo justwpforms_read_unread_badge( $id );
				break;
		}
	}

	public function row_actions( $actions, $post ) {
		$post_type = justwpforms_get_form_controller()->post_type;

		if ( $post->post_type === $post_type ) {
			$link_template = '<a href="%s">%s</a>';
			$links = array();

			switch ( $post->post_status ) {
				case 'archive':
					$links['restore'] = sprintf(
						$link_template,
						add_query_arg( 'status', 'publish', justwpforms_get_form_status_link( array( $post->ID ) ) ),
						__( 'Restore', 'justwpforms' )
					);
					$links['delete'] = sprintf(
						$link_template,
						get_delete_post_link( $post->ID, '', true ),
						__( 'Delete Permanently', 'justwpforms' )
					);

					break;
				case 'publish':
					$links['edit'] = $actions['edit'];
					$links['duplicate'] = $actions['duplicate'];
					$links['archive'] = sprintf(
						$link_template,
						add_query_arg( array(
							'status' => 'archive',
							'archived' => 1
						), justwpforms_get_form_status_link( array( $post->ID ) ) ),
						__( 'Archive', 'justwpforms' )
					);
					$links['trash'] = $actions['trash'];

					break;
				default:
					break;
			}

			if ( ! empty( $links ) ) {
				$actions = $links;
			}

			$activity_count = justwpforms_submission_counter()->get_total_submissions( $post->ID );

			if ( 0 < $activity_count ) {
				if ( isset( $actions['trash'] ) ) {
					unset( $actions['trash'] );
				}

				if ( isset( $actions['delete'] ) ) {
					unset( $actions['delete'] );
				}
			}
		}

		return $actions;
	}

	public function pre_get_posts( $query ) {
		if ( ! is_admin() ) {
			return;
		}

		$post_type = $query->get( 'post_type' );
		$controller = justwpforms_get_form_controller();

		if ( $post_type !== $controller->post_type ) {
			return;
		}

		$query_vars = &$query->query_vars;

		if ( empty( $query_vars['post_status'] ) ) {
			$query_vars['post_status'] = 'publish';
		}

		$orderby = $query->get( 'orderby');

		if ( 'messages' === $orderby ) {
			$query->set( 'meta_key', '_justwpforms_count_submissions_total' );
			$query->set( 'orderby', 'meta_value_num' );
		}
	}

	public function bulk_actions( $actions ) {
		$new_actions = array();

		$new_actions['archive'] = __( 'Move to Archive', 'justwpforms' );
		$new_actions['justwpforms-trash'] = __( 'Move to Trash', 'justwpforms' );

		if ( isset( $_GET['post_status'] ) && 'all' !== $_GET['post_status'] ) {
			$new_actions = array();

			if ( 'archive' === $_GET['post_status'] ) {
				unset( $actions['trash'] );
				unset( $actions['archive'] );

				$actions['restore_archive'] = __( 'Restore', 'justwpforms' );
				$actions['delete'] = __( 'Delete Permanently', 'justwpforms' );
			}

			if ( 'trash' === $_GET['post_status'] ) {
				unset( $actions['archive'] );
				unset( $actions['restore_archive'] );
			}
		}

		if ( ! empty( $new_actions ) ) {
			$actions = $new_actions;
		}

		return $actions;
	}

	public function handle_bulk_actions( $redirect_to, $action, $ids ) {
		$keys_to_remove = array( 'restored', 'forms_trashed', 'not_trashed', 'archived' );
		$redirect_to = remove_query_arg( $keys_to_remove, $redirect_to );
		$form_controller = justwpforms_get_form_controller();

		switch ( $action ) {
			case 'untrashed':
				$redirect_to = add_query_arg( array(
					'untrashed' =>  count( $ids )
				), $redirect_to );
				break;
			case 'justwpforms-trash':
				$trashed_ids = array();
				$not_trashed_ids = array();

				foreach ( $ids as $id ) {
					$form = $form_controller->get( $id );

					if ( ! $form ) {
						continue;
					}

					if ( wp_trash_post( $id ) ) {
						$trashed_ids[] = $id;
					} else {
						$not_trashed_ids[] = $id;
					}
				}

				$redirect_to = add_query_arg( array(
					'forms_trashed' =>  count( $trashed_ids ),
					'form_ids' => join( ',', $trashed_ids )
				), $redirect_to );

				if( ! empty( $not_trashed_ids ) ) {
					$redirect_to = add_query_arg( array(
						'not_trashed' =>  count( $not_trashed_ids )
					), $redirect_to );
				}
				break;
			case 'archive':
				foreach ( $ids as $id ) {
					$form = $form_controller->get( $id );

					if ( ! $form ) {
						continue;
					}

					wp_update_post( array(
						'ID' => $id,
						'post_status' => 'archive'
					) );
				}

				$redirect_to = add_query_arg( array(
					'archived' => count( $ids ),
					'form_ids' => join( ',', $ids )
				), $redirect_to );

				break;
			case 'restore_archive':
				foreach ( $ids as $id ) {
					$form = $form_controller->get( $id );

					if ( ! $form ) {
						continue;
					}

					wp_update_post( array(
						'ID' => $id,
						'post_status' => 'publish'
					) );
				}

				$redirect_to = add_query_arg( array(
					'restored' => count( $ids ),
					'form_ids' => join( ',', $ids ),
				), $redirect_to );

				break;
		}

		return $redirect_to;
	}

	public function pre_trash_delete_post( $trash, $post ){
		$activity_count = justwpforms_submission_counter()->get_total_submissions( $post->ID );

		if ( 0 < $activity_count ) {
			return false;
		}

		return $trash;
	}

	public function print_notices() {
		$messages = array();

		if ( isset( $_GET['archived'] ) && ! empty( $_GET['archived'] ) ) {
			$count = intval( $_GET['archived'] );
			$text = _n( 'form moved to the Archive.', 'forms moved to the Archive.', $count, 'justwpforms' );

			if ( isset( $_GET['form_ids'] ) ) {
				$ids = explode( ',', $_GET['form_ids'] );

				$restore_link = add_query_arg( array(
					'status' => 'publish',
					'undo' => 1,
					'restored' => 1
				), justwpforms_get_form_status_link( $ids ) );

				$text = $text . sprintf(
					" <a href=\"%s\">%s</a>",
					$restore_link,
					__( 'Undo', 'justwpforms' )
				);
			}

			$message = "{$count} {$text}";
			$messages[] = $message;
		}

		if ( isset( $_GET['restored'] ) && ! empty( $_GET['restored'] ) ) {
			$count = intval( $_GET['restored'] );
			$message = _n( 'form restored from the Archive.', 'forms restored from the Archive.', $count, 'justwpforms' );

			$message = "{$count} {$message}";
			$messages[] = $message;
		}

		if ( isset( $_GET['forms_trashed'] ) && ! empty( $_GET['forms_trashed'] ) ) {
			$count = intval( $_GET['forms_trashed'] );
			$message = _n( 'form moved to the Trash.', 'forms moved to the Trash.', $count, 'justwpforms' );

			if ( isset( $_GET['form_ids'] ) ) {
				$ids = explode( ',', $_GET['form_ids'] );

				$restore_link = add_query_arg( array(
					'status' => 'publish',
					'undo' => 1,
					'untrashed' => 1
				), justwpforms_get_form_status_link( $ids ) );

				$message = $message . sprintf(
					" <a href=\"%s\">%s</a>",
					$restore_link,
					__( 'Undo', 'justwpforms' )
				);
			}
			$message = "{$count} {$message}";
			$messages[] = $message;
		}

		if ( isset( $_GET['not_trashed'] ) && ! empty( $_GET['not_trashed'] ) ) {
			$count = intval( $_GET['not_trashed'] );
			$message = _n(
				"form couldn't be moved to the Trash because it has replies.",
				"forms couldn't be moved to the Trash because they have replies.",
				$count, 'justwpforms'
			);

			$message = "{$count} {$message}";
			$messages[] = $message;
		}

		if ( ! empty( $messages ) ) {
		?>
			<div id="message" class="updated notice is-dismissible"><p><?php echo join( '<br>', $messages ); ?></p></div>
			<script type="text/javascript" charset="utf-8">
			if ( window.history ) {
				var url = new URL( document.location );

				url.searchParams.delete( 'forms_trashed' );
				url.searchParams.delete( 'form_ids' );

				window.history.replaceState( null, '', url.href );
			}
			</script>
		<?php
		}
	}

	public function order_views( $views ) {
		/**
		 * Push Trash view always to the end.
		 */
		if ( ! isset( $views['trash'] ) ) {
			return $views;
		}

		$trash_view = $views['trash'];
		unset( $views['trash'] );

		$views['trash'] = $trash_view;

		return $views;
	}

	public function sortable_columns( $columns ) {
		$columns['messages'] = array( 'messages', true );

		return $columns;
	}
}

justwpforms_Form_Admin_Upgrade::instance();
