<?php
if ( ! function_exists( 'justwpforms_upgrade_template_path' ) ) :

function justwpforms_upgrade_template_path( $path, $form = array(), $part = array() ) {
	$path = apply_filters( 'justwpforms_get_upgrade_template_path', $path, $form, $part );
	$path = "/templates/{$path}.php";
	$path = justwpforms_get_include_folder() . $path;

	return $path;
}

endif;

if ( ! function_exists( 'justwpforms_password_submit' ) ) :

function justwpforms_password_submit( $form ) {
	include( justwpforms_upgrade_template_path( 'partials/form-password-submit', $form ) );
}

endif;

if ( ! function_exists( 'justwpforms_get_client_user_agent' ) ) :

function justwpforms_get_client_user_agent() {
	$key = 'HTTP_USER_AGENT';

	if ( isset( $_SERVER[$key] ) && ! empty( $_SERVER[$key] ) ) {
		return $_SERVER[$key];
	}

	return '';
}

endif;

if ( ! function_exists( 'justwpforms_get_current_url' ) ) :

function justwpforms_get_current_url() {
	$protocol = isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http';
	$host = $_SERVER['HTTP_HOST'];
	$uri = $_SERVER['REQUEST_URI'];
	$url = $protocol . '://' . $host . $uri;

	return $url;
}

endif;

if ( ! function_exists( 'justwpforms_get_referer' ) ) :

function justwpforms_get_referer() {
	$key = 'HTTP_REFERER';

	if ( isset( $_SERVER[$key] ) && ! empty( $_SERVER[$key] ) ) {
		return $_SERVER[$key];
	}

	return '';
}

endif;

if ( ! function_exists( 'justwpforms_form_is_available' ) ) :

function justwpforms_form_is_available( $form ) {
	$available = true;

	$available = $available && ( ! justwpforms_get_schedule()->is_restricted( $form ) );

	return $available;
}

endif;

if ( ! function_exists( 'justwpforms_get_page_breaks' ) ) :

function justwpforms_get_page_breaks( $form ) {
	$breaks = justwpforms_get_form_controller()->get_parts_by_type( $form, 'page_break' );
	$breaks = wp_list_pluck( $breaks, 'id' );

	return $breaks;
}

endif;

if ( ! function_exists( 'justwpforms_get_current_page_break' ) ) :

function justwpforms_get_current_page_break( $form, $index = false ) {
	$step = justwpforms_get_current_step( $form );
	$breaks = justwpforms_get_page_breaks( $form );

	if ( ! in_array( $step, $breaks ) ) {
		return false;
	}

	if ( true === $index ) {
		$step = array_search( $step, $breaks );
	}

	return $step;
}

endif;


if ( ! function_exists( 'justwpforms_get_poll_votes_label' ) ) :

function justwpforms_get_poll_votes_label( $votes_number ) {
	$label = __( 'votes', 'justwpforms' );

	if ( 1 === intval( $votes_number ) ) {
		$label = __( 'vote', 'justwpforms' );
	}

	return $label;
}

endif;

if ( ! function_exists( 'justwpforms_geolocation_button' ) ) :

function justwpforms_geolocation_button( $part ) {
	?>
	<button class="justwpforms-plain-button justwpforms-address-geolocate justwpforms-address-geolocate--default">
		<span class="screen-reader-text"><?php _e( 'Get location', 'justwpforms' ); ?></span>
		<svg xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 0 24 24" width="24" class="justwpforms-address-geolocate__crosshair"><path d="M0 0h24v24H0V0z" fill="none"/><path d="M12 8c-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4-1.79-4-4-4zm8.94 3c-.46-4.17-3.77-7.48-7.94-7.94V1h-2v2.06C6.83 3.52 3.52 6.83 3.06 11H1v2h2.06c.46 4.17 3.77 7.48 7.94 7.94V23h2v-2.06c4.17-.46 7.48-3.77 7.94-7.94H23v-2h-2.06zM12 19c-3.87 0-7-3.13-7-7s3.13-7 7-7 7 3.13 7 7-3.13 7-7 7z"/></svg>

		<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="spinner" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" class="justwpforms-address-geolocate__spinner"><path fill="currentColor" d="M304 48c0 26.51-21.49 48-48 48s-48-21.49-48-48 21.49-48 48-48 48 21.49 48 48zm-48 368c-26.51 0-48 21.49-48 48s21.49 48 48 48 48-21.49 48-48-21.49-48-48-48zm208-208c-26.51 0-48 21.49-48 48s21.49 48 48 48 48-21.49 48-48-21.49-48-48-48zM96 256c0-26.51-21.49-48-48-48S0 229.49 0 256s21.49 48 48 48 48-21.49 48-48zm12.922 99.078c-26.51 0-48 21.49-48 48s21.49 48 48 48 48-21.49 48-48c0-26.509-21.491-48-48-48zm294.156 0c-26.51 0-48 21.49-48 48s21.49 48 48 48 48-21.49 48-48c0-26.509-21.49-48-48-48zM108.922 60.922c-26.51 0-48 21.49-48 48s21.49 48 48 48 48-21.49 48-48-21.491-48-48-48z" class=""></path></svg>
	</button>
	<?php
}

endif;

if ( ! function_exists( 'justwpforms_phone_allows_multiple_conventions' ) ) :

function justwpforms_phone_allow_multiple_conventions( $part ) {
	$allow_multiple = false;

	if ( 0 == $part['mask_allow_only_this_convention'] ) {
		$allow_multiple = true;
	}

	/**
	 * Handle back compatibility with previous control labeled "Allow all conventions".
	 */
	if ( isset( $part['mask_allow_all_countries'] ) ) {
		if ( 1 == $part['mask_allow_all_countries'] && 1 != $part['mask_allow_only_this_convention'] ) {
			$allow_multiple = true;
		} else {
			$allow_multiple = false;
		}
	}

	return $allow_multiple;
}

endif;

if ( ! function_exists( 'justwpforms_get_datetime_placeholders' ) ) :

function justwpforms_get_datetime_placeholders( $component = false ) {
	$placeholders = array(
		'year' => __( 'Year', 'justwpforms' ),
		'month' => __( 'Month', 'justwpforms' ),
		'day' => __( 'Day', 'justwpforms' ),
	);

	$placeholders = apply_filters( 'justwpforms_get_datetime_placeholders', $placeholders );

	if ( false !== $component ) {
		if ( isset( $placeholders[$component] ) ) {
			return $placeholders[$component];
		} else {
			return '';
		}
	}

	return $placeholders;
}

endif;
