<?php

class naked_gallery_settings_controller extends nu_settings
{
	/*** private static variables ***/
	private static $_debug 			= false; // toggle debug information
	private static $_instance 	= null; // stores class instance

	public $options_page_key; // used to create the page slug
	public $options_group;		// ... can't remember ... - Andre
	public $options_key;			// var name used to store the options in the db
	public $cap_level;				// the user capability level required for access
	public $page_title; 			// the settings page title
	public $menu_title;				// the settings page menu title


	/**
	 * get_instance
	 *
	 * Retrieves an instance of this class or creates a new one if it doesn't exist
	 */
	static function get_instance() {

		if( null === self::$_instance )
			self::$_instance = new self();

		return self::$_instance;
	} 


	private function __construct()
	{
		// initialize the settings page
		$this->init_settings();
	}


	/**
	 * Sets the values of all class variables. Called by self::init_settings().
	 */
	public function set_class_vars()
	{
		$this->options_page_key = 'naked_gallery';
		$this->options_group 		= 'naked_gallery_group';
		$this->options_key 			= 'naked_gallery_options';
		$this->cap_level 				= 'manage_options';
		$this->page_title 			= __( 'Gallery Options', 'naked_gallery' );
		$this->menu_title 			= __( 'Gallery', 'naked_gallery' );
	}


	/**
	 * Called by self::reg_setting_sections().
	 *
	 * @return (array) A list of setting sections.
	 */
	public function get_setting_sections()
	{
		$sections = array(
			'section_general' => array(
    		'title' 	=> 'General',
    		'callback' 	=> array( &$this, 'get_section_desc' ),
    		'page'		=> $this->options_page_key,
    	),
		);

		$sections = apply_filters( 'ng_setting_sections', $sections );

		return $sections;
	}


	/**
	 * Called by self::reg_setting_fields().
	 *
	 * @return (array) A list of setting fields
	 */
	public function get_setting_fields()
	{
		$refresh_select_options = $this->_get_refresh_select_options();

		$fields = array(
			// number of next/prev clicks before a refresh
    	'refresh_threshold' => array(
    		'title' 	=> __( 'Refresh Threshold', 'naked_gallery' ),
    		'callback' 	=> array( &$this, 'build_form_fields' ),
    		'page'		=> $this->options_page_key,
    		'section' 	=> 'section_general',
    		'args' 	=> array(
    			'id' => 'refresh_threshold',
    			'type' => 'select',
    			'desc' => __( 'The number of next/prev clicks that should occur before forcing a page refresh. If -1 then it will never refresh while the user is navigating through the same gallery.', 'naked_gallery' ),
    			'options'	=> $refresh_select_options
    		)	
    	),
		);

		$fields = apply_filters( 'ng_setting_fields', $fields );

		return $fields;
	}


	/**
	 Helpers
	 */


	/**
	 * @return (array) the default options values 
	 */
	public function get_default_option_values()
	{
		return array(
			'refresh_threshold' => -1
		);
	}


	/**
	 * Retrieves a section description
	 *
	 * We don't need a section desc so just returning an empty string
	 */
	public function get_section_desc( $args )
	{
		return '';
	}


	/**
	 * Builds the options array for the 'refresh threshold' select box
	 *
	 * @return (array)
	 */
	private function _get_refresh_select_options()
	{
		$opts = array();
		for ( $i=-1; $i<=20 ; $i++ ) { 
			$opts[] = $i; 
		} 

		return $opts;
	}	
}

function naked_gallery_get_option( $option ) 
{
	// $gallery = new naked_gallery_settings_controller();
	$gallery = naked_gallery_settings_controller::get_instance();
	return $gallery->get_option_value( $option );
}