<?php

if (! function_exists('cmb2_get_nav_option')) {
    function cmb2_get_nav_option($menu_item_id, $key = '', $default = null)
    {
        return \NSRosenqvist\CMB2\NavMenus\Integration::get_nav_option($menu_item_id, $key, $default);
	}
}
