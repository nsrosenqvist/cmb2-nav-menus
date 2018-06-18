CMB2 Nav Menus
==============

_Lets you use CMB2 in nav menu entries._

Register menu location to enable CMB2:

```php
// Add filter for locations
add_filter('cmb2_nav_menus', function($menu_slugs) {
    $menu_slugs[] = 'my_menu';

    return $menu_slugs;
}, 10, 1);
```

Register CMB2 fields for menu:

```php
// For all menus
add_filters('cmb2_nav_menu_fields, function($fields) {
    // You can set ID both as the key and in the array
    $fields['icon-class'] = [
        'name' => __( 'Icon Class', 'theme' ),
        'type' => 'fontawesome_icon',
        'help' => 'Choose a FontAwesome icon class name (eg. fa-circle)',
    ];
}, 10, 1);

// For specific menu
add_filters('cmb2_nav_menu_fields_my_menu, function($fields) {
    // You can set ID both as the key and in the array
    $fields['disabled'] = [
        'name' => __( 'Disabled', 'theme' ),
        'type' => 'checkbox',
        'style' => 'thin',
    ];
}, 10, 1);
```

Get the nav menu item option with the included helper:

```php
cmb2_get_nav_option($menu_item_id, $key = '', $default = null);
```
