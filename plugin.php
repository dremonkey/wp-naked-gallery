<?php
 
/*
Plugin Name: Naked Gallery
Description: This turns a normal paginated wordpress post with a gallery post type into an AJAX powered slideshow. This plugin (like the rest of the 'naked' series) utilizes classes found in the 'naked-utils' plugin so make sure that is installed.
Author: Andre Deutmeyer
Version: 0.1
*/

// ===============
// = Plugin Name =
// ===============
define( 'NG_PLUGIN_NAME', 'naked-gallery' );

// ==================
// = Plugin Version =
// ==================
define( 'NG_PLUGIN_VERSION', '0.1' );

// =======================
// = Plugin Path and URL =
// =======================
define( 'NG_URL', plugin_dir_url( __FILE__ ) );
define( 'NG_PATH', trailingslashit( dirname( __FILE__ ) ) );



/** 
 * Files are added through the 'plugins_loaded' hook so that we
 * can ensure that naked-utils is loaded before trying to use
 * classes and function declared there. 
 */
add_action( 'plugins_loaded', 'naked_gallery_init' );

// warn if naked-utils / or json-api is not installed
add_action( 'admin_notices', 'naked_gallery_activation_notice');


/**
 * Initializes all files
 */
function naked_gallery_init()
{
	// only load these if naked-utils is active otherwise we get all sorts of errors
	if( class_exists( 'nu_singleton' ) ) {

		// include the models
		require_once( NG_PATH . 'core/models/gallery.php' );

		// include the controllers
		require_once( NG_PATH . 'core/controllers/gallery.php' );
		require_once( NG_PATH . 'core/controllers/settings.php' );

		// include the template tags
		require_once( NG_PATH . 'template-tags.php' );

		// temp... needed once to update the galleries
		require_once( NG_PATH . 'core/update-legacy.class.php' );

		// instantiate the controller(s) and classes
		naked_gallery_controller::get_instance();
		naked_gallery_settings_controller::get_instance();

		// new naked_gallery_update_legacy_galleries();
	}
}


function naked_gallery_activation_notice()
{
	$json_api_exists = class_exists( 'JSON_API' );

	if( !defined ( 'NAKED_UTILS' ) ) {
		// warn if naked-utils is not active
		if( current_user_can( 'install_plugins' ) ) {
			echo '<div class="error"><p>';
      printf( __('Naked Gallery requires Naked Utils. Please make sure that you have installed and activated <a href="%s">Naked Utils</a>. They are like peas in a pod.', 'naked_gallery' ), '#' );
      echo "</p></div>";
		}
	}
	
	if( !$json_api_exists ) {
		// warn if json-api is not active
		if( current_user_can( 'install_plugins' ) ) {
			echo '<div class="error"><p>';
	    	printf( __('Naked Gallery requires JSON-API to work. Please make sure that you have installed and activated <a href="%s">JSON-API</a>. They are like peas in a pod.', 'naked_gallery' ), 'http://wordpress.org/extend/plugins/json-api/' );
	   	 	echo "</p></div>";
	  }
	}
	elseif( $json_api_exists ) {
		// warn if the feature controller has not been activated
		$active_controllers = explode(',', get_option('json_api_controllers', 'core'));
		if( !in_array( 'gallery', $active_controllers ) ) {
			echo '<div class="error"><p>';
      		printf( __( 'You must activate the "Gallery" controller on the <a href="%s">JSON-API options page</a> in order to use naked_gallery', 'naked_gallery' ), get_admin_url( '', 'options-general.php?page=json-api' ) );
      		echo "</p></div>";
		}
	}
}