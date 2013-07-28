<?php

/*
Controller name: Gallery
Controller description: Retrieves a gallery post
*/

class json_api_gallery_controller
{

    /**
     * @todo need to modify this function to work with videos too
     */
    public function get_gallery() {
        global $json_api;
        
        extract($json_api->query->get(array('id', 'slug', 'post_id', 'post_slug')));
        
        if ($id || $post_id) {
            
            if ( !$id ) {
                $id = $post_id;
            }
            
            $posts = $json_api->introspector->get_posts( array(
                'p' => $id
            ), true );
        } 
        else if ($slug || $post_slug) {
            if ( !$slug ) {
                $slug = $post_slug;
            }
                $posts = $json_api->introspector->get_posts( array(
                    'name' => $slug
                ), true );
        } 
        else {
          $json_api->error("Include 'id' or 'slug' var in your request.");
        }
        

        if ( count( $posts ) == 1 ) {
            $post   = $posts[0];
            $id     = $post->ID;

            $response['next_json'] = $this->_get_next_json( $id );
            $response['next_link'] = $this->_get_next_link( $id );
            $response['prev_json'] = $this->_get_prev_json( $id );
            $response['prev_link'] = $this->_get_prev_link( $id );
            
            $response['numpages'] = $this->_get_numpages();

            $post = new JSON_API_Post($post);

            // get the current image
            $response['media'] = $this->_get_media( $post );
            $response['page'] = $json_api->query->get('page');

            // remove the image or embed from the the post content
            $response['post'] = $this->_get_content( $post );

            return $response;
        } 
        else {
          $json_api->error("Not found.");
        }
    }


    /**
     * Uses a regex to extract the currently gallery image id
     *
     * @todo The size should be set via an admin page
     */
    private function _get_media( $post )
    {
        $gallery    = naked_gallery_controller::get_instance();
        $media      = $gallery->get_media( $post->id, $post->content );

        return $media;
    }


    private function _get_content( $post )
    {
        $gallery = naked_gallery_controller::get_instance();
        $content = $gallery->get_description( $post->content );

        $post->content = $content;

        return $post;
    }


    private function _get_numpages()
    {
        $gallery = naked_gallery_controller::get_instance();

        $numpages = $gallery->get_numpages();

        return $numpages;
    }


    private function _get_next_json( $id )
    {
        $gallery = naked_gallery_controller::get_instance(); 

        $link = $gallery->get_next_json_link( $id ); 

        return $link; 
    }


    private function _get_next_link( $id )
    {
        $gallery = naked_gallery_controller::get_instance(); 

        $link = $gallery->get_next_link( $id ); 

        return $link; 
    }


    private function _get_prev_json( $id )
    {
        $gallery = naked_gallery_controller::get_instance();

        $link = $gallery->get_prev_json_link( $id );

        return $link;
    }


    private function _get_prev_link( $id )
    {
        $gallery = naked_gallery_controller::get_instance(); 

        $link = $gallery->get_prev_link( $id ); 

        return $link; 
    }
}