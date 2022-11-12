<?php

if ( ! function_exists( 'justwpforms_get_modal_id' ) ):
/**
 * Get a modal html id.
 *
 * @param array $form    Current form data.
 *
 * @return string
 */
function justwpforms_get_modal_id( $form ) {
	/**
	 * Filter the id of a modal element.
	 *
	 * @param string $id    Current id.
	 * @param array $form   Current form data.
	 *
	 * @return string
	 */
	$id = 'justwpforms-modal-' . esc_attr( $form['ID'] );
	$id = apply_filters( 'justwpforms_modal_id', $id, $form );

	return $id;
}

endif;

if ( ! function_exists( 'justwpforms_the_modal_id' ) ):
/**
 * Output a form's html id.
 *
 * @param array $form Current form data.
 *
 * @return string
 */
function justwpforms_the_modal_id( $form ) {
	echo justwpforms_get_modal_id( $form );
}

endif;

if ( ! function_exists( 'justwpforms_get_modal_class' ) ):
/**
 * Get a modal html class.
 *
 * @param array $form    Current form data.
 *
 * @return string
 */
function justwpforms_get_modal_class( $form ) {
	$classes = justwpforms_get_modals()->modal_html_class( $form );

	/**
	 * Filter the list of classes of a modal element.
	 *
	 * @since 1.3
	 *
	 * @param array $classes List of current classes.
	 * @param array $form    Current form data.
	 *
	 * @return string
	 */
	$classes = apply_filters( 'justwpforms_modal_class', $classes, $form );
	$classes = implode( ' ', $classes );

	return $classes;
}

endif;

if ( ! function_exists( 'justwpforms_the_modal_class' ) ):
/**
 * Output a modal html class.
 *
 * @param array $form Current form data.
 *
 * @return string
 */
function justwpforms_the_modal_class( $form ) {
	echo justwpforms_get_modal_class( $form );
}

endif;

if ( ! function_exists( 'justwpforms_get_modal_styles' ) ):

function justwpforms_get_modal_styles( $form ) {
	$styles = justwpforms_get_modals()->modal_html_styles( $form );

	/**
	 * Filter the css styles of a form modal.
	 *
	 * @since 1.4.5
	 *
	 * @param array $styles Current styles attributes.
	 * @param array $form   Current form data.
	 *
	 * @return array
	 */
	$styles = apply_filters( 'justwpforms_modal_styles', $styles, $form );

	return $styles;
}

endif;

if ( ! function_exists( 'justwpforms_the_modal_styles' ) ):

function justwpforms_the_modal_styles( $form ) {
	$styles = justwpforms_get_modal_styles( $form );
	?>
	<!-- justwpforms modal CSS variables -->
	<style>
	#<?php justwpforms_the_modal_id( $form ); ?> {
		<?php foreach( $styles as $key => $style ) {
			$variable = $style['variable'];
			$value = $form[$key];
			$unit = isset( $style['unit'] ) ? $style['unit']: '';

			if ( isset( $style['format'] ) ) {
				$format = $style['format'];

				switch( $format ) {
					case 'rgba':
						$alpha = isset( $style['alpha'] ) ? $style['alpha']: 1;
						$value = justwpforms_hex_to_rgba( $value, $alpha );
						break;
					default:
						break;
				}
			}

			echo "{$variable}: {$value}{$unit};\n";
		} ?>
	}
	</style>
	<!-- End of justwpforms modal CSS variables -->
	<?php
}

endif;

if ( ! function_exists( 'justwpforms_hex_to_rgba' ) ):

function justwpforms_hex_to_rgba( $hex, $alpha ) {
	$hex = str_replace( '#', '', $hex );
	$rgba = str_split( $hex, 2 );
	$rgba = array_map( 'hexdec', $rgba );
	$rgba[] = $alpha;
	$value = 'rgba(' . implode( ', ', $rgba ) . ')';

	return $value;
}

endif;
