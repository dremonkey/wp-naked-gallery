<?php
/**
 * Template Utility Functions
 */


/**
 * Convenience function used to retrieve a single option value.
 *
 * @uses naked_gallery_settings_controller::get_option_value
 */
function ng_get_option( $key )
{
	$settings = naked_gallery_settings_controller::get_instance();
	return $settings->get_option_value( $key );
}


/**
 * ng_get_media
 *
 * Retrieves the primary media to be displayed on a gallery slide.
 *
 * Currently only supports an image or a video.
 */
function ng_get_media( $echo=true )
{
	$gallery 	= naked_gallery_controller::get_instance();
	$media 		= $gallery->get_media();

	if( $echo ) echo $media['html'];
	else return $media;
}



/**
 * ng_get_description
 *
 * Retrieves description text for the slide.
 */
function ng_get_description( $echo=true )
{
	$gallery 	= naked_gallery_controller::get_instance();
	$desc 		= $gallery->get_description();

	if( $echo ) echo $desc;
	else return $desc;
}


/**
 * ng_get_nav
 *
 * Retrieves the gallery navigation links
 */
function ng_get_nav( $echo=true )
{
	$gallery 	= naked_gallery_controller::get_instance();
	$nav 		= $gallery->get_nav();

	if( $echo ) echo $nav;
	else return $nav;
}