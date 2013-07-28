<?php

/**

 Temporary. To be removed / disabled when update is complete

 */

class naked_gallery_update_legacy_galleries
{
	private static $debug = false;
	private static $opts_name = 'ng_update_data';
	private static $throttle_transient = 'ng_throttle_update';
	private static $throttle_time = 60; // in seconds
	private static $max_updates_per_loop 	= 10; // how many will be fixed at once
	private static $start_date = '2012-05-31'; // YYYY-MM-DD


	public function __construct()
	{
		// for testing
		// if( is_admin() ) {
		// 	$this->_reset_throttle_and_options();
		// }

		// check to see if the update should be run
		if( $this->_do_update() ) {
			// run the update
			add_action( 'init', array( &$this, '_update' ) );
		}
	}


	private function _do_update()
	{
		$update 	= false;
		$todo 		= $this->_get_option( 'todo' );
		$throttle 	= get_transient( self::$throttle_transient );

		// check to see if we need to throttle the updating 
		if( !$throttle ) {
			// if no throttle check to see if there are any posts left to update
			if( null === $todo || ( is_array( $todo ) && !empty( $todo ) ) )
				$update = true;
		}

		if( self::$debug )
			nu_debug( 'Legacy Gallery Remaining Posts to Update', array( 'throttle'=>$throttle, 'todo' => $todo, 'update' => $update ) );

		$update = false;
		return $update;
	}


	public function _update()
	{
		$updated 	= array();
		$check 		= array();

		if( self::$debug )
			nu_debug( 'Running Legacy Update', 'running' );

		// grab the posts that need to be fixed
		$todo = $this->_get_todo();

		// take a subset of the posts that needs to be fixed
		$todo2 = array_slice( $todo, 0, self::$max_updates_per_loop );

		// flip the array to make item removal simplier
		$f_todo = array_flip( $todo );

		// If no post content and has attachments this is a legacy gallery, so we will put attachments + page breaks into the post content and save.
		foreach ( $todo2 as $postid ) {
			// if no post content grab the attachments
			$media = get_children( array(
				'post_parent' 	=> $postid,
				'post_type' 	=> 'attachment'
			) );

			if( self::$debug )
				nu_debug( 'Attachments', $media );

			// change the post formate
			set_post_format( $postid, 'gallery' );

			// update the post with the new content   
			$data['ID'] = $postid;
			$data['post_content'] = $this->_insert_media_in_content( $media, $postid );
			
			$postid = wp_update_post( $data );
			if( 0 !== $postid ) $updated[] = $postid;
			else $check[] = $postid;

			// remove the post from the todo list
			unset( $f_todo[ $postid ] );
		}

		// set the throttle transient
		set_transient( self::$throttle_transient, 1, self::$throttle_time );

		// save updated / unsuccessful updates to the options table
		$this->_record_updated( $updated );
		$this->_record_check( $check );

		// flip, remap, and save the todo list
		$todo = array_values( array_flip( $f_todo ) );
		$this->_set_option( 'todo', $todo );
	}


	private function _get_todo()
	{
		// get the remaining posts to fix
		$todo = $this->_get_option( 'todo' );

		// if false, then this has never been run so build the todo list
		if( null === $todo ) {

			$todo = array();

			// alter the where statement so that the query only retrieves posts before the day that we did all the importing
			add_filter( 'posts_where', array( &$this, '_filter_where' ) );

			// grab the posts
			$args = array( 
				'post_type' 		=> 'post', 
				'post_status' 		=> 'publish',
				'posts_per_page' 	=> -1,
				'nopaging'			=> true,
			);

			$query = new WP_Query( $args );
			$posts = $query->posts;

			if( self::$debug )
				nu_debug( 'All Posts', array( $query->request ) );

			// remove the previously added alteration to the where filter otherwise it will be applied to all queries
			remove_filter( 'posts_where', array( &$this, '_filter_where' ) );

			// loop through posts and check to see if it has no post content.
			foreach ( $posts as $post ) {
				if( '' == trim( $post->post_content ) ) {
					$todo[] = $post->ID;
				}
			}

			// save the todo list
			$this->_set_option( 'todo', $todo );
		}

		if( self::$debug )
			nu_debug( 'Legacy Galleries TODO', $todo );

		return $todo;
	}


	/**
	 * @todo change the date
	 */
	public function _filter_where( $where='' )
	{
		global $wpdb, $blog_id;

		$table 		= $wpdb->posts ;
		$start_date = self::$start_date;		

		$where .= " AND $table.post_date <= '$start_date'";

		return $where; 
	}


	/**
	 * @param media (array) an array of attachment post objects 
	 */
	private function _insert_media_in_content( $media, $parent_id )
	{
		$ids 		= array_keys( $media );
		$content 	= '';

		foreach( $ids as $i=>$id ) {
			
			if( 0 !== $i ) $content .= '<!--nextpage-->';

			$title 	= esc_attr( strip_tags( get_the_title( $parent_id ) ) );
			$img 	= nu_media_utils::get_img_src( $id, 'large' );
			$src 	= $img['sizes']['large']['src'];
			$w 		= $img['sizes']['large']['width'];
			$h 		= $img['sizes']['large']['height'];

			$content .= sprintf( "<img class=\"aligncenter size-full wp-image-%d\" title=\"%s\" alt=\"%s\" src=\"%s\" width=\"%d\" height=\"%d\" />", $id, $title, $title, $src, $w, $h );
		}

		return $content;
	}


	private function _record_updated( $postids ) 
	{
		$updated = $this->_get_option( 'updated' );

		if( null === $updated )
			$updated = array();

		$updated = array_merge( $updated, $postids );
		$this->_set_option( 'updated', $updated );
	}


	private function _record_check( $postids ) 
	{
		$check = $this->_get_option( 'check' );

		if( null === $check )
			$check = array();

		$check = array_merge( $check, $postids );
		$this->_set_option( 'check', $check );
	}


	private function _get_option( $key ) 
	{
		$name 	= self::$opts_name;
		$opts 	= get_option( $name );

		if( isset( $opts[ $key ] ) )
			return $opts[ $key ];

		return null;
	}


	private function _set_option( $key, $val ) 
	{
		$name 	= self::$opts_name;
		$opts 	= get_option( $name, null );

		if( null === $opts ) {
			$opts 			= array();
			$opts[ $key ] 	= $val;

			// add the option
			add_option( $name, $opts, '', 'no' );
		}
		else {
			$opts[ $key ] 	= $val;

			// update the option
			update_option( $name, $opts );
		}
	}


	/**
	 * Convenience function to reset the throttle and options so that the update runs
	 */
	private function _reset_throttle_and_options()
	{
		delete_option( self::$opts_name );
		delete_transient( self::$throttle_transient );
	}
}