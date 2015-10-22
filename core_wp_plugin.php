<?php

function smn_digitalforest_init(){

    add_action( 'widgets_init', 'register_digit_widget' ); 
    add_filter( 'the_content', 'the_content_add' );

    }
    
    add_action( 'plugins_loaded', 'smn_digitalforest_init' );
    
function the_content_add( $content = '' ) {
    $dfapi = new Dfapi();
    $fff = '';
    $blog_id = get_current_blog_id();
    $proc = 0;
    
    if( get_site_option('forallsiteid') && get_option('siteid') ) {
        // begin 
        if( get_site_option('code_begin_tip') ) {
            $tip = get_site_option('code_begin_tip');

                if( get_site_option('is_create_begin'.$blog_id) !== get_option('siteid') ){
                    $code = $dfapi->informercode( $tip ); 
                    update_site_option('is_create_begin'.$blog_id, get_option('siteid'));
                    update_option('code_begin',$code);

                }
            $content =  stripslashes( get_option('code_begin') ) . $content;   
        }
        //middle
        if( get_site_option('code_middle_tip') ) { 
        
        $tip = get_site_option('code_middle_tip');

                if( get_site_option('is_create_middle'.$blog_id) !== get_option('siteid') ){
                    $code = $dfapi->informercode( $tip ); 
                    update_site_option('is_create_middle'.$blog_id, get_option('siteid'));
                    update_option('code_middle',$code);

                }        
        $code = $dfapi->informercode( $tip );
        $middle = intval( mb_strlen($content ) / 2);
        $positions = get_occurrences( $content, "</p>" );
        $positions = array_merge( $positions, get_occurrences( $content, "</div>" ) );
        $positions = array_merge( $positions, get_occurrences( $content, "</ul>" ) );
        $positions = array_merge( $positions, get_occurrences( $content, "</ol>" ) );
        $positions = array_merge( $positions, get_occurrences( $content, "</pre>" ) );
        $deviations = array();
        foreach ( $positions as $pos ) {
           $diff = abs( $pos - $middle );
           $deviations[$diff] = $pos;
           }
        ksort( $deviations );
        $final = array_shift( $deviations );
        if ( $final > 0 ) {
           $content = substr( $content, 0, $final - 1 ) . stripslashes( get_option('code_middle') ) . substr( $content, $final - 1 );
           } else {
            $content =  stripslashes( get_option('code_middle') ) . $content ;
           }
        }
        //after
        if( get_site_option('code_after_tip' ) ) {
        
        $tip = get_site_option('code_after_tip');

        if( get_site_option('is_create_after'.$blog_id) !== get_option('siteid') ){
                    $code = $dfapi->informercode( $tip ); 
                    update_site_option('is_create_after'.$blog_id, get_option('siteid'));
                    update_option('code_after',$code);

                }
        $content =  $content . stripslashes( get_option('code_after') );
        
        }
        
    }   

    if( get_site_option('forall') ) {     

    for($i=0;$i<get_site_option('all_id_max')+1;$i++){
    if( $blog_id == get_site_option('all_id'.$i) ) $proc = 1; 
    }

    if( $proc ) {
    
    if ( !get_option('siteid') ) {
            $result = $dfapi->createsite(); 
            update_option('siteid', $result->siteid);
    }    
        
        //begin 
    if( get_site_option('code_begin_tip') ) 
        { 

            $tip = get_site_option('code_begin_tip');

                if( get_site_option('is_create_begin'.$blog_id) !== get_option('siteid') ){
                    $code = $dfapi->informercode( $tip ); 
                    update_option('code_begin',$code);
                    update_site_option('is_create_begin'.$blog_id, get_option('siteid'));

                }

            $content =  stripslashes( get_option('code_begin') ) . $content;

        } 
        //middle
    if( get_site_option('code_middle_tip') ) 
    {

        $tip = get_site_option('code_middle_tip');

        if( get_site_option('is_create_middle'.$blog_id) !== get_option('siteid') ){
                    $code = $dfapi->informercode( $tip ); 
                    update_option('code_middle',$code);
                    update_site_option('is_create_middle'.$blog_id, get_option('siteid')); 

                }
        
        $middle = intval( mb_strlen($content ) / 2);
        $positions = get_occurrences( $content, "</p>" );
        $positions = array_merge( $positions, get_occurrences( $content, "</div>" ) );
        $positions = array_merge( $positions, get_occurrences( $content, "</ul>" ) );
        $positions = array_merge( $positions, get_occurrences( $content, "</ol>" ) );
        $positions = array_merge( $positions, get_occurrences( $content, "</pre>" ) );
        $deviations = array();
        foreach ( $positions as $pos ) {
           $diff = abs( $pos - $middle );
           $deviations[$diff] = $pos;
           }
        ksort( $deviations );
        $final = array_shift( $deviations );
        if ( $final > 0 ) {
           $content = substr( $content, 0, $final - 1 ) . stripslashes( get_option('code_middle') ) . substr( $content, $final - 1 );
           } else {
            $content =  stripslashes( get_option('code_middle') ) . $content ;
           }
    }  
        //after
    if( get_site_option('code_after_tip' ) ) 
    { 

        $tip = get_site_option('code_after_tip');

        if( get_site_option('is_create_after'.$blog_id) !== get_option('siteid') ){
                    $code = $dfapi->informercode( $tip );
                    update_site_option('is_create_after'.$blog_id, get_option('siteid')); 
                    update_option('code_after',$code);

                }
        
        $content =  $content . stripslashes( get_option('code_after') ); 
    }     
    }
    }
    
    return $content;
  
}
 
function get_occurrences( $content, $what ) {
    $result = array();
    $pos = 0;
    while($pos !== false) {
       $pos = strpos( $content, $what, $pos );
       if ( $pos === false ) {
           return $result;
       }
       $pos += mb_strlen( $what ) + 1;
       array_push( $result, $pos );
       if ( $pos >= mb_strlen($content) ) {
           return $result;
       }
    }
    return $result;
}

function register_digit_widget(){
register_widget( 'DigitWidget' );
}


class DigitWidget extends WP_Widget {
    
    function DigitWidget() {

        $widget_ops = array( 'classname' => 'Digital Forest', 'description' => __('Виджет вывода информера ', 'example') );
        $control_ops = array( 'width' => '', 'height' => '', 'id_base' => 'digit-widget' );
        $this->WP_Widget( 'digit-widget', __('Digital Forest', 'Digital Forest'), $widget_ops, $control_ops );
    }

    public function form( $instance ) {

        if ( !empty( $instance ) ) {
            $tip = $instance["cat"];
            $dfapi = new Dfapi();
            $code = $dfapi->informercode( $tip ); 
            update_option('code'.$tip,$code);    
        }
        
        echo '<br><label for="' . $this->get_field_id("cat") . '">Выберите тип:</label><br>';
        echo '<select name="' . $this->get_field_name("cat") . '" id="'.$this->get_field_id("cat") . '">';
        for( $i=1; $i<get_site_option('max_id')+1 ;$i++ ){
            echo '<option value="' . $i . '"' . selected( $instance['cat'], $i, false ) . '>' . get_site_option('name_'.$i) . '</option>';
        }
        echo '</select><br>';
    }

    public function update( $newInstance, $oldInstance ) {
        $values = array();
        $values["cat"] = htmlentities( $newInstance["cat"] ); 
        return $values;
    }

    public function widget( $args, $instance ) {
        echo get_option('code'.$instance["cat"]);
    }
 
}


    



