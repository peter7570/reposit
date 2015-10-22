<?php

function smn_digitalforest_admin_menu_setup() {
    $message = null;
    $page_hook_suffix = add_submenu_page(
        'options-general.php',
        'Digitalforest Settings',
        'Digitalforest Settings',
        'Digitalforest',
        'smn_digitalforest',
        'smn_digitalforest_admin_page_screen'
    );

}

add_action('admin_menu', 'smn_digitalforest_admin_menu_setup');

function smn_digitalforest_admin_page_screen() {
    global $submenu;
    $dfapi = new Dfapi();
    $page_data = array();
    $userid = get_site_option('userid');
     
    foreach ($submenu['options-general.php'] as $i => $menu_item) {
        if ($submenu['options-general.php'][$i][2] == 'smn_digitalforest') {
            $page_data = $submenu['options-general.php'][$i];
        }
    }
    if ( isset($_POST['submit_userid']) ) 
        {    
            $message = 'Userid сохранен';
            if ( function_exists('current_user_can') && !current_user_can('manage_options') ) die ( _e('Hacker?', 'admin_userid') );
            if ( function_exists ('check_admin_referer') ) check_admin_referer('userid_form');

            $userid = $_POST['userid'];
            update_site_option('userid', $userid);
            
        }
        
    if ( isset($_POST['submit_informersdictionary']) ) 
        {    
            $message = 'Типы информеров обновлены';
            if ( function_exists('current_user_can') && !current_user_can('manage_options') ) die ( _e('Hacker?', 'admin_informersdictionary') );
            if ( function_exists ('check_admin_referer') ) check_admin_referer('informersdictionary_form');

            $dfapi->informersdictionary();
                   
           echo '<br>test='.get_option('name_4');
            
        }
        
    if ( isset($_POST['submit_createsite']) ) 
        {    
            $message = 'Сайт зарегестрирован';
            if ( function_exists('current_user_can') && !current_user_can('manage_options') ) die ( _e('Hacker?', 'admin_createsite') );
            if ( function_exists ('check_admin_referer') ) check_admin_referer('createsite_form');

            $result = $dfapi->createsite();
            update_option('siteid', $result->siteid); 
   
           echo 'test='.get_option('siteid');
            
        }
   
   
    if ( isset($_POST['submit_adcode']) ) 
        { 
            $message = 'Информеры добавлены';
            if ( function_exists('current_user_can') && !current_user_can('manage_options') ) die ( _e('Hacker?', 'admin_createsite') );
            if ( function_exists ('check_admin_referer') ) check_admin_referer('adcode_form');
            
            update_option("code_begin_tip", null); 
            update_option("code_middle_tip", null); 
            update_option("code_after_tip", null);
            
            update_site_option("code_begin_tip", null); 
            update_site_option("code_middle_tip", null); 
            update_site_option("code_after_tip", null);

            update_site_option('forallsiteid', null);
            update_site_option('forall', null); 

            if ( $_POST['forall'] == 'forall' ) {
                
                global $wpdb;
                $blogs = $wpdb->get_results("
                SELECT blog_id
                FROM {$wpdb->blogs}
                WHERE site_id = '{$wpdb->siteid}'
                AND spam = '0'
                AND deleted = '0'
                AND archived = '0'
                AND blog_id != 1
                ");
                foreach ($blogs as $i=>$blog) {
                update_site_option('is_create_begin'.$blog->blog_id, 0);
                update_site_option('is_create_middle'.$blog->blog_id ,0);
                update_site_option('is_create_after'.$blog->blog_id ,0);
                update_site_option('all_id'.$i, $blog->blog_id);
                update_site_option('all_id_max', $i);
                }

                $content_begin  = $_POST['adcode_beginning'];
                $content_middle  = $_POST['adcode_middle'];
                $content_after  = $_POST['adcode_after'];
              
                update_site_option('radio_tip',$_POST['forall']);
                update_site_option('forall', 1);

                update_site_option('code_begin_tip',$content_begin); 
                update_site_option('code_middle_tip',$content_middle);
                update_site_option('code_after_tip',$content_after);

                echo '$content_begin_forall='.$content_begin;
                echo '$content_middle_forall='.$content_middle;
                echo '$content_after_forall='.$content_after;
                echo 'radio_tip_forall='.get_site_option('radio_tip');
                echo 'code_begin_tip='.get_site_option('code_begin_tip'); 

            } else if ( isset($_POST['forall']) == 'forallsiteid' ) {
                
                update_site_option('forallsiteid', 1);
                update_site_option('radio_tip',$_POST['forall']);

                global $wpdb;
                $blogs = $wpdb->get_results("
                SELECT blog_id
                FROM {$wpdb->blogs}
                WHERE site_id = '{$wpdb->siteid}'
                AND spam = '0'
                AND deleted = '0'
                AND archived = '0'
                AND blog_id != 1
                ");
                foreach ($blogs as $blog) {
                update_site_option('is_create_begin'.$blog->blog_id,0);
                update_site_option('is_create_middle'.$blog->blog_id,0);
                update_site_option('is_create_after'.$blog->blog_id,0);
                }

                $content_begin  = $_POST['adcode_beginning'];
                $content_middle  = $_POST['adcode_middle'];
                $content_after  = $_POST['adcode_after'];
             
                update_site_option('code_begin_tip',$content_begin); 
                update_site_option('code_middle_tip',$content_middle);
                update_site_option('code_after_tip',$content_after);
                
                update_site_option('ispost_begin',1);
                update_site_option('ispost_middle',1);
                update_site_option('ispost_after',1); 

                echo '$content_begin_forallsiteid='.$content_begin;
                echo '$content_middle_forallsiteid='.$content_middle;
                echo '$content_after_forallsiteid='.$content_after;
                echo 'radio_tip_forallsiteid='.get_site_option('radio_tip');
                echo 'forallsiteid='.get_site_option('forallsiteid'); 

            } else {

            }
  
        }

?>
    <div class="wrap">
    <?php if ($message) : ?>
    <div id="message" class="updated fade"><p><?php echo $message; ?></p></div>
    <?php endif; ?>
        <?php screen_icon(); ?>
        <h2><?php echo $page_data[3]; ?></h2>
        
            <form name="admin_userid" method="post" action="">
                                
                <?php 
                    if (function_exists ('wp_nonce_field') )
                    {
                        wp_nonce_field('userid_form'); 
                    }
                ?>
                
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row"><?php _e('Userid', 'admin_userid'); ?></th>
                        
                        <td>
                            <input type="text" name="userid" size="10" value="<?php echo $userid; ?>" />
                        </td>
                    </tr>

                </table>
                
                <input type="hidden" name="action" value="update" />
                <input type="hidden" name="page_options" value="userid" />
        
                <p class="submit">
                <input type="submit" name="submit_userid" value="Сохранить userid" />
                </p>
            </form>
            
            <form name="admin_informersdictionary" method="post" action="">
                           
                <?php 
                    if (function_exists ('wp_nonce_field') )
                    {
                        wp_nonce_field('informersdictionary_form'); 
                    }
                ?>
                                
                <input type="hidden" name="action" value="update" />
                <input type="hidden" name="page_options" value="informersdictionary" />
        
                <p class="submit">
                <input type="submit" name="submit_informersdictionary" value="Обновить типы информеров" />
                </p>
            </form>
            
            <form name="admin_createsite" method="post" action="">
                            
                <?php 
                    if (function_exists ('wp_nonce_field') )
                    {
                        wp_nonce_field('createsite_form'); 
                    }
                ?>
                                
                <input type="hidden" name="action" value="update" />
                <input type="hidden" name="page_options" value="createsite" />
        
                <p class="submit">
                <input type="submit" name="submit_createsite" value="Регистрировать сайт" />
                </p>
            </form>
        
            <form name="admin_adcode" method="post" action="">
            
                <?php 
                 if (function_exists ('wp_nonce_field') )
                 {
                    wp_nonce_field('adcode_form'); 
                 }

                 echo '<br><label for="">Выберите тип: (adcode_beginning)</label><br>';
                 echo '<select name="adcode_beginning">';
                 echo '<option value="0">Нет информера</option>';
                 for($i=1;$i<get_site_option('max_id')+1;$i++){
                 if ( get_site_option('code_begin_tip') ) echo '<option value="'.$i.'"'.selected( get_site_option('code_begin_tip'), $i, false ). '>' . get_site_option('name_'.$i) . '</option>'; else
                 echo '<option value="'.$i.'">' . get_option('name_'.$i) . '</option>';
                 }
                 echo '</select><br>';
                 

                 echo '<br><label for="">Выберите тип: (adcode_middle)</label><br>';
                 echo '<select name="adcode_middle">';
                 echo '<option value="0">Нет информера</option>';
                 for($i=1;$i<get_site_option('max_id')+1;$i++){
                 if ( get_site_option('code_middle_tip') ) echo '<option value="'.$i.'"'.selected( get_site_option('code_middle_tip'), $i, false ). '>' . get_site_option('name_'.$i) . '</option>'; else
                 echo '<option value="'.$i.'">' . get_option('name_'.$i) . '</option>';
                 }
                 echo '</select><br>';
                 
                 echo '<br><label for="">Выберите тип: (adcode_after)</label><br>';
                 echo '<select name="adcode_after">';
                 echo '<option value="0">Нет информера</option>';
                 for($i=1;$i<get_site_option('max_id')+1;$i++){
                 if ( get_site_option('code_after_tip') ) echo '<option value="'.$i.'"'.selected( get_site_option('code_after_tip'), $i, false ). '>' . get_site_option('name_'.$i) . '</option>'; else
                 echo '<option value="'.$i.'">' . get_option('name_'.$i) . '</option>';
                 }
                 echo '</select><br>';
                 ?>

                 <br><?php    echo '<input name="forall" type="radio" value="forall"'.checked( get_site_option('radio_tip'), 'forall', false ). '>';   ?>
                 <label>Для всех сайтов</label> 
                 
                 <br><?php    echo '<input name="forall" type="radio" value="forallsiteid"'.checked( get_site_option('radio_tip'), 'forallsiteid', false ). '>';   ?> 
                 <label>Для зарегестрированых</label> 
                             
                 <input type="hidden" name="action" value="update" />
                 <input type="hidden" name="page_options" value="adcode" />
        
                 <p class="submit">
                 <input type="submit" name="submit_adcode" value="Добавить информер" />
                 </p>
            
            
            </form>
  
         
    </div>
    <?php
}

function smn_digitalforest_settings_link($actions, $file) {
    if (false !== strpos($file, 'smn-digitalforest')) {
        $actions['settings'] = '<a href="options-general.php?page=smn_digitalforest">Settings</a>';
    }
 
    return $actions;
}
 
add_filter('plugin_action_links', 'smn_digitalforest_settings_link', 2, 2);

function smn_digitalforest_settings_init() {
    register_setting(
        'smn_digitalforest_options',
        ''
    );
 
    add_settings_section(
        'smn_digitalforest_authorbox',
        'Author\'s box',
        '',
        'smn_digitalforest'
    );
 
    add_settings_field(
        'smn_digitalforest_authorbox_template',
        'Template',
        'smn_digitalforest',
        ''
        
    );
}
 
add_action('admin_init', 'smn_digitalforest_settings_init');

