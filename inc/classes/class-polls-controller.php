<?php

class justwpforms_Polls_Controller {

	/**
	 * The singleton instance.
	 *
	 * @var justwpforms_Polls_Controller
	 */
	private static $instance;

	public $post_type = 'justwpforms-poll';

	private $frontend_styles = false;

	private $vote_meta_prefix = 'votes_';

	/**
	 * The singleton constructor.
	 *
	 * @return justwpforms_Polls_Controller
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		self::$instance->hook();

		return self::$instance;
	}

	public function hook() {
		$this->form_controller = justwpforms_get_form_controller();

		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action( 'justwpforms_form_updated', array( $this, 'update_poll' ) );
		add_action( 'justwpforms_customize_enqueue_scripts', array( $this, 'customize_enqueue_scripts' ) );

		add_filter( 'justwpforms_style_fields', array( $this, 'form_style_fields' ) );
		add_filter( 'justwpforms_style_controls', array( $this, 'form_style_controls' ) );
	}

	public function register_post_type() {
		$labels = array(
			'name' => __( 'Polls', 'justwpforms' ),
			'singular_name' => __( 'Poll', 'justwpforms' ),
			'edit_item' => __( 'View Poll', 'justwpforms' ),
			'view_item' => __( 'View Poll', 'justwpforms' ),
			'view_items' => __( 'View Polls', 'justwpforms' ),
			'search_items' => __( 'Search Polls', 'justwpforms' ),
			'not_found' => __( 'No poll found', 'justwpforms' ),
			'not_found_in_trash' => __( 'No poll found in Trash', 'justwpforms' ),
			'all_items' => __( 'All Polls', 'justwpforms' ),
			'menu_name' => __( 'All Polls', 'justwpforms' ),
		);

		$args = array(
			'labels' => $labels,
			'public' => false,
			'publicly_queryable' => false,
			'exclude_from_search' => true,
			'show_ui' => true,
			'show_in_menu' => false,
			'query_var' => true,
			'capability_type' => 'page',
			'has_archive' => false,
			'hierarchical' => false,
			'can_export' => false,
			'supports' => array( 'custom-fields' ),
		);

		register_post_type( $this->post_type, $args );
	}

	public function create( $form, $poll_data ) {
		$post_data = $this->get_insert_post_data( $form, $poll_data );
		$poll_id = wp_insert_post( wp_slash( $post_data ), true );

		return $poll_id;
	}

	public function get_post_fields() {
		$fields = array(
			'post_title' => '',
			'post_name' => '',
			'post_type' => $this->post_type,
			'post_status' => 'publish'
		);

		return $fields;
	}

	public function get_meta_fields() {
		$fields = array(
			'form_id' => 0,
			'poll_id' => '',
			'options' => array()
		);

		return $fields;
	}

	public function get_defaults( $group = '' ) {
		$fields = array();

		switch ( $group ) {
			case 'post':
				$fields = $this->get_post_fields();
				break;
			case 'meta':
				$fields = $this->get_meta_fields();
				break;
			default:
				$fields = array_merge(
					$this->get_post_fields(),
					$this->get_meta_fields()
				);
				break;
		}

		return $fields;
	}

	public function get( $post_ids = '' ) {
		$query_params = array(
			'post_type' => $this->post_type,
			'post_status' => array( 'publish', 'draft', 'trash' ),
			'posts_per_page' => -1,
		);

 		if ( ! empty( $post_ids ) ) {
			if ( is_numeric( $post_ids ) ) {
				$query_params['p'] = $post_ids;
			} else if ( is_array( $post_ids ) ) {
				$query_params['post__in'] = $post_ids;
			}
		}

 		$polls = get_posts( $query_params );
		$polls = array_map( array( $this, 'to_array'), $polls );
 		if ( is_numeric( $post_ids ) ) {
			if ( count( $polls ) > 0 ) {
				return $polls[0];
			} else {
				return false;
			}
		}

 		return $polls;
	}

	public function to_array( $poll ) {
		$poll_array = $poll->to_array();
		$poll_meta = justwpforms_unprefix_meta( get_post_meta( $poll->ID ) );
		$form_id = $poll_meta['form_id'];
		$form = justwpforms_get_form_controller()->get( $form_id );
		$meta_defaults = $this->get_meta_fields();
		$poll_array = array_merge( $poll_array, wp_parse_args( $poll_meta, $meta_defaults ) );

 		return $poll_array;
	}

	public function form_style_fields( $fields ) {
		$fields = array_merge( $fields, $this->get_form_styles() );

		return $fields;
	}

	public function get_form_styles() {
		$styles = array(
			'color_poll_bar' => array(
				'default' => '#e8e8e8',
				'target' => 'css_var',
				'variable' => '--justwpforms-poll-bar-color',
				'sanitize' => 'sanitize_text_field'
			),
			'color_poll_link' => array(
				'default' => '#000000',
				'target' => 'css_var',
				'variable' => '--justwpforms-poll-link-color',
				'sanitize' => 'sanitize_text_field'
			),
			'color_poll_winner' => array(
				'default' => '#000000',
				'target' => 'css_var',
				'variable' => '--justwpforms-poll-winner-color',
				'sanitize' => 'sanitize_text_field'
			),
		);

		return $styles;
	}

	public function form_style_controls( $controls ) {
		$style_controls = array(
			10000 => array(
				'type' => 'divider',
				'id' => 'poll',
				'label' => __( 'Poll', 'justwpforms' )
			),
			10001 => array(
				'type' => 'color',
				'label' => __( 'Bar', 'justwpforms' ),
				'field' => 'color_poll_bar'
			),
			10003 => array(
				'type' => 'color',
				'label' => __( 'Winner', 'justwpforms' ),
				'field' => 'color_poll_winner'
			),
		);

		$controls = justwpforms_safe_array_merge( $controls, $style_controls );

		return $controls;
	}

	public function get_by_form( $form_id, $ids_only = false ) {
		if ( $ids_only ) {
			global $wpdb;

			$query = $wpdb->prepare( "
				SELECT p.ID FROM $wpdb->posts p
				JOIN $wpdb->postmeta m ON p.ID = m.post_id
				WHERE p.post_type = 'justwpforms-poll'
				AND m.meta_key = '_justwpforms_form_id'
				AND m.meta_value = %d;
			", $form_id );

			$results = $wpdb->get_col( $query );

			return $results;
		}

		$query_params = array(
			'post_type' => $this->post_type,
			'post_status' => 'any',
			'posts_per_page' => -1,
			'meta_query' => array(
				array(
					'key' => '_justwpforms_form_id',
					'value' => $form_id,
				)
			)
		);

		$polls = get_posts( $query_params );
		$polls_results = array_map( array( $this, 'to_array' ), $polls );

		return $polls_results;
	}

	public function get_poll_by_id( $form, $poll_id ) {
		$polls = get_posts( array(
			'post_type' => $this->post_type,
			'name' => $form['ID'] . '-' . $poll_id
		) );

		$polls_results = array_map( array( $this, 'to_array' ), $polls );

		return ( isset( $polls_results[0] ) ) ? $polls_results[0] : array();
	}

	/**
	 * Saves poll entry to database.
	 *
	 * All votes are stored to Poll's custom post type meta data. This method goes through all Poll choices
	 * submitted in Poll, then increases counters for all choices that were selected. There is quite some extra
	 * handling involved mentioned in the comments inside a method to handle Poll with multiple choices and or with 'Other'
	 * choice. Both pass a slightly different data.
	 *
	 * @param array $poll       Poll part data.
	 * @param array $form       Form data.
	 * @param array $submission Form submission data.
	 *
	 * @return void
	 */
	public function save_poll_entry( $poll, $form, $submission ) {
		$poll = $this->form_controller->get_part_by_id( $form, $poll['id'] );

		if ( ! empty( $poll ) ) {
			/**
			 * First, we get poll post by poll's ID. This is something we can do because poll post entries follow
			 * the 'formID-pollID' format for names.
			 */
			$poll_post = $this->get_poll_by_id( $form, $poll['id'] );

			/**
			 * Create empty array to keep track of which votes need to be handled.
			 */
			$records_votes_for_meta = array();

			if ( isset( $submission[$poll['id']] ) ) {
				$poll_submission = $submission[$poll['id']];
				$poll_options = array();

				foreach ( $poll['options'] as $option ) {
					$poll_options[$option['id']] = $option['label'];
				}

				/**
				 * The case where Poll allows multiple choices to be selected.
				 */
				if ( 1 == $poll['allow_multiple'] ) {
					// If Poll accepts "Other" choice, the value is serialized array. Unserialize it.
					if ( 1 == $poll['other_option'] ) {
						$poll_submission = maybe_unserialize( $submission[$poll['id']] );
					}

					if ( ! is_array( $poll_submission ) ) {
						// Make an array of comma separated values (stringified value)
						$poll_submission = explode( ', ', $poll_submission );
					}

					// Loop through the values and find option keys.
					foreach ( $poll_submission as $value ) {
						$key = array_search( $value, $poll_options );

						// If key is found, it's a legit option created in backend.
						if ( $key ) {
							$records_votes_for_meta[] = $this->vote_meta_prefix . $key;
						} else {
							// Otherwise, it's "Other" choice, so we add a suffix to it.
							$records_votes_for_meta[] = $this->vote_meta_prefix . 'other';
						}
					}
				/**
				 * Polls with single option.
				 */
				} else {
					$key = array_search( $poll_submission, $poll_options );

					// If key is found, it's a legit option created in backend.
					if ( $key ) {
						$records_votes_for_meta[] = $this->vote_meta_prefix . $key;
					}

					/**
					 * If "Other" choice is enabled and key is not found, this is value of this "Other" option
					 * so we add a suffix to it.
					 */
					if ( 1 == $poll['other_option'] && ! $key ) {
						$records_votes_for_meta[] = $this->vote_meta_prefix . 'other';
					}
				}

				/**
				 * Finally, go through all entries in `records_votes_for_meta`, get number of votes, and then
				 * increase the counter for that vote. After this loop finishes, we have all vote entries in the
				 * database.
				 */
				foreach ( $records_votes_for_meta as $meta_key ) {
					$votes = justwpforms_get_meta( $poll_post['ID'], $meta_key, true );
					$votes = intval( $votes );

					justwpforms_update_meta( $poll_post['ID'], $meta_key, $votes + 1 );
				}
			}
		}
	}

	/**
	 * Updates poll post on form update. Runs when `justwpforms_form_updated` action is triggered (on form update).
	 * This method is responsible for creating a poll post if it does not exist yet, or updating it if it's found.
	 * It keeps track of newly added and deleted options in the poll.
	 *
	 * @hooked action `justwpforms_form_updated`
	 *
	 * @param array $form Form data.
	 *
	 * @return void
	 */
	public function update_poll( $form ) {
		// First, get all polls in the form.
		$polls = $this->form_controller->get_parts_by_type( $form, 'poll' );

		if ( ! empty( $polls ) ) {
			foreach ( $polls as $poll ) {
				$poll_post_id = 0;

				$poll_post = $this->get_poll_by_id( $form, $poll['id'] );

				// If poll post already exists, we're going to use its ID from now on.
				if ( ! empty( $poll_post ) ) {
					$poll_post_id = $poll_post['ID'];
				}

				// If poll does not exist yet, we're going to prepare the data and create it.
				if ( 0 === $poll_post_id ) {
					$data = array(
						'poll_id' => $poll['id'],
						'options' => $poll['options']
					);

					$poll_post_id = $this->create( $form, $data );
				} else {
					$options = justwpforms_get_meta( $poll_post_id, 'options', true );
					$deleted_options = justwpforms_get_meta( $poll_post_id, 'deleted_options', true );

					// If there are no deleted options already in our sight, create an empty array.
					if ( ! $deleted_options ) {
						$deleted_options = array();
					}

					/**
					 * Loop through options that we're already aware of in our poll post and see if they
					 * still exist in the form's Poll part. If not, let's add that option to deleted options
					 * array.
					 */
					foreach ( $options as $option ) {
						if ( ! in_array( $option, $poll['options'] ) && count( $poll['options'] ) < count( $options ) ) {
							$deleted_options[] = array(
								'option' => $option,
								'votes' => $this->get_poll_option_votes( $poll_post, $option ),
								'deleted' => time()
							);

							justwpforms_update_meta( $poll_post_id, $this->vote_meta_prefix . $option['id'], 0 );
						}
					}

					/**
					 * Handles adding the deleted option entry for 'Other' choice in Poll part. If Poll part
					 * no longer offers 'Other' choice, it's considered to be deleted from Poll and we add it
					 * to the list of deleted options.
					 */
					if ( 0 == $poll['other_option'] && justwpforms_get_meta( $poll_post_id, $this->vote_meta_prefix . 'other', true ) ) {
						$deleted_options[] = array(
							'option' => array(
								'id' => 'other',
								'label' => $poll['other_option_label']
							),
							'votes' => $this->get_poll_option_votes( $poll_post, $option ),
							'deleted' => time()
						);

						justwpforms_update_meta( $poll_post_id, $this->vote_meta_prefix . 'other', 0 );
					}

					// Update deleted options Poll post meta.
					justwpforms_update_meta( $poll_post_id, 'deleted_options', $deleted_options );
				}

				// Update Poll post meta with the most up to date list of options.
				justwpforms_update_meta( $poll_post_id, 'options', $poll['options'] );

				// Update Poll post with the latest data.
				wp_update_post( array(
					'ID' => $poll_post_id,
					'post_title' => $poll['label'],
					'post_name' => $form['ID'] . '-' . $poll['id']
				) );
			}
		}
	}

	public function get_insert_post_data( $form, $poll ) {
		$defaults = $this->get_post_fields();
		$defaults_meta = $this->get_meta_fields();
		$poll_meta = wp_parse_args( array(
			'form_id' => $form['ID'],
			'poll_id' => $poll['poll_id'],
			'options' => $poll['options']
		), $defaults_meta );
		$poll_meta = array_merge( $poll_meta, $poll );
		$poll_meta = justwpforms_prefix_meta( $poll_meta );
		$post_data = array_merge( $defaults, array(
			'meta_input' => $poll_meta
		) );

		return $post_data;
	}

	/**
	 * Get number of votes for specific Poll choice.
	 *
	 * @param array $poll_post Poll post data.
	 * @param array $option    Choice data.
	 *
	 * @return int Number of votes.
	 */
	public function get_poll_option_votes( $poll_post, $option ) {
		$votes = 0;

		if ( $poll_post ) {
			$option_votes_meta_key = $this->vote_meta_prefix . $option['id'];
			$votes = intval( justwpforms_get_meta( $poll_post['ID'], $option_votes_meta_key, true ) );
		}

		return $votes;
	}

	/**
	 * Get percentage presentation of votes.
	 *
	 * @param integer $option_votes Actual votes number to determine percentage for.
	 * @param integer $total_votes  Total votes to base calculation on.
	 *
	 * @return integer Percentage of votes.
	 */
	public function get_poll_option_votes_percentage( $option_votes, $total_votes ) {
		if ( 0 === $option_votes ) {
			return 0;
		}

		$percentage = $option_votes * 100 / $total_votes;
		$percentage = number_format( $percentage, 2 );

		return $percentage;
	}

	/**
	 * Get total number of votes for poll.
	 *
	 * @param array $poll_post Poll post data.
	 * @param array $part      Poll part data with choices information.
	 *
	 * @return integer Total number of votes added together.
	 */
	public function get_poll_total_votes( $poll_post, $part ) {
		$votes = 0;

		if ( $poll_post ) {
			foreach ( $part['options'] as $option ) {
				$option_votes_meta_key = $this->vote_meta_prefix . $option['id'];
				$option_votes = justwpforms_get_meta( $poll_post['ID'], $option_votes_meta_key, true );

				$votes = $votes + intval( $option_votes );
			}

			$other_option_votes = justwpforms_get_meta( $poll_post['ID'], $this->vote_meta_prefix . 'other', true );

			$votes = $votes + intval( $other_option_votes );
		}

		return $votes;
	}

	/**
	 * Get poll choice with most votes.
	 *
	 * @param array $poll_post Poll post data.
	 * @param array $part      Poll part data.
	 *
	 * @return string Poll choice ID.
	 */
	public function get_poll_winner_vote( $poll_post, $part ) {
		$votes = array();

		if ( $poll_post ) {
			foreach( $part['options'] as $option ) {
				$option_votes_meta_key = $this->vote_meta_prefix . $option['id'];
				$option_votes = intval( justwpforms_get_meta( $poll_post['ID'], $option_votes_meta_key, true ) );
				$votes[$option['id']] = $option_votes;
			}

			$winner = 0;

			if ( ! empty( $votes ) ) {
				$winner = max( array_values( $votes ) );
			}

			if ( 0 === $winner ) {
				return;
			}

			$winner_option_id = array_keys( $votes, $winner );

			if ( 1 < count( $winner_option_id ) ) {
				return;
			}

			return $winner_option_id[0];
		}
	}

	/**
	 * Gets all poll parts in the form.
	 *
	 * @param array $form Form data.
	 *
	 * @return array Array of poll parts with their data.
	 */
	public function get_all_form_polls( $form ) {
		$polls = $this->form_controller->get_parts_by_type( $form, 'poll' );

		if ( ! $polls ) {
			return false;
		}

		return $polls;
	}

	/**
	 * Get all votes of all polls in the form.
	 *
	 * @param array $form Form data.
	 *
	 * @return integer All votes of all Poll parts added together.
	 */
	public function get_all_form_polls_votes( $form ) {
		$polls = $this->form_controller->get_parts_by_type( $form, 'poll' );

		if ( ! $polls ) {
			return false;
		}

		$votes = 0;

		foreach ( $polls as $poll ) {
			$poll_post = $this->get_poll_by_id( $form, $poll['id'] );
			$votes = $votes + $this->get_poll_total_votes( $poll_post, $poll );
		}

		return $votes;
	}

	/**
	 * Gets votes of deleted options in the poll.
	 *
	 * @param array $poll Poll post data.
	 *
	 * @return array List of poll options data and their votes.
	 */
	public function get_poll_deleted_votes( $poll ) {
		if ( ! $poll ) {
			return;
		}

		$deleted_votes = justwpforms_get_meta( $poll['ID'], 'deleted_options', true );

		if ( empty( $deleted_votes ) ) {
			return;
		}

		return $deleted_votes;
	}

	public function customize_enqueue_scripts( $deps ) {
		wp_enqueue_script(
			'justwpforms-polls',
			justwpforms_get_plugin_url() . 'inc/assets/js/customize/polls.js',
			$deps, justwpforms_get_version(), true
		);
	}

}

if ( ! function_exists( 'justwpforms_get_polls_controller' ) ):
/**
 * Get the justwpforms_Polls_Controller class instance.
 *
 * @since 1.5
 *
 * @return justwpforms_Polls_Controller
 */
function justwpforms_get_polls_controller() {
	return justwpforms_Polls_Controller::instance();
}

endif;

/**
 * Initialize the justwpforms_Polls_Controller class immediately.
 */
justwpforms_get_polls_controller();
