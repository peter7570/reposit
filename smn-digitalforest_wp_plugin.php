<?php

/*
  Plugin Name: Smn Digital Forest (DF)
  Description: Create informers
  Version: 1.0
  Author:  Peter Yurzhenko
  Author URI: 
  Plugin URI: 
 */

define('SMN_DIGITALFOREST_DIR', plugin_dir_path(__FILE__));
define('SMN_DIGITALFOREST_URL', plugin_dir_url(__FILE__));

function smn_digitalforest_load() {

    if (is_admin()) { 
        require_once(SMN_DIGITALFOREST_DIR . 'includes/admin.php');
    }

    require_once(SMN_DIGITALFOREST_DIR . 'includes/core.php');
    require_once(SMN_DIGITALFOREST_DIR . 'includes/dfapi.php');
}

register_activation_hook(__FILE__, 'smn_digitalforest_activation');

function smn_digitalforest_activation() {

    register_uninstall_hook(__FILE__, 'smn_digitalforest_uninstall');
}

function smn_digitalforest_uninstall() {

}

smn_digitalforest_load();
