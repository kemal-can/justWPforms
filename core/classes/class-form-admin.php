<?php

class justwpforms_Form_Admin {

	/**
	 * The singleton instance.
	 *
	 * @since 1.0
	 *
	 * @var justwpforms_Form_Admin
	 */
	private static $instance;

	private $added_to_links = array();

	/**
	 * The singleton constructor.
	 *
	 * @since 1.0
	 *
	 * @return justwpforms_Form_Admin
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
		$post_type = justwpforms_get_form_controller()->post_type;

		add_action( 'admin_head', array( $this, 'admin_head' ) );
		add_filter( 'admin_url', array( $this, 'admin_url' ), 10, 2 );
		add_filter( "views_edit-{$post_type}", array( $this, 'table_view_links' ) );
		add_filter( 'get_edit_post_link', array( $this, 'get_edit_post_link' ), 10, 3 );
		add_filter( 'post_updated_messages', array( $this, 'post_updated_messages' ) );
		add_filter( "bulk_actions-edit-{$post_type}", array( $this, 'bulk_actions' ) );
		add_filter( 'bulk_post_updated_messages', array( $this, 'bulk_post_updated_messages' ), 10, 2 );
		add_action( 'load-edit.php', array( $this, 'define_screen_settings' ) );
		add_filter( 'pre_months_dropdown_query', array( $this, 'pre_months_dropdown_query' ), 10, 2 );
		add_filter( "manage_{$post_type}_posts_columns", array( $this, 'column_headers' ), PHP_INT_MAX );
		add_filter( "manage_edit-{$post_type}_sortable_columns", array( $this, 'sortable_columns' ) );
		add_action( "manage_{$post_type}_posts_custom_column", array( $this, 'column_content' ), 10, 2 );
		add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ) );
		add_filter( 'post_row_actions', array( $this, 'row_actions' ), 10, 2 );
		add_action( 'load-edit.php', array( $this, 'duplicate_form_redirect' ) );
		add_filter( 'admin_footer_text', 'justwpforms_admin_footer' );
		add_action( 'load-edit.php', array( $this, 'change_bulk_draft_post_status' ) );
		add_action( 'transition_post_status', array( $this, 'change_draft_post_status' ), 10, 3 );
		add_action( 'load-edit.php', array( $this, 'get_added_to_links' ) );

	}

	/**
	 * Action: output custom styles for the All Forms screen.
	 *
	 * @since 1.0
	 *
	 * @hooked action admin_head
	 *
	 * @return void
	 */
	public function admin_head() {
		global $pagenow;
		$post_type = justwpforms_get_form_controller()->post_type;

		if ( 'edit.php' === $pagenow && $post_type === get_post_type() ) : ?>
		<style>
		.alignleft.actions { height: 32px; }
		fieldset.view-mode { display: block; }
		</style>
		<?php endif;
	}

	/**
	 * Filter: filter the Add New link url
	 *
	 * @since 1.5
	 *
	 * @hooked filter admin_url
	 *
	 * @param string $url  The current url
	 * @param string $path The current path
	 *
	 * @return array
	 */
	public function admin_url( $url, $path ) {
		$post_type = justwpforms_get_form_controller()->post_type;
		$new_form_url = 'post-new.php?post_type=' . $post_type;

		if ( $new_form_url === $path ) {
			$url = justwpforms_get_form_edit_link( 0 );
		}

		return $url;
	}

	/**
	 * Filter: filter the row actions links
	 * below entries in the All Forms admin table.
	 *
	 * @since 1.0
	 *
	 * @hooked filter views_edit-justwpform
	 *
	 * @param array $views The original array of action links.
	 *
	 * @return array
	 */
	public function table_view_links( $views ) {
		unset( $views['publish'] );

		return $views;
	}

	/**
	 * Filter: return a form post object edit url.
	 *
	 * @since 1.0
	 *
	 * @hooked filter get_edit_post_link
	 *
	 * @param string     $link    The original url.
	 * @param int|string $post_id The ID of the form post object.
	 * @param string     $context The context this function is being called in.
	 *
	 * @return string
	 */
	public function get_edit_post_link( $link, $post_id, $context ) {
		return justwpforms_get_form_edit_link( $post_id, '', $context );
	}

	/**
	 * Filter: tweak the text of the form post actions admin notices.
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
		$post_type = justwpforms_get_form_controller()->post_type;
		$permalink = get_permalink();
		$preview_url = get_preview_post_link();
		$view_form_link_html = sprintf(
			' <a href="%1$s">%2$s</a>',
			esc_url( $permalink ),
			__( 'View form' )
		);
		$preview_post_link_html = sprintf(
			' <a target="_blank" href="%1$s">%2$s</a>',
			esc_url( $preview_url ),
			__( 'Preview form' )
		);

		$messages[$post_type] = array(
			'',
			__( 'Form updated.' ) . $view_form_link_html,
			__( 'Custom field updated.' ),
			__( 'Custom field deleted.' ),
			__( 'Form updated.' ),
			isset($_GET['revision']) ? sprintf( __( 'Form restored to revision from %s.' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			__( 'Form published.' ) . $view_form_link_html,
			__( 'Form saved.' ),
			__( 'Form submitted.' ),
			__( 'Form scheduled.' ),
			__( 'Form draft updated.' ) . $preview_post_link_html,
		);

		return $messages;
	}

	/**
	 * Filter: tweak the text of the form post
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
		$post_type = justwpforms_get_form_controller()->post_type;

		$messages[$post_type] = array(
			'updated'   => _n( '%s form updated.', '%s forms updated.', $count['updated'] ),
			'locked'    => _n( '%s form not updated, somebody is editing it.', '%s forms not updated, somebody is editing them.', $count['locked'] ),
			'deleted'   => _n( '%s form permanently deleted.', '%s forms permanently deleted.', $count['deleted'] ),
			'trashed'   => _n( '%s form moved to the Trash.', '%s forms moved to the Trash.', $count['trashed'] ),
			'untrashed' => _n( '%s form restored from the Trash.', '%s forms restored from the Trash.', $count['untrashed'] ),
		);

		return $messages;
	}

	public function bulk_actions( $actions ) {
		unset( $actions['edit'] );

		return $actions;
	}

	/**
	 * Action: ensure the current screen object is initialized.
	 *
	 * @since 1.0
	 *
	 * @hooked action load-edit.php
	 *
	 * @return void
	 */
	public function define_screen_settings() {
		$screen = get_current_screen();
	}

	/**
	 *
	 * Filters the months list in the "All dates"
	 * filter dropdown.
	 *
	 */
	public function pre_months_dropdown_query( $months, $post_type ) {
		if ( $post_type !== justwpforms_get_form_controller()->post_type ) {
			return $months;
		}

		global $wpdb, $wp_locale;

		$extra_checks = "AND post_status != 'auto-draft'";

		if ( ! isset( $_GET['post_status'] ) ) {
			$extra_checks .= " AND post_status != 'trash' AND post_status != 'archive'";
		} elseif ( $_GET['post_status'] !== 'all' ) {
			$extra_checks = $wpdb->prepare( ' AND post_status = %s', $_GET['post_status'] );
		}

		$query = $wpdb->prepare("
			SELECT DISTINCT YEAR( post_modified ) AS year, MONTH( post_modified ) AS month
			FROM $wpdb->posts
			WHERE post_type = %s
			$extra_checks
			ORDER BY post_modified DESC",
			$post_type
		);

		$months = $wpdb->get_results( $query );

		return $months;
	}

	/**
	 * Filter: filter the column headers for the
	 * All Forms admin screen table.
	 *
	 * @since 1.0
	 *
	 * @hooked filter manage_justwpform_posts_columns
	 *
	 * @param array $columns  The original table headers.
	 *
	 * @return array          The filtered table headers.
	 */
	public function column_headers( $columns ) {
		$date_column = $columns['date'];
		$columns = array(
			'cb' => $columns['cb'],
			'title' => $columns['title'],
			'author' => __( 'Author', 'justwpforms' ),
			'added_to' => __( 'Added to', 'justwpforms' ),
			'shortcode' => __( 'Shortcode', 'justwpforms' ),
			'modified' => __( 'Date', 'justwpforms' )
		);
		/**
		 * Filter the column headers of forms admin table.
		 *
		 * @since 1.4.5
		 *
		 * @param array  $columns Current column headers.
		 *
		 * @return array
		 */
		$columns = apply_filters( 'justwpforms_manage_form_column_headers', $columns );

		return $columns;
	}

	/**
	 *
	 * Make modified date columnn sortable
	 *
	 */
	public function sortable_columns( $columns ) {
		$columns['modified'] = array( 'modified', true );

		return $columns;
	}

	/**
	 * Filter: output the columns content for the
	 * All Forms admin screen table.
	 *
	 * @since 1.0
	 *
	 * @hooked filter manage_justwpform_posts_custom_column
	 *
	 * @param array      $column   The current column header.
	 * @param int|string $id       The current form post object ID.
	 *
	 * @return void
	 */
	public function column_content( $column, $id ) {
		switch ( $column ) {
			case 'shortcode':
				$shortcode = justwpforms_get_shortcode( $id );
				?>
				<input type="text" size="15" readonly value="<?php echo esc_html( $shortcode ); ?>" />
				<div class="justwpforms-clipboard">
					<button type="button" class="button justwpforms-clipboard__button" data-value="<?php echo esc_html( $shortcode ); ?>"><?php _e( 'Copy to clipboard', 'justwpforms' ); ?></button><span aria-hidden="true" class="hidden"><?php _e( 'Copied!', 'justwpforms' ); ?></span>
				</div>
				<?php
				break;
			case 'modified':
				$t_time = sprintf(
					__( '%1$s at %2$s' ),
					get_the_modified_time( __( 'Y/m/d' ), $id ),
					get_the_modified_time( __( 'g:i a' ), $id )
				);

				printf( '%1$s<br>%2$s', __( 'Last modified', 'justwpforms' ), $t_time );
				break;
			case 'added_to':
				$html_links = '';

				foreach ( $this->added_to_links as $link ) {
					if ( $id != $link['id'] ) {
						continue;
					}

					if ( '' !== $html_links ) {
						$html_links .= ', ';
					}

					$html_links .= sprintf( '<a href="%s">%s</a>', $link['edit_url'], $link['label'] );

					if ( 'post' === $link['type'] && 'draft' === $link['status'] ) {
						$html_links .= ' — Draft';
					}
				}

				if ( empty( $html_links ) ) {
					$html_links = '—';
				}

				echo $html_links;
				break;
		}
	}

	/**
	 *
	 * Use modified date for date filtering
	 *
	 */
	public function pre_get_posts( $query ) {
		if ( ! is_admin()
			|| ! $query->is_main_query()
			|| $query->get( 'post_type' ) !== justwpforms_get_form_controller()->post_type ) {

			return;
		}

		$orderby = $query->get( 'orderby' );

		if ( empty( $orderby ) ) {
			$query->set( 'orderby', 'modified' );
			$query->set( 'order', 'desc' );
		}

		$m = $query->get( 'm' );

		if ( empty( $m ) ) {
			return;
		}

		$query->set( 'm', '' );

		$year = substr( $m, 0, 4 );
		$month = substr( $m, 4, 2 );

		$query->set( 'date_query', array(
			array(
				'column' => 'post_modified',
				'year' => $year,
				'month' => $month,
			),
		) );
	}

	/**
	 * Filter: filter the row actions contents for the
	 * All Form admin screen table.
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
		$post_type = justwpforms_get_form_controller()->post_type;

		if ( $post->post_type === $post_type ) {
			if ( ! isset( $actions['inline hide-if-no-js'] ) ) {
				return $actions;
			}

			$actions = array();
			$link_template = '<a href="%s">%s</a>';
			$duplicate_url = add_query_arg(
				array(
					'justwpforms_duplicate_nonce' => wp_create_nonce( 'duplicate' ),
					'post_type' => $post_type,
					'form_id' => $post->ID,
				),
				admin_url( 'edit.php' )
			);

			$links = array(
				'edit' => array(
					__( 'Edit', 'justwpforms' ),
					get_edit_post_link( $post->ID, 'build' )
				),
				'duplicate' => array(
					__( 'Duplicate', 'justwpforms' ),
					$duplicate_url,
				),
				'trash' => array(
					__( 'Trash', 'justwpforms' ),
					get_delete_post_link( $post->ID, '' )
				),
			);

			foreach( $links as $key => $values ) {
				$actions[$key] = sprintf( $link_template, $values[1], $values[0] );
			}
		}

		return $actions;
	}

	/**
	 * Action: handle the redirect following a form
	 * duplicate action.
	 *
	 * @since 1.0
	 *
	 * @hooked action load-edit.php
	 *
	 * @return void
	 */
	public function duplicate_form_redirect() {
		if ( ! isset( $_GET['justwpforms_duplicate_nonce'] )
			|| ! wp_verify_nonce( $_GET['justwpforms_duplicate_nonce'], 'duplicate' )
			|| ! isset( $_GET['form_id'] ) ) {
			return;
		}

		$form = get_post( $_GET['form_id'] );

		if ( is_a( $form, 'WP_Post' ) ) {
			$controller = justwpforms_get_form_controller();
			$new_form_id = $controller->duplicate( $form );

			if ( ! is_wp_error( $new_form_id ) ) {
				$redirect = add_query_arg(
					array( 'post_type' => $controller->post_type ),
					admin_url( 'edit.php' )
				);

				$notice = sprintf(
					'%s <a href="%s">%s</a>',
					__( '1 form duplicated.', 'justwpforms' ),
					get_delete_post_link( $new_form_id, '', true ),
					__( 'Undo', 'justwpforms' )
				);

				$admin_notices = justwpforms_get_admin_notices();
				$admin_notices->register(
					'justwpforms_form_duplicated',
					$notice,
					array(
						'type' => 'success',
						'dismissible' => true,
						'screen' => array( 'edit-justwpform' ),
						'one-time' => true,
					)
				);

				wp_safe_redirect( $redirect );
				exit();
			}
		}
	}

	/**
	 * Action: change bulk post from draft to publish.
	 *
	 * @since 1.0
	 *
	 * @hooked action load-edit.php
	 *
	 * @return void
	 */
	public function change_bulk_draft_post_status() {
		$post_type = justwpforms_get_form_controller()->post_type;

		$args = array(
			'post_type' => $post_type,
			'post_status' => 'draft',
			'posts_per_page' => -1,
			'fields' => 'ids'
		);

		$draft_forms = get_posts( $args );

		foreach( $draft_forms as $form_id ) {
			$query = array(
				'ID' => $form_id,
				'post_status' => 'publish',
			);
			wp_update_post( $query, true );
		}
	}

	/**
	 * Action: change post from draft to publish.
	 *
	 * @since 1.24.1
	 *
	 * @hooked action transition_post_status
	 *
	 * @return void
	 */
	public function change_draft_post_status( $new, $old, $post ) {
		$post_type = justwpforms_get_form_controller()->post_type;

		if ( $post_type !== $post->post_type ) {
			return;
		}

		if ( $new === $old ) {
			return;
		}

		if ( 'draft' !== $new ) {
			return;
		}

		wp_update_post( array( 'ID' => $post->ID, 'post_status' => 'publish' ) );
	}

	public function get_added_to_links() {
		remove_filter( 'get_edit_post_link', array( $this, 'get_edit_post_link' ), 10 );

		global $wpdb;
		
		$regex_block = '/wp:thethemefoundry\/justwpforms {"id":"([0-9]*)"} \/-->/';
		$regex_shortcode = '/\[form id="([0-9]*)"\s?\]/';
		$search_string_block = '%wp:thethemefoundry/justwpforms%';
		$search_string_shortcode = '%[form id="%';

		$query = $wpdb->prepare("
			SELECT ID, post_title, post_content, post_status FROM $wpdb->posts WHERE 1=1
			AND ( ( post_content LIKE %s ) OR ( post_content LIKE %s ) )
			AND post_type IN ( 'post', 'page', 'wp_block' )
			AND post_status IN ( 'publish', 'future', 'draft', 'pending', 'private' )
			ORDER BY post_date DESC",
			$search_string_block,
			$search_string_shortcode
		);

		$posts = $wpdb->get_results( $query );

		if ( ! empty( $posts ) ) {
			foreach( $posts as $post ) {
				$label = '' == $post->post_title ? 'Untitled' : $post->post_title;

				$link_data = array(
					'content' => $post->post_content,
					'label' => $label,
					'edit_url' => get_edit_post_link( $post->ID ),
					'type' => 'post',
					'post_id' => $post->ID,
					'status' => $post->post_status,
				);

				$this->create_added_to_link( $link_data, $regex_block );
				$this->create_added_to_link( $link_data, $regex_shortcode );
			}
		}

		// check if added to widget areas
		global $wp_registered_sidebars;
		$widget_areas = wp_get_sidebars_widgets();
		$url_admin_widget_areas = get_admin_url( null, 'widgets.php' );

		foreach( $widget_areas as $widget_area_id => $widget_area ) {
			if ( 'wp_inactive_widgets' === $widget_area_id ) {
				continue;
			}

			if ( ! isset( $wp_registered_sidebars[ $widget_area_id ] ) ) {
				continue;
			}

			$widget_justwpforms = get_option( 'widget_justwpforms_widget', array() );
			$widget_block = get_option( 'widget_block', array() );

			foreach ( $widget_area as $widget_id ) {
				$widget_area_name = $wp_registered_sidebars[ $widget_area_id ]['name'];
				$widget_name = substr( $widget_id, 0, -2 );
				$widget_index = substr( $widget_id, -1 );

				if ( 'block' === $widget_name ) {
					if ( ! isset( $widget_block[ $widget_index ] ) ) {
						continue;
					}

					$link_data = array(
						'content' => $widget_block[ $widget_index ]['content'],
						'label' => $widget_area_name,
						'edit_url' => $url_admin_widget_areas,
						'type' => 'widget',
					);

					$this->create_added_to_link( $link_data, $regex_block );
					$this->create_added_to_link( $link_data, $regex_shortcode );

				} else if ( 'justwpforms_widget' === $widget_name ) {
					if ( ! isset( $widget_justwpforms[ $widget_index ] ) ) {
						continue;
					}

					$hf_widget_data = $widget_justwpforms[ $widget_index ];
					$form_id = $hf_widget_data['form_id'];
					$link_added = $this->added_to_link_exists( $form_id, $widget_area_name, 'widget' );

					if ( ! $link_added ) {
						$added_to_link = array(
							'id' => $form_id,
							'label' => $widget_area_name,
							'edit_url' => $url_admin_widget_areas,
							'type' => 'widget',
						);

						$this->added_to_links[] = $added_to_link;
					}
				}
			}
		}

		// check if added to templates
		if ( current_theme_supports( 'block-templates' ) ) {
			$templates = get_block_templates( array(), 'wp_template' );
			$template_parts = get_block_templates( array(), 'wp_template_part' );

			$theme_templates = array_merge( $templates, $template_parts );

			foreach ( $theme_templates as $template ) {
				$edit_url = add_query_arg(
					array(
						'postType' => $template->type,
						'postId' => $template->id,
					),
					admin_url( 'site-editor.php' )
				);

				$link_data = array(
					'content' => $template->content,
					'label' => $template->title,
					'edit_url' => $edit_url,
					'type' => $template->type,
				);

				$this->create_added_to_link( $link_data, $regex_block );
				$this->create_added_to_link( $link_data, $regex_shortcode );
			}
		}

		add_filter( 'get_edit_post_link', array( $this, 'get_edit_post_link' ), 10, 3 );
	}

	public function create_added_to_link( $link_data, $regex ) {
		$has_matches = preg_match_all( $regex, $link_data['content'], $matches );

		if ( empty( $has_matches ) ) {
			return;
		}

		foreach ( $matches[1] as $form_id ) {
			$label = $link_data['label'];
			$type = $link_data['type'];
			$link_added = false;

			if ( 'post' === $type ) {
				$link_added = $this->added_to_link_exists( $form_id, $label, $type, $link_data['post_id'] );
			} else {
				$link_added = $this->added_to_link_exists( $form_id, $label, $type );
			}

			if ( $link_added ) {
				continue;
			}

			$added_to_link = array(
				'id' => $form_id,
				'label' => $label,
				'edit_url' => $link_data['edit_url'],
				'type' => $type,
			);

			if ( 'post' === $type ) {
				$added_to_link['post_id'] = $link_data['post_id'];
				$added_to_link['status'] = $link_data['status'];
			}

			$this->added_to_links[] = $added_to_link;
		}
	}

	public function added_to_link_exists( $form_id,  $label, $type, $post_id = 0 ) {
		$exists = false;

		foreach ( $this->added_to_links as $link ) {
			if ( 'post' === $type ) {
				$exists = ( $type === $link['type'] && $form_id === $link['id'] &&
					$label === $link['label'] && $post_id === $link['post_id'] );
			} else {
				$exists = ( $type === $link['type'] && $form_id === $link['id'] && $label === $link['label'] );
			}

			if ( $exists ) {
				break;
			}
		}

		return $exists;
	}

}

/**
 * Initialize the justwpforms_Form_Admin class immediately.
 */
justwpforms_Form_Admin::instance();
