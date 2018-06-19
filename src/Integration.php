<?php namespace NSRosenqvist\CMB2\NavMenus;

use NSRosenqvist\CMB2\NavMenus\WalkerNavMenuEdit;
use CMB2_hookup;
use CMB2;

class Integration
{
	static $init = false;
	static $columns = [];
	static $cmb;

	static $menuId = 0;
	static $menuSlug = '';
	static $menuLocation = '';

	static function init()
	{
		if (self::$init) {
			return;
		}

		$init = true;

		// Don't run too early
		add_action('admin_init', function() {
			global $pagenow;

			// Load CMB2 menu if current menu is provided through filter
			$showOn = apply_filters('cmb2_nav_menus', []);
			$showOnIds = [];
			$currentMenu = self::getCurrentMenuId();
			$locations = get_registered_nav_menus();
			$menu_locations = get_nav_menu_locations();

			foreach ($showOn as $menu) {
				foreach ($locations as $slug => $name) {
					if ($slug == $menu) {
						$showOnIds[] = $menu_locations[$slug];
					}
				}
			}

			if (is_nav_menu($currentMenu) && in_array($currentMenu, $showOnIds)) {
				// Bind hooks
				add_action('wp_nav_menu_item_custom_fields', [self::class, 'form'], 10, 4);
				add_action('wp_update_nav_menu_item', [self::class, 'save'], 10, 3);
				add_filter('manage_nav-menus_columns', [self::class, 'columns'], 99);
				add_filter('wp_edit_nav_menu_walker', [self::class, 'walker'], 99);
				add_filter('cmb2_admin_init', [self::class, 'adminInit']);

				// This isn't really used, it's just there to avoid the metabox showing on
				// any other page.
				add_filter('cmb2_show_on', [self::class, 'show_on'], 10, 2);

				// If we're on the nav-menus page, also include the css
				if ($pagenow == 'nav-menus.php') {
					add_action('admin_init', [self::class, 'includes'], 9978);
				}
			}
		}, 10);
	}

	public static function getCurrentMenuId()
	{
		if (self::$menuId) {
			return self::$menuId;
		}

		$navMenuRequest = isset($_REQUEST['menu']) ? (int) $_REQUEST['menu'] : 0;

		// Get recently edited
		$recentlyEdited = absint(get_user_option('nav_menu_recently_edited'));

		if (empty($recentlyEdited) && is_nav_menu($navMenuRequest)) {
			$recentlyEdited = $navMenuRequest;
		}

		// Use $recentlyEdited if none are selected.
		if (empty($navMenuRequest) && ! isset($_GET['menu']) && is_nav_menu($recentlyEdited)) {
			$navMenuRequest = $recentlyEdited;
		}

		return self::$menuId = $navMenuRequest;
	}

	public static function getCurrentMenuSlug()
	{
		if (self::$menuSlug) {
			return self::$menuSlug;
		}

		$menu = wp_get_nav_menu_object(self::getCurrentMenuId());

		return self::$menuSlug = $menu->slug ?? false;
	}

	public static function getCurrentMenuLocation()
	{
		if (self::$menuLocation) {
			return self::$menuLocation;
		}

		foreach (get_nav_menu_locations() as $location => $id) {
			if ($id == self::getCurrentMenuId()) {
				self::$menuLocation = $location;
			}
		}

		return self::$menuLocation;
	}

	public static function includes()
	{
		if (defined('DOING_AJAX') && DOING_AJAX) {
			return;
		}

		if (defined('CMB2_LOADED')) {
			CMB2_hookup::enqueue_cmb_css();
			CMB2_hookup::enqueue_cmb_js();
		}

		// Register assets
		add_action('admin_enqueue_scripts', function() {
			wp_register_style('cmb2_nav_menus', self::plugins_url('cmb2-nav-menus', '/assets/cmb2-nav-menus.css', __FILE__, 1), false, '1.0.0');
			wp_register_script('cmb2_nav_menus', self::plugins_url('cmb2-nav-menus', '/assets/cmb2-nav-menus.js', __FILE__, 1), ['jquery'], '1.0.0');

            wp_enqueue_style('cmb2_nav_menus');
			wp_enqueue_script('cmb2_nav_menus');
        });
	}

	static function adminInit()
	{
		// Add columns
		foreach (self::getFields() as $field) {
			self::$columns[$field['id']] = $field['name'];
		}
	}

	static function getFields()
	{
		$fields = apply_filters('cmb2_nav_menu_fields', []);
		$fields = apply_filters('cmb2_nav_menu_fields_'.self::getCurrentMenuLocation(), $fields);

		// Supporting either defining the fields in the typical CMB2 style
		// but also by having the keys as id's instead of as a field for
		// greater readability
		if (self::is_assoc($fields)) {
			foreach ($fields as $id => $field) {
				$fields[$id]['id'] = $id;
			}
		}
		else {
			foreach ($fields as $key => $field) {
				unset($fields[$key]);
				$fields[$field['id']] = $field;
			}
		}

		return $fields;
	}

	static function cmb($menu_item_id, $depth = null)
	{
		$cmb = new CMB2(array(
	        'id'            => 'cmb2_nav_menus',
	        'title'         => __('CMB2 Nav Menus', 'theme'),
	        'show_on'       => array('key' => 'nav-menus'),
	        'context'       => 'side',
	    ), 'cmb2_nav_menus');

		// Save as option instead of post meta data
		$cmb->object_type('options-page');
		$cmb->object_id('cmb2_nav_menus');

		// Add fields
		foreach (static::getFields() as $field) {
			// Set classes
			if (isset($field['classes'])) {
				if ( ! is_array($fields['classes'])) {
					$fields['classes'] = [$fields['classes']];
				}
			}
			// Skip adding field if a depth parameter have been set in the field
			// definition and it doesn't include this level
			if (! is_null($depth) && isset($field['depth'])) {
				$depthDef = (! is_array($field['depth'])) ? [$field['depth']] : $field['depth'];

				// Add an extra attribute that tells the javascript what depths
				// it should be visible on
				if (! isset($field['attributes'])) {
					$field['attributes'] = [];
				}

				$field['attributes'] = ['data-depth' => json_encode($depthDef)];
			}

			$field['classes'][] = sprintf('field-%s', $field['id']);
			$field['classes'][] = 'cmb2-nav-menus';
			$field['id'] .= '-'.$menu_item_id;

			$cmb->add_field($field);
		}

		return $cmb;
	}

	static function save($menu_id, $menu_item_db_id, $menu_item_args)
	{
		if (defined('DOING_AJAX') && DOING_AJAX) {
			return;
		}

		check_admin_referer('update-nav_menu', 'update-nav-menu-nonce');

		$cmb = self::cmb($menu_item_db_id);
		$object_id = $cmb->object_id();
		$object_type = $cmb->object_type();

		$sanitized = $cmb->get_sanitized_values($_POST);
		$cmb->save_fields($object_id, $object_type, $sanitized);
	}

	static function form($id, $item, $depth, $args)
	{
		echo '<div style="clear: both;">';
		$cmb = self::cmb($item->ID, $depth);
		$cmb->show_form();
		echo '<div>';
	}

	// This is to enable toggling fields as visible/hidden
	static function columns($columns)
	{
		return array_merge($columns, self::$columns);
	}

	static function walker()
	{
		return WalkerNavMenuEdit::class;
	}

	static function show_on($display, $meta_box)
	{
        if ( ! isset($meta_box['show_on']['key']) || ! $meta_box['show_on']['key'] != 'nav-menus') {
    		return $display;
    	}

    	global $pagenow;

    	if ($pagenow == 'nav-menus.php') {
    		return true;
    	}

    	return false;
    }

	static function get_nav_option($menu_item_id, $key = '', $default = null)
	{
		$opts = get_option('cmb2_nav_menus', null);

		if ($opts) {
			$key .= '-'.$menu_item_id;
			return (isset($opts[$key])) ? $opts[$key] : $default;
		}

		return $default;
	}

	private static function is_assoc(array $arr)
	{
        if (array() === $arr) return false;
        return array_keys($arr) !== range(0, count($arr) - 1);
    }

	static function plugins_url($name, $file, $__FILE__, $depth = 0)
	{
		// Traverse up to root
		$dir = dirname($__FILE__);

		for ($i = 0; $i < $depth; $i++) {
			$dir = dirname($dir);
		}

		$root = $dir;
		$plugins = dirname($root);

		// Compare plugin directory with our found root
		if ($plugins !== WP_PLUGIN_DIR || $plugins !== WPMU_PLUGIN_DIR) {
			// Must be a symlink, guess location based on default directory name
			$resource = $name.'/'.$file;
			$url = false;

			if (file_exists(WPMU_PLUGIN_DIR.'/'.$resource)) {
				$url = WPMU_PLUGIN_URL.'/'.$resource;
			}
			elseif (file_exists(WP_PLUGIN_DIR.'/'.$resource)) {
				$url = WP_PLUGIN_URL.'/'.$resource;
			}

			if ($url) {
				if (is_ssl() && substr($url, 0, 7) !== 'https://') {
					$url = str_replace('http://', 'https://', $url);
				}

				return $url;
			}
		}

		return plugins_url($file, $root);
	}
}
