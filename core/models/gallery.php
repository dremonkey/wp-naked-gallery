<?php

/**
 * Gallery Model
 *
 * Retrieves content from the database
 *
 * @package Naked Gallery
 * @since 0.1
 */

class naked_gallery_model extends nu_singleton
{
	private static $debug = false;

	protected function __construct()
	{
		// not used
	}

	public function get_next_gallery( $id=null ) {
		return $this->get_adjacent_gallery( $id, false );
	}


	public function get_prev_gallery( $id=null ) {
		return $this->get_adjacent_gallery( $id, true );
	}


	public function get_adjacent_gallery( $id=null, $previous=true ) {
		global $post, $wpdb;

		if ( empty( $post ) && $id ) {
			if( $id )
				$post = get_post( $id );
			else
				return null;
		}

		$current_post_date = $post->post_date;

		// get the term ids for the gallery and video gallery post formats
		$formats = $this->_get_format_ids();

		$join = " INNER JOIN $wpdb->term_relationships AS tr ON p.ID = tr.object_id INNER JOIN $wpdb->term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id AND tt.taxonomy = 'post_format' AND tt.term_id IN (" . implode(',', $formats) . ")";

		$adjacent = $previous ? 'previous' : 'next';
		$op = $previous ? '<' : '>';
		$order = $previous ? 'DESC' : 'ASC';

		$where = $wpdb->prepare("WHERE p.post_date $op %s AND p.post_type = %s AND p.post_status = 'publish'", $current_post_date, $post->post_type);

		$sort  = "ORDER BY p.post_date $order LIMIT 1";

		$query = "SELECT p.* FROM $wpdb->posts AS p $join $where $sort";
		$query_key = 'adjacent_gallery_' . md5($query);
		$result = wp_cache_get($query_key, 'counts');

		if ( false !== $result )
			return $result;

		$result = $wpdb->get_row( $query );

		// if no null we are on the first / lastest gallery post so if we are on the latest gallery then use the first gallery as the 'next' gallery post. And if we are on the first gallery then use the latest gallery as the 'prev' gallery post.
		if ( null === $result ) {

			$where = $wpdb->prepare("WHERE p.post_type = %s AND p.post_status = 'publish'", $post->post_type);

			$query = "SELECT p.* FROM $wpdb->posts AS p $join $where $sort";
			$query_key = 'adjacent_gallery_' . md5($query);

			$result = wp_cache_get($query_key, 'counts');

			if ( false !== $result )
				return $result;

			$result = $wpdb->get_row( $query );
		}

		wp_cache_set($query_key, $result, 'counts');

		if( self::$debug )
			nu_debug( "Adjacent Gallery", array( $query, $result ) );

		return $result;
	}


	private function _get_format_ids()
	{
		$ids = array();

		// term names
		$names = array( 'Gallery' );

		$formats = get_terms( 'post_format' );

		foreach( $formats as $format ) {
			foreach( $names as $name ) {
				if( strtolower( $name ) == strtolower( $format->name ) )
					$ids[ $format->slug ] = $format->term_id; 
			}
		}

		return $ids;
	}

}