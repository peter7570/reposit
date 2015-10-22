<?php

class Dfapi {

    function informersdictionary() {

        $url = 'http://admin.digital-forest.info/rest/informersdictionary';
        $args = array(
        'timeout'     => 5,
        'redirection' => 5,
        'httpversion' => '1.0',
        'user-agent'  => 'WordPress/' . $wp_version . '; ' . get_bloginfo( 'url' ),
        'blocking'    => true,
        'headers'     => array(),
        'cookies'     => array(),
        'body'        => null,
        'compress'    => false,
        'decompress'  => true,
        'sslverify'   => true,
        'stream'      => false,
        'filename'    => null
        );
        $remote_get = wp_remote_get( $url, $args );

        $results_arr = json_decode( $remote_get['body'] );
        foreach( $results_arr as $result ) {
        update_option( 'name_'. $result->id, $result->name );
        update_option( 'type_'. $result->id, $result->type ); 
        update_option( 'max_id', $result->id ); 
        update_site_option( 'name_'. $result->id, $result->name );
        update_site_option( 'type_'. $result->id, $result->type ); 
        update_site_option( 'max_id', $result->id );
         
        }

    }
    
    
    function createsite() {
        
        $post_settings = array(
        'name' => get_bloginfo( 'name' ),
        'address' => get_home_url(),
        'staturl' => get_home_url(),
        'userid' => get_site_option( 'userid' )
        );

        $url = 'http://admin.digital-forest.info/rest/createsite';
        $args = array(
        'method' => 'POST',
        'timeout' => 45,
        'redirection' => 5,
        'httpversion' => '1.0',
        'blocking' => true,
        'headers' => array(),
        'body' => $post_settings,
        'cookies' => array()
        );
    
        $response = wp_remote_post( $url, $args );

        if ( is_wp_error( $response ) ) {
        $error_message = $response->get_error_message();
        echo "Что-то пошло не так: $error_message";
        } else {

        $result = json_decode( $response['body'] );
        if( $result->status ) return $result; 
  
        }

    }

    function informercode($content) {

        $tip = $content;
        $post_settings = array(
        'name' => get_bloginfo('name').$tip,
        'typeid' => $tip,
        'siteid' => get_option('siteid'),
        'userid' => get_site_option('userid')
        );

        $url = 'http://admin.digital-forest.info/rest/informercode';
        $args = array(
       'method' => 'POST',
       'timeout' => 45,
       'redirection' => 5,
       'httpversion' => '1.0',
       'blocking' => true,
       'headers' => array(),
       'body' => $post_settings,
       'cookies' => array()
       );
    
       $response = wp_remote_post( $url, $args );
    
          if ( is_wp_error( $response ) ) {
             $error_message = $response->get_error_message();
             echo "Что-то пошло не так: $error_message";
          } else {
             $result = json_decode( $response['body'] );
                if( $result->status ) {
                   return $code = $result->code;
                }
          }
    }
} 

?>
