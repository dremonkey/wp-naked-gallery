<?php
/**
 * Gallery Controller
 *
 * This is for both the Gallery Archive and Single Gallery Pages
 *
 * @package Naked Gallery
 * @since 0.1
 */


class naked_gallery_controller extends nu_singleton
{
	private static $debug = false;

	protected function __construct()
	{
		// Set the template
		add_filter( 'template_include', array( &$this, 'set_template' ), 10 );

		// Add the js
		add_filter( 'init', array( &$this, 'reg_js' ) );
		add_filter( 'nu_load_js' , array( &$this, 'load_js' )  );

		// Initialize some js variables
		add_action( 'nu_after_load_js', array( &$this, 'set_js_vars' ) );

		// Add our javascript template
		add_action( 'wp_footer', array( &$this, 'load_js_template'), 0 );

		// Add the JSON-API controller
		add_filter( 'json_api_controllers' , array( &$this, 'add_jsonapi_controller' ) );
		add_filter( 'json_api_gallery_controller_path' , array( &$this, 'set_jsonapi_gallery_controller_path' ) );
	}


	/**
	 Load the slide template
	 */


	/**
	 * load_template
	 *
	 * This checks the child and parent theme directory to see if the template exists in the theme
	 * directory. If it does we will be loading that template. Otherwise we default to the
	 * template found in this plugin.
	 *
	 * @uses template_include hook
	 */
	public function set_template( $template )
	{
	 	if( is_single() && 'gallery' == get_post_format() ) {

	 		$tpl_path = '';

			// if a child theme is being used check the child theme first
			if( is_child_theme() ) {
				$fpath = get_stylesheet_directory() . '/single-gallery.php';
				if( file_exists( $fpath ) ) $tpl_path = $fpath;
			} 
			
			// if a no child theme is being used or the tpl was not found in the child theme
			if( !is_child_theme() || !$tpl_path ) {
				$fpath = get_template_directory() . '/single-gallery.php';
				if( file_exists( $fpath ) ) $tpl_path = $fpath;
			}

			// if no template was found in either the parent or child theme 
			// then default to the template bundled here in core/views
			if( !$tpl_path )
				$tpl_path = NG_PATH . 'core/views/single-gallery.php';

			// allow other plugins or the theme to change the location of the template
			$template = apply_filters( 'ng_gallery_view_path', $tpl_path );

			// modify the body class
			add_filter( 'body_class', array( &$this, 'set_body_class' ) );

		}

		return $template;
	}


	/**
	 * set_body_class
	 */
	public function set_body_class( $class )
	{
		$class[] = 'gallery-item';
		return $class;
	}


	/**
	 Gallery Navigation Link Stuff
	 */

	/**
	 *
	 * @uses _get_adjacent_image_links()
	 */
	public function get_nav()
	{
		$nav = $this->_get_adjacent_slide_links();
		return $nav;
	}


	/**
	 * Helper function. Builds the html for the next/prev page nav links
	 *
	 * @uses _get_adjacent_attachment_links
	 *
	 * @return $output (string)
	 * 	The HTML for the fully built next/prev page nav links
	 */
	private function _get_adjacent_slide_links()
	{
		global $post, $page, $numpages, $more;

		$content_model = naked_gallery_model::get_instance();

		$output = '';
		$text_prev = '&lt;';
		$text_next = '&gt;';

		if( 'gallery' == get_post_format() ) {

			// for standard galleries
			if( $numpages >  1 ) {
				// build the previous page link
				$i = $page - 1;
				if ( $i && $more ) {
					if ( 1 == $i ) {
						$url = get_permalink();
					} else {
						$url = get_permalink() . user_trailingslashit( $i, 'single_paged' );
					}

					$output .= '<a class="prev button" href="' . esc_url( $url ) . '">';
					$output .= $text_prev . '</a>';
				}
				elseif( 0 == $i ) {
					
					$classes = 'prev button';
					$gallery = $content_model->get_prev_gallery();

					if( $gallery ) {
						$url = esc_url( get_permalink( $gallery->ID ) );
					}
					else {
						$url = '#';
						$classes .= ' disabled';
					}

					$output .= '<a class="' . $classes . '" href="' . $url . '">';
					$output .= $text_prev . '</a>';	
				}

				// build the next page link
				$i = $page + 1;
				if ( $i <= $numpages && $more ) {
					
					$url = get_permalink() . user_trailingslashit( $i, 'single_paged' );

					$output .= '<a class="next button" href="' . esc_url( $url ) . '">';
					$output .= $text_next . '</a>';
				}
				elseif( $page == $numpages ) {
					
					$classes 	= 'next button';
					$gallery 	= $content_model->get_next_gallery();

					if( $gallery ) {
						$url = esc_url( get_permalink( $gallery->ID ) );
					}
					else {
						$url = '#';
						$classes .= ' disabled';
					}

					$output .= '<a class="' . $classes . '" href="' . $url . '">';
					$output .= $text_next . '</a>';
				}
			}
			else {
		 		return;
		 	}
		}
		// get adjacent images for attachment pages ( with a parent )
		elseif( 'attachment' == $post->post_type 
						&& false !== strpos( $post->post_mime_type, 'image' )
						&& $post->post_parent ) {

			// grab images attached to the parent of the current attachment being viewed
			$args = array(
				'post_parent' => $post->post_parent,
				'post_status'	=> 'any', 
				'post_type' => 'attachment', 
				'post_mime_type' => 'image', 
				'order' => 'ASC',
			);

			$images = get_children( $args );

			if( self::$debug )
				nu_debug::var_dump( $images );

			$output = $this->_get_adjacent_attachment_links( $images, $text_prev, $text_next );
		}

		// add the count ( i.e. 1 of 3 )
		if( !isset( $count_text ) || !$count_text ) {
			$p = '<strong class="current">' . $page . '</strong>';
			$total = '<strong class="numpages">' . $numpages . '</strong>';
			$count_text = __( sprintf('%s of %s', $p, $total ), 'naked_gallery' );
		}

		$output .= '<span class="count">' . $count_text . '</span>';

		return $output;
	}


	/**
	 * Helper function used to build the adjacent image links for attachment galleries
	 * that meet one of the following criteria:
	 * - legacy galleries (i.e. gallery post format but no body content)
	 * - attachments of a specific post
	 * - attachments with no parent and not sorted by a specific tag
	 */
	private function _get_adjacent_attachment_links( $images, $text_prev, $text_next )
	{
		global $post, $numpages, $page;

		if( is_attachment( $post->ID ) ) {
			$post_id = $post->post_parent;
			$img_id  = $post->ID;
		}
		else {
			$post_id = $post->ID;
		}

		$output = '';
		// Change the order so that the featured post is always first. This assumes that
		// a featured post is set which we can safely make because the archive query only
		// displays posts that have a featured image set.
		//
		// @see controllers/gallery.php alter_the_query()
		$feat_id = get_post_thumbnail_id( $post_id );

		if( $feat_id ) {
			$feat = array( $feat_id => $images[ $feat_id ] );
			unset( $images[ $feat_id ] );
			$images = $feat + $images;
		}

		// set up some variables //
		$numpages = count( $images );
		$pages = array_keys( $images );
	
		// normal attachment galleries
		if( is_attachment( $post->ID ) ) {
			$page = array_search( $post->ID, $pages );
		}
		// for legacy galleries
		else {
			$page = array_search( $feat_id, $pages );
		}

		if( $numpages >= 1 ) {
			// build the previous page link
			if( 0 === $page ) {
				$output .= '<span class="prev button disabled">' . $text_prev . '</span>';
			}
			else {
				$i = $page - 1;
				// pages[$i] is the post_id of the previous attachment
				$url = get_permalink( $pages[$i] );

				$output .= '<a class="prev button" href="' . esc_url( $url ) . '">';
				$output .= $text_prev . '</a>';
			}

			// build the next page link
			if( $page == $numpages - 1 ) {
				$output .= '<span class="next button disabled">' . $text_next . '</span>';	
			}
			else {
				// because $page is not zero indexed 
				$i = $page + 1;
				// pages[$i] is the post_id of the attachment
				$url = $url = get_permalink( $pages[$i] );

				$output .= '<a class="next button" href="' . esc_url( $url ) . '">';
				$output .= $text_next . '</a>';
			}

			// for the actual display add 1 so that when we add the count_text
			// it looks like 1 of 3 instead of 0 of 3;
			$page += 1;
	 	}

	 	return $output;
	}


	/**
	 The Current Slide's Main Content (image or video) Stuff
	 */

	/**
	 * Retrieve the main content of the current slide / gallery page.
	 *
	 * @param post_id (int) the post id of a post with post-format 'gallery'
	 * @param content (str) the content from which to extract the slides main content from
	 */
	public function get_media( $post_id=null, $content='' )
	{
		$r = array(); // return value

		// try to grab the embed from the content
		$embed = $this->_get_content_embed( $content );
		if( $embed ) {
			// build the return array
			$r['html'] = $embed;
			$r['type'] = 'embed';	
		}
		else {
			// try to grab the image from the content
			$img = $this->_get_content_image( $content );

			// last resort... if no image / no embed then grab the featured image
			if( !$img )
				$img = $this->_get_featured_image( $post_id );

			// build the return array
			$src 		= $img['src'];
			$size 		= $img['size'];
			$lazy_load 	= false;

			$r['html'] 		= $this->_build_image_html( $src, $img, $size, $lazy_load );
			$r['width'] 	= $img['width'];
			$r['height'] 	= $img['height'];
			$r['type'] 		= 'image';
		}

		return $r;
	}


	/**
	 * Retrieves the first embedded content
	 */
	private function _get_content_embed( $content='' )
	{
		if( !$content )
			$content = get_the_content();

		preg_match( '/<iframe[^>]*>(.*?)<\/iframe>|<embed[^>]*>|<object[^>]*>(.*?)<\/object>/i', $content, $matches );

		$video = $matches ? $matches[0] : '';

		return $video;
	}


	/**
	 * Uses a regex to extract the ID of the first image from the post content that was
	 * passed in and retrieves image data 
	 *
	 * @uses _get_image_by_id()
	 *
	 * @param content (str) the text from which to extract the image id
	 *
	 * @return (array|null) Returns an array containing data for the image or null
	 */
	private function _get_content_image( $content='' )
	{
		global $page;

		$r = null; // return value
		
		if( !$content )
			$content = get_the_content();

		// if there is content
		if( $content ) {
			// grab the id of the image from the image class
			preg_match( '/<img[^>]+class=["\'][^"\']+wp-image-([a-zA-Z0-9]+)[^"\']*?["\'].+?>/', $content, $matches );

			$id = $matches ? intval( $matches[1] ) : '';

			$r 	= $this->_get_image_by_id( $id );
		}

		return $r;
	}


	/**
	 * Retrieves the data for the featured image 
	 *
	 * @uses _get_image_by_id()
	 *
	 * @param post_id (int) the post ID
	 *
	 * @return (array|null) Returns an array containing data for the image or null
	 */
	private function _get_featured_image( $post_id=null )
	{
		if( !$post_id )
			$post_id = get_the_ID();

		$id = get_post_thumbnail_id( $post_id );
		$r 	= $this->_get_image_by_id( $id );

		return $r;
	}


	/**
	 * Retrieves image data using an image ID
	 *
	 * @param $id (int) the id of the image to retrieve data for
	 */
	private function _get_image_by_id( $id )
	{
		if( !$id )
			return null;

		$r = array(); // return value

		$size = 'xlarge';
		$img = nu_media_utils::get_img_src( $id, $size );
		$src = $img['sizes'][ $size ]['src'];

		// if the large size doesn't exist... get the full size
		if( !$src ) {
			$size = 'full';
			$img = nu_media_utils::get_img_src( $id, $size );
			$src = $img['sizes'][ $size ]['src'];
		}

		$r['src'] 		= $src;
		$r['size'] 		= $size;
		$r['sizes']		= $img['sizes'];
		$r['width'] 	= $img['sizes'][$size]['width'];
		$r['height'] 	= $img['sizes'][$size]['height'];

		return $r;
	}


	/**
	 * Builds the image html
	 */
	private function _build_image_html( $src, $img, $size, $lazy_load )
	{
		// set the height and width
		$w = $img['sizes'][ $size ]['width'];
		$h = $img['sizes'][ $size ]['height'];

		$title_attr = the_title_attribute( array( 'echo' => 0 ) );

		$html = '<img class="aligncenter '. $size .'" title="' . $title_attr . '" alt="' . $title_attr . '" src="' . $src . '" width="' . $w . '" height="' . $h . '"/>';	

		return $html;
	}


	/**
	 The Current Slide's Text
	 */

	 /**
	  * @param $content (str) the original post content
	  */
	public function get_description( $content='' )
	{
		if( !$content ) {
			$content = apply_filters( 'the_content', get_the_content( '', false ) );
		}

		// strip out the images and the videos
		if( is_single() && 'gallery' == get_post_format() ) {
			
			// strip out the images from the body content
			$content = preg_replace( '/(<p[^>]*>)*(<a[^>]+><img[^>]+><\/a>){1}(<\/p>)*|(<p[^>]*>)*(<img[^>]+>){1}(<\/p>)*/i', '', $content );

			// strip out any embedded stuff from the body content
			$content = preg_replace( '/<iframe[^>]*>(.*?)<\/iframe>|<embed[^>]*>|<object[^>]*>(.*?)<\/object>/i', '', $content );
		}

		// remove leading/trailing whitespace
        $content = trim( $content );

		return $content;
	}


	/**
	 Javascript / JSON API functions and helpers
	 */

	/**
	 * Adds new controllers to the list of available controllers on the JSON-API settings page
	 */
	public function add_jsonapi_controller( $controllers )
	{
		$controllers[] = 'gallery';
		return $controllers;
	}


	/**
	 * Sets the correct path to the json_api_features_controller because by default JSON-API 
	 * assumes that the controller is in the json-api/controller directory 
	 *
	 * @uses json_api_[controller]_controller_path filter
	 */
	public function set_jsonapi_gallery_controller_path( $path )
	{
		$path = NG_PATH . 'core/controllers/json-api-gallery.php';
		return $path;
	}

	
	public function reg_js()
	{
		$bn = NG_PLUGIN_NAME;

		$base_uri = NG_URL;

		nu_lazy_load_js::reg_js( $base_uri . 'inc/js/jquery.gallery.js', array('jquery', 'backbone'), NG_PLUGIN_VERSION, true, $bn );
	}


	public function load_js( $scripts )
	{
		$bn = NG_PLUGIN_NAME;

		$scripts['singular'][] = $bn . '-jquery_gallery';

		return $scripts;
	}


	/**
	 * Used to add javascript variables to a page
	 *
	 * Generates something that looks like this:
	 * 
	 *  <script type="text/javascript">
	 *	var js_vars = {
   	 *		key: value
	 *	};
	 *  </script>
	 *
	 * @see http://www.garyc40.com/2010/03/5-tips-for-using-ajax-in-wordpress/
	 *
	 * @uses wp_localize_script()
	 */
	public function set_js_vars()
	{	
		global $post;

		$page = get_query_var( 'page' );

		$bn = NG_PLUGIN_NAME;

		if( 'gallery' == get_post_format() ) {
	
			$data = array( 
				'data_url'			=> $this->get_json_url(),
				'post_id' 			=> $post->ID,
				'current_page' 		=> 0 == $page ? 1 : $page,
				'numpages'			=> $this->get_numpages(),
				'next_json'			=> $this->get_next_json_link(),
				'prev_json'			=> $this->get_prev_json_link(),
				'next_link'			=> $this->get_next_link(),
				'prev_link'			=> $this->get_prev_link(),
				'refresh_threshold'	=> naked_gallery_get_option( 'refresh_threshold' ),
			);

			$script = $bn . '-jquery_gallery';
			$var = 'naked_gallery';
			
			wp_localize_script( $script, $var, $data );
		}
	}


	/**
	 * Retrieves the next gallery page link. If on the last page of a gallery
	 * then the next gallery is retrieved instead.
	 *
	 * @todo do next gallery
	 */
	public function get_next_json_link( $id=null )
	{
		return $this->get_adjacent_link( $id, 'next', 'json' );
	}


	/**
	 * Retrieves the prev gallery page link. If on the last page of a gallery
	 * then the prev gallery is retrieved instead.
	 *
	 * @todo do prev gallery
	 */
	public function get_prev_json_link( $id=null )
	{
		return $this->get_adjacent_link( $id, 'prev', 'json' );
	}


	/**
	 * Retrieves the next gallery page link
	 */
	public function get_next_link( $id=null )
	{
		return $this->get_adjacent_link( $id, 'next', 'permalink' );
	}


	/**
	 * Retrieves the prev gallery page link
	 */
	public function get_prev_link( $id=null )
	{
		return $this->get_adjacent_link( $id, 'prev', 'permalink' );
	}


	public function get_adjacent_link( $id=null, $var='next', $type='json' )
	{
		global $post;

		$page = get_query_var( 'page' );

		$content_model = naked_gallery_model::get_instance();

		if( !$id )
			$id = $post->ID;

		$numpages = $this->get_numpages();
		
		// if not set then we are on page 1
		if( !isset( $page ) || 0 == $page )
			$page = 1;

		// check to see if need to grab the link for the next / previous gallery
		if( ( $numpages == $page && 'next' == $var ) 
			|| ( 1 == $page && 'prev' ==$var ) ) {

			if( 'next' == $var ) $gallery = $content_model->get_next_gallery();
			elseif( 'prev' == $var ) $gallery = $content_model->get_prev_gallery();

			if( $gallery ) {
				if( 'json' == $type ) {
					$params = array(
						'id' 	=> (int) $gallery->ID,
						'page'	=> 1
					);

					$link = esc_url_raw( $this->get_json_url() . http_build_query( $params ), array( 'http', 'https' ) );
				}
				else {
					$link = get_permalink( $gallery->ID );
				}
			}
			else {
				$link = '#';
			}
		}
		// if no grab the link for the next / previous page of the same gallery
		else {

			$adj_page = $var == 'next' ? $page + 1 : $page - 1;

			if( 'json' == $type ) {

				$params = array(
					'id' 	=> (int) $id,
					'page'	=> $adj_page
				);

				$link = esc_url_raw( $this->get_json_url() . http_build_query( $params ), array( 'http', 'https' ) );
			}
			else {
				$link = get_permalink( $id ) . $adj_page . '/';
			}
		}

		return $link;
	}


	public function get_numpages()
	{
		global $post;

		$numpages = substr_count( $post->post_content, '<!--nextpage-->' );
		$numpages += 1; // this is zero indexed so we add 1
		
		return $numpages;
	}



	public function get_json_url()
	{
		$siteurl 	= trailingslashit( get_site_url() );
		$api_base 	= get_option( 'json_api_base' );

		if( '' == $api_base )
			$api_base = 'api';

		$url = $siteurl . $api_base . '/gallery/get_gallery/?';

		return $url;  
	}


	public function load_js_template()
	{
		if( 'gallery' == get_post_format() ) {
			$dir = NG_PATH . 'core/views/';
			include( $dir . 'backbone-tpl.inc' );
		}
	}
}