<?php defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/*
Plugin Name: CMB2 Nav Menus
Description: Allow CMB2 forms to be used in the nav menu
Version: 1.0.0
Author: Niklas Rosenqvist
Author URI: https://www.nsrosenqvist.com/
*/

if (! class_exists('CMB2_NavMenus')) {
    class CMB2_NavMenus
    {
        static function init()
        {
            if (! class_exists('CMB2')) {
                return;
            }

            // Include files
            require_once __DIR__.'/src/Integration.php';
            require_once __DIR__.'/src/WalkerNavMenuEdit.php';
            require_once __DIR__.'/src/helpers.php';

            // Initialize plugin
            \NSRosenqvist\CMB2\NavMenus\Integration::init();
        }
    }
}
add_action('init', [CMB2_NavMenus::class, 'init']);
