<?php

/**
 * Plugin Name: justWPforms
 * Plugin URI:  https://demo.justWPforms.io
 * Description: Form builder to get in touch with visitors, grow your email list and collect payments.
 * Author:      justWPforms
 * Version:     1.1.0
 * Author URI:  mail@kemalcan.net
 */

update_option( 'ttf-api-key','39a089cbeba1418fb56360916fa284d5', true );
update_option( 'ttf_updates_key_justwpforms',true, true );

/**
 * Prevent collision with free version.
 */
if ( defined( 'JUSTWPFORMS_VERSION' ) && ! defined( 'JUSTWPFORMS_UPGRADE_VERSION' ) ) {
	if ( is_plugin_active( 'justwpforms/justwpforms.php' ) ) {
		deactivate_plugins( 'justwpforms/justwpforms.php' );
	}

	wp_redirect( $_SERVER['REQUEST_URI'] );
	exit;
}

/**
 * The current version of the plugin.
 */
define( 'justwpforms_UPGRADE_VERSION', '1.33.0' );

if ( ! function_exists( 'justwpforms_get_version' ) ):

function justwpforms_get_version() {
    return justwpforms_UPGRADE_VERSION;
}

endif;

if ( ! function_exists( 'justwpforms_plugin_file' ) ):
/**
 * Get the absolute path to the plugin file.
 *
 * @return string Absolute path to the plugin file.
 */
function justwpforms_plugin_file() {
	return __FILE__;
}

endif;

if ( ! function_exists( 'justwpforms_plugin_name' ) ):
/**
 * Get the plugin basename.
 *
 * @return string The plugin basename.
 */
function justwpforms_plugin_name() {
	return plugin_basename( __FILE__ );
}

endif;

if ( ! function_exists( 'justwpforms_get_plugin_url' ) ):
/**
 * Get the plugin url.
 *
 * @return string The url of the plugin.
 */
function justwpforms_get_plugin_url() {
	return plugin_dir_url( __FILE__ );
}

endif;

if ( ! function_exists( 'justwpforms_get_plugin_path' ) ):
/**
 * Get the absolute path of the plugin folder.
 *
 * @return string The absolute path of the plugin folder.
 */
function justwpforms_get_plugin_path() {
	return plugin_dir_path( __FILE__ );
}

endif;

if ( ! function_exists( 'justwpforms_get_include_folder' ) ):
/**
 * Get the path of the PHP include folder.
 *
 * @return string The path of the PHP include folder.
 */
function justwpforms_get_include_folder() {
	return dirname( __FILE__ ) . '/inc';
}

endif;

if ( ! function_exists( 'justwpforms_get_core_folder' ) ):

function justwpforms_get_core_folder() {
	return dirname( __FILE__ ) . '/core';
}

endif;

if ( ! function_exists( 'justwpforms_get_integrations_folder' ) ):

function justwpforms_get_integrations_folder() {
	return dirname( __FILE__ ) . '/integrations';
}

endif;

if ( ! function_exists( 'justwpforms_get_updater_folder' ) ):

function justwpforms_get_updater_folder() {
	return dirname( __FILE__ ) . '/updater';
}

endif;

if ( ! function_exists( 'justwpforms_get_plugin_metadata' ) ) :

function justwpforms_get_plugin_metadata() {
	$metadata = array(
		'name' => 'justwpforms',
		'id' => 'justwpforms-upgrade',
		'version' => justwpforms_UPGRADE_VERSION,
		'slug' => plugin_basename( __FILE__ ),
		'plan' => 'justwpforms',
		'type' => 'plugin',
		'url' => 'https://licenses.justwpforms.io',
	);

	return $metadata;
}

endif;

/**
 * Activate
 */
require_once( justwpforms_get_core_folder() . '/helpers/helper-activation.php' );

/**
 * Core
 */
require_once( justwpforms_get_core_folder() . '/classes/class-justwpforms-core.php' );

/**
 * Upgrade
 */
require_once( justwpforms_get_include_folder() . '/helpers/helper-activation.php' );
require_once( justwpforms_get_include_folder() . '/classes/class-justwpforms-upgrade.php' );

/**
 * Main handler
 */
if ( ! function_exists( 'justwpforms' ) ):
/**
 * Get the global justwpforms class.
 *
 * @return justwpforms_Upgrade
 */
function justwpforms() {
	global $justwpforms;

	if ( is_null( $justwpforms ) ) {
		$justwpforms = new justwpforms_Upgrade();
	}

	return $justwpforms;
}

endif;

/**
 * Start general admin and frontend hooks.
 */
add_action( 'plugins_loaded', array( justwpforms(), 'initialize_plugin' ) );

/**
 * Start Customize screen specific hooks.
 */
add_filter( 'customize_loaded_components', array( justwpforms(), 'initialize_customize_screen' ) );
