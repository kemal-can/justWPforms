<?php

if ( ! function_exists( 'justwpforms_parse_pixel_value' ) ):
/**
 * Sanitize checkbox values.
 *
 * @since 1.0
 *
 * @param int|string $value The original value.
 *
 * @return int|string       1 if value was 1, or empty string.
 */
function justwpforms_parse_pixel_value( $value ) {
	return is_numeric( $value ) ? "{$value}px" : $value;
}

endif;

if ( ! function_exists( 'justwpforms_get_frontend_stylesheet_url' ) ):

	function justwpforms_get_frontend_stylesheet_url( $stylesheet_name = '' ) {
		if ( empty( $stylesheet_name ) ) {
			return;
		}

		$stylesheets_url = justwpforms_get_plugin_url() . 'inc/assets/css/frontend';
		$stylesheets_url = apply_filters( 'justwpforms_frontend_stylesheets_url', $stylesheets_url );
		$style_suffix = justwpforms_get_version();

		$style_url = "{$stylesheets_url}/{$stylesheet_name}?ver={$style_suffix}";

		return $style_url;
	}

endif;
