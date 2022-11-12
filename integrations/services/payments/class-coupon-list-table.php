<?php
class justwpforms_Coupon_List_Table extends WP_List_Table {
	public $coupon_controller = null;

	function __construct(){
		global $status, $page;

		$this->coupon_controller = justwpforms_get_coupon_controller();

		parent::__construct( array(
			'singular' => 'coupon',
			'plural' => 'coupons',
			'screen' => 'forms_page_justwpforms-coupon',
		) );

	}

	function column_default($item, $column_name){
		$text = '';
		$coupon = $this->coupon_controller->get( $item->ID );
		switch( $column_name ) {
			case 'post_title':
				$edit_link = sprintf( 'admin.php?page=justwpforms-coupon&coupon_ID=%s', $coupon['ID'] );
				$edit_link = esc_url ( admin_url( $edit_link ) );

				$delete_link = sprintf( 'admin.php?page=justwpforms-coupon&coupon_ID=%s&action=justwpforms_ajax_delete_coupon', $coupon['ID'] );
				$delete_link = esc_url ( admin_url( wp_nonce_url( $delete_link, 'justwpforms-coupon-nonce' ) ) );


				$quick_edit_button = sprintf(
			'<button type="button" class="button-link editinline" aria-label="%s" aria-expanded="false">%s</button>',
			/* translators: %s: Taxonomy term name. */
			esc_attr( sprintf( __( 'Quick edit &#8220;%s&#8221; inline' ), $column_name ) ),
				__( 'Quick&nbsp;Edit' )
			);

		        $actions = array(
		            'edit' => sprintf( '<a href="%s">Edit</a>',$edit_link ),
		            'inline hide-if-no-js' => $quick_edit_button,
		            'delete' => sprintf( '<a href="%s" class="delete-coupon aria-button-if-js">Delete</a>', $delete_link ),
		        );

		        $text = sprintf('<a class="row-title" href="%s">%s</a> %s', $edit_link, $coupon['post_title'], $this->row_actions($actions) );
		        $text .= '<div class="hidden" id="inline_' . $coupon['ID'] . '">';
				$text .= '<div class="post_title">' . $coupon['post_title'] . '</div>';
				$text .= '<div class="discount_type">' . $coupon['discount_type'] . '</div>';
				$text .= '<div class="discount_amount">' . $coupon['discount_amount'] . '</div>';
				$text .= '</div>';

				break;
			case 'description':
				$text = justwpforms_get_meta( $item->ID, 'description', true );

				if ( empty( $text ) ) {
					$text = '—';
				}
				break;
			case 'redemptions':
				$text = justwpforms_get_meta( $item->ID, 'redemptions', true );
		}
		return apply_filters( "manage_justwpforms_coupon_custom_column", $text, $column_name, $item->ID );
	}



	function column_cb( $coupon ) {
		return sprintf(
			'<label class="screen-reader-text" for="cb-select-%1$s">%2$s</label>' .
			'<input type="checkbox" name="delete_coupons[]" value="%1$s" id="cb-select-%1$s" />',
			$coupon->ID,
			sprintf( __( 'Select %s' ), $coupon->name )
		);
	}

	function get_columns(){
		$columns = array(
			'cb' => '<input type="checkbox" />',
			'post_title' => __( 'Name', 'justwpforms' ),
			'description' => __( 'Description', 'justwpforms'),
			'redemptions' => __( 'Redemptions', 'justwpforms' ),
		);
		return $columns;
	}

	function get_sortable_columns() {
		return array(
			'post_title' => array( 'post_title', false ),
			'description' => array( 'description', false ),
			'redemptions' => array( 'redemptions', false ),
		);
	}

	function get_bulk_actions() {
		$actions = array();

			$actions['justwpforms_bulk_delete_coupon'] = __( 'Delete', 'justwpforms' );

		return $actions;
	}

	public function no_items() {
		echo __( 'No coupons found.', 'justwpforms' );
	}

    function prepare_items() {
        global $wpdb;

        $coupon_controller = $this->coupon_controller;

        $post_type = $coupon_controller->post_type;

		$items_per_page = $this->get_items_per_page( 'edit_justwpforms-coupon_per_page' );
		$items_per_page = apply_filters( 'edit_justwpforms_coupon_per_page', $items_per_page );

		$args = array(
			'post_type'   => $post_type,
			'page'       => $this->get_pagenum(),
			'numberposts'     => $items_per_page,
		);

		$columns = $this->get_columns();
		$hidden  = get_hidden_columns( $this->screen );
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, $hidden, $sortable );

		if ( ! empty( $_REQUEST['orderby'] ) ) {
			$args['orderby'] = trim( wp_unslash( $_REQUEST['orderby'] ) );
		}

		if ( ! empty( $_REQUEST['order'] ) ) {
			$args['order'] = trim( wp_unslash( $_REQUEST['order'] ) );
		}

		$args['offset'] = ( $args['page'] - 1 ) * $args['numberposts'];


		$this->callback_args = $args;

		$this->items = get_posts( $args );

		$total_items = wp_count_posts( $coupon_controller->post_type )->publish;

		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $items_per_page,
				'total_pages' => ceil( $total_items / $items_per_page ),
			)
		);
    }

    public function single_row( $coupon, $level = 0 ) {
		echo '<tr id="coupon-' . $coupon->ID . '">';
		$this->single_row_columns( $coupon );
		echo '</tr>';
	}

    public function inline_edit() {

	$labels = $this->coupon_controller->get_post_object()->labels;


?>

		<form method="get">
		<table style="display: none"><tbody id="inlineedit">

			<tr id="inline-edit" class="inline-edit-row" style="display: none">
			<td colspan="<?php echo $this->get_column_count(); ?>" class="colspanchange">
			<div class="inline-edit-wrapper">

			<fieldset>
				<legend class="inline-edit-legend"><?php _e( 'Quick Edit' ); ?></legend>
				<div class="inline-edit-col">
				<label>
					<span class="title"><?php _e( 'Name', 'term name' ); ?></span>
					<span class="input-text-wrap"><input type="text" name="post_title" class="ptitle" value="" /></span>
				</label>
				<label>
					<span class="title"><?php _e( 'Discount Type', 'term name' ); ?></span>
					<span class="justwpforms-buttongroup justwpforms-buttongroup-field_width">
						<label for="inline_discount_type_fixed">
							<input type="radio" id="inline_discount_type_fixed" value="fixed" name="inline_discount_type" checked="checked" />
							<span><?php _e( 'Fixed', 'justwpforms' ); ?></span>
						</label>
						<label for="inline_discount_type_percentage">
							<input type="radio" id="inline_discount_type_percentage" value="percentage" name="inline_discount_type" />
							<span><?php _e( 'Percentage', 'justwpforms' ); ?></span>
						</label>
					</span>
				</label>
				<label>
					<span class="title title-discount_amount"><?php _e( 'Discount Amount', 'justwpforms' ); ?></span>
					<span class="title title-discount_percentage"><?php _e( 'Discount Percentage', 'justwpforms' ); ?></span>
					<span class="input-text-wrap"><input type="number" name="discount_amount" class="ptitle" value="" min="0" /></span>
				</label>
				</div>
			</fieldset>

			<?php
			$core_columns = array(
				'cb'          => true,
				'description' => true,
				'name'        => true,
				'slug'        => true,
				'posts'       => true,
			);

			list( $columns ) = $this->get_column_info();

			foreach ( $columns as $column_name => $column_display_name ) {
				if ( isset( $core_columns[ $column_name ] ) ) {
					continue;
				}

				/** This action is documented in wp-admin/includes/class-wp-posts-list-table.php */
				do_action( 'quick_edit_custom_box', $column_name, 'edit-tags', $this->screen->taxonomy );
			}
			?>

			<div class="inline-edit-save submit">
				<button type="button" class="save button button-primary"><?php echo $labels->update_item; ?></button>
				<button type="button" class="cancel button"><?php _e( 'Cancel' ); ?></button>
				<span class="spinner"></span>

				<?php wp_nonce_field( 'justwpformscouponinlineedit', '_inline_edit', false ); ?>
				<input type="hidden" name="post_type" value="<?php echo $this->coupon_controller->post_type; ?>" />

				<div class="notice notice-error notice-alt inline hidden">
					<p class="error"></p>
				</div>
			</div>
			</div>

			</td></tr>

		</tbody></table>
		</form>
		<?php
	}


}