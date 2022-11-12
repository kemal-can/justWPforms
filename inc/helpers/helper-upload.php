<?php
if ( ! function_exists( 'justwpforms_get_deprecated_mime_groups' ) ):

function justwpforms_get_deprecated_mime_groups() {
	$types = wp_get_ext_types();
	$documents = array_merge( $types['document'], $types['spreadsheet'], $types['text'] );
	$archives = $types['archive'];
	$images = $types['image'];
	$media = array_merge( $types['audio'], $types['video'] );
	$groups = array(
		'documents' => $documents,
		'archives' => $archives,
		'images' => $images,
		'media' => $media,
	);

	return $groups;
}

endif;

if ( ! function_exists( 'justwpforms_allowed_file_extensions' ) ):

function justwpforms_allowed_file_extensions() {
	$types = get_allowed_mime_types();
	$allowed_extensions = [];

	array_walk( $types, function( $type, $extension ) use ( &$allowed_extensions ) {
		$allowed_extensions = array_merge( $allowed_extensions, explode( '|', $extension ) );
	} );

	return $allowed_extensions;
}

endif;


if ( ! function_exists( 'justwpforms_get_file_mime' ) ):

function justwpforms_get_file_mime( $extension, $only_mime = false ) {
	$mimes = get_allowed_mime_types();
	$file_mime = array();

	foreach( $mimes as $ext => $mime ) {
		if ( preg_match( "/^($ext)$/", $extension ) ) {
			$file_mime[$ext] = $mime;
			break;
		}
	}

	if ( true === $only_mime ) {
		return $mime;
	}

	return $file_mime;
}

endif;