<?php

if ( ! function_exists( 'justwpforms_zapier_get_part_value' ) ) :

function justwpforms_zapier_get_part_value( $value, $part, $form, $activity ) {
	$value = apply_filters( 'justwpforms_zapier_part_value', $value, $part, $form, $activity );

	return $value;
}

endif;

if ( ! function_exists( 'justwpforms_zapier_part_visible' ) ) :

function justwpforms_zapier_part_visible( $part ) {
	$visible = apply_filters( 'justwpforms_zapier_part_visible', true, $part );

	return $visible;
}

endif;